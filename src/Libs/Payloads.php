<?php

namespace A8\Client\Api\Libs;

class Payloads
{
    public function _get_next_data( $payloads )
    {

        if ( isset( $payloads->items ) && isset ( $payloads->_links->next )) {
            
            return (object)[
                'items' => $payloads->items, 
                'next' => $payloads->_links->next
            ];
        
        }

    }
    
    public function _has_next( $payloads )
    {
        return isset ( $payloads->_links->next );
    }

}
