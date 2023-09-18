<?php

namespace A8Client;

use A8Client\libraries\Services\BridgeService;

class Client 
{

    const KEY = 'key';
    const CODE = 'code';
    const CLIENT_DOMAIN = 'client_domain';
    const OPTION = 'option';

    private $_config = [];
    private $_key;
    private $_scode;
    private $_cdomain;
    private $_option;

    public function __construct( String $_key, String $_scode, String $_cdomain, $_option = [] )
    {

        $this->_key = $_key;
        $this->_scode = $_scode;
        $this->_cdomain = $_cdomain;
        $this->_option = $_option;

    }

    public function __get( $resource )
    {
        return $this->getService( $resource );
    }

    public function getService ( $resource ) 
    {

        self::set_cred();

        if ( $ret = (new BridgeService ( $resource, $this->_config )) ) {

            return $ret;

        }

        return "Endpoint not found";
    }

    private function set_cred()
    {
        // 1. check key if not exist
        if (!$this->_key)
        {
            throw new \Exception("API key is required.");
        }

        // 2. check if secret code not exist
        if ( !$this->_scode ) 
        {
            throw new \Exception("API secret code is required");
        }

        // 3. check if client domain not exist
        if ( !$this->_cdomain ) {
            throw new \Exception("API client domain is required.");
        }

        $this->_config = [
            self::KEY => $this->_key,
            self::CODE => $this->_scode,
            self::CLIENT_DOMAIN => $this->_cdomain,
            self::OPTION => $this->_option
        ];
    }

}
