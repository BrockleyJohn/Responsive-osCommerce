<?php
/* sew_utils.php 
  version 1.0
  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
	
	handler for switching config vars
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

/*  echo '<pre>';
	
	print_r($_GET);
	print_r($_POST);
	
  echo '</pre>'; */
	
	$action = (isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '') );
	$return = (isset($_POST['return']) ? $_POST['return'] : (isset($_GET['return']) ? $_GET['return'] : '') );
	
	if (! strlen($return)) {
	  echo 'missing return parameter';
		exit;
	}

	switch($action) {
	
	  case 'set_var' :
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
			
		  break;
	
	}

  $params = (isset($_POST['param_string']) ? $_POST['param_string'] : '');
	tep_redirect(tep_href_link($return, $params));

?>