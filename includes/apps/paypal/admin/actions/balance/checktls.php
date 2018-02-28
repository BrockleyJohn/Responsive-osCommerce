<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  $tlsResult = array('rpcStatus' => -1);

    $tlsResponse = $OSCOM_PayPal->getApiResult('APP', 'TestTLS');

    if ( is_array($tlsResponse)) {
			if (isset($tlsResponse['ACK']) && ($tlsResponse['ACK'] == 'Success') ) {
				$tlsResult['rpcStatus'] = 1;
			}
			foreach ($tlsResponse as $key => $value) {
				$tlsResult[$key] = $value;
			}
		} else {
			$tlsResult['rpcResponse'] = $tlsResponse;
		}

  if ( function_exists('json_encode') ) {
    echo json_encode($tlsResult);
  } else {
    $tlsResultCompat = 'rpcStatus=' . $tlsResult['rpcStatus'] . "\n";

    if ( isset($tlsResult['balance']) ) {
      foreach ( $tlsResult['balance'] as $key => $value ) {
        $tlsResultCompat .= $key . '=' . $value . "\n";
      }
    }

    echo trim($tlsResultCompat);
  }

  exit;
?>
