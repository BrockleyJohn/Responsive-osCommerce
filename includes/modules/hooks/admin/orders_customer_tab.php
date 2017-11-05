<?php
/*
  $Id$

  add customer orders tab to admin / orders.php
	
	author: John Ferguson @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License
*/

  class hook_admin_orders_customer_tab {
		
		function load_language() {
		  global $language;
      include_once(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/hooks/admin/' . basename(__FILE__));
		}

    function execute() {
      global $oID, $languages_id;
			$this->load_language();

      $output = '';

      $status = array();

      $query = tep_db_query("SELECT o.orders_id, o.date_purchased, os.orders_status_name, o.payment_method, ots.title as order_shipping, ott.text as order_total FROM ".TABLE_ORDERS." o left join ".TABLE_ORDERS_TOTAL." ots on (ots.orders_id = o.orders_id) left join ".TABLE_ORDERS_TOTAL." ott on (ott.orders_id = o.orders_id) left join ".TABLE_ORDERS_STATUS." os on (os.orders_status_id = o.orders_status) where ots.class = 'ot_shipping' and ott.class = 'ot_total' and os.language_id = '" . (int)$languages_id . "' and o.customers_id in (select customers_id from ".TABLE_ORDERS." where orders_id = '" . (int)$oID . "') order by date_purchased desc");
      if ( tep_db_num_rows($query) > 1 ) {
			
			  // if there are more orders - make a list of them (this one highlighted, others clickable to load admin orders page)
				$order_list = '<table border="0" width="100%" cellspacing="0" cellpadding="5">
      <tr class="dataTableHeadingRow">
        <td class="dataTableHeadingContent">'.TABLE_HEADING_DATE_PURCHASED.'</td>
        <td class="dataTableHeadingContent" align="right">'.TABLE_HEADING_ORDER_TOTAL.'</td>
        <td class="dataTableHeadingContent">'.TABLE_HEADING_STATUS.'</td>
        <td class="dataTableHeadingContent">'.TABLE_HEADING_PAYMENT_METHOD.'</td>
        <td class="dataTableHeadingContent">'.TABLE_HEADING_SHIPPING_METHOD.'</td>
      </tr>
';
        while ($order = tep_db_fetch_array($query)) {

					if ($order['orders_id'] == $oID) {
						$order_list .= '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="">' . "\n";
					} else {
						$order_list .= '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $order['orders_id'] . '&action=edit') . '\'">' . "\n";
					}
		
					$order_list .= '                <td class="dataTableContent">'. tep_datetime_short($order['date_purchased']) .'</td>
                <td class="dataTableContent" align="right">'. strip_tags($order['order_total']) .'</td>
                <td class="dataTableContent">'. $order['orders_status_name'] . '</td>
                <td class="dataTableContent">'. $order['payment_method'] .'</td>
                <td class="dataTableContent">'. $order['order_shipping'] .'</td>
              </tr>
';
         }
				 $order_list .= '</table>'."\n";

          $tab_title = addslashes(TAB_CUSTOMER_ORDERS);
          $tab_link = tep_href_link('orders.php', tep_get_all_get_params()) . '#section_customer_orders';

          $output = <<<EOD
<script>
$(function() {
  $('#orderTabs ul:first').append('<li><a href="{$tab_link}">{$tab_title}</a></li>');
});
</script>

<div id="section_customer_orders" style="padding: 10px;">
  $order_list
</div>
EOD;

      }

      return $output;
    }

  } 
