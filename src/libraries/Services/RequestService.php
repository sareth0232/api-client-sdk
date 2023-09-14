<?php

namespace A8Client\Services;

use GuzzleHttp\Client;

class RequestService
{

	private static $client;

	private static function clientInstance()
    {
        static::$client = new Client([
            'client_domain' => $conf['option']['base_path'],
            'headers'       => [
                'API_KEY' => $conf['api_key'],
                'SECRET_CODE' => $conf['secret_code'],
                'CLIENT_DOMAIN' => $conf['client_domain'],
                'AUTH_TYPE' => $conf['option']['auth_type'] ?? $conf['default_auth_type']
            ],
            'http_errors'  => $conf['http_errors']
        ]);
    }
    
    public static function __callStatic($method, $args) 
    {
        static::clientInstance();

        $method == 'put' && $method = 'patch';

        switch ($method) {
            case 'get':
                    $param_index = 'query';
                break;

            case 'post':
                    $param_index = 'form_params';
                break;

            case 'patch':
                    $param_index = 'form_params';
                break;

            case 'delete':
                    $param_index = 'query';
                break;
            
            default:
                    return false;
                break;
        }

        $path = isset($args[0]) ? $args[0] : '';
        $params = isset($args[1])? $args[1] : [];

        $request = static::$client->request($method, $path, [$param_index => $params]);
        $contents = json_decode($request->getBody()->getContents());

        if (is_null($contents)) {

            return (object) ['status' => $request->getStatusCode()];
        
        } else {
            
            return $contents;
        
        }
    }
	
}