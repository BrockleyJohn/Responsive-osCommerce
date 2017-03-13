<?php
/*
  Store Opening Times BS
	- set of modules for Responsive osCommerce
	- this dashboard module allows admin to open / close the store early for the day
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

  class d_store_times {
    var $code;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->title = MODULE_ADMIN_DASHBOARD_STORE_TIMES_TITLE;
      $this->description = MODULE_ADMIN_DASHBOARD_STORE_TIMES_DESCRIPTION;

      if ( defined('MODULE_ADMIN_DASHBOARD_STORE_TIMES_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_STORE_TIMES_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_STORE_TIMES_STATUS == 'True');
      }
    }

    function getOutput() {
      global $store_timecode, $store_time, $store_date, $store_day;

      $output = '';

			$msg_type = 'Info';
			$msg = tep_draw_form('store_times','sew_utils.php');
      $store_status = sew_is_store_open();
			$msg .= strftime('%H:%M '.DATE_FORMAT_LONG,$store_timecode). ' - ';
			
      switch ($store_status) {
			  case 'open':
			  case 'open-override':
			    if ($change = sew_store_closes()) { // has returned a close time, will close automatically
				    $msg .= sprintf(MODULE_ADMIN_DASHBOARD_STORE_TIMES_OPEN,$change);
				  } else {
				    $msg .= MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSE_OVERRIDDEN;
			      $msg_type = 'Error';
					}
					$msg .= tep_draw_button(MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSE_NOW,'power');
				  if ($store_status == 'open') {
						$msg .= tep_draw_hidden_field('var','STORE_OPEN_OVERRIDE');
						$msg .= tep_draw_hidden_field('value','True');
					} else {
						$msg .= tep_draw_hidden_field('var','STORE_CLOSE_OVERRIDE');
						$msg .= tep_draw_hidden_field('value','False');
					}
					break;
			  case 'closed':
			  case 'closed-override':
			    if ($change = sew_store_opens()) { // has returned a opens time, will open automatically
				    $msg .= sprintf(MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSED,$change);
				  } else {
				    $msg .= MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSED_NOW;
					}
					$msg .= tep_draw_button(MODULE_ADMIN_DASHBOARD_STORE_TIMES_OPEN_NOW,'power');
				  if ($store_status == 'closed') {
						$msg .= tep_draw_hidden_field('var','STORE_CLOSE_OVERRIDE');
						$msg .= tep_draw_hidden_field('value','True');
					} else {
						$msg .= tep_draw_hidden_field('var','STORE_OPEN_OVERRIDE');
						$msg .= tep_draw_hidden_field('value','False');
					}
					break;
				case 'holiday':
				case 'weekly':
				  $msg .= MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSED_TODAY;
			    $msg_type = 'Warning';
					break;
				default:
				  $msg .= MODULE_ADMIN_DASHBOARD_STORE_TIMES_ERROR;
			    $msg_type = 'Error';
			}
			$msg .= tep_draw_hidden_field('action','set_var');
			$msg .= tep_draw_hidden_field('return','index.php');
			$msg .= '</form>';
		
      $output .= '<div class="sec'.$msg_type.'">';
      $output .= '<p class="smallText">' . $msg . '</p>';
      $output .= '</div>';

      return $output;
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_DASHBOARD_STORE_TIMES_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Security Checks Module', 'MODULE_ADMIN_DASHBOARD_STORE_TIMES_STATUS', 'True', 'Do you want to run the security checks for this installation?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_STORE_TIMES_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_STORE_TIMES_STATUS', 'MODULE_ADMIN_DASHBOARD_STORE_TIMES_SORT_ORDER');
    }
  }
// helper functions
include_once(DIR_FS_CATALOG.'/includes/functions/store_times.php');
