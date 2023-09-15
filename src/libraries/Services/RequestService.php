<?php

namespace A8Client\libraries\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;

class RequestService
{

    const HEADER_AUTHORIZATION = 'Authorization';
    const HEADER_ACCEPT = 'Accept';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_TIMESTAMP = 'Aas-Timestamp';
    const HEADER_SIGNATURE = 'Aas-Signature';
    const HEADER_AUTH_TYPE = 'Aas-Auth-Type';
    const GLUE = '/';
    const METHOD_GLUE = '_';
    const BASE_URI = 'https://api7.devcebu.com'; // need to place in config
    const AUTH_TYPE = 3;
    const CLIENT_DOMAIN = 'client7.devcebu.com';
    const API_KEY = 'a8591d1354e958cc842d210a932be3fb';
    const API_SECRET_AUTH_3 = '895a744cb823686f485ab1d9ff1b228d9823bef8214b0439954dbebe4c0c5f92';
    const HTTP_ERRORS = FALSE;
    const JWT_KEY = 'enMB8ms2Nh8RNz7nRKrGTiMRG9aRPp8G5d';
    const JWT_ALGORITHM = 'HS256';
    const TOKEN_TTL = 3600 * 4; 

	private static $_client;
    private static $_header;
    private static $_current_timestamp;
    private static $_method;
    private static $_args;
    private static $_auth_data;
    private static $_authorization;

    private static function clientInstance()
    {        
        static::auth_data();
        static::get_authorization();

        static::$_current_timestamp = gmdate('Y-m-d\TH:i:s\Z');

        static::$_client = new Client([
            'base_uri' => self::BASE_URI,
            'headers' => static::prep_headers(),
            self::API_KEY => static::auth(),
            'http_errors'  => self::HTTP_ERRORS
        ]);
    }
    
    public static function __callStatic($method, $args) 
    {
        static::$_method = $method;
        static::$_args = $args;

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
        static::clientInstance();

        $path = implode(self::GLUE, static::$_args);
        
        return static::send( static::$_method, $path );
    
    }

    private static function prep_headers()
    {
        return [
            self::HEADER_AUTHORIZATION => 'Basic '.static::$_authorization,
            self::HEADER_ACCEPT => 'application/json',
            self::HEADER_CONTENT_TYPE => 'application/x-www-form-urlencoded',
            self::HEADER_TIMESTAMP => self::$_current_timestamp,
            self::HEADER_SIGNATURE => '95d068137575e16392c0b42153c7451fd10ad126904a84581dcc88191ed5fac4',
            self::HEADER_AUTH_TYPE => self::AUTH_TYPE
        ];
    }

    private static function auth()
    {
        $token = static::generate_token( static::$_args );

        return json_encode((object)[
            "domain" => self::CLIENT_DOMAIN,
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

                return JWT::encode( $data, self::JWT_KEY, self::JWT_ALGORITHM );
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
            'domain' => $_SERVER['SERVER_NAME'],
            'jwt' => static::get_access(56)
        ]);
    }

    private static function get_access( $person_id )
    {
        $payload = [
            'exp' => time() + (int) self::TOKEN_TTL,
            'id' => $person_id,
        ];
                
        return JWT::encode($payload, 
            self::API_SECRET_AUTH_3,
            self::JWT_ALGORITHM,
        );
    }

    private static function get_authorization()
    {
        static::$_authorization = base64_encode( self::API_KEY . ':' . static::$_auth_data );
    }
	
}
