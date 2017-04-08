<?php
/* sew_utils.php 
  version 2.0
  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
	
	actions for utility handler separated to include files
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

		  // clear existing vars
			if (isset($_POST['var']) && isset($_POST['value'])) {
			  $var = filter_input(INPUT_POST, 'var', FILTER_SANITIZE_STRING);
			  $value = filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING);
				if (tep_db_num_rows(tep_db_query('select configuration_value from configuration where configuration_key = "'.$var.'"'))) {
				  tep_db_query('update configuration set configuration_value = "'.$value.'" where configuration_key = "'.$var.'"');
				} else {
          tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_group_id, date_added) values ('switching var added by sew_utils', '".$var."', '".$value."', '6', now())");
				}
			}