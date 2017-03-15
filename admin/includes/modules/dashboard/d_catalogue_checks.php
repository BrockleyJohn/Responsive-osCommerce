<?php
/*
  Catalogue Checks Admin Dashboard Module
	- flag hierarchy issues in catalogue:
	- highlight and link to any categories that contain both products and categories

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

  class d_catalogue_checks {
    var $code;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->title = MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_TITLE;
      $this->description = MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_DESCRIPTION;

      if ( defined('MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_STATUS == 'True');
      }
    }

    function getOutput() {

      $output = ''; $result = '';

      $cat_query = tep_db_query('select cp.categories_id, cp.parent_id, pcd.categories_name from categories cp, categories cc, products_to_categories p2c, categories_description pcd, categories_description ccd where cc.parent_id = cp.categories_id and p2c.categories_id = cp.categories_id and pcd.categories_id = cp.categories_id and ccd.categories_id = cc.categories_id group by categories_id');
			
			while ($category = tep_db_fetch_array($cat_query)) {
				$result .= '<a href="'.tep_href_link('categories.php','cPath='.($category['parent_id'] > 0 ? $category['parent_id'] . '_' : '').$category['categories_id']).'">'.$category['categories_name'].'</a><br>'."\n";
			}
			
      if (strlen($result)) {
        $output .= '<div class="secError">';
        $output .= '<p class="smallText">' . MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_FAIL . '</p>';
        $output .= '<p class="smallText">' . $result . '</p>';
        $output .= '</div>';
      } else {
        $output .= '<div class="secSuccess"><p class="smallText">' . MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_SUCCESS . '</p></div>';
			}

      return $output;
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Security Checks Module', 'MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_STATUS', 'True', 'Do you want to run the security checks for this installation?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_STATUS', 'MODULE_ADMIN_DASHBOARD_CATALOGUE_CHECKS_SORT_ORDER');
    }
  }