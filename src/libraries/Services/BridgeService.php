<?php

namespace A8Client\Services;

use A8Client\Services\ModelServiceFactory;

class BridgeService extends ModelServiceFactory
{

	private $config;

    public function __construct( $resource, $config )
    {
    	
    	$this->config = $config;

        parent::__construct( $resource );

    }

}