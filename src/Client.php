<?php

namespace A8Client;

use A8Client\libraries\Services\BridgeService;

class Client 
{

    public $config = [];
    public $_key;
    public $_scode;
    public $_cdomain;
    public $_option;

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

        if ( $ret = (new BridgeService ( $resource, $this->config )) ) {

            return $ret;

        }

        return "Endpoint not found";
    }

}
