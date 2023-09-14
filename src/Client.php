<?php

namespace A8Client;

require_once 'Config.php';

use A8Client\libraries\Services\BridgeService;

class Client 
{

    private $model = [];

    public $config = [];

    public function __construct( $conf )
    {

        $this->config = $conf;

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
