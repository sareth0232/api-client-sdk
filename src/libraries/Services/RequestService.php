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
    const HTTP_ERRORS = FALSE;
    const JWT_KEY = 'enMB8ms2Nh8RNz7nRKrGTiMRG9aRPp8G5d';
    const JWT_ALGORITHM = 'HS256';
    const USERNAME = 'a8591d1354e958cc842d210a932be3fb';
    const PASSWORD = '{"domain":"client7.devcebu.com","jwt":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE2OTQ3NzM0NDAsImlkIjo1NiwidXNlcm5hbWUiOiJVU0VSMSJ9.9Jxy3L173Igib6FOyvCXBNs2MzedxFQuVHPj9o_yCx4"}';
    

	private static $_client;
    private static $_header;
    private static $_current_timestamp;
    private static $_method;
    private static $_args;
    private static $_auth_data;

	private static function clientInstance()
    {        
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

        $path = implode("/", static::$_args);
        
        return static::send( static::$_method, $path );
    
    }

    private static function prep_headers()
    {
        static::auth_data();
        // print_r(base64_encode(self::API_KEY.':'.static::$_auth_data));exit;
        return [
            self::HEADER_AUTHORIZATION => 'Basic YTg1OTFkMTM1NGU5NThjYzg0MmQyMTBhOTMyYmUzZmI6eyJkb21haW4iOiJjbGllbnQ3LmRldmNlYnUuY29tIiwiand0IjoiZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6STFOaUo5LmV5SmxlSEFpT2pFMk9UUTNOek0wTkRBc0ltbGtJam8xTml3aWRYTmxjbTVoYldVaU9pSlZVMFZTTVNKOS45Snh5M0wxNzNJZ2liNkZPeXZDWEJOczJNemVkeEZRdVZIUGo5b195Q3g0In0=',
            self::HEADER_ACCEPT => 'application/json',
            self::HEADER_CONTENT_TYPE => 'application/x-www-form-urlencoded',
            self::HEADER_TIMESTAMP => self::$_current_timestamp,
            self::HEADER_SIGNATURE => '95d068137575e16392c0b42153c7451fd10ad126904a84581dcc88191ed5fac4',
            self::HEADER_AUTH_TYPE => self::AUTH_TYPE
        ];
    }

    private static function auth()
    {
        $token = static::generate_token();

        return json_encode((object)[
            "domain" => self::CLIENT_DOMAIN,
            "jwt" => $token
        ]);
    }

    private static function generate_token( $data = null )
    {
        $data['API_TIME'] = time();

        if ( $data && is_array( $data ) ) 
        {
            try
            {
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
            'domain'=> self::CLIENT_DOMAIN,
            'username'=> self::USERNAME,
            'password'=> self::PASSWORD
        ]);
    }
	
}
