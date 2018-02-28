<?php
/*
USPS Rate V4 Intl Rate V2
  $Mod: Changed from Parcel Post to Standard Post 20130129 Kymation $
  $Mod: USPS API changes 20130729 Kymation v 1.3 $
  $Mod: USPS API changes 20140310 a.forever $
  $Mod: USPS API changes 20140802 a.forever $
  $Mod: USPS API changes 20140823 Kymation v 1.4 $
  $Mod: USPS API changes 20140907 Kymation & a.forever v 1.5 $
  $Mod: USPS API changes 20160119 a.forever $
  $Mod: USPS API changes 2017???? Kymation v 1.8 $


Copyright (c) 2012 osCbyJetta
Released under the GNU General Public License
*/
if (! defined('DIR_WS_ICONS')) define('DIR_WS_ICONS','images/icons/');

  class usps {
    var $code, $title, $description, $icon, $enabled, $usps_weight, $xml_errors;
    // The server URL and DLL are here in case they ever need to be changed
    var $usps_server = 'production.shippingapis.com';
    var $api_page = '/shippingapi.dll';
    
    
    function __construct() {
      global $order;

      $this->code = 'usps';
      $this->title = MODULE_SHIPPING_USPS_TEXT_TITLE;
      $this->description = MODULE_SHIPPING_USPS_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_SHIPPING_USPS_SORT_ORDER;
      $this->icon = DIR_WS_ICONS . 'shipping_usps.gif';
      $this->tax_class = MODULE_SHIPPING_USPS_TAX_CLASS;
      $this->enabled = ((MODULE_SHIPPING_USPS_STATUS == 'True') ? true : false);
      
      if ($this->enabled == true && (int) MODULE_SHIPPING_USPS_ZONE > 0) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_USPS_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1)
            $check_flag = true;
          elseif ($check['zone_id'] == $order->delivery['zone_id']) $check_flag = true;
        }
        if ($check_flag == false)
          $this->enabled = false;
      }
      
      if ( MODULE_SHIPPING_USPS_SERVER != '' ) {
        $this->usps_server = MODULE_SHIPPING_USPS_SERVER;
      }
    }
    
    function quote($method = '') {
      global $order, $shipping_num_boxes, $currencies, $shipping, $shipping_weight;
      
      $iInfo = '';
      $methods = array ();

      $this->usps_weight = ( $shipping_weight < 0.0625 ? 0.0625 : $shipping_weight );
      $this->pounds = (int) $this->usps_weight;
      $this->ounces = round(16 * ($this->usps_weight - $this->pounds), 3);
      
      //Get the quote from USPS
      $uspsQuote = $this->_getQuote();
      if (isset ($uspsQuote['Number']))
        return false;
      if ($order->delivery['country']['iso_code_2'] == 'US') {
        $dExtras = array ();
        $dOptions = explode(', ', MODULE_SHIPPING_USPS_DMST_SERVICES);
        foreach ($dOptions as $key => $val) {
          if (strlen($dOptions[$key]) > 1) {
            if ($dOptions[$key +1] == 'C' || $dOptions[$key +1] == 'S' || $dOptions[$key +1] == 'H') {
              $dExtras[$dOptions[$key]] = $dOptions[$key +1];
            }
          }
        }
      } else {
        $iExtras = array ();
        $iOptions = explode(', ', MODULE_SHIPPING_USPS_INTL_SERVICES);
        foreach ($iOptions as $key => $val) {
          if (strlen($iOptions[$key]) > 1) {
            if ($iOptions[$key +1] == 'C' || $iOptions[$key +1] == 'S' || $iOptions[$key +1] == 'H') {
              $iExtras[$iOptions[$key]] = $iOptions[$key +1];
            }
          }
        }
        if (MODULE_SHIPPING_USPS_REGULATIONS == 'True') {
          $iInfo = '<div id="iInfo">' .
            '<div id="showInfo" class="ui-state-error" style="cursor:pointer; text-align:center;" onclick="$(\'#showInfo\').hide();$(\'#hideInfo, #Info\').show();">' . MODULE_SHIPPING_USPS_TEXT_INTL_SHOW . '</div>' .
            '<div id="hideInfo" class="ui-state-error" style="cursor:pointer; text-align:center; display:none;" onclick="$(\'#hideInfo, #Info\').hide();$(\'#showInfo\').show();">' . MODULE_SHIPPING_USPS_TEXT_INTL_HIDE . '</div>' .
            '<div id="Info" class="ui-state-highlight" style="display:none; padding:10px; max-height:200px; overflow:auto;">' . '<b>Prohibitions:</b><br>' . nl2br($uspsQuote['Package']['Prohibitions']) . '<br><br><b>Restrictions:</b><br>' . nl2br($uspsQuote['Package']['Restrictions']) . '<br><br><b>Observations:</b><br>' . nl2br($uspsQuote['Package']['Observations']) . '<br><br><b>CustomsForms:</b><br>' . nl2br($uspsQuote['Package']['CustomsForms']) . '<br><br><b>PriorityMailExpress:</b><br>' . nl2br($uspsQuote['Package']['PriorityMailExpress']) . '<br><br><b>AreasServed:</b><br>' . nl2br($uspsQuote['Package']['AreasServed']) . '<br><br><b>AdditionalRestrictions:</b><br>' . nl2br($uspsQuote['Package']['AdditionalRestrictions']) . '</div>' .
            '</div>';
        }
      }
      
      if (isset ($uspsQuote['Package']['Postage']) && tep_not_null($uspsQuote['Package']['Postage'])) {
        $PackageSize = 1;
      } else {
        $PackageSize = ($order->delivery['country']['iso_code_2'] == 'US' ? sizeof($uspsQuote['Package']) : sizeof($uspsQuote['Package']['Service']));
      }
        
      for ($i = 0; $i < $PackageSize; $i++) {
        $Services = array ();
        $shownServices = array ();
        $hiddenServices = array ();
        $customerServices = array ();
        $hiddenCost = 0;
        $shownCost = 0;
        $shownString = '';
        $customerString = '';
        $handling = 0;
        $types = explode(', ', MODULE_SHIPPING_USPS_TYPES);
        if (isset ($uspsQuote['Package'][$i]['Error']) && tep_not_null($uspsQuote['Package'][$i]['Error']))
          continue;
        $Package = ($PackageSize == 1 ? $uspsQuote['Package']['Postage'] : ($order->delivery['country']['iso_code_2'] == 'US' ? $uspsQuote['Package'][$i]['Postage'] : $uspsQuote['Package']['Service'][$i]));
        if ($order->delivery['country']['iso_code_2'] == 'US') {
          if (tep_not_null($Package['SpecialServices']['SpecialService']))
            foreach ($Package['SpecialServices']['SpecialService'] as $key => $val)
              if (isset ($dExtras[$val['ServiceName']]) && tep_not_null($dExtras[$val['ServiceName']]) && ((MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' && $val['AvailableOnline'] == 'true') || (MODULE_SHIPPING_USPS_RATE_TYPE == 'Retail' && $val['Available'] == 'true'))) {
                $val['ServiceAdmin'] = $dExtras[$val['ServiceName']];
                $Services[] = $val;
              }
          $cost = MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' && tep_not_null($Package['CommercialRate']) ? $Package['CommercialRate'] : $Package['Rate'];
          $type = $Package['MailService'];
        } else {
          foreach ($Package['ExtraServices']['ExtraService'] as $key => $val) {
            if ( is_array ( $val ) && 
                 array_key_exists ( 'ServiceName', $val ) && 
                 isset ( $iExtras[$val['ServiceName']] ) && 
                 tep_not_null($iExtras[$val['ServiceName']]) && 
                 ((MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' && 
                     $val['AvailableOnline'] == 'True') || 
                   (MODULE_SHIPPING_USPS_RATE_TYPE == 'Retail' && 
                     $val['Available'] == 'True'))) {
              $val['ServiceAdmin'] = $iExtras[$val['ServiceName']];
              $Services[] = $val;
            }
          }
//          $cost = MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' && tep_not_null($Package['CommercialPostage']) ? $Package['CommercialPostage'] : $Package['Postage'];
          $cost = MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' && tep_not_null($Package['CommercialRate']) ? $Package['CommercialRate'] : $Package['Rate'];
          $type = $Package['SvcDescription'];
        }
        
        if ($cost == 0) {
          continue;
        }
          
        foreach ($types as $key => $val) {
          if (!is_numeric($val) && $val == $type) {
            $minweight = $types[$key +1];
            $maxweight = $types[$key +2];
            $handling = $types[$key +3];
          }
        }
        
        foreach ($Services as $key => $val) {
          $sDisplay = $Services[$key]['ServiceAdmin'];
          if ($sDisplay == 'H')
            $hiddenServices[] = array (
              $Services[$key]['ServiceName'] => (MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' ? $Services[$key]['PriceOnline'] : $Services[$key]['Price'])
            );
          elseif ($sDisplay == 'S') $shownServices[] = array (
            $Services[$key]['ServiceName'] => (MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' ? $Services[$key]['PriceOnline'] : $Services[$key]['Price'])
          );
          elseif ($sDisplay == 'C') $customerServices[] = array (
            $Services[$key]['ServiceName'] => (MODULE_SHIPPING_USPS_RATE_TYPE == 'Online' ? $Services[$key]['PriceOnline'] : $Services[$key]['Price'])
          );
        }
        
        foreach ($hiddenServices as $key => $val) {
          foreach ($hiddenServices[$key] as $key1 => $val1) {
            $hiddenCost += $val1;
          }
        }
        
//				echo 'services:<br><pre>'.print_r($Services,true) . "</pre><br>\n";
	//			echo 'hidden services:<br><pre>'.print_r($hiddenServices,true) . "</pre><br>\n";
		//		echo 'shown services:<br><pre>'.print_r($shownServices,true) . "</pre><br>\n";
//				echo 'customer services:<br><pre>'.print_r($customerServices,true) . "</pre><br>\n";
        if (sizeof($shownServices) > 0) {
          $shownString = '<div id="shownString" style="float:right; padding-right:10px; display:none;">' .
          '<div id="shownStringShow" style="cursor:pointer; text-align:center;" onclick="$(\'#shownStringShow\', $(this).parent().parent()).hide();$(\'#shownStringHide, #shownStringInfo\', $(this).parent().parent()).show();">' . MODULE_SHIPPING_USPS_TEXT_SSTRING_SHOW . '</div>' .
          '<div id="shownStringHide" style="cursor:pointer; text-align:center; display:none;" onclick="$(\'#shownStringHide, #shownStringInfo\', $(this).parent().parent()).hide();$(\'#shownStringShow\', $(this).parent().parent()).show();">' . MODULE_SHIPPING_USPS_TEXT_SSTRING_HIDE . '</div>' .
          '</div><div style="clear:both;"></div>' .
          '<div id="shownStringInfo" style="display:none;">' .
          '<div style="padding-left:20px; float:left;">' . MODULE_SHIPPING_USPS_TEXT_BASE_COST . '</div><div style="padding-right:20px; float:right;">' . ($cost == 0 ? MODULE_SHIPPING_USPS_TEXT_FREE : $currencies->format(($cost + (sizeof($hiddenServices) > 0 ? $handling + $hiddenCost : 0)) * $shipping_num_boxes)) . '</div><div style="clear:both;"></div>';
          if (sizeof($hiddenServices) == 0) {
            $shownString .= '<div style="padding-left:20px; float:left;">' . MODULE_SHIPPING_USPS_TEXT_HANDLING_COST . '</div>' .
              '<div style="padding-right:20px; float:right;">' . ($handling == 0 ? MODULE_SHIPPING_USPS_TEXT_FREE : $currencies->format($handling * $shipping_num_boxes)) . '</div>' .
              '<div style="clear:both;"></div>' .
              '<div style="clear:both;"></div>';
          }
          foreach ($shownServices as $key => $val) {
            foreach ($shownServices[$key] as $key1 => $val1) {
              $shownString .= '<div style="padding-left:20px; float:left;">' . str_replace(array (
                'RM',
                'TM'
              ), array (
                '&reg;',
                '&trade;'
              ), $key1) . '</div><div style="padding-right:20px; float:right;">' . ($val1 == 0 ? MODULE_SHIPPING_USPS_TEXT_FREE : $currencies->format($val1 * $shipping_num_boxes)) . '</div><div style="clear:both;"></div>';
              $shownCost = $shownCost + $val1;
            }
          }
          $shownString .= '</div><div style="clear:both;"></div>';
        }
        
        if (sizeof($customerServices) > 0) {
          $customerString = '<div id="customerString" style="float:right; padding-right:10px; display:none;">' .
          '<div id="customerStringShow" style="cursor:pointer; text-align:center;" onclick="$(\'#customerStringShow\', $(this).parent().parent()).hide();$(\'#customerStringHide, #customerStringInfo\', $(this).parent().parent()).show();">' . MODULE_SHIPPING_USPS_TEXT_CSTRING_SHOW . '</div>' .
          '<div id="customerStringHide" style="cursor:pointer; text-align:center; display:none;" onclick="$(\'#customerStringHide, #customerStringInfo\', $(this).parent().parent()).hide();$(\'#customerStringShow\', $(this).parent().parent()).show();">' . MODULE_SHIPPING_USPS_TEXT_CSTRING_HIDE . '</div>' .
          '</div><div style="clear:both;"></div>' .
          '<div id="customerStringInfo" style="display:none;">';
          foreach ($customerServices as $key => $val) {
            foreach ($customerServices[$key] as $key1 => $val1) {
              $customerString .= '<div style="padding-left:20px; float:left;">' . str_replace(array (
                'RM',
                'TM'
              ), array (
                '&reg;',
                '&trade;'
              ), $key1) . '</div>' .
              '<div style="padding-right:20px; float:right;"><input type="checkbox" name="' . $key1 . '" value="' . $val1 * $shipping_num_boxes . '" id="' . $type . '"></div>' .
              '<div style="padding-right:5px; float:right;">' . ($val1 == 0 ? MODULE_SHIPPING_USPS_TEXT_FREE : $currencies->format($val1 * $shipping_num_boxes)) . '</div>' .
              '<div style="clear:both;"></div>';
              $customerCost = $customerCost + $val1;
            }
          }
          $customerString .= '</div><div style="clear:both;"></div>';
        }

//        echo '<pre>types '.print_r($types,true)."</pre><br>\n";
//      echo '<pre>method '.$method.' type '.$type."</pre><br>\n";
				if ((($method == '' && in_array($type, $types)) || $method == $type) && $this->usps_weight < $maxweight && $this->usps_weight > $minweight) {
          $methods[] = array (
            'id' => $type,
            'title' => str_replace(array (
              'RM',
              'TM',
              '**'
            ), array (
              '<sup>&#174;</sup>',
              '<sup>&#8482;</sup>',
              ''
            ), $type),
            'cost' => ($cost + $handling + $hiddenCost + $shownCost) * $shipping_num_boxes,
            'shownString' => (string) $shownString,
            'customerString' => (string) $customerString
          );
        }
      }
      if (sizeof($methods) == 0)
        return false;
      if (sizeof($methods) > 1) {
        foreach ($methods as $c => $key) {
          $sort_cost[] = $key['cost'];
          $sort_id[] = $key['id'];
        }
        array_multisort($sort_cost, (MODULE_SHIPPING_USPS_RATE_SORTER == 'Ascending' ? SORT_ASC : SORT_DESC), $sort_id, SORT_ASC, $methods);
      }

      $weight_text = MODULE_SHIPPING_USPS_WEIGHTS == 'True' ? ' ' . $this->pounds . ' lbs, ' . $this->ounces . ' oz' : '';
      $this->quotes = array (
        'id' => $this->code,
        'module' => $this->title . $weight_text,
        'methods' => $methods,
        'tax' => $this->tax_class > 0 ? tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']) : null,
        'icon' => tep_not_null($this->icon) || tep_not_null($iInfo) ? (tep_not_null($this->icon) ? tep_image($this->icon, $this->title) : '') . (tep_not_null($iInfo) ? '<br>' . $iInfo : '') : null
      );
      
      return $this->quotes;
    }
    
    function _getQuote() {
      global $order;
      
      // Build a request string
      if ($order->delivery['country']['iso_code_2'] == 'US') {
        $ZipDestination = substr(str_replace(' ', '', $order->delivery['postcode']), 0, 5);
        $request = '<RateV4Request USERID="' . MODULE_SHIPPING_USPS_USERID . '">' . '<Revision>2</Revision>';
        $package_count = 0;
        $service = '';
        
        foreach (explode(',', MODULE_SHIPPING_USPS_TYPES) as $request_type) {
          $request_type = trim( $request_type );
          if (is_numeric($request_type) || preg_match('#International#', $request_type))
            continue;
          $first_class_type = '';
          $container = 'VARIABLE';
//					echo $request_type . "<br>\n";
          if( preg_match( '#First\-Class#', $request_type ) && $this->usps_weight < 16/16 ) {
//					echo 'processing first class' . "<br>\n";
            if( MODULE_SHIPPING_USPS_RATE_TYPE === 'Retail' ) {
              $service = 'First Class';
            } else {
              $service = 'First Class Commercial';
            }
//					echo $service . "<br>\n";

            if ($request_type == 'First-Class MailRM Stamped Letter') {
              if($this->usps_weight <= 3.5/16) {
                $first_class_type = '<FirstClassMailType>LETTER</FirstClassMailType>';
              } else {
                $first_class_type = '';
              }
            } elseif ($request_type == 'First-Class MailRM Large Envelope') {
              $first_class_type = '<FirstClassMailType>FLAT</FirstClassMailType>';
            } else {
              $first_class_type = '<FirstClassMailType>PARCEL</FirstClassMailType>';
            }
//					echo $first_class_type . "<br>\n";
          } elseif ($request_type == 'Media Mail Parcel') {
            $service = 'MEDIA';
          } elseif ($request_type == 'Library Mail Parcel') {
            $service = 'LIBRARY';
          } elseif ($request_type == 'USPS Retail GroundTM') {
            $service = 'RETAIL GROUND';
          } elseif (preg_match('#Priority MailTM#', $request_type)) {
            $service = 'PRIORITY COMMERCIAL';
            if ($request_type == 'Priority MailTM Flat Rate Envelope') {
              $container = 'FLAT RATE ENVELOPE';
            } elseif ($request_type == 'Priority MailTM Legal Flat Rate Envelope') {
              $container = 'LEGAL FLAT RATE ENVELOPE';
            } elseif ($request_type == 'Priority MailTM Padded Flat Rate Envelope') {
              $container = 'PADDED FLAT RATE ENVELOPE';
            } elseif ($request_type == 'Priority MailTM Small Flat Rate Box') {
              $container = 'SM FLAT RATE BOX';
            } elseif ($request_type == 'Priority MailTM Medium Flat Rate Box') {
              $container = 'MD FLAT RATE BOX';
            } elseif ($request_type == 'Priority MailTM Large Flat Rate Box') {
              $container = 'LG FLAT RATE BOX';
            } elseif ($request_type == 'Priority MailTM Regional Rate Box A') {
              $container = 'REGIONALRATEBOXA';
            } elseif ($request_type == 'Priority MailTM Regional Rate Box B') {
              $container = 'REGIONALRATEBOXB';
            }
          } elseif (preg_match('#Priority Mail ExpressTM#', $request_type)) {
            $service = 'EXPRESS COMMERCIAL';
            if ($request_type == 'Priority Mail ExpressTM Flat Rate Envelope') {
              $container = 'FLAT RATE ENVELOPE';
            } elseif ($request_type == 'Priority Mail ExpressTM Legal Flat Rate Envelope') {
              $container = 'LEGAL FLAT RATE ENVELOPE';
            }
          } else {
            continue;
          }
            
          // Create XML for this package using settings determined above
          $request .= '<Package ID="' . $package_count . '">' .
            '<Service>' . $service . '</Service>' .
            $first_class_type .
            '<ZipOrigination>' . SHIPPING_ORIGIN_ZIP . '</ZipOrigination>' .
            '<ZipDestination>' . $ZipDestination . '</ZipDestination>' .
            '<Pounds>' . $this->pounds . '</Pounds>' .
            '<Ounces>' . $this->ounces . '</Ounces>' .
            '<Container>' . $container . '</Container>' .
            '<Size>REGULAR</Size>' .
            '<Machinable>TRUE</Machinable>' .
            '</Package>';
          $package_count++;
        }
        
        $request .= '</RateV4Request>';
//					echo $request . "<br>\n";
        $request = 'API=RateV4&XML=' . urlencode($request);
        
      } else {
        //International delivery
        $request = '<IntlRateV2Request USERID="' . MODULE_SHIPPING_USPS_USERID . '">' .
        '<Revision>2</Revision>' .
        '<Package ID="0">' .
        '<Pounds>' . $this->pounds . '</Pounds>' .
        '<Ounces>' . $this->ounces . '</Ounces>' .
        '<MailType>All</MailType>' .
        '<GXG>' .
        '<POBoxFlag>N</POBoxFlag>' .
        '<GiftFlag>N</GiftFlag>' .
        '</GXG>' .
        '<ValueOfContents>' . ($order->info['subtotal'] + $order->info['tax']) . '</ValueOfContents>' .
        '<Country>' . tep_get_country_name($order->delivery['country']['id']) . '</Country>' .
        '<Container>RECTANGULAR</Container>' .
        '<Size>LARGE</Size>' .
        '<Width>0.2</Width>' .
        '<Length>0.2</Length>' .
        '<Height>0.2</Height>' .
        '<Girth>0</Girth>' .
        '<OriginZip>' . SHIPPING_ORIGIN_ZIP . '</OriginZip>' .

        // Changed N to Y to activate optional commercial base pricing for international services - 01/27/13 a.forever edit
        '<CommercialFlag>Y</CommercialFlag>' .
        '<ExtraServices>' .
        '<ExtraService>0</ExtraService>' .
        '<ExtraService>1</ExtraService>' .
        '<ExtraService>2</ExtraService>' .
        '<ExtraService>3</ExtraService>' .
        '<ExtraService>6</ExtraService>' .
        '<ExtraService>9</ExtraService>' .
        '</ExtraServices>' .
        '</Package>' .
        '</IntlRateV2Request>';
        $request = 'API=IntlRateV2&XML=' . urlencode($request);
      }
      
      // Connect to the USPS server and retrieve a quote
      $response_array = $this->retrieve_usps_response( $request );
	//				echo '<pre>'.print_r($response_array,true) . "</pre><br>\n";

      // Clean up the response
      $response_array = $this->clean_services( $response_array );
	//				echo 'cleaned:<br><pre>'.print_r($response_array,true) . "</pre><br>\n";
        
      return $response_array;
    }
    
    ////
    // This method was written for the July 2013 API changes
    // It removes the "Delivery Date" code from the MailService names
    // The "Delivery Date" codes are added to the Response array for future use
    function clean_services( $response_array ) {
      global $order;
      
      // Scrub these out of the MailService names
      $pattern = array (
        '/ 1-Day/i',
        '/ 2-Day/i',
        '/ 3-Day/i',
        '/ Military/i',
        '/ DPO/i'
      );
      
      // A single quote is a special case
      if (isset ($response_array['Package']['Postage']) && tep_not_null($response_array['Package']['Postage'])) {
        $service = $response_array['Package']['Postage']['MailService'];
        $temp_service = preg_replace( $pattern, '', $service );
        $response_array['Package']['Postage']['MailService'] = preg_replace( '/\s+/', ' ', $temp_service );
        $response_array['Package']['Postage']['DeliveryDays'] = $this->get_delivery_days( $pattern, $service );
      } else {  
        // Step through all of the quotes
        $count_services = count( $response_array['Package'] );
        for( $index=0; $index<$count_services; $index++ ) {
          $service = $response_array['Package'][$index]['Postage']['MailService'];
          // First Class service hack, because USPS isn't returning what they say they do.
          // Remove this block if they ever get their act together. 
          if( $service == 'First-Class Mail' ) {
            $first_class_type = $response_array['Package'][$index]['FirstClassMailType'];
            switch( $first_class_type ) {
              case 'LETTER' :
                $service .= 'RM Stamped Letter';
                break;

              case 'FLAT' :
                $service .= 'RM Large Envelope';
                break;
                
              case 'PARCEL' :
                $service .= 'Parcel';
                break;
                
              default :
                break;
            }
          }
          // End hack
          $temp_service = preg_replace( $pattern, '', $service );
          $response_array['Package'][$index]['Postage']['MailService'] = preg_replace( '/\s+/', ' ', $temp_service );
          $response_array['Package'][$index]['Postage']['DeliveryDays'] = $this->get_delivery_days( $pattern, $service );
        }
      }
        
      return $response_array;
    }
    
    function install() {
      tep_db_query("ALTER TABLE `configuration` CHANGE `configuration_value` `configuration_value` TEXT NOT NULL, CHANGE `set_function` `set_function` TEXT NULL DEFAULT NULL");
      tep_db_query("update " . TABLE_CONFIGURATION . " SET configuration_value =  'true' where configuration_key = 'EMAIL_USE_HTML'");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable USPS Shipping', 'MODULE_SHIPPING_USPS_STATUS', 'True', 'Do you want to offer USPS shipping?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Enter the USPS User ID', 'MODULE_SHIPPING_USPS_USERID', 'NONE', 'Enter the USPS USERID assigned to you.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_USPS_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Zone', 'MODULE_SHIPPING_USPS_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_USPS_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Shipping Methods (Domestic and International)',  'MODULE_SHIPPING_USPS_TYPES', '0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00, 0, 70, 0.00', '<b><u>Checkbox:</u></b> Select the services to be offered<br><b><u>Minimum Weight (lbs)</u></b>first input field<br><b><u>Maximum Weight (lbs):</u></b>second input field<br><br>USPS returns methods based on cart weights.  These settings will allow further control (particularly helpful for flat rate methods) but will not override USPS limits', '6', '0', 'tep_cfg_usps_services(array(" . $this->get_usps_services_list() . "), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Extra Services (Domestic)', 'MODULE_SHIPPING_USPS_DMST_SERVICES', 'Certified MailRM, N, Insurance, N, Adult Signature Restricted Delivery, N, Registered without Insurance, N, Registered MailTM, N, Collect on Delivery, N, Return Receipt for Merchandise, N, Return Receipt, N, Certificate of Mailing, N, Express Mail Insurance, N, Delivery ConfirmationTM, N, Signature ConfirmationTM, N', 'Included in postage rates.  Not shown to the customer.', '6', '0', 'tep_cfg_usps_extraservices(array(\'Certified MailRM\', \'Insurance\', \'Adult Signature Restricted Delivery\', \'Registered without Insurance\', \'Registered MailTM\', \'Collect on Delivery\', \'Return Receipt for Merchandise\', \'Return Receipt\', \'Certificate of Mailing\', \'Express Mail Insurance\', \'Delivery ConfirmationTM\', \'Signature ConfirmationTM\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Extra Services (International)', 'MODULE_SHIPPING_USPS_INTL_SERVICES', 'Registered Mail, N, Insurance, N, Return Receipt, N, Restricted Delivery, N, Pick-Up, N, Certificate of Mailing, N', 'Included in postage rates.  Not shown to the customer.', '6', '0', 'tep_cfg_usps_extraservices(array(\'Registered Mail\', \'Insurance\', \'Return Receipt\', \'Restricted Delivery\', \'Pick-Up\', \'Certificate of Mailing\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Retail pricing or Online pricing?', 'MODULE_SHIPPING_USPS_RATE_TYPE', 'Retail', 'Rates will be returned ONLY for methods available in this pricing type.  Applies to prices <u>and</u> add on services', '6', '0', 'tep_cfg_select_option(array(\'Retail\', \'Online\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rates Sort Order:', 'MODULE_SHIPPING_USPS_RATE_SORTER', 'Ascending', 'Ascending: Low to High<br>Descending: High to Low', '6', '0', 'tep_cfg_select_option(array(\'Ascending\', \'Descending\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Show International Regulations:', 'MODULE_SHIPPING_USPS_REGULATIONS', 'True', 'Displays international regulations and customs information.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Show Weights', 'MODULE_SHIPPING_USPS_WEIGHTS', 'True', 'Displays the package weight on the quotes.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('USPS Server', 'MODULE_SHIPPING_USPS_SERVER', '', 'The USPS server to send the request to. <b>Change this only if instructed to do so!</b>', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Send Debug Email', 'MODULE_SHIPPING_USPS_DEBUG', 'False', 'Send an email to the store owner with the USPS request and response.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
    }
    
    function keys() {
      return array (
        'MODULE_SHIPPING_USPS_STATUS',
        'MODULE_SHIPPING_USPS_USERID',
        'MODULE_SHIPPING_USPS_TAX_CLASS',
        'MODULE_SHIPPING_USPS_ZONE',
        'MODULE_SHIPPING_USPS_SORT_ORDER',
        'MODULE_SHIPPING_USPS_TYPES',
        'MODULE_SHIPPING_USPS_DMST_SERVICES',
        'MODULE_SHIPPING_USPS_INTL_SERVICES',
        'MODULE_SHIPPING_USPS_RATE_TYPE',
        'MODULE_SHIPPING_USPS_RATE_SORTER',
        'MODULE_SHIPPING_USPS_REGULATIONS',
        'MODULE_SHIPPING_USPS_WEIGHTS',
        'MODULE_SHIPPING_USPS_SERVER',
        'MODULE_SHIPPING_USPS_DEBUG'
      );
    }
    
    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
    
    function check() {
      if (!isset ($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_USPS_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }

      return $this->_check;
    }
    
    function get_delivery_days( $delivery_array, $service ) {
      $return = NULL;
      foreach( $delivery_array as $delivery ) {
        if( strpos( $service, $delivery) !== false ) {
          $return = $delivery;
          break;
        }
      }
      return $return;
    }
  
    ////
    // Moved the list of functions here to make it easier to change in the future.
//        \'First-ClassTM Package Service\',
    // These are used in the install method to set the desired services
    function get_usps_services_list() {
      $services_list = "
        \'First-Class MailRM Stamped Letter\', 
        \'First-Class MailRM Large Envelope\', 
        \'First-Class MailRM Parcel\',
        \'First-Class Package Service - RetailTM\',
        \'Media Mail Parcel\', 
        \'Library Mail Parcel\',
        \'USPS Retail GroundTM\', 
        \'Priority MailTM\', 
        \'Priority MailTM Flat Rate Envelope\', 
        \'Priority MailTM Legal Flat Rate Envelope\', 
        \'Priority MailTM Padded Flat Rate Envelope\', 
        \'Priority MailTM Small Flat Rate Box\', 
        \'Priority MailTM Medium Flat Rate Box\', 
        \'Priority MailTM Large Flat Rate Box\', 
        \'Priority MailTM Regional Rate Box A\', 
        \'Priority MailTM Regional Rate Box B\', 
        \'Priority Mail ExpressTM\', 
        \'Priority Mail ExpressTM Flat Rate Envelope\', 
        \'Priority Mail ExpressTM Legal Flat Rate Envelope\', 
        \'First-Class MailRM International Letter\', 
        \'First-Class MailRM International Large Envelope\', 
        \'First-Class Package International ServiceTM\', 
        \'Priority Mail InternationalRM\', 
        \'Priority Mail InternationalRM Flat Rate Envelope\', 
        \'Priority Mail InternationalRM Small Flat Rate Box\', 
        \'Priority Mail InternationalRM Medium Flat Rate Box\', 
        \'Priority Mail InternationalRM Large Flat Rate Box\', 
        \'Priority Mail Express InternationalTM\', 
        \'Priority Mail Express InternationalTM Flat Rate Envelope\', 
        \'USPS GXGTM Envelopes\', 
        \'Global Express GuaranteedRM (GXG)\'
      ";
      
      return preg_replace( '/\s+/', ' ', $services_list );
    }


    ////
    // Connect to the USPS server, send a request, and retrieve a response in array form
    function retrieve_usps_response( $request ) {
      // Try to use cURL to get the response
      $curl_response = $this->curl_get_response( $request );
      if( $curl_response !== false ) {
        $response = $curl_response['content'];
      } else { // cURL has failed, so try the HTTP client class
        $http_response = $this->http_get_response( $request );
        if( $http_response !== false ) {
          $response = $http_response['content'];
        }
      }
      
      // Send the debug email if set
      if ( MODULE_SHIPPING_USPS_DEBUG == 'True' ) {
        $mail_body = "Request:\n" . urldecode($request) . "\n\nResponse:\n" . $response . "\n\nErrors:\n" . $this->xml_errors;
        mail( STORE_OWNER_EMAIL_ADDRESS, 'USPS Debug Message for ' . STORE_NAME, $mail_body );
      }
      
      // Convert the XML string to an array and return
      $response_array = $this->xml_to_array($response);
      
      return $response_array;
    } // private function retrieve_usps_response
    
    
    ////
    // Convert XML to an array
    // $xmlstring: A string containing valid XML
    function xml_to_array( $xmlstring ) {
        $xmlstring = preg_replace( array(
                  '{& }',  /* Fix improper encoding of Post Office names - Sept. 2014 Update */
                  '{&lt;sup&gt;&#174;&lt;/sup&gt;}',  /* Registered Trademark symbol - July 2013 update */
                  '{&lt;sup&gt;&#8482;&lt;/sup&gt;}',  /* Trademark symbol - July 2013 update */
                  '/<br>/'
                ), array (
                  '&amp;',
                  'RM',
                  'TM',
                  'BREAK'
                ), htmlspecialchars_decode($xmlstring));
        
      // Convert the XML string into a class of values
      $xml = simplexml_load_string( $xmlstring );
      
      // Test the result and send errors if any
      $this->xml_errors = '';
      if ($xml === false) {
        $this->xml_errors .= "Failed loading XML\n";
        foreach(libxml_get_errors() as $error) {
          $this->xml_errors .= "\t" . $error->message;
        }
      }

      // Sadly, the best way to convert the weird class that PHP generates
      // into a usable array is to encode it using JSON and then decode it again
      //TODO: Come up with a better way to do this. Seriously.
      $json = json_encode( $xml );
      $array = json_decode( $json, true );
    
      return $array;
    } //function xml_to_array


    ////
    // Use cURL to connect to USPS and get the response
    // Returns an array containing the body of the response and error codes.
    // $request is the formatted XML request string
    function curl_get_response( $request ) {
      $url = 'http://' . $this->usps_server . '/' . $this->api_page . '?' . $request;
    
      // Check whether cURL is available
      if( function_exists( 'curl_init' ) ) {
        // Initialize cURL
        $ch = curl_init ( $url );
    
        // Set up the transfer options
        $options = array(
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle compressed
            CURLOPT_USERAGENT => "osCommerce", // who am i
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_MAXREDIRS => 5  // stop after 5 redirects
        );
        curl_setopt_array( $ch, $options );
    
        // execute cURL and get the content of the response
        $content = curl_exec( $ch );
    
        // Get the error information if any
        $err = curl_errno( $ch );
        $errmsg = curl_error( $ch );
    
        // Get transfer info
        $header = curl_getinfo( $ch );
    
        // Close the connection
        curl_close( $ch );
    
        // Add the information to the header array and return it
        $header['content'] = $content;
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
    
        return $header;
    
      } else { // cURL failed
        return false;
      }
    } // private function curl_get_response
    
    
    ////
    // Use the HTTP Client class to connect to USPS and return a quote
    function http_get_response( $request ) {
      @include_once DIR_WS_CLASSES . 'http.php';
      $errmsg = array();
      $content = '';
       
      if( class_exists( 'http_class' ) ) {
        $arguments = '';
        // Instantiate the http class
        $http = new http_class;
    
        // Set Connection timeout
        $http->timeout = 0;
    
        /* Data transfer timeout */
        $http->data_timeout = 0;
    
        /* Output debugging information about the progress of the connection */
        $http->debug = 0;
    
        /* Format dubug output to display with HTML pages */
        $http->html_debug = 0;
         
        // Follow the URL of redirect responses
        $http->follow_redirect = 1;
    
        // How many consecutive redirected requests the class should follow.
        $http->redirection_limit = 5;
    
        // We already tried cURL, so leave this off.
        $http->prefer_curl = 0;
    
        $url = 'http://' . $this->usps_server . '/' . $this->api_page . '?' . $request;
         
        // Generate a list of arguments for opening a connection and make an
        // HTTP request from a given URL.
        $errmsg['args'] = $http->GetRequestArguments( $url, $arguments );
    
        // Set additional request headers */
        $arguments['Headers']['Host'] = 'production.shippingapis.com';
        $arguments['Headers']['User-Agent'] = 'osCommerce';
        $arguments['Headers']['Connection'] = 'Close';
    
        // Open the connection
        $errmsg['open'] = $http->Open($arguments);
    
        if( !tep_not_null( $errmsg['open'] ) ) {
          // Send the request
          $errmsg['send'] = $http->SendRequest( $arguments );
    
          if( !tep_not_null( $errmsg['send'] ) ) {
            $headers = array();
            $errmsg['reply_headers'] = $http->ReadReplyHeaders( $headers );
             
            if( !tep_not_null( $errmsg['reply_headers'] ) ) {
              while( ( $reply_error = $http->ReadReplyBody( $body, 1000 ) ) === '' ) {
                if( strlen( $body ) == 0 )  break;
    
                $content .= $body;
              }
            }
          }
    
          // Close the connection
          $http->Close();
        }
    
        // Add the information to the header array and return it
        $header['content'] = $content;
        $header['errmsg'] = $errmsg;
    
        return $header;
    
      } else { // http class does not exist
        return false;
      }
    } // private function http_get_response
    
  } // class

  
  ////
  //
  if( !function_exists( 'tep_cfg_usps_services' ) ) {
    function tep_cfg_usps_services($select_array, $key_value, $key = '') {
      $key_values = explode(", ", $key_value);
      $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
      $string = '<b><div style="width:20px;float:left;text-align:center;">&nbsp;</div><div style="width:30px;float:left;text-align:center;">Min</div><div style="width:30px;float:left;text-align:center;">Max</div><div style="float:left;"></div><div style="width:55px;float:right;text-align:center;">Handling</div></b><div style="clear:both;"></div>';
      for ($i = 0; $i < sizeof($select_array); $i++) {
        $string .= '<div id="' . $key . $i . '">';
        $string .= '<div style="width:20px;float:left;text-align:center;">' . tep_draw_checkbox_field($name, $select_array[$i], (in_array($select_array[$i], $key_values) ? 'CHECKED' : '')) . '</div>';
        if (in_array($select_array[$i], $key_values))
          next($key_values);
        $string .= '<div style="width:30px;float:left;text-align:center;">' . tep_draw_input_field($name, current($key_values), 'size="1"') . '</div>';
        next($key_values);
        $string .= '<div style="width:30px;float:left;text-align:center;">' . tep_draw_input_field($name, current($key_values), 'size="1"') . '</div>';
        next($key_values);
        $string .= '<div style="float:left;padding:5px;">' . preg_replace(array (
          '/RM/',
          '/TM/',
          '/International/',
          '/Envelope/',
          '/ Mail/',
          '/Large/',
          '/Medium/',
          '/Small/',
          '/First/',
          '/Legal/',
          '/Padded/',
          '/Flat Rate/',
          '/Regional Rate/',
          '/Express Guaranteed /'
        ), array (
          '',
          '',
          'Int\'l',
          'Env',
          '',
          'Lg.',
          'Md.',
          'Sm.',
          '1st',
          'Leg.',
          'Pad.',
          'F/R',
          'R/R',
          'Exp Guar'
        ), $select_array[$i]) . '</div>';
        $string .= '<div style="width:55px;float:right;text-align:center;">$' . tep_draw_input_field($name, current($key_values), 'size="2"') . '</div>';
        next($key_values);
        $string .= '<div style="clear:both;"></div></div>';
      }
      return $string;
    }
  }
  
  if( !function_exists( 'tep_cfg_usps_extraservices' ) ) {
    function tep_cfg_usps_extraservices($select_array, $key_value, $key = '') {
      $key_values = explode(", ", $key_value);
      $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
      $string = '<b><div style="width:20px;float:left;text-align:center;">N</div><div style="width:20px;float:left;text-align:center;">S</div><div style="width:20px;float:left;text-align:center;">H</div></b><div style="clear:both;"></div>';
      for ($i = 0; $i < sizeof($select_array); $i++) {
        $string .= tep_draw_hidden_field($name, $select_array[$i]);
        next($key_values);
        $string .= '<div id="' . $key . $i . '">';
        $string .= '<div style="width:20px;float:left;text-align:center;"><input type="checkbox" name="' . $name . '" value="N" ' . (current($key_values) == 'N' || current($key_values) == '' ? 'CHECKED' : '') . ' id="N" onClick="if($(this).is(\':checked\')) $(\'#C, #S, #H\', $(\'#' . $key . $i . '\')).removeAttr(\'checked\'); if($(\':checkbox:checked\', $(\'#' . $key . $i . '\')).size() == 0) $(this).attr(\'checked\', \'checked\');"></div>';
        $string .= '<div style="width:20px;float:left;text-align:center;"><input type="checkbox" name="' . $name . '" value="S" ' . (current($key_values) == 'S' ? 'CHECKED' : '') . ' id="S" onClick="if($(this).is(\':checked\')) $(\'#N, #C, #H\', $(\'#' . $key . $i . '\')).removeAttr(\'checked\'); if($(\':checkbox:checked\', $(\'#' . $key . $i . '\')).size() == 0) $(\'#N\', $(\'#' . $key . $i . '\')).attr(\'checked\', \'checked\');"></div>';
        $string .= '<div style="width:20px;float:left;text-align:center;"><input type="checkbox" name="' . $name . '" value="H" ' . (current($key_values) == 'H' ? 'CHECKED' : '') . ' id="H" onClick="if($(this).is(\':checked\')) $(\'#N, #C, #S\', $(\'#' . $key . $i . '\')).removeAttr(\'checked\'); if($(\':checkbox:checked\', $(\'#' . $key . $i . '\')).size() == 0) $(\'#N\', $(\'#' . $key . $i . '\')).attr(\'checked\', \'checked\');"></div>';
        next($key_values);
        $string .= preg_replace(array (
          '/Signature/',
          '/without/',
          '/Merchandise/',
          '/TM/',
          '/RM/'
        ), array (
          'Sig',
          'w/out',
          'Merch.',
          '',
          ''
        ), $select_array[$i]) . '<br>';
        $string .= '<div style="clear:both;"></div></div>';
      }
      return $string;
    }
  }
  
?>