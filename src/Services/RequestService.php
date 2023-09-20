<?php

namespace A8Client\libraries\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use A8Client\libraries\Services\SecurityService;
use A8Client\libraries\Services\HeadersService;
use A8Client\Libs\Payloads;

class RequestService
{
    
    const GLUE = '/';
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
        $this->headers = new HeadersService( $this->cred, $this->args, $this->method );

        $this->client = new Client([
            'base_uri' => $this->headers->base_path,
            'headers' => $this->headers->_prep_headers(),
            $this->cred['secret_key'] => $this->headers->_auth(),
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

            return $this->send( $this::DEFAULT_RESOURCE, $path );
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

    private function _prep_headers()
    {
        
    }

    private function _auth()
    {
        
    }
	
}
