<?php
/*

	Manage sort orders on one screen
	- category within parent (standard)
	- product sort order within category (on products_to_categories)
	Author: BrockleyJohn john@sewebsites.net
	
	File version 2.2 for addon version 1.1
	- add paging and option
   
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License

  loosely derived from Products Sorter
  Erich Paeper - info@cooleshops.de 

*/

	include('includes/application_top.php');

// Edge compatibility
  if (!defined('DIR_WS_INCLUDES')) define('DIR_WS_INCLUDES','includes/');
  if (!defined('DIR_WS_IMAGES')) define('DIR_WS_IMAGES','images/');
	
	// check for database mods
	if (tep_db_num_rows(tep_db_query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". DB_DATABASE . "' AND TABLE_NAME='products_to_categories' AND COLUMN_NAME LIKE 'products_sort_order'")) != 1 ) {
		tep_db_query("alter table products_to_categories add column `products_sort_order` INT(11) NOT NULL DEFAULT '0' AFTER `categories_id`");
			$messageStack->add(MSG_DB_UPDATED, 'success');
	}
	// check for setting
	if (!defined('ADDON_SORT_ORDER_MANAGER_VIEW_PAGED')) {
  	tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sort Order Manager View Paged', 'ADDON_SORT_ORDER_MANAGER_VIEW_PAGED', 'False', 'Do you want to page the view of products in a category in Sort Order Manager?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		define('ADDON_SORT_ORDER_MANAGER_VIEW_PAGED','False');
	}

	if (isset($_GET['paged']) && ($_GET['paged'] == 'True' || $_GET['paged'] == 'False')) {
		tep_db_query("update configuration set configuration_value = '" . $_GET['paged'] . "' where configuration_key = 'ADDON_SORT_ORDER_MANAGER_VIEW_PAGED'");
		$paged = $_GET['paged'];
	} else {
		$paged = ADDON_SORT_ORDER_MANAGER_VIEW_PAGED;
	}
		
	if ($_POST['cat_sort_order_update']) {
		//set counter
		$sort_i = 0;
		foreach($_POST['cat_sort_order_update'] as $key => $value) {
			tep_db_query("UPDATE ".TABLE_CATEGORIES." SET sort_order = ".(int)$value." WHERE categories_id = ".(int)$key);
			$sort_i++;
		}
		$messageStack->add(sprintf(MSG_SORT_ORDER_CAT_UPDATED,$sort_i), 'success');
	}

	if ($_POST['prod_sort_order_update']) {
		//set counter
		$sort_i = 0;
		foreach($_POST['prod_sort_order_update'] as $key => $value) {
			tep_db_query("UPDATE ".TABLE_PRODUCTS_TO_CATEGORIES." SET products_sort_order = ".(int)$value." WHERE products_id = ".(int)$key." AND categories_id=".(int)$current_category_id);
			$sort_i++;
		}
		$messageStack->add(sprintf(MSG_SORT_ORDER_PROD_UPDATED,$sort_i), 'success');
	}

  require(DIR_WS_INCLUDES . 'template_top.php');
?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php 
							echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
          <tr>
            <td>
<?php
    echo tep_draw_form('goto', 'sort_order_manager.php', '', 'get');
    echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onchange="this.form.submit();"');
    echo tep_hide_session_id() . '</form>';
?>
            </td>
            <td align="right">
<?php
		echo tep_draw_form('sort_view', 'sort_order_manager.php', '', 'get');
		if (isset($cPath)) echo tep_draw_hidden_field('cPath',$cPath);
		echo TEXT_VIEW_PAGED . ' ' . tep_draw_radio_field('paged', 'True', false, $paged) . ' ' . tep_draw_radio_field('paged', 'False', false, $paged) . ' ' . TEXT_VIEW_ALL;
		echo tep_hide_session_id() . '</form>';
?>
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"> <?php echo tep_draw_form('sort', 'sort_order_manager.php', (isset($cPath) ? 'cPath=' . $cPath : ''), 'post'); ?>
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent"></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_MODEL; ?></td>
								<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_SORT_ORDER; ?></td>
              </tr>
<?php

//JAF v2.0 do categories first...
			$cat_query = tep_db_query("SELECT c.categories_id, cd.categories_name, c.categories_image, c.sort_order from categories c, categories_description cd WHERE c.parent_id = '" . (int)$current_category_id . "' AND c.categories_id = cd.categories_id AND cd.language_id = '".$languages_id ."' order by c.sort_order, cd.categories_name"); 			 
     while ($results = tep_db_fetch_array($cat_query)) {
						 echo '<tr class="dataTableRow"><td class="dataTableContent" align="center">' . $results['categories_id'] . '</td>';
             echo '<td class="dataTableContent" align="center">' . tep_image(DIR_WS_CATALOG . DIR_WS_IMAGES . $results['categories_image'], 'ID  ' . $results['categories_id'] . ': ' . $results['categories_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</td>';
						 echo '<td class="dataTableContent">' . $results['categories_name'] . '</td>';
             echo '<td class="dataTableContent">CAT</td>';
			 echo '<td class="dataTableContent" align="center">' . '<input type="text" size="3" name="cat_sort_order_update[' . $results['categories_id'] . ']" value="' . $results['sort_order'] . '">' . '</td></tr>';
    }

      // get all active prods in that specific category

    $product_query_raw = "SELECT p.products_id, p.products_model, p. products_quantity, p.products_status, p2c.products_sort_order, p.products_image, pd.products_name from products p, products_to_categories p2c, products_description pd where p.products_id = p2c.products_id and p.products_id = pd.products_id and language_id = $languages_id and p2c.categories_id = '" . (int)$current_category_id . "' order by p2c.products_sort_order, pd.products_name";
    if ($paged == 'True') $product_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $product_query_raw, $product_query_numrows);
    $product_query = tep_db_query($product_query_raw);

     while ($results = tep_db_fetch_array($product_query)) {
						 echo '<tr class="dataTableRow"><td class="dataTableContent" align="center">' . $results['products_id'] . '</td>';
             echo '<td class="dataTableContent" align="center">' . tep_image(DIR_WS_CATALOG . DIR_WS_IMAGES . $results['products_image'], 'ID  ' . $results['products_id'] . ': ' . $results['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</td>';
						 echo '<td class="dataTableContent">' . $results['products_name'] . '</td>';
             echo '<td class="dataTableContent">' . $results['products_model'] . '</td>';
			 echo '<td class="dataTableContent" align="center">' . '<input type="text" size="3" name="prod_sort_order_update[' . $results['products_id'] . ']" value="' . $results['products_sort_order'] . '">' . '</td></tr>';
    }
  echo '<tr class="dataTableRow">';
  echo '<td class="smalltext" align="right" colspan="10"><br><br><br><br>';
  echo tep_draw_button(IMAGE_UPDATE, 'disk', null, 'primary') . '</td></tr>';

	if ($paged == 'True') {
?>
      <tr>
        <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="smallText" valign="top"><?php echo $product_split->display_count($product_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
            <td class="smallText" align="right"><?php echo $product_split->display_links($product_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page'))); ?></td>
          </tr>
        </table></td>
      </tr><?php } ?>
    </table>
  </form></td>
</tr></table></td>
	</tr>
</table>
<script type='text/javascript'>
 $(document).ready(function() { 
   $('input[name=paged]').change(function(){
        $('form[name=sort_view]').submit();
   });
  });
</script>
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>