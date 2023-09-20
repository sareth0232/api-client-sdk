<?php

namespace A8\Client\Api\Services;

use A8\Client\Api\Libs\Exceptions;

class RemapResponseService
{
	
	const SYSTEM_ERROR = 'E00000';

	public function __construct()
	{

		$this->exception = new Exceptions;

	}

	public function _single_response( $items, $exclude )
	{
		if( !empty( $items ) )
		{
			$keys = array_keys((array) $items);
			$href = '';

			foreach ( $keys as $key )
			{
				if ( isset ( $items->href ) ) 
				{
					$href == '' && $href = $items->href;
				}
				$items = (array) $items;
			
				if ( is_object( $items[$key] ) && !in_array($key, explode(",", $exclude) ) )
				{
					$items[$key."_id"] = $items[$key]->id;
					unset($items[$key]);
				}
			}
			$items = (object) $items;
			unset($items->href);
			$items->href = $href;

			return $items;
		}
		return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid Response.");
	}

	public function _multiple_response( $items, $exclude )
	{
		if ( !empty( $items ) )
		{
			$counter = 0;
			foreach( $items->items as $item )
			{
				$items->items[$counter] = $this->_single_response( $item, $exclude );
				$counter++;
			}
			return $items;
		}
		return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid Response.");

	}

	public function _all_response( $items, $exclude )
	{
		if ( !empty( $items ) )
		{
			$counter = 0;
			$ret = [];
			foreach( $items as $item )
			{
				$ret[] = $this->_get_item( $item, $exclude );
			}
			return (object) $ret;
		}
		return $this->exception->_generate_error($this::SYSTEM_ERROR, "Invalid Response.");
	}

	protected function _get_item( $items, $exclude )
	{
		$ret = [];
		foreach( $items as $item )
		{
			$ret[] = $this->_single_response( $item, $exclude );
		}
		return (object) $ret;
	}

}
