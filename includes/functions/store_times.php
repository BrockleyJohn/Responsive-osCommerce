<?php
/*
  Store Opening Times BS
	- set of modules for Responsive osCommerce
	- helper functions used by module classes
	- modules in set:
	-- header content module (includes all settings)
	-- admin dashboard module
	-- footer module

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

// Function to check whether store is open
// returns open / closed / weekly (shut this day every week) / holiday (shut on this date this year)
  if( !function_exists( 'sew_is_store_open' ) ) {
    function sew_is_store_open() {
      global $store_timecode, $store_time, $store_date, $store_day;
			$store_timecode = sew_store_time();
			$store_time = date('H:i', $store_timecode);
			$store_date = date('m/d', $store_timecode);
			$store_day = date('l', $store_timecode);
			
			if (sew_is_store_holiday($store_date)) return 'holiday';
			
			if (defined('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_HOLIDAY') && constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_HOLIDAY') == 'True') return 'weekly';
			// ok, so it's supposed to be open today, check times and override
			if (defined('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_OPENING') && defined('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_CLOSING') && ($store_time < constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_OPENING') || $store_time > constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_CLOSING'))) {
			  // normally shut at this time - check overrides
				sew_override_open('False');
				if (sew_is_close_overridden()) {
				  return 'open-override';
				} else {
				  return 'closed';
				}
			} else {
			  // normally open at this time - check overrides
				sew_override_closed('False');
				if (sew_is_open_overridden()) {
				  return 'closed-override';
				} else {
				  return 'open';
				}
			}
    }
  }

// Function returns opening time if in future or false
  if( !function_exists( 'sew_store_opens' ) ) {
    function sew_store_opens() {
      global $store_timecode, $store_time, $store_date, $store_day;
			if (defined('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_OPENING') && $store_time < constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_OPENING')) {
		    return constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_OPENING');
			} else {
			  return false;
			}
    }
	}

// Function returns closing time if in future or false
  if( !function_exists( 'sew_store_closes' ) ) {
    function sew_store_closes() {
      global $store_timecode, $store_time, $store_date, $store_day;
			if (defined('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_OPENING') && defined('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_CLOSING') && $store_time < constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_CLOSING')) {
			  return constant('MOD_CON_HDR_STORE_TIMES_'.strtoupper($store_day).'_CLOSING');
			} else {
			  return false;
			}
    }
	}

// Function sets override open variable
  if( !function_exists( 'sew_override_open' ) ) {
    function sew_override_open($value) {
			sew_set_var('STORE_OPEN_OVERRIDE',$value);
    }
  }
// Function sets override closed variable
  if( !function_exists( 'sew_override_closed' ) ) {
    function sew_override_closed($value) {
			sew_set_var('STORE_CLOSE_OVERRIDE',$value);
    }
  }
// Function checks override open variable
  if( !function_exists( 'sew_is_open_overridden' ) ) {
    function sew_is_open_overridden() {
			return (defined('STORE_OPEN_OVERRIDE') && STORE_OPEN_OVERRIDE == 'True') ;
    }
  }
// Function checks override closed variable
  if( !function_exists( 'sew_is_close_overridden' ) ) {
    function sew_is_close_overridden() {
			return (defined('STORE_CLOSE_OVERRIDE') && STORE_CLOSE_OVERRIDE == 'True') ;
    }
  }
// Function sets variable
  if( !function_exists( 'sew_set_var' ) ) {
    function sew_set_var($var,$value) {
				if (tep_db_num_rows(tep_db_query('select configuration_value from configuration where configuration_key = "'.$var.'"'))) {
				  tep_db_query('update configuration set configuration_value = "'.$value.'" where configuration_key = "'.$var.'"');
				} else {
          tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_group_id, date_added) values ('switching var added by sew_set_var', '".$var."', '".$value."', '6', now())");
				}
		}
  }

// Function returns store time
  if( !function_exists( 'sew_store_time' ) ) {
    function sew_store_time() {
      // check if store timezone is set
			if (defined('MOD_CON_HDR_STORE_TIMES_STORE_TIME_ZONE') && strlen(MOD_CON_HDR_STORE_TIMES_STORE_TIME_ZONE) > 0) {
			  date_default_timezone_set(MOD_CON_HDR_STORE_TIMES_STORE_TIME_ZONE);
			}
			return time();
    }
  }
// Function returns boolean whether passed mm/dd is a store holiday
// NB checks holiday date format but assumes passed is correct 2 digit mm/dd
  if( !function_exists( 'sew_is_store_holiday' ) ) {
    function sew_is_store_holiday($day) {
			if (strpos($day,'/') && defined('MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS') && strlen(MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS) > 0) {
				$holidays = explode(',',MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS);
				if (count($holidays)) {
					if (MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAY_FORMAT == 'd/m') {
					  $d = 0;
						$m = 1;
					} else {
					  $d = 1;
						$m = 0;
					}
				  foreach($holidays as $holiday) {
					  if (strpos($holiday,'-')) {
						  $range = explode('-',$holiday);
							$from_bits = explode('/',$range[0]);
							$to_bits = explode('/',$range[1]);
							for ($i = 0; $i < 2; $i++) {
								$from_bits[$i] = (strlen(trim($from_bits[$i])) < 2 ? '0'.trim($from_bits[$i]) : trim($from_bits[$i]));
								$to_bits[$i] = (strlen(trim($to_bits[$i])) < 2 ? '0'.trim($to_bits[$i]) : trim($to_bits[$i]));
							}
							$from = implode('/',array($from_bits[$m],$from_bits[$d]));
							$to = implode('/',array($to_bits[$m],$to_bits[$d]));
							if ($to_bits[$m] > $from_bits[$m] || ($to_bits[$m] == $from_bits[$m] && $to_bits[$d] > $from_bits[$d])) { // end of range numerically greater than start
							  if (trim($day) >= trim($from) && trim($day) <= trim($to)) { 
								  return true;
								}
							} elseif ($to_bits[$m] < $from_bits[$m] && $to_bits[$m] < 13 && $from_bits[$m] < 13) { // valid month numbers apparently across new year
							  if (trim($day) >= trim($from) || trim($day) <= trim($to)) { 
								  return true;
								}
							}
						} else {
						  $hol_bits = explode('/',$holiday);
							for ($i = 0; $i < 2; $i++) {
								$hol_bits[$i] = (strlen(trim($hol_bits[$i])) < 2 ? '0'.trim($hol_bits[$i]) : trim($hol_bits[$i]));
							}
							$holiday = implode('/',array($hol_bits[$m],$hol_bits[$d]));
							if (trim($day) == trim($holiday)) return true;
						}
					}
				}
			}
			return false;
    }
  }
// Function returns list of holidays in date order
// NB checks holiday date format 
  if( !function_exists( 'sew_holiday_list' ) ) {
    function sew_holiday_list() {
      if (defined('MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS') && strlen(MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS) > 0) {
				$key_hols = array();
				$holidays = explode(',',MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS);
				if (count($holidays)) {
					if (MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAY_FORMAT == 'd/m') {
					  $d = 0;
						$m = 1;
					} else {
					  $d = 1;
						$m = 0;
					}
				  foreach($holidays as $holiday) {
					  if (strpos($holiday,'-')) {
						  $range = explode('-',$holiday);
							$key_bits = explode('/',$range[0]);
							$to_bits = explode('/',$range[1]);
						} else {
						  $key_bits = explode('/',$holiday);
						}
						for ($i = 0; $i < 2; $i++) {
							$key_bits[$i] = (strlen(trim($key_bits[$i])) < 2 ? '0'.trim($key_bits[$i]) : trim($key_bits[$i]));
						  if (strpos($holiday,'-')) {
								$to_bits[$i] = (strlen(trim($to_bits[$i])) < 2 ? '0'.trim($to_bits[$i]) : trim($to_bits[$i]));
							}
						}
						$key = implode('/',array($key_bits[$m],$key_bits[$d]));
						if (substr(DATE_FORMAT,0,1) == 'm' || substr(DATE_FORMAT,0,1) == 'M') {
						  $hol_format = $key;
							if (strpos($holiday,'-')) $hol_format .= ' - ' . implode('/',array($to_bits[$m],$to_bits[$d]));
						} else {
						  $hol_format = implode('/',array($key_bits[$d],$key_bits[$m]));
							if (strpos($holiday,'-')) $hol_format .= ' - ' . implode('/',array($to_bits[$d],$to_bits[$m]));
						}
						$key_hols[$key] = $hol_format;
					}
					ksort($key_hols);
					return implode(', ',$key_hols);
				}
			} 
			return '';
		}
	}
