<?php 
/*
 
	Manage sort orders on one screen
	- category within parent (standard)
	- product sort order within category (on products_to_categories)
	Author: BrockleyJohn john@sewebsites.net
   
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License

  loosely derived from Products Sorter
  Erich Paeper - info@cooleshops.de 

*/

define('MSG_DB_UPDATED', 'Database updates applied');
define('MSG_SORT_ORDER_CAT_UPDATED', 'Updated sort order of %s categories.');
define('MSG_SORT_ORDER_PROD_UPDATED', 'Updated sort order of %s products.');
define('MSG_SORT_ORDER_UPDATE_ERROR', 'Found unknown type %s after updating sort order of %s items.');
define('HEADING_TITLE', 'Sort Order Manager');
define('HEADING_TITLE_GOTO', 'Go To:');
define('TABLE_HEADING_CATEGORIES_PRODUCTS', 'Categories / Products ID');
define('TABLE_HEADING_MODEL', 'Model');
define('TABLE_HEADING_SORT_ORDER', 'Sort Order');
define('TEXT_VIEW_PAGED','View paged');
define('TEXT_VIEW_ALL','View all');
?>