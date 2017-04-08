<?php
/* sew_utils.php 
  version 2.0
  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
	
	handler for switching config vars
	actions for utility handler separated to include files
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

/*  echo '<pre>Get
	';
	
	print_r($_GET);
	echo '
	Post
	';
	print_r($_POST);
	
  echo '</pre>'; */
	
	$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
	$return = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_STRING);
	
	if (! strlen($return)) {
	  echo 'missing return parameter';
		exit;
	}

	if (! strlen($action)) {
	  echo 'missing action';
		exit;
	}

	if (file_exists('includes/sew_actions/'.$action.'.php')) {
	  include('includes/sew_actions/'.$action.'.php');
	} else {
	  echo 'invalid action';
		exit;
	}
	
  $params = (isset($_POST['param_string']) ? $_POST['param_string'] : '');
	tep_redirect(tep_href_link($return, $params));
?>