<?php
/*
  $Id: products_multi.php, v 2.6

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
  $cat_stat=0; // internal use -- 0 = no / 1 = yes

	if (!function_exists('tep_array_merge')) {
		function tep_array_merge($array1, $array2, $array3 = '') {
			if ($array3 == '') $array3 = array();
			if (function_exists('array_merge')) {
				$array_merged = array_merge($array1, $array2, $array3);
			} else {
				while (list($key, $val) = each($array1)) $array_merged[$key] = $val;
				while (list($key, $val) = each($array2)) $array_merged[$key] = $val;
				if (sizeof($array3) > 0) while (list($key, $val) = each($array3)) $array_merged[$key] = $val;
			}
	
			return (array) $array_merged;
		}
	}

	if (!function_exists('tep_get_parent_cat')) {
		function tep_get_parent_cat($categ_id = '') {
			global $current_category_id;
				
			$parent_category_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where categories_id = '" . (int)$categ_id . "'");
			$parent_category = tep_db_fetch_array($parent_category_query);
		 
			$categ = $parent_category['parent_id'];
			
			return $categ;
		}
	}

  $action=(isset($_REQUEST['action']) ? $_REQUEST['action'] : '');
  if (tep_not_null($action)) {
    $choose = (isset($_REQUEST['choose']) ? $_REQUEST['choose'] : '');
    switch ($action) {
      case 'delete_product_confirm':
              $del_art=(isset($_REQUEST['del_art']) ? $_REQUEST['del_art'] : '');
              foreach ($choose as $products_id) {
                  if ($del_art=='complete')    tep_remove_product($products_id);
                  elseif ($del_art=='this_cat') {
                      $duplicate_check = tep_db_fetch_array(tep_db_query("select count(*) as total from ".TABLE_PRODUCTS_TO_CATEGORIES. " where products_id='".tep_db_input($products_id). "' and categories_id<>'". tep_db_input($current_category_id)."'"));
                      //If product exists only in this category we remove him totally
                      //If we have this product in other categories we just remove from TABLE_PRODUCTS_TO_CATEGORIES.
                      if ($duplicate_check['total']>0)     tep_db_query("delete from ".TABLE_PRODUCTS_TO_CATEGORIES.
                             " where products_id='". $products_id."' and categories_id='".$current_category_id."'");
                      else    tep_remove_product($products_id);
                  }
                  if (USE_CACHE=='true') {tep_reset_cache_block('categories'); tep_reset_cache_block('also_purchased');}
              }
              tep_redirect(tep_href_link('products_multi.php', 'cPath=' . $cPath));
              break;
      case 'move_product_confirm':
              foreach ($choose as $products_id) {
                  $new_parent_id=tep_db_prepare_input($_REQUEST['categories_id']);
                  $duplicate_check_query=tep_db_query("select count(*) as total from ".TABLE_PRODUCTS_TO_CATEGORIES.
                             " where products_id='".tep_db_input($products_id)."' and categories_id='". tep_db_input($new_parent_id)."'");
                  $duplicate_check = tep_db_fetch_array($duplicate_check_query);
                  if($duplicate_check['total']<1)      tep_db_query("update ".TABLE_PRODUCTS_TO_CATEGORIES.
                             " set categories_id ='".tep_db_input($new_parent_id).
                             "' where products_id='".tep_db_input($products_id)."' and categories_id='". $current_category_id."'");
                  if (USE_CACHE == 'true') {tep_reset_cache_block('categories'); tep_reset_cache_block('also_purchased');}
              }
              tep_redirect(tep_href_link('products_multi.php', 'cPath='.$new_parent_id.'&pID='.$products_id));
              break;
      case 'link_to_confirm':
              foreach ($choose as $products_id) {
                  $categories_id = tep_db_prepare_input($_REQUEST['categories_id']);
                  if ($_REQUEST['categories_id'] != $current_category_id) {
                      $check=tep_db_fetch_array(tep_db_query("select count(*) as total from ".
                                     TABLE_PRODUCTS_TO_CATEGORIES." where products_id='".tep_db_input($products_id) .
                                      "' and categories_id='". tep_db_input($categories_id) . "'"));
                      if ($check['total']<'1')    tep_db_query("insert into ".TABLE_PRODUCTS_TO_CATEGORIES.
                                    " (products_id, categories_id) values ('".tep_db_input($products_id)."', '".tep_db_input($categories_id)."')");
                  } else    $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
                   if (USE_CACHE == 'true') {tep_reset_cache_block('categories'); tep_reset_cache_block('also_purchased');}
              }
              tep_redirect(tep_href_link('products_multi.php', 'cPath=' . $categories_id . '&pID=' . $products_id));
              break;
      case 'copy_to_confirm':
              foreach ($choose as $products_id) {
                  $product_query = tep_db_fetch_array(tep_db_query('select * from '.TABLE_PRODUCTS.' where products_id="'.(int)$products_id.'"'));
                  $product_query['products_id'] = '';
                  tep_db_perform(TABLE_PRODUCTS, $product_query);
                  $categories_id = tep_db_prepare_input($_REQUEST['categories_id']);
                  $dup_products_id = tep_db_insert_id();

//Attributes Copy, Contributed by Joshua Guerra, josh@xvracing.com
                  $num_row_query=tep_db_query("select products_attributes_id from ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id=".$products_id);
                  $num_rows_attrib = array();
                  while ($categorie = tep_db_fetch_array($num_row_query))     $num_rows_attrib[]=$categorie['products_attributes_id'];
                  sort($num_rows_attrib);
                  //for ($j=0; $j < sizeof($num_rows_attrib); $j++) {
                  foreach($num_rows_attrib as $attrib_id) {
                      $attrib = tep_db_fetch_array(tep_db_query("select products_attributes_id, options_id, options_values_id, options_values_price, price_prefix from ".TABLE_PRODUCTS_ATTRIBUTES." where products_attributes_id=".$attrib_id));

                      tep_db_query("insert into ".TABLE_PRODUCTS_ATTRIBUTES." (products_id, options_id, options_values_id, options_values_price, price_prefix) values ('".$dup_products_id."','".$attrib['options_id']."','". $attrib['options_values_id']."','".tep_db_input($attrib['options_values_price']).
                      "','".tep_db_input($array['price_prefix'])."')");
                  }
// EOF: Attributes Copy

                  $description_query=tep_db_query("select language_id, products_name, products_description, products_url from ".TABLE_PRODUCTS_DESCRIPTION." where products_id='".$products_id."'");
                  while ($description = tep_db_fetch_array($description_query)) {
                      tep_db_query("insert into ".TABLE_PRODUCTS_DESCRIPTION." (products_id, language_id, products_name, products_description, products_url, products_viewed) values ('".$dup_products_id."', '".$description['language_id'] . "', '" . tep_db_input($description['products_name']) . "', '" . tep_db_input($description['products_description']) . "', '" . tep_db_input($description['products_url']) . "', '0')");
                  }
				  
// Products Images, Contributed by f.figue - Fernando Figueroa - fernando.figue@terra.com
            $product_images_query = tep_db_query("select image, htmlcontent, sort_order from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$products_id . "'");
            while ($product_images = tep_db_fetch_array($product_images_query)) {
              tep_db_query("insert into " . TABLE_PRODUCTS_IMAGES . " (products_id, image, htmlcontent, sort_order) values ('" . (int)$dup_products_id . "', '" . tep_db_input($product_images['image']) . "', '" . tep_db_input($product_images['htmlcontent']) . "', '" . tep_db_input($product_images['sort_order']) . "')");
            }
// EOF Products Images

                 tep_db_query("insert into ".TABLE_PRODUCTS_TO_CATEGORIES." (products_id, categories_id) values ('". $dup_products_id."', '".$categories_id."')" );
                   if (USE_CACHE == 'true') {tep_reset_cache_block('categories'); tep_reset_cache_block('also_purchased');}
              }
              tep_redirect(tep_href_link('products_multi.php', 'cPath='.$categories_id.'&pID=' .$dup_products_id));
              break;
    }
  }

// check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG_IMAGES)) {
    if (!is_writeable(DIR_FS_CATALOG_IMAGES))    $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else      $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');

require(DIR_WS_INCLUDES . 'template_top.php');
?>


<script language="JavaScript" type="text/javascript">
<!--
/**
* Checks/unchecks all tables
*
* @param   string   the form name
* @param   boolean  whether to check or to uncheck the element
*
* @return  boolean  always true

*/
function setCheckboxes(the_form, do_check) {
    var elts=(typeof(document.forms[the_form].elements['choose[]']) != 'undefined')
                  ? document.forms[the_form].elements['choose[]']
                  : (typeof(document.forms[the_form].elements['choose[]']) != 'undefined')
          ? document.forms[the_form].elements['choose[]']
          : document.forms[the_form].elements['choose[]'];
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;

    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
            elts[i].checked = do_check;
        } // end for
    } else {
        elts.checked        = do_check;
    } // end if... else

    return true;
} // end of the 'setCheckboxes()' function

//-->
</script>

<table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading">
            <?php
            echo HEADING_TITLE;
            //echo $action.count($choose).$categories_id.$current_category_id; // nur zu testzwecken - only for tests
            ?>
            </td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('spacer/pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr><?php echo tep_draw_form('search', 'products_multi.php', tep_get_all_get_params(), 'post'); ?>
                <td class="smallText" align="right"><?php echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search', $_REQUEST['search']); ?></td>
              </form></tr>
              <tr><?php echo tep_draw_form('goto', 'products_multi.php', '', 'get'); ?>
                <td class="smallText" align="right"><?php echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"'); ?></td>
              </form></tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php echo tep_draw_form('mainForm', 'products_multi.php', 'cPath='.$cPath.'&cID='. $cID, 'post'); ?>
<!--
<form method="post" action="products_multi.php?cPath=<?php echo $cPath; ?>&cID=<?php echo $cID; ?>" name="mainForm">
-->
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_SELECT; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_MANUFACTURERS_NAME; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS_QUANTITY; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?> </td>
              </tr>
<?php
    $categories_count = 0;
    $rows = 0;
    if ($_REQUEST['search']) {
      if($cat_stat==1) {
          $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.categories_status from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . $languages_id . "' and cd.categories_name like '%" . $_REQUEST['search'] . "%' order by c.sort_order, cd.categories_name");
      } else {
          $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . $languages_id . "' and cd.categories_name like '%" . $_REQUEST['search'] . "%' order by c.sort_order, cd.categories_name");
      }
    } else {
      if($cat_stat==1) {
          $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.categories_status from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . $current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . $languages_id . "' order by c.sort_order, cd.categories_name");
      } else {
          $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . $current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . $languages_id . "' order by c.sort_order, cd.categories_name");
      }
    }
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_count++;
      $rows++;

// Get parent_id for subcategories if search
      if ($_REQUEST['search']) $cPath= $categories['parent_id'];

      if ( ((!$_REQUEST['cID']) && (!$_REQUEST['pID']) || (@$_REQUEST['cID'] == $categories['categories_id'])) && (!$cInfo) && (substr($_REQUEST['action'], 0, 4) != 'new_') ) {
        $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
        $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

        $cInfo_array = tep_array_merge($categories, $category_childs, $category_products);
        $cInfo = new objectInfo($cInfo_array);
      }

      if ( (is_object($cInfo)) && ($categories['categories_id'] == $cInfo->categories_id) ) {
        echo '              <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link('products_multi.php',  tep_get_path($categories['categories_id'])) . '\'">' . "\n";
      } else {
        echo '              <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link('products_multi.php', 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"></td>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('products_multi.php', tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a> <b>' . $categories['categories_name'] . '</b>'; ?></td>
                <td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="center">
<?php
      if($cat_stat==1) {
          if ($categories['categories_status'] == '1') {
            echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '  ' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10);
          } else {
            echo tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '  ' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
          }
      }
?>
                 </td>
<td class="dataTableContent" align="left"></td>
<td class="dataTableContent" align="center"></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($cInfo)) && ($categories['categories_id'] == $cInfo->categories_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('products_multi.php', 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }

    $products_count = 0;
    if ($_REQUEST['search']) {
$products_query = tep_db_query("select p.products_tax_class_id, p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_model, p2c.categories_id, m.manufacturers_name from " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_MANUFACTURERS . " m ON p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = p2c.products_id and pd.products_name like '%" . $_REQUEST['search'] . "%' order by pd.products_name");
    } else {
$products_query = tep_db_query("select p.products_tax_class_id, p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_model, m.manufacturers_name from " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_MANUFACTURERS . " m ON p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . $current_category_id . "' order by pd.products_name");
    }
    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;

// Get categories_id for product if search
      if ($_REQUEST['search']) $cPath=$products['categories_id'];

      if ( ((!$_REQUEST['pID']) && (!$_REQUEST['cID']) || (@$_REQUEST['pID'] == $products['products_id'])) && (!$pInfo) && (!$cInfo) && (substr($_REQUEST['action'], 0, 4) != 'new_') ) {
// find out the rating average from customer reviews
        $reviews_query = tep_db_query("select (avg(reviews_rating) / 5 * 100) as average_rating from " . TABLE_REVIEWS . " where products_id = '" . $products['products_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);
        $pInfo_array = tep_array_merge($products, $reviews);
        $pInfo = new objectInfo($pInfo_array);
      }

      if ( (is_object($pInfo)) && ($products['products_id'] == $pInfo->products_id) ) {
        echo '              <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'">' . "\n";
      } else {
        echo '              <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'">' . "\n";
      }
?>
                <td class="dataTableContent"><input type="checkbox" name="choose[]" value="<?php echo $products['products_id']; ?>" id="checkbox_choose_<?php echo $products_count; ?>" <?php if($checkall==1) { echo 'checked'; } ?>></td>
                <td class="dataTableContent"><?php echo $products['products_name']; ?></td>
                <td class="dataTableContent" align="left"><?php echo $products['products_model']; ?>
				<td class="dataTableContent" align="center">
                <?php 
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '  ' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10);
      } else {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '  ' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?>
                </td>
<td class="dataTableContent" align="center"><?php echo $products['manufacturers_name']; ?></td>
<td class="dataTableContent" align="center"><?php echo $products['products_quantity']; ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($pInfo)) && ($products['products_id'] == $pInfo->products_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('products_multi.php', 'cPath=' . $cPath . '&pID=' . $products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    } // zu: while ($products = tep_db_fetch_array($products_query)) {

    if ($cPath_array) {
      $cPath_back = '';
      for($i=0;$i<sizeof($cPath_array)-1;$i++) {
        if ($cPath_back == '') {
          $cPath_back .= $cPath_array[$i];
        } else {
          $cPath_back .= '_' . $cPath_array[$i];
        }
      }
    }

 //   $cPath_back = ($cPath_back) ? 'cPath=' . $cPath_back . '&' : '';
	$categ_padre_id = tep_get_parent_cat($current_category_id);
    $cPath_back = (($cPath_back) ? 'cPath=' . $cPath_back . '&' : ($categ_padre_id) ? 'cPath=' . $categ_padre_id . '&' : '');
	
?>
              <tr>
                <td colspan="7"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                      <td style="line-height:10px;" valign="top">
                        <a href="<?php echo tep_href_link('products_multi.php', '&cPath='. $cPath.'&cID='.$cID.'&checkall=1');?>" onClick="setCheckboxes('mainForm', true); return false;">[<?php echo TEXT_CHOOSE_ALL; ?>]</a>&nbsp;&nbsp;<a href="<?php echo tep_href_link('products_multi.php', '&cPath='. $cPath.'&cID='.$cID);?>" onClick="setCheckboxes('mainForm', false); return false;" >[<?php echo TEXT_CHOOSE_ALL_REMOVE; ?>]</a></td>
                    <td align="right" valign="top"class="smallText" height="40">
			  <?php  if ($cPath) echo tep_draw_button(IMAGE_BACK, 'triangle-1-w', tep_href_link('products_multi.php',  $cPath_back . 'cID=' . $current_category_id)); ?>					
					</td>
                  </tr>
                  <tr><input type="hidden" name="cPath" value="<?php echo $cPath; ?>"><input type="hidden" name="cID" value="<?php echo $cID; ?>"></td>
                  </tr>
                  <tr>
                    <td class="smallText"><?php echo TEXT_CATEGORIES . ' ' . $categories_count . '<br>' . TEXT_PRODUCTS . ' ' . $products_count . tep_get_parent_cat($current_category_id); ?></td>
                    <td align="right" class="smallText" colspan="2">
                    <?php echo TEXT_MOVE_TO; ?><input type="radio" name="action" title="<?php echo TITLE_MOVE_TO; ?>" value="move_product_confirm" id="move_product_confirm">
                    <?php echo TEXT_COPY_TO; ?><input type="radio" name="action" title="<?php echo TITLE_COPY_TO; ?>" value="copy_to_confirm" id="copy_to_confirm" align="bottom">
                    <?php echo TEXT_LINK_TO; ?><input type="radio" name="action" title="<?php echo TITLE_LINK_TO; ?>" value="link_to_confirm" id="link_to_confirm" checked>
                    <?php echo tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id); ?></td>
                  </tr>
                  <tr>
                    <td align="right" class="smallText" colspan="4">
                    <?php echo TEXT_DELETE_PRODUCT; ?><input type="radio" name="action"  title="<?php echo TITLE_DELETE_PRODUCT; ?>" value="delete_product_confirm" id="delete_product_confirm">
                    <select name="del_art" title="<?php echo DEL_CHOOSE_DELETE_ART; ?>" id="del_art"><option value="this_cat" selected><?php echo DEL_THIS_CAT; ?></option><option value="complete"><?php echo DEL_COMPLETE; ?></option></select>
                    </td>
                  </tr>
                  <tr>
                    <td align="right" class="smallText" colspan="3">
					<?php echo tep_draw_button(IMAGE_UPDATE, 'disk', null, 'primary'); ?>
					</td>
                  </tr>
                  <tr>
                    <td class="dataTableContent" colspan="3">
                    <?php echo TEXT_ATTENTION_DANGER; ?>
                    </td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
    $heading = array();
    $contents = array();
    switch ($_REQUEST['action']) {
      default:
        if ($rows > 0) {
          if (is_object($cInfo)) { // category info box contents
            $heading[] = array('text' => '<b>' . $cInfo->categories_name . '</b>');

            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added));
            if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified));
            $contents[] = array('text' => '<br>' . tep_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT) . '<br>' . $cInfo->categories_image);
            $contents[] = array('text' => '<br>' . TEXT_SUBCATEGORIES . ' ' . $cInfo->childs_count . '<br>' . TEXT_PRODUCTS . ' ' . $cInfo->products_count);
          } elseif (is_object($pInfo)) { // product info box contents
            $heading[] = array('text' => '<b>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</b>');

            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($pInfo->products_date_added));
            if (tep_not_null($pInfo->products_last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($pInfo->products_last_modified));
            if (date('Y-m-d') < $pInfo->products_date_available) $contents[] = array('text' => TEXT_DATE_AVAILABLE . ' ' . tep_date_short($pInfo->products_date_available));
            $contents[] = array('text' => '<br>' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<br>' . $pInfo->products_image);
            $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_PRICE_INFO . ' ' . $currencies->format($pInfo->products_price) . '<br>' . TEXT_PRODUCTS_QUANTITY_INFO . ' ' . $pInfo->products_quantity);
            $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_AVERAGE_RATING . ' ' . number_format($pInfo->average_rating, 2) . '%');
          }
        } else { // create category/product info
          $heading[] = array('text' => '<b>' . EMPTY_CATEGORY . '</b>');

          $contents[] = array('text' => sprintf(TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS, $parent_categories_name));
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
<?php
  //}
?>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</form>
</table>
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>