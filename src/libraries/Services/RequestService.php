<?php

namespace A8Client\libraries\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use A8Client\libraries\Services\SecurityService;

class RequestService extends SecurityService
{

    const CONFIG_PATH = __DIR__.'/../../api_client_sdk_config.ini';
    const HEADER_AUTHORIZATION = 'Authorization';
    const HEADER_ACCEPT = 'Accept';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_TIMESTAMP = 'Aas-Timestamp';
    const HEADER_SIGNATURE = 'Aas-Signature';
    const HEADER_AUTH_TYPE = 'Aas-Auth-Type';
    const GLUE = '/';
    const SIGN_GLUE = '\n';
    const METHOD_GLUE = '_';
    const REQUIRED_KEY = [
        'key',
        'code', 
        'client_domain', 
        'option'
    ];

    private static $_client;
    private static $_header;
    private static $_current_timestamp;
    private static $_method;
    private static $_args;
    private static $_auth_data;
    private static $_authorization;
    private static $_config;
    private static $_body;
    private static $_cred;
    private static $_base_path;
    private static $_auth_type;

    public function __construct()
    {
        print_r($this->_config);exit;
    }

    private static function clientInstance()
    {   
        
        static::initialize_config();
        static::auth_data();
        static::get_authorization();

        static::$_current_timestamp = gmdate('Y-m-d\TH:i:s\Z');

        static::$_client = new Client([
            'base_uri' => static::$_base_path,
            'headers' => static::prep_headers(),
            static::$_cred['key'] => static::auth(),
            'http_errors'  => static::$_config['HTTP_ERRORS']
        ]);
    }
    
    public static function __callStatic($method, $args) 
    {
        static::get_cred($args);
        static::$_method = $method;
        static::$_args = static::get_params($args);
        static::$_body = '';

        $method = self::METHOD_GLUE.$method;

        return static::$method();

    }

    private static function send( $method, $path, $body = [] )
    {
        try 
        {
            static::clientInstance();

            $response = static::$_client->request($method, self::GLUE.$path );

        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {            
            return $response = $e->getMessage();
        }
        
        $contents = json_decode($response->getBody()->getContents());

        if (is_null($contents)) {
        
            return (object) [
                'code' => $response->getStatusCode(),
                'message' => $response->getReasonPhrase()
            ];
        
        } else {
            
            return $contents;
        
        }
    }

    public static function _get()
    {
        if ( count(static::$_args) === 4 )
        {
            $path = implode(self::GLUE, [static::$_args[0], static::$_args[1]]);
            $select = static::$_args[2];
            $with = explode(',', static::$_args[3]);
            return static::send( static::$_method, $path.'?$expand='.$with[0].'&$select='.$select );
        }

        throw new \Exception('Invalid arguments in get method.');
    }

    private static function prep_headers()
    {
        return [
            self::HEADER_AUTHORIZATION => 'Basic '.static::$_authorization,
            self::HEADER_ACCEPT => 'application/json',
            self::HEADER_CONTENT_TYPE => 'application/x-www-form-urlencoded',
            self::HEADER_TIMESTAMP => static::$_current_timestamp,
            self::HEADER_SIGNATURE => static::sign(),
            self::HEADER_AUTH_TYPE => static::$_auth_type
        ];
    }

    private static function auth()
    {
        $token = static::generate_token( static::$_args );

        return json_encode((object)[
            "domain" => static::$_cred['client_domain'],
            "jwt" => $token
        ]);
    }

    private static function generate_token( $data = null )
    {
        if ( $data && is_array( $data ) ) 
        {
            try
            {
                $data['API_TIME'] = time();

                return JWT::encode( $data, static::$_config['JWT_KEY'], static::$_config['JWT_ALGORITHM'] );
            }
            catch ( Exception $e ) 
            {
                return "Message: ".$e->getMessage();
            }

        }
        else
        {
            return "Token data is required";
        }

    }

    private static function auth_data()
    {
        static::$_auth_data = json_encode([
            'domain' => static::$_cred['client_domain'],
            'jwt' => static::get_access(56)
        ]);
    }

    private static function get_access( $person_id )
    {
        $payload = [
            'exp' => time() + (int) static::$_config['TOKEN_TTL'],
            'id' => $person_id,
        ];
                
        return JWT::encode($payload, 
            static::$_cred['code'],
            static::$_config['JWT_ALGORITHM'],
        );
    }

    private static function get_authorization()
    {
        static::$_authorization = base64_encode( static::$_cred['key'] . ':' . static::$_auth_data );
    }

    private static function initialize_config()
    {
        if ( file_exists( self::CONFIG_PATH ) ) 
        {
            $config = parse_ini_file(self::CONFIG_PATH);

            if ( $config ) {
                static::$_config = $config;
                static::$_base_path = static::$_config['BASE_URI'];
                static::$_auth_type = static::$_config['AUTH_TYPE'];

                // 1. check if base_path key is exist
                if ( array_key_exists('base_path', static::$_cred['option']) )
                {
                    if ( static::$_cred['option']['base_path'] ) 
                    {
                        static::$_base_path = static::$_cred['option']['base_path'];        
                    }
                }

                // 2. check if auth_type key is exist
                if ( array_key_exists('base_path', static::$_cred['option']) )
                {
                    if ( static::$_cred['option']['base_path'] ) 
                    {
                        static::$_auth_type = static::$_cred['option']['auth_type'];        
                    }
                }

            }
        }
    }

    private static function sign()
    {
        list($path, $query_str) = static::$_args;

        $query_str = explode("?", $query_str);
        
        $unsigned = [
            static::$_method,
            static::$_base_path.self::GLUE.$path.self::GLUE.$query_str[0],
            (isset($query_str[1]) ? $query_str[1] : '' ) ,
            static::$_body,
            static::$_current_timestamp
        ];
        
        $to_sign = implode(self::SIGN_GLUE, $unsigned);

        $signed = hash_hmac(static::$_config['ALGO'], $to_sign, static::$_cred['code']);
        return $signed;
    }

    private static function get_cred( $args )
    {
        $keys = [];

        foreach ( self::REQUIRED_KEY as $k=>$val )
        {
            $keys[$val] = $args[(count($args)-1)][$val];
        }

        static::$_cred = $keys;

    }

    private static function get_params( $args )
    {
       
        unset($args[(count($args)-1)]);
        
        return $args;
    
    }
	
}
