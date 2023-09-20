<?php

namespace A8\Client\Api\Libs;

class Exceptions
{

    public function __construct()
    {

    }

    public function _generate_error($code, $message)
    {
        return (object) [
            'code' => $code,
            'message' => $message
        ];
    }

}
