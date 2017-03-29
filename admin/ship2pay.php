<?php
/*
  another rewrite for 2.3.4BS: @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
------------------------------------------
This module has been rewritten for Osc 2.3.1.

Copyright (c) 2012 Leonsajaxpage (leon.mail@hccnet.nl)

Released under the GNU General Public License
------------------------------------------

Original from
------------------------------------------
  $Id: Ship2Pay, v1.5 2005/01/07 00:00:00 gjw Exp $

  osCommerce, Open Source E-Commerce Solutions

  http://www.oscommerce.com

  Copyright (c) 2003 Edwin Bekaert (edwin@ednique.com)

  Released under the GNU General Public License

  http://forums.oscommerce.com/viewtopic.php?t=36112

*/

  require('includes/application_top.php');
	$query = tep_db_query('SHOW TABLES LIKE \'ship2pay\'');
	if (!tep_db_num_rows($query)) {
	  tep_db_query('CREATE TABLE `ship2pay` (`s2p_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `shipment` VARCHAR( 100 ) NOT NULL, `payments_allowed` VARCHAR( 250 ) NOT NULL, `status` TINYINT NOT NULL)');
	}
  require('includes/classes/shipping.php');
  $cShip = new shipping;
  require('includes/classes/payment.php');
  $cPay = new payment;
  if ($_GET['action']) {
    switch ($_GET['action']) {
      case 'insert':
        $shp_id = tep_db_prepare_input($_POST['shp_id']);
        if (isset($_POST['pay_ids'])){
          $pay_ids = tep_db_prepare_input(implode(";", $_POST['pay_ids']));
        }
        tep_db_query("insert into ship2pay (shipment, payments_allowed,status) values ('" . tep_db_input($shp_id) . "', '" . tep_db_input($pay_ids)."',0)");
        tep_redirect(tep_href_link('ship2pay.php'));
        break;
      case 'save':
        $s2p_id = tep_db_prepare_input($_GET['s2p_id']);
        $shp_id = tep_db_prepare_input($_POST['shp_id']);
        if (isset($_POST['pay_ids'])) {
          $pay_ids = tep_db_prepare_input(implode(";", $_POST['pay_ids']));
        }
        tep_db_query("update ship2pay set payments_allowed = '" . tep_db_input($pay_ids) . "', shipment = '" . tep_db_input($shp_id) . "' where s2p_id = ". tep_db_input($s2p_id));
        tep_redirect(tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p_id));
        break;
      case 'deleteconfirm':
        $s2p_id = tep_db_prepare_input($_GET['s2p_id']);
        tep_db_query("delete from ship2pay where s2p_id = " . tep_db_input($s2p_id));
        tep_redirect(tep_href_link('ship2pay.php', 'page=' . $_GET['page']));
        break;
      case 'disable':
        $shp_id = tep_db_prepare_input($_GET['s2p_id']);
        tep_db_query("update ship2pay set status = 0 where s2p_id = " . tep_db_input($shp_id));
        tep_redirect(tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p_id));
        break;
      case 'enable':
        $shp_id = tep_db_prepare_input($_GET['s2p_id']);
        tep_db_query("update ship2pay set status = 1 where s2p_id = " . tep_db_input($shp_id));
        tep_redirect(tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p_id));
        break;
    }
  }
  

  require('includes/template_top.php');
?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_SHIPMENT; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PAYMENTS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $s2p_query_raw = "select s2p_id, shipment, payments_allowed, status from ship2pay";
  $s2p_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $s2p_query_raw, $s2p_query_numrows);
  $s2p_query = tep_db_query($s2p_query_raw);
  while ($s2p = tep_db_fetch_array($s2p_query)) {
    if (((!$_GET['s2p_id']) || (@$_GET['s2p_id'] == $s2p['s2p_id'])) && (!$trInfo) && (substr($_GET['action'], 0, 3) != 'new')) {
      $trInfo = new objectInfo($s2p);
    }

    if ( (is_object($trInfo)) && ($s2p['s2p_id'] == $trInfo->s2p_id) ) {
      echo '              <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id . '&action=edit') . '\'">' . "\n";
    } else {
      echo '              <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p['s2p_id']) . '\'">' . "\n";
    }
?>
                <td class="dataTableContent">&nbsp;<?php echo $s2p['shipment']; ?></td>
                <td class="dataTableContent"><?php echo $cPay->GetModuleName($s2p['payments_allowed']); ?></td>
                <td class="dataTableContent" align="center">
                <?php
                      if ($s2p['status'] == '1') {
                        echo tep_image('images/icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p['s2p_id'] . '&action=disable') . '">' . tep_image('images/icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
                      } else {
                        echo '<a href="' . tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p['s2p_id'] . '&action=enable') . '">' . tep_image('images/icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image('images/icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
                      }
                ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($trInfo)) && ($s2p['s2p_id'] == $trInfo->s2p_id) ) { echo tep_image('images/icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $s2p['s2p_id']) . '">' . tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $s2p_split->display_count($s2p_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PAYMENTS); ?></td>
                    <td class="smallText" align="right"><?php echo $s2p_split->display_links($s2p_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
<?php
  if (!$_GET['action']) {
?>
                  <tr>
                    <td colspan="5" align="right"><?php echo tep_draw_button(IMAGE_INSERT, 'plus', tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&action=new')); ?></td>
                  </tr>
<?php
  }
?>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();
  switch ($_GET['action']) {
    case 'new':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_SHP2PAY . '</b>');
      $contents = array('form' => tep_draw_form('s2p', 'ship2pay.php', 'page=' . $_GET['page'] . '&action=insert'));
      $contents[] = array('text' => TEXT_INFO_INSERT_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_INFO_SHIPMENT . '<br>' . $cShip->shipping_select('name="shp_id"'));
      $contents[] = array('text' => '<br>' . TEXT_INFO_PAYMENTS . '<br>' . $cPay->payment_multiselect('name="pay_ids[]"'));
      $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_SAVE, 'disk', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('ship2pay.php', 'page=' . $_GET['page'])));
      break;
    case 'edit':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_SHP2PAY . '</b>');
      $contents = array('form' => tep_draw_form('s2p', 'ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id  . '&action=save'));
      $contents[] = array('text' => TEXT_INFO_EDIT_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_INFO_SHIPMENT . '<br>' . $cShip->shipping_select('name="shp_id"',$trInfo->shipment));
      $contents[] = array('text' => '<br>' . TEXT_INFO_PAYMENTS . '<br>' . $cPay->payment_multiselect('name="pay_ids[]"', $trInfo->payments_allowed));
      $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_SAVE, 'disk', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id)));
      break;
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_SHP2PAY . '</b>');
      $contents = array('form' => tep_draw_form('s2p', 'ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id  . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $trInfo->shipment . ' >> ' . $cPay->GetModuleName($trInfo->payments_allowed) . '</b>');
      $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_DELETE, 'trash', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id)));
      break;
    default:
      if (is_object($trInfo)) {
        $heading[] = array('text' => '<b>' . $trInfo->shipment . '</b>');
        $contents[] = array('align' => 'center', 'text' => tep_draw_button(IMAGE_EDIT, 'document', tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id . '&action=edit')) . tep_draw_button(IMAGE_DELETE, 'trash', tep_href_link('ship2pay.php', 'page=' . $_GET['page'] . '&s2p_id=' . $trInfo->s2p_id . '&action=delete')));
        $contents[] = array('text' => '<br>' . TEXT_INFO_PAYMENTS_ALLOWED . '<br><b>' . $cPay->GetModuleName($trInfo->payments_allowed) .'</b>');
      }
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
    </table>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
