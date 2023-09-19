<?php

namespace A8\Client\Api\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use A8\Client\Api\Services\SecurityService;
use A8\Client\Api\Services\HeaderService;
use A8\Client\Api\Libs\Payloads;

class RequestService
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
    const DEFAULT_RESOURCE = 'ping';
    const DEFAULT_METHOD = 'GET';
    const DEFAULT_DATA_LIMIT = 1000;
    const REQUIRED_KEY = [
        'key',
        'code', 
        'client_domain', 
        'option'
    ];

    protected $client;
    protected $header;
    protected $current_timestamp;
    protected $method;
    protected $args;
    protected $auth_data;
    protected $authorization;
    protected $config;
    protected $body;
    protected $cred;
    protected $base_path;
    protected $auth_type;
    protected $payloads;

    public function __construct( $cred )
    {
        $this->cred = $cred;
        $this->current_timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $this->_initialize_config();
        $this->_auth_data();
        $this->_get_authorization();

        $this->client = new Client([
            'base_uri' => $this->base_path,
            'headers' => $this->_prep_headers(),
            $this->cred['secret_key'] => $this->_auth(),
            'http_errors' => $this->config['HTTP_ERRORS']
        ]);

        $this->payloads = new Payloads;

    }

    public function __call($method, $args) 
    {
        $this->method = $method;
        $this->args = $args;
        $this->body = '';

        $method = self::METHOD_GLUE.$method;
    
        return static::$method();

    }

    private function send( $method, $path, $body = [] )
    {
        try 
        {

            $response = $this->client->request($method, $path );

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

    private function _all()
    {
        $path = $this->args[0];
        $select = $this->args[1];
        $with = explode(',', $this->args[2]);
        $path = $this::GLUE.$path.'?$expand='.$with[0].'&$select='.$select;

        $ret = [];
        $next = '';
        $pages = 0;

        do {
            
            $path = ( $next != '' ? $next : $path );

            $payload = $this->send( $this::DEFAULT_METHOD, $path );

            $get_next_data = $this->payloads->_get_next_data( $payload );

            if ( isset( $get_next_data->next ) && isset( $get_next_data->items ) ) {

                $ret[] = $get_next_data->items;

                $next = str_replace($this->base_path, '', $get_next_data->next->href);

            }

            $pages++;

        } while ( $this->payloads->_has_next( $payload ) && $pages < $this::DEFAULT_DATA_LIMIT );

        return (object) $ret;
    
    }

    private function _get()
    {
        if ( count($this->args) === 4 )
        {
            $path = implode($this::GLUE, [$this->args[0], $this->args[1]]);
            $select = $this->args[2];
            $with = explode(',', $this->args[3]);

            return $this->send( $this->method, $this::GLUE.$path.'?$expand='.$with[0].'&$select='.$select );
        }

        throw new \Exception('Invalid arguments in get method.');
    }

    private function _find () 
    {
        if ( count($this->args) === 7 )
       {
            $path = $this->args[0];
            $where = $this->payloads->_conditions( $this->args[1] );
            $sort = $this->payloads->_sort( $this->args[2] );
            $limit = $this->args[3];
            $offset = $this->args[4];
            $select = $this->args[5];
            $with = explode(',', $this->args[6]);
            $path = $this::GLUE.$path.'?$expand='.$with[0].'&$select='.$select.'&'.$where.'&'.$sort.'&$limit='.$limit.'&$offset='.$offset;

            return $this->send( $this::DEFAULT_METHOD, $path );
        }

        throw new \Exception('Invalid arguments in get method.');

    }

    private function _create ( array $data ) 
    {

        return $this->createService ( $data );

    }

    private function _update ( $id, array $data ) 
    {

        return $this->updateService ( $id, $data );

    }

    private function _delete ( $id ) 
    {

        return $this->deleteService ( $id );

    }

    private function _initialize_config()
    {
        if ( file_exists( $this::CONFIG_PATH ) ) 
        {
            $config = parse_ini_file($this::CONFIG_PATH);
            if ( $config ) {
                $this->config = $config;
                $this->base_path = $this->config['BASE_URI'];
                $this->auth_type = $this->config['AUTH_TYPE'];
                
                // 1. check if base_path key is exist
                if ( array_key_exists('base_path', $this->cred['options']) )
                {
                    if ( $this->cred['options']['base_path'] ) 
                    {
                        $this->base_path = $this->cred['option']['base_path'];        
                    }
                }

                // 2. check if auth_type key is exist
                if ( array_key_exists('base_path', $this->cred['options']) )
                {
                    if ( $this->cred['options']['base_path'] ) 
                    {
                        $this->auth_type = $this->cred['options']['auth_type'];        
                    }
                }

            }
        }
    }

    private function _prep_headers()
    {
        return [
            $this::HEADER_AUTHORIZATION => 'Basic '.$this->authorization,
            $this::HEADER_ACCEPT => 'application/json',
            $this::HEADER_CONTENT_TYPE => 'application/x-www-form-urlencoded',
            $this::HEADER_TIMESTAMP => $this->current_timestamp,
            $this::HEADER_SIGNATURE => $this->_sign(),
            $this::HEADER_AUTH_TYPE => $this->auth_type
        ];
    }

    private function _auth()
    {
        $token = $this->_generate_token( $this->args );

        return json_encode((object)[
            "domain" => $this->cred['client_domain'],
            "jwt" => $token
        ]);
    }

    private function _generate_token( $data = null )
    {
        $data['API_TIME'] = time();
        if ( $data && is_array( $data ) ) 
        {
            try
            {
                return JWT::encode( $data, $this->config['JWT_KEY'], $this->config['JWT_ALGORITHM'] );
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

    private function _sign()
    {
        list($path, $query_str) = $this->args;

        $query_str = explode("?", $query_str);
        
        $unsigned = [
            $this->method,
            $this->base_path.$this::GLUE.$path.$this::GLUE.$query_str[0],
            (isset($query_str[1]) ? $query_str[1] : '' ) ,
            $this->body,
            $this->current_timestamp
        ];
        
        $to_sign = implode($this::SIGN_GLUE, $unsigned);

        $signed = hash_hmac($this->config['ALGO'], $to_sign, $this->cred['secret_code']);
        return $signed;
    }

    private function _get_authorization()
    {
        $this->authorization = base64_encode( $this->cred['secret_key'] . ':' . $this->auth_data );
    }

    private function _auth_data()
    {
        $this->auth_data = json_encode([
            'domain' => $this->cred['client_domain'],
            'jwt' => $this->_get_access(56)
        ]);
    }

    private function _get_access( $person_id )
    {
        $payload = [
            'exp' => time() + (int) $this->config['TOKEN_TTL'],
            'id' => $person_id,
        ];
                
        return JWT::encode($payload, 
            $this->cred['secret_code'],
            $this->config['JWT_ALGORITHM'],
        );
    }
	
}
