<?php
/*
  Store Opening Times BS
	- set of modules for Responsive osCommerce
	- this header tags module diverts pages depending on settings in content module
	- it also contains all the main settings for the suite (e.g. the opening times)
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

  class ht_store_times {
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));
      $this->title = MODULE_HEADER_TAGS_STORE_TIMES_TITLE;
      $this->description = MODULE_HEADER_TAGS_STORE_TIMES_DESCRIPTION;

      if ( defined('MODULE_HEADER_TAGS_STORE_TIMES_STATUS') ) {
        $this->sort_order = MODULE_HEADER_TAGS_STORE_TIMES_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_STORE_TIMES_STATUS == 'True');
      }
    }

    function execute() {
      global $PHP_SELF, $oscTemplate, $store_status;

      if (defined('MOD_CON_HDR_STORE_TIMES_DIVERT_PAGES') && MOD_CON_HDR_STORE_TIMES_DIVERT_PAGES == 'True') {
        if (tep_not_null(MOD_CON_HDR_STORE_TIMES_CLOSE_PAGES)) {
          $pages_array = array();

          foreach (explode(';', MOD_CON_HDR_STORE_TIMES_CLOSE_PAGES) as $page) {
            $page = trim($page);

            if (!empty($page)) {
              $pages_array[] = $page;
            }
          }
        
          if (in_array(basename($PHP_SELF), $pages_array)) {
		        $store_status = sew_is_store_open();
            if ($store_status <> 'open') tep_redirect('store_times.php');
          }
        }
      }
		}

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_HEADER_TAGS_STORE_TIMES_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Store Times HT Module', 'MODULE_HEADER_TAGS_STORE_TIMES_STATUS', 'True', 'Do you want to enable the Store Times divert module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_STORE_TIMES_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_HEADER_TAGS_STORE_TIMES_STATUS', 'MODULE_HEADER_TAGS_STORE_TIMES_SORT_ORDER');
    }
  }

// helper functions
include_once(DIR_FS_CATALOG.'/includes/functions/store_times.php');