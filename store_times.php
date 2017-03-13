<?php
/*
  Store Opening Times BS
	- set of modules for Responsive osCommerce
	- selected store pages divert to this page when store is closed (if diversion is enabled)
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

  require('includes/application_top.php');

  require('includes/languages/' . $language . '/store_times.php');

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link('store_times.php'));

  require('includes/template_top.php');
  require_once('includes/functions/store_times.php');

		if (!isset($store_status) || strlen($store_status) == 0) $store_status = sew_is_store_open();

			$status_msg = strftime('%H:%M '.DATE_FORMAT_LONG,$store_timecode). ' - ';
			$change_msg = '';
			
      switch ($store_status) {
			  case 'open':
			  case 'open-override':
			    $status_msg .= STATUS_OPEN;
					if ($change = sew_store_closes()) { // has returned a close time, will close automatically
				    $change_msg .= sprintf(TEXT_OPEN_UNTIL,$change);
				  } else {
				    $change_msg .= TEXT_OPEN;
					}
					break;
			  case 'closed':
			  case 'closed-override':
			    $status_msg .= STATUS_CLOSED;
			    if ($change = sew_store_opens()) { // has returned a opens time, will open automatically
				    $change_msg .= sprintf(TEXT_NOT_OPEN_YET,$change);
				  } else {
				    $change_msg .= TEXT_CLOSED_NOW;
					}
					break;
				case 'holiday':
				case 'weekly':
			    $status_msg .= STATUS_CLOSED;
				  $change_msg .= TEXT_CLOSED_TODAY;
					break;
				default:
				  $msg .= TEXT_ERROR;
			}

?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
  <h2><?php echo $status_msg; ?></h2>
  <p><?php echo $change_msg; ?></p>
</div>

<div class="contentContainer">
  <div class="contentText">
    <h3><?php echo SUBHEAD_NORMAL_TIMES; ?></h3>
    <table>
    <?php foreach (array('SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY') as $day) {
		  echo '<tr><td>'.constant('TEXT_'.$day).'</td><td>&nbsp;</td>'.(constant('MOD_CON_HDR_STORE_TIMES_'.$day.'_HOLIDAY') == 'True' ? '<td colspan="3">'.TEXT_CLOSED_DAY : '<td>'.constant('MOD_CON_HDR_STORE_TIMES_'.$day.'_OPENING').'</td><td>&nbsp;&rArr;&nbsp;</td><td>'.constant('MOD_CON_HDR_STORE_TIMES_'.$day.'_CLOSING')) ."</td></tr>\n";
		} ?>
    </table>
    <h3><?php echo SUBHEAD_HOLIDAYS; ?></h3>
    <p><?php echo sew_holiday_list(); ?></p>
  </div>

  <div class="buttonSet">
    <div class="text-right"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'fa fa-angle-right', tep_href_link('index.php')); ?></div>
  </div>
</div>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
