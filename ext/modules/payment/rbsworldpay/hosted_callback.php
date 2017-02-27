<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  chdir('../../../../');
  require ('includes/application_top.php');

  if ( !defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS') || (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS  != 'True') ) {
    exit;
  }

  include('includes/languages/' . basename($_POST['M_lang']) . '/modules/payment/rbsworldpay_hosted.php');
  include('includes/modules/payment/rbsworldpay_hosted.php');

  $rbsworldpay_hosted = new rbsworldpay_hosted();

  $error = false;
  $cancelled = false;

  if ( !isset($_GET['installation']) || ($_GET['installation'] != MODULE_PAYMENT_RBSWORLDPAY_HOSTED_INSTALLATION_ID) ) {
    $error = true;
  } elseif ( !isset($_POST['installation']) || ($_POST['installation'] != MODULE_PAYMENT_RBSWORLDPAY_HOSTED_INSTALLATION_ID) ) {
    $error = true;
  } elseif ( tep_not_null(MODULE_PAYMENT_RBSWORLDPAY_HOSTED_CALLBACK_PASSWORD) && (!isset($_POST['callbackPW']) || ($_POST['callbackPW'] != MODULE_PAYMENT_RBSWORLDPAY_HOSTED_CALLBACK_PASSWORD)) ) {
    $error = true;
  } elseif ( !isset($_POST['transStatus']) || ($_POST['transStatus'] != 'Y') ) {
    if ($_POST['transStatus'] == 'C') {
		  $cancelled = true;
    } else {
      $error = true;
    }
  } elseif ( !isset($_POST['M_hash']) || !isset($_POST['M_sid']) || !isset($_POST['M_cid']) || !isset($_POST['cartId']) || !isset($_POST['M_lang']) || !isset($_POST['amount']) || ($_POST['M_hash'] != md5($_POST['M_sid'] . $_POST['M_cid'] . $_POST['cartId'] . $_POST['M_lang'] . number_format($_POST['amount'], 2) . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_MD5_PASSWORD)) ) {
    $error = true;
  }

  if ( $error == false ) {
    $order_query = tep_db_query("select orders_id, orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . (int)$_POST['cartId'] . "' and customers_id = '" . (int)$_POST['M_cid'] . "'");

    if (!tep_db_num_rows($order_query)) {
      $error = true;
    }
  }

  if ( $error == true ) {
    $rbsworldpay_hosted->sendDebugEmail();

    exit;
  }
	
if ($cancelled == false) {

  $order = tep_db_fetch_array($order_query);

  if ($order['orders_status'] == MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID) {
    $order_status_id = (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID);

    tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . $order_status_id . "', last_modified = now() where orders_id = '" . (int)$order['orders_id'] . "'");

    $sql_data_array = array('orders_id' => $order['orders_id'],
                            'orders_status_id' => $order_status_id,
                            'date_added' => 'now()',
                            'customer_notified' => '0',
                            'comments' => '');

    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  }

  $trans_result = 'WorldPay: Transaction Verified (Callback)' . "\n" .
                  'Transaction ID: ' . $_POST['transId'];

  if (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TESTMODE == 'True') {
    $trans_result .= "\n" . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_WARNING_DEMO_MODE;
  }
	
	if (isset($_POST['wafMerchMessage'])) {
	  if ($_POST['wafMerchMessage'] == 'waf.warning') {
			$trans_result .= "\n\n" . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_WAF . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_WAF_WARNING ."\n";
		} elseif ($_POST['wafMerchMessage'] == 'waf.caution') {
			$trans_result .= "\n\n" . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_WAF . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_WAF_CAUTION ."\n";
		}
	}
	
	if (isset($_POST['AVS']) && is_numeric($_POST['AVS']) && strlen($_POST['AVS']) == 4) {
    $valid_result = array(0,1,2,4,8);
		$avs = array(MODULE_PAYMENT_RBSWORLDPAY_HOSTED_AVS_CVV, MODULE_PAYMENT_RBSWORLDPAY_HOSTED_AVS_POSTCODE, MODULE_PAYMENT_RBSWORLDPAY_HOSTED_AVS_ADDRESS, MODULE_PAYMENT_RBSWORLDPAY_HOSTED_AVS_COUNTRY);
		for ($i = 0, $n = count($avs); $i < $n ; $i++) {
		  if (in_array(substr($_POST['AVS'],$i,1),$valid_result)) {
			  $trans_result .= "\n" . $avs[$i] . constant('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_AVS_'.substr($_POST['AVS'],$i,1));
			}
		}
	}

  $sql_data_array = array('orders_id' => $order['orders_id'],
                          'orders_status_id' => MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID,
                          'date_added' => 'now()',
                          'customer_notified' => '0',
                          'comments' => $trans_result);

  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
	
}
$url = tep_href_link(($cancelled ? 'checkout_payment.php' : 'checkout_process.php'), tep_session_name() . '=' . $_POST['M_sid'] . '&hash=' . $_POST['M_hash'], 'SSL', false);
?>
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
<title><?php echo tep_output_string_protected(MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_CALLBACK_TITLE); ?></title>
<meta http-equiv="refresh" content="3; URL=<?php echo $url; ?>">
<style>
 body {font-family:Geneva, Arial, Helvetica, sans-serif;}
</style>
</head>
<body>
<h1><?php echo STORE_NAME; ?></h1>

<p><?php echo ($cancelled ? MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_CANCEL_TRANSACTION : MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_SUCCESSFUL_TRANSACTION); ?></p>

<form action="<?php echo tep_href_link($url); ?>" method="post" target="_top">
  <p><input type="submit" value="<?php echo sprintf(($cancelled ? MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_RETURN_BUTTON : MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_CONTINUE_BUTTON), addslashes(STORE_NAME)); ?>" /></p>
</form>

<p>&nbsp;</p>

<WPDISPLAY ITEM=banner>

</body>
</html>

<?php
  tep_session_destroy();
?>
