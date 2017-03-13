<?php
/*
  Store Opening Times BS
	- set of modules for Responsive osCommerce
	- this dashboard module allows admin to open / close the store early for the day
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

define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_TITLE', 'Store Times Admin Override');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_DESCRIPTION', 'Show the current status and allow override');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_OPEN', 'Open until %s ');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSED', 'Closed until %s ');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSED_NOW', 'Closed for the rest of today ');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSED_TODAY', 'Closed all day ');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_OPEN_NOW', 'Open Now');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSE_NOW', 'Close Now');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_CLOSE_OVERRIDDEN', 'OPEN - but closing time passed. MANUAL CLOSE REQUIRED! ');
define('MODULE_ADMIN_DASHBOARD_STORE_TIMES_ERROR', 'An error has occurred');
