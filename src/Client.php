<?php

namespace A8Client;

use A8Client\libraries\Services\ModelService;

class Client
{

    const SECRET_KEY = 'secret_key';
    const SECRET_CODE = 'secret_code';
    const CLIENT_DOMAIN = 'client_domain';
    const OPTIONS = 'options';
    const METHOD_GLUE = '_';

    protected $requestService;
    protected $resource;
    protected $cred;

    public function __construct( string $secret_key, string $secret_code, string $client_domain, $options = [] )
    {

        // validate if require parameters are present
        $this->_resolve_cred($secret_key, $secret_code, $client_domain);

        $this->cred = [
            $this::SECRET_KEY => $secret_key,
            $this::SECRET_CODE => $secret_code,
            $this::CLIENT_DOMAIN => $client_domain,
            $this::OPTIONS => $options
        ];
        
        // $this->requestService = new ModelServiceFactory ( $cred );
        
    }

    public function __get( $resource )
    {  
        return (new ModelService ( $resource, $this->cred ));
    }

    protected function _resolve_cred( $secret_key, $secret_code, $client_domain )
    {
        // 1. check key if not exist
        if ( !$secret_key )
        {
            throw new \Exception("API key is required.");
        }

        // 2. check if secret code not exist
        if ( !$secret_code ) 
        {
            throw new \Exception("API secret code is required");
        }

        // 3. check if client domain not exist
        if ( !$client_domain ) {
            throw new \Exception("API client domain is required.");
        }
    }

}
