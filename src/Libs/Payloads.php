<?php

namespace A8\Client\Api\Libs;

class Payloads
{
	const FILTER_VAR = '$filter=';
    const SORT_VAR = '$sort=';

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
        $ret = FALSE;

        if ( isset ( $payloads->_links->next ) ) 
        {
            $ret = TRUE;
        }

        return $ret;
    }

    public function _conditions( $conditions )
    {
        $ret = '';

        foreach( $conditions as $where )
        {

            if ( count($where) === 2 ) 
            {
                list($field, $value) = $where;
                if ( !$ret )
                {
                    $ret .= "{$field} = {$value}";
                }
                else {
                    $ret .= "and {$field} = {$value}";
                }
                     
            }
            else if ( count($where) === 3 ) 
            {
                list($field, $value, $operator) = $where;
                if ( !$ret )
                {
                    $ret .= "{$field} {$operator} {$value}";
                }
                else {
                    $ret .= ", {$field} {$operator} {$value}";
                }
                     
            } else {
                throw new \Exception( "Invalid where clause provided." );
            }

        }

        return $this::FILTER_VAR.$ret;
    }

    public function _sort( $sorts )
    {
        $ret = '';

        foreach ( $sorts as $sort )
        {
            if ( count( $sort ) === 2 ) 
            {
                list($field, $order) = $sort;
                if ( !$ret )
                {
                    $ret .= "-{$field}";
                }
                else {
                    $ret .= ",-{$field}";
                }
            } 
            else 
            {
                throw new \Exception("Invalid sort arguments provided.");
            }
        }

        return $this::SORT_VAR.$ret;
    }

}
