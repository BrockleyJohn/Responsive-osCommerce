<?php
/*
  author: @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_MODULES ) {
      $group['apps'][] = array(
							'code' => 'ship2pay.php',
							'title' => BOX_MODULES_SHIP2PAY,
							'link' => tep_href_link('ship2pay.php')
						  );  
      break;
    }
  }
