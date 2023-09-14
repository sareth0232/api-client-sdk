<?php

namespace A8Client\libraries\Service;

include_once ( '../../Config.php' );

class SecurityService
{
	
	public function __construct()
	{



	}

	public function _generate_signature ( array $params ) 
	{

		try {

			$timestamp = gmdate('Y-m-d\TH:i:s\Z');

			$payload = [
				$params['method'],
				$params['url'],
				$params['query'],
				$params['form_body'],
				$timestamp
			];

			$payload_to_sign = implode("\n", $tmp);
			
			return hash_hmac('sha256', $payload_to_sign, $conf['secret_code']);

		} catch ( \Exception $e ) {

			return $e->getMessage();

		}

	}

}
