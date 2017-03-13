<?php 
/*
  Store Opening Times BS
	- set of modules for Responsive osCommerce
	- this content module displays a status message on each page of the store
	- it also contains all the main settings for the suite (e.g. the opening times)
	- modules in set:
	-- admin dashboard module - show status and quick override
	-- content module - display message and handle all settings
	-- header tags module - divert closed pages
	-- footer module - display store times

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/
class cm_header_store_times {

  function __construct() {
    $this->code = get_class($this);
    $this->group = basename(dirname(__FILE__));

    $this->title = MODULE_CONTENT_HEADER_STORE_TIMES_TITLE;
    $this->description = MODULE_CONTENT_HEADER_STORE_TIMES_DESCRIPTION;
    $this->description .= '<div class="secWarning">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

    if ( defined('MODULE_CONTENT_HEADER_STORE_TIMES_STATUS') ) {
      $this->sort_order = MODULE_CONTENT_HEADER_STORE_TIMES_SORT_ORDER;
      $this->enabled = (MODULE_CONTENT_HEADER_STORE_TIMES_STATUS == 'True');
    }
  }

  function execute() {
    global $PHP_SELF, $oscTemplate, $store_timecode, $store_time, $store_date, $store_day, $store_status;
      
    $content_width = (int)MODULE_CONTENT_HEADER_STORE_TIMES_CONTENT_WIDTH;
		
		if (!isset($store_status) || strlen($store_status) == 0) $store_status = sew_is_store_open();
		$msg = strftime('%H:%M '.DATE_FORMAT_LONG,$store_timecode). ' - ';
    switch ($store_status) {
		  case 'open' :
		  case 'open-override' :
				if ($change = sew_store_closes()) { // has returned a close time, will close automatically
					$msg .= sprintf(MODULE_CONTENT_HEADER_STORE_TIMES_OPEN,$change);
				} else {
					$msg .= MODULE_CONTENT_HEADER_STORE_TIMES_OPEN_LATE;
				}
				$type = 'success';
				break;
		  case 'closed' :
				if ($change = sew_store_opens()) { // has returned an opens time, will open automatically
					$msg .= sprintf(MODULE_CONTENT_HEADER_STORE_TIMES_CLOSED,$change);
				} else {
					$msg .= MODULE_CONTENT_HEADER_STORE_TIMES_CLOSED_NOW;
				}
				$type = 'warning';
				break;
		  case 'closed-override' :
				$msg .= MODULE_CONTENT_HEADER_STORE_TIMES_CLOSED_EARLY;
				$type = 'warning';
				break;
				break;
		  case 'weekly' :
			  $msg .= MODULE_CONTENT_HEADER_STORE_TIMES_WEEKLY;
				$type = 'warning';
				break;
		  case 'holiday' :
			  $msg .= MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAY;
				$type = 'warning';
				break;
			default :
			  $msg = 'error condition';
				$type = 'error';
		}
		
    ob_start();
    include('includes/modules/content/' . $this->group . '/templates/store_times.php');
    $template = ob_get_clean();

    $oscTemplate->addContent($template, $this->group);
  }

  function isEnabled() {
    return $this->enabled;
  }

  function check() {
    if (!isset($this->_check)) {
      $check_query = tep_db_query("select configuration_value from configuration where configuration_key = 'MODULE_CONTENT_HEADER_STORE_TIMES_STATUS'");
      $this->_check = tep_db_num_rows($check_query);
    }
    return $this->_check;
  }

  function install($parameter = null) {
    // check for installation settings of addon 8044 Store Closed and use if present
		if (file_exists(DIR_FS_CATALOG . 'includes/opencloz.php')){
      if (!defined('DIR_WS_INCLUDES')) define('DIR_WS_INCLUDES','../includes/');
			include_once( DIR_FS_CATALOG . 'includes/opencloz.php');
			initialize_open_cloze();
		}
		$params = $this->getParams();

    if (isset($parameter)) {
      if (isset($params[$parameter])) {
        $params = array($parameter => $params[$parameter]);
      } else {
        $params = array();
      }
    }

    foreach ($params as $key => $data) {
      $sql_data_array = array('configuration_title' => $data['title'],
                              'configuration_key' => $key,
                              'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
                              'configuration_description' => $data['desc'],
                              'configuration_group_id' => '6',
                              'sort_order' => '0',
                              'date_added' => 'now()');

      if (isset($data['set_func'])) {
        $sql_data_array['set_function'] = $data['set_func'];
      }

      if (isset($data['use_func'])) {
        $sql_data_array['use_function'] = $data['use_func'];
      }

      tep_db_perform('configuration', $sql_data_array);
    }
  }

  function remove() {
    tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  function keys() {
    $keys = array_keys($this->getParams());

    if ($this->check()) {
      foreach ($keys as $key) {
        if (!defined($key)) {
          $this->install($key);
        }
      }
    }

    return $keys;
  }

  function getParams() {
    global $open_for_business,$days_closed; // if defaults not set from previous addon, set them here
		if (!isset($open_for_business) || !is_array($open_for_business) || count($open_for_business['store_hours']) < 7 ) {
			$opening_times = array(
			  'sunday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False'),
			  'monday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False'),
			  'tuesday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False'),
			  'wednesday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False'),
			  'thursday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False'),
			  'friday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False'),
			  'saturday' => array('open' => '15:00', 'close' => '21:45', 'holiday' => 'False')
			);
			$holidays = array();
			$format = (defined('DATE_FORMAT') && strlen(DATE_FORMAT) > 3 && in_array(substr(DATE_FORMAT,0,3), array('d/m','m/d')) ? substr(DATE_FORMAT,0,3) : 'd/m');
		} else {
		  $opening_times = array();
			foreach ($open_for_business['store_hours'] as $day_times) {
			  if ($day_times['close'] > $day_times['open']) {
				  $opening_times[$day_times['day']] = array('open' => substr($day_times['open'],0,5), 'close' => substr($day_times['close'],0,5), 'holiday' => 'False');
				} else {
				  $opening_times[$day_times['day']] = array('open' => '', 'close' => '', 'holiday' => 'True');
				}
			}
			$holidays = array();
			if (isset($days_closed) && is_array($days_closed) && count($days_closed)) {
			  foreach($days_closed as $day_closed) {
				  if (trim($day_closed) <> 'NONE') $holidays[] = str_replace(' to ','-',$day_closed);
				}
			}
			$format = 'm/d';
		}
    $params = array('MODULE_CONTENT_HEADER_STORE_TIMES_STATUS' => array('title' => 'Enable Store Times Module',
                                                                        'desc' => 'Do you want to enable the Store Times content module?',
                                                                        'value' => 'True',
                                                                        'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
																																				),
                    'MODULE_CONTENT_HEADER_STORE_TIMES_CONTENT_WIDTH' => array('title' => 'Content Width',
                                                                        'desc' => 'In what width container should the content be shown?',
                                                                        'value' => '12',
                                                                        'set_func' => 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), '
																																				),
                    'MODULE_CONTENT_HEADER_STORE_TIMES_SORT_ORDER' => array('title' => 'Sort Order',
                                                                        'desc' => 'Sort order of display. Lowest is displayed first.',
																																				'value' => '0'
																																				),
                    'MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAY_FORMAT' => array('title' => 'Holiday Date Format',
                                                                        'desc' => 'Choose between formats for holiday dates: day before month or month before day',
																																				'value' => $format,
                                                                        'set_func' => 'tep_cfg_select_option(array(\'d/m\', \'m/d\'), '
																																				),
                    'MODULE_CONTENT_HEADER_STORE_TIMES_HOLIDAYS' => array('title' => 'Holiday Dates',
                                                                        'desc' => 'Holidays in chosen format, separated by commas and dashes, eg 1/5,24/6-13/7,24/12,31/12',
																																				'value' => implode(',',$holidays)
																																				),
                    );
		// Store Times Normal Hours Settings
		foreach(array('sunday','monday','tuesday','wednesday','thursday','friday','saturday') as $day) {
			$params['MOD_CON_HDR_STORE_TIMES_'.strtoupper($day).'_OPENING'] = array('title' => ucfirst($day).' Opening',
																												'desc' => 'The time the store opens on '.ucfirst($day).' - required unless '.ucfirst($day).' Holiday is true. 24 hour clock format HH:mm',
																												'value' => $opening_times[$day]['open']);
			$params['MOD_CON_HDR_STORE_TIMES_'.strtoupper($day).'_CLOSING'] = array('title' => ucfirst($day).' Closing',
																												'desc' => 'The time the store closes on '.ucfirst($day).' - required unless '.ucfirst($day).' Holiday is true. 24 hour clock format HH:mm',
																												'value' => $opening_times[$day]['close']);
			$params['MOD_CON_HDR_STORE_TIMES_'.strtoupper($day).'_HOLIDAY'] = array('title' => ucfirst($day).' Holiday',
																												'desc' => 'Is the store closed every '.ucfirst($day).'?',
																												'value' => $opening_times[$day]['holiday'],
                                                        'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), ');
		}
		// Functionality settings
		$params['MOD_CON_HDR_STORE_TIMES_DIVERT_PAGES'] = array('title' => 'Divert store pages',
                                                                       'desc' => 'Do you want to divert pages when store is closed?',
                                                                       'value' => 'True',
                                                                       'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), ');
		$params['MOD_CON_HDR_STORE_TIMES_CLOSE_PAGES'] = array('title' => 'Pages to divert',
                                                                       'desc' => 'List of page names separated by ;',
                                                                       'value' => 'checkout_shipping.php;checkout_payment.php;checkout_confirmation.php');
    // Time calculation settings
		$params['MOD_CON_HDR_STORE_TIMES_STORE_TIME_ZONE'] = array('title' => 'Store time zone',
                                                                       'desc' => 'Use if the server is not in the same time zone as the store - <a href="http://php.net/manual/en/timezones.php" target="_blank">click for valid values</a>',
                                                                       'value' => '');

    return $params;
	}
}

// helper functions
include_once(DIR_FS_CATALOG.'/includes/functions/store_times.php');