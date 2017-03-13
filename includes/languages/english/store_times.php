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

define('NAVBAR_TITLE', 'Store Times');
define('HEADING_TITLE', 'Store Times');
define('STATUS_CLOSED', 'The store is currently closed.');
define('STATUS_OPEN', 'The store is open.');
define('SUBHEAD_NORMAL_TIMES', 'Regular Opening Times');
define('SUBHEAD_HOLIDAYS', 'Store Holidays');

define('TEXT_OPEN', 'We are happy to take your order now.');
define('TEXT_OPEN_UNTIL', 'We are happy to take your order now. The store closes at %s.');
define('TEXT_NOT_OPEN_YET', 'The store is due to open at %s.');
define('TEXT_CLOSED_NOW', 'The store is now closed for the day.');
define('TEXT_CLOSED_TODAY', 'The store is closed all day today.');
define('TEXT_HOLIDAYS_TEXT', 'Closed all day on these dates:');
define('TEXT_CLOSED_DAY', 'Closed all day');
define('TEXT_SUNDAY', 'Sunday');
define('TEXT_MONDAY', 'Monday');
define('TEXT_TUESDAY', 'Tuesday');
define('TEXT_WEDNESDAY', 'Wednesday');
define('TEXT_THURSDAY', 'Thursday');
define('TEXT_FRIDAY', 'Friday');
define('TEXT_SATURDAY', 'Saturday');
define('TEXT_ERROR', 'Sorry - an error has occurred.');
?>