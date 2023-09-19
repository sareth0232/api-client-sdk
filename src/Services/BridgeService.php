<?php

namespace A8\Client\Api\Services;

use A8\Client\Api\Services\ModelServiceFactory;

class BridgeService extends ModelServiceFactory
{

	public $_cred;

    public function __construct( $resource, $_cred )
    {
        $this->_cred = $_cred;

        parent::__construct( $resource );

    }

}
