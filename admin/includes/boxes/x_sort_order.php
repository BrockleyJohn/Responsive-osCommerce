<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_CATALOG ) {
      $group['apps'][] = array('code' => 'sort_order_manager.php',
                               'title' => BOX_CATALOG_SORT_ORDER,
                               'link' => tep_href_link('sort_order_manager.php'));

      break;
    }
  }
?>
