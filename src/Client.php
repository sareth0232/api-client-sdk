<?php

namespace A8Client\Client;

require_once 'Config.php';

use A8Client\Services\BridgeService;

class Client 
{

    private $model = [];

    public $config = [];

    public function __construct()
    {

        $this->config = $conf;

    }

    public function __get( $resource )
    {
        return $this->getService( $resource );
    }

    public function getService ( $resource ) 
    {

        if ( $ret = (new BridgeServiceFactory ( $resource, $this->config )) ) {

            return $ret;

        }

        return "Endpoint not found";
    }

}