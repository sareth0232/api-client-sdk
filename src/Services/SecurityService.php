<?php

namespace A8\Client\Api\Services;

use Firebase\JWT\JWT;

class SecurityService
{
    const CONFIG_PATH = __DIR__.'/../../api_client_sdk_config.ini';
    const GLUE = '/';
    const SIGN_GLUE = '\n';

    public $current_timestamp;
    protected $config;
    protected $cred;
    protected $args;
    protected $method;
    protected $body;

    public function __construct( $cred, $args, $method )
    {

        $this->current_timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $this->cred = $cred;
        $this->_initialize_config();

    }

	protected function _initialize_config()
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

    public function _get_authorization()
    {
        return $this->authorization = base64_encode( $this->cred['secret_key'] . ':' . $this->_auth_data() );
    }

    public function _get_auth_type()
    {

        return $this->auth_type = $this->config['AUTH_TYPE'];

    }

    protected function _auth_data()
    {
        return json_encode([
            'domain' => $this->cred['client_domain'],
            'jwt' => $this->_get_access(56)
        ]);
    }

    protected function _get_access( $person_id )
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

    public function _sign()
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

    public function _generate_token( $data = null )
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
}
