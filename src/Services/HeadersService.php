<?php

namespace A8\Client\Api\Services;

use A8\Client\Api\Services\SecurityService;

class HeadersService
{

	const HEADER_AUTHORIZATION = 'Authorization';
    const HEADER_ACCEPT = 'Accept';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_TIMESTAMP = 'Aas-Timestamp';
    const HEADER_SIGNATURE = 'Aas-Signature';
    const HEADER_AUTH_TYPE = 'Aas-Auth-Type';

    protected $cred;
    protected $current_timestamp;
    public $base_path;
    protected $args;
    
    public function __construct ( $cred, $args, $method ) 
    {

        $this->security_service = new SecurityService($cred, $args, $method);
        $this->base_path = $this->security_service->base_path;
        $this->current_timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $this->cred = $cred;
        $this->args = $args;
        

    }

    public function _prep_headers() 
    {
        return [
            $this::HEADER_AUTHORIZATION => 'Basic '.$this->security_service->_get_authorization(),
            $this::HEADER_ACCEPT => 'application/json',
            $this::HEADER_CONTENT_TYPE => 'application/x-www-form-urlencoded',
            $this::HEADER_TIMESTAMP => $this->current_timestamp,
            $this::HEADER_SIGNATURE => $this->security_service->_sign(),
            $this::HEADER_AUTH_TYPE => $this->security_service->_get_auth_type()
        ];
    }

    public function _auth()
    {
        $token = $this->security_service->_generate_token( $this->args );

        return json_encode((object)[
            "domain" => $this->cred['client_domain'],
            "jwt" => $token
        ]);
    }

}
