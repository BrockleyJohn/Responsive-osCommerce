<?php
/*
  author: @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_CATALOG ) {
      $group['apps'][] = array(
								'code' => 'products_multi.php',
								'title' => BOX_CATALOG_CATEGORIES_PRODUCTS_MULTI,
								'link' => tep_href_link('products_multi.php')
						  );  
      break;
    }
  }
