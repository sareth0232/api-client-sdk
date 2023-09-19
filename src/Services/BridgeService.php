<?php

namespace A8Client\libraries\Services;

use A8Client\libraries\Services\ModelServiceFactory;

class BridgeService extends ModelServiceFactory
{

	public $_cred;

    public function __construct( $resource, $_cred )
    {
        $this->_cred = $_cred;

        parent::__construct( $resource );

    }

}
