<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class cm_google_reviews_badge {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_GCR_BADGE_TITLE;
      $this->description = sprintf(MODULE_CONTENT_GCR_BADGE_DESCRIPTION,$this->group);
      $this->description .= '<div class="secWarning">' . MODULE_CONTENT_GCR_BADGE_CAVEAT . '</div>';
      $this->description .= '<div class="secWarning">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_CONTENT_GCR_BADGE_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_GCR_BADGE_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_GCR_BADGE_STATUS == 'True');
      }
    }

    function execute() {
      global $PHP_SELF, $oscTemplate;
      
      if (defined('MODULE_HEADER_TAGS_GCR_STATUS') && MODULE_HEADER_TAGS_GCR_STATUS == 'True' && MODULE_HEADER_TAGS_GCR_BADGE_POSITION == 'Inline' && tep_not_null(MODULE_HEADER_TAGS_GCR_BADGE_PAGES)) {
        $pages_array = array();

        foreach (explode(';', MODULE_HEADER_TAGS_GCR_BADGE_PAGES) as $page) {
          $page = trim($page);

          if (!empty($page)) {
            $pages_array[] = $page;
          }
        }

        if (in_array(basename($PHP_SELF), $pages_array)) {
					$content_width = (int)MODULE_CONTENT_GCR_BADGE_CONTENT_WIDTH;
					$merchant_id = MODULE_HEADER_TAGS_GCR_MERCHANT_ID;
					
					ob_start();
					include('includes/modules/content/' . $this->group . '/templates/google_reviews_badge.php');
					$template = ob_get_clean();
		
					$oscTemplate->addContent($template, $this->group);
				}
			}
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_GCR_BADGE_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Header Logo Module', 'MODULE_CONTENT_GCR_BADGE_STATUS', 'True', 'Do you want to enable the Logo content module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_GCR_BADGE_CONTENT_WIDTH', '1', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_GCR_BADGE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_GCR_BADGE_STATUS', 'MODULE_CONTENT_GCR_BADGE_CONTENT_WIDTH', 'MODULE_CONTENT_GCR_BADGE_SORT_ORDER');
    }
  }

