<?php

namespace A8\Client\Api\Services;

use GuzzleHttp\Client;
use A8\Client\Api\Services\SecurityService;
use A8\Client\Api\Services\HeadersService;
use A8\Client\Api\Services\RemapResponseService;
use A8\Client\Api\Libs\Payloads;
use A8\Client\Api\Libs\Exceptions;

class RequestService
{
    const OPERATOR_EQUAL = '==';
    const OPERATOR_NOT_EQUAL = '!=';
    const OPERATOR_EQUAL_BLANK = '= '; 
    const OPERATOR_NOT_EQUAL_BLANK = '! ';
    const OPERATOR_LESS_THAN = '<';
    const OPERATOR_LESS_THAN_OR_EQUAL = '<=';
    const OPERATOR_GREATER_THAN = '>';
    const OPERATOR_GREATER_THAN_OR_EQUAL = '>=';
    const OPERATOR_CONTAIN = 'like';
    const OPERATOR_DOES_NOT_CONTAIN = 'not like';
    const OPERATOR_CONTAIN_LANG_SET = 'translate';
    const OPERATOR_DOES_NOT_CONTAIN_LANG_SET = 'not translate';

    const GLUE = '/';
    const METHOD_GLUE = '_';
    const DEFAULT_RESOURCE = 'ping';
    const GET_METHOD = 'GET';
    const POST_METHOD = 'POST';
    const PATCH_METHOD = 'PATCH';
    const DELETE_METHOD = 'DELETE';
    const DEFAULT_DATA_LIMIT = 1000;
    const HTTP_ERRORS = FALSE;
    const SYSTEM_ERROR = 'E00000';

    // Class variables
    protected $client;
    protected $current_timestamp;
    protected $method;
    protected $args;
    protected $body;
    protected $cred;
    protected $payloads;
    
    // Use for request variables
    protected $select;
    protected $with;
    protected $filter;
    protected $sort;
    protected $limit;
    protected $offset;

    protected $sysmtem_operators;

    public function __construct( $cred )
    {
        $this->cred = $cred;
        $this->current_timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $this->_initialize_system_operators();
        $this->remamp_response = new RemapResponseService;
        $this->exception = new Exceptions;
        $this->headers = new HeadersService( $this->cred, $this->args, $this->method );

        if ( empty($this->client) ) {
            $this->client = new Client([
                'base_uri' => $this->headers->base_path,
                'headers' => $this->headers->_prep_headers(),
                $this->cred['secret_key'] => $this->headers->_auth(),
                'http_errors' => $this::HTTP_ERRORS
            ]);
        }
        
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
            
            $body = $this->_prep_body( $body );

            $response = $this->client->request($method, $path, $body);
        
        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {            
            return $this->exception->_generate_error($this::SYSTEM_ERROR, $e->getMessage());
        }
        
        $contents = json_decode($response->getBody()->getContents());

        if (is_null($contents)) {
            
            return $this->exception->_generate_error($this::SYSTEM_ERROR, $response->getReasonPhrase());
        
        } else {

            return $contents;
        
        }
    }

    private function _all()
    {
        if ( count($this->args) === 3 )
        {
            $path = $this->args[0];
            $this->_prep_select( $this->args[1] );
            $this->_prep_with( $this->args[2] );
            $path = $this::GLUE . $path . ( $this->_prep_query() ? "?" . $this->_prep_query() : "" );
            
            $items = [];
            $next = '';
            $pages = 0;

            do {
                
                $path = ( $next ? $next : $path );
                $payload = $this->send( $this::GET_METHOD, $path );
                $get_next_data = $this->payloads->_get_next_data( $payload );

                if ( isset( $get_next_data->next ) && isset( $get_next_data->items ) ) 
                {
                    array_push($items, $get_next_data->items);
                    $next = str_replace($this->headers->base_path, '', $get_next_data->next->href);
                }

                $pages++;
            } while ( $this->payloads->_has_next( $payload ) && $pages < $this::DEFAULT_DATA_LIMIT );

            if ( $ret = (object) $items )
            {
                $with = $this->args[2];
                return $this->remamp_response->_all_response( $ret, $with );
            }
        }
        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid arguments in all method.");
    }

    protected function _get()
    {
        if ( count($this->args) === 4 )
        {
            $path = implode($this::GLUE, [$this->args[0], $this->args[1]]);
            $this->_prep_select( $this->args[2] );   
            $this->_prep_with( $this->args[3] ); 
            $path = $this::GLUE . $path . ( $this->_prep_query() ? "?" . $this->_prep_query() : "" );
            
            if ( $ret = $this->send( $this->method, $path ) )
            {
                $with = $this->args[3];
                return $this->remamp_response->_single_response( $ret, $with );
            }

            return $this->exception->_generate_error($this::SYSTEM_ERROR, "Could not read response.");
        
        }

        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid arguments in get method.");
    }

    protected function _find() 
    {
        if ( count($this->args) === 7 )
       {
            $path = $this->args[0];
            $this->_prep_filter( $this->args[1] );
            $this->_prep_sort( $this->args[2] );
            $this->_prep_limit( $this->args[3] );
            $this->_prep_offset( $this->args[4] );
            $this->_prep_select( $this->args[5] );
            $this->_prep_with( $this->args[6] );
            $path = $this::GLUE . $path . ( $this->_prep_query() ? "?" . $this->_prep_query() : "" );
            
            if ( $ret = $this->send( $this::GET_METHOD, $path ) )
            {
                $with = $this->args[6];
                return $this->remamp_response->_multiple_response( $ret, $with );
            }
        }

        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid arguments in find method.");

    }

    protected function _create() 
    {
        if ( count( $this->args ) === 2 )
        {
            $path = $this::GLUE . $this->args[0];
            $body = $this->args[1];

            return $this->send( $this::POST_METHOD, $path, $body );
        }

        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid arguments in create method.");
    }

    protected function _update() 
    {

        if ( count( $this->args ) === 3 )
        {
            $path = $this::GLUE . implode($this::GLUE, [$this->args[0], $this->args[1]]);
            $body = $this->args[2];
            
            return $this->send( $this::PATCH_METHOD, $path, $body );
        }
        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid arguments in update method.");
    }

    protected function _delete() 
    {
        if ( count( $this->args ) === 2 )
        {
            $path = $this::GLUE . implode($this::GLUE, [$this->args[0], $this->args[1]]);

            return $this->send( $this::DELETE_METHOD, $path );
        }
        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid arguments in delete method.");
    }

    protected function _initialize_system_operators()
    {
        $this->sysmtem_operators = [
            $this::OPERATOR_EQUAL => '==',
            $this::OPERATOR_NOT_EQUAL => '!=',
            $this::OPERATOR_EQUAL_BLANK => '=~',
            $this::OPERATOR_NOT_EQUAL_BLANK => '!~',
            $this::OPERATOR_LESS_THAN => '<',
            $this::OPERATOR_LESS_THAN_OR_EQUAL => '<=',
            $this::OPERATOR_GREATER_THAN => '>',
            $this::OPERATOR_GREATER_THAN_OR_EQUAL => '>=',
            $this::OPERATOR_CONTAIN => '=@',
            $this::OPERATOR_DOES_NOT_CONTAIN => '!@',
            $this::OPERATOR_CONTAIN_LANG_SET => '=%',
            $this::OPERATOR_DOES_NOT_CONTAIN_LANG_SET => '!%'
        ];
    }

    protected function _prep_select( $params )
    {
        if ( trim ( $params ) ) 
        {
            $this->select = '&$select=' . trim ( $params );
        }
    }

    protected function _prep_with( $params )
    {
        if ( trim ( $params ) )
        {
            $this->with = '&$expand=' . trim ( $params );
        }
    }

    protected function _prep_filter( $params )
    {
        $queries = "";

        if ( $params )
        {
            foreach ( $params as $filter )
            {
                // check if has 3rd index
                if ( count( $filter ) === 3 ) 
                {
                    list($field, $value, $operator) = $filter;
                    if ( $this->_is_operator_valid( $operator ) ) 
                    {
                        $queries .= "and {$field} " . $this->_get_system_operator( $operator ) . " {$value} ";
                    }
                    else 
                    {
                        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid operator provided");
                    }
                }
                // check if has 2nd index
                else if ( count( $filter ) === 2 )
                {
                    list($field, $value) = $filter;

                    $queries .= "and {$field} == {$value} ";
                }
                else
                {
                    return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid operator provided");
                }
            }
            $this->filter = '&$filter=' . substr($queries, 4);
        }
    }

    protected function _prep_sort( $params )
    {
        $queries = "";
        $system_order = [
            "desc" => "-",
            "asc" => "",
        ];

        if ( $params )
        {
            foreach ( $params as $sort )
            {
                // check if has to indexes
                if ( count( $sort ) === 2 )
                {
                    list($field, $order) = $sort;
                    if ( isset ( $system_order[$order] ) )
                    {
                        $queries .= "," . $system_order[$order] . "{$field}";
                    }
                    else {
                        return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid sort order");
                    }
                }
            }
            $this->sort = '&$sort=' . substr($queries, 1);
        }
    }

    protected function _prep_limit ( $limit )
    {
        if ( $limit )
        {
            $this->limit = '&$limit=' . $limit;   
        }
    }

    protected function _prep_offset ( $offset )
    {
        if ( $offset )
        {
            $this->offset = '&$offset=' . $offset;   
        }
    }

    protected function _prep_query()
    {
        return substr($this->select . $this->with . $this->filter . $this->sort . $this->limit . $this->offset, 1);
    }

    protected function _prep_body( $body )
    {
        return ['form_params' => $body ?? ""]; 
    }

    protected function _is_operator_valid( $operator )
    {
        return isset( $this->sysmtem_operators[ $operator ] );
    }

    protected function _get_system_operator($operator)
    {
        return $this->sysmtem_operators[ $operator ] ?? '';
    }

    protected function _prep_headers()
    {
        
    }

    protected function _auth()
    {
        
    }
	
}
