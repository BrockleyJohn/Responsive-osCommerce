<?php
/*
  $Id: 

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  class cm_ip_manufacturer_promotion {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_IP_MANUFACTURER_PROMO_TITLE;
      $this->description = MODULE_CONTENT_IP_MANUFACTURER_PROMO_DESCRIPTION;
      $this->description .= '<div class="secWarning">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_CONTENT_IP_MANUFACTURER_PROMO_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_IP_MANUFACTURER_PROMO_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_IP_MANUFACTURER_PROMO_STATUS == 'True');
      }      
    }

    function execute() {
      global $oscTemplate, $category, $cPath_array, $cPath, $current_category_id, $languages_id;
      
      $content_width  = MODULE_CONTENT_IP_MANUFACTURER_PROMO_CONTENT_WIDTH;
      $promo_width = MODULE_CONTENT_IP_MANUFACTURER_PROMO_CONTENT_WIDTH_EACH;
      
			$promo_boxes = '';
			
      if ((isset($_GET['manufacturers_id']) && $_GET['manufacturers_id'] == '2')
			||  (isset($_GET['cPath']) && !empty($_GET['cPath']) && isset($_GET['filter_id']) && $_GET['filter_id'] == '2')) {
			  $promo_boxes .= '<div class="brand-promo well-sm col-sm-'.$promo_width.'">
				We are running promotions across several Microsoft ranges. See <a href="index.php?cPath=1_9&amp;filter_id=2">Microsoft mice</a> and keyboards.
				</div>'."\n";
			}
			
			if ((isset($_GET['cPath']) && $_GET['cPath'] == '1_9')
			|| (isset($_GET['manufacturers_id']) && !empty($_GET['manufacturers_id']) && isset($_GET['filter_id']) && $_GET['filter_id'] == '9')) {
			  $promo_boxes .= '<div class="category-promo well-sm col-sm-'.$promo_width.'">
				FREE - lifetime supply of cheese with every Microsoft mouse. See <a href="index.php?cPath=1_9&amp;filter_id=2">Microsoft mice</a>.
				</div>'."\n";
			}
		
		  if (strlen($promo_boxes)) {

        ob_start();
        include('includes/modules/content/' . $this->group . '/templates/manufacturer_promotion.php');
        $template = ob_get_clean();

        $oscTemplate->addContent($template, $this->group);
			}
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_IP_MANUFACTURER_PROMO_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Manufacturer Promotion Module', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_STATUS', 'True', 'Should this module be enabled?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_CONTENT_WIDTH', '12', 'What width container should the whole promotional area be shown in?', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Individual Width', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_CONTENT_WIDTH_EACH', '4', 'What width container should each promotion be shown in?', '6', '3', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_SORT_ORDER', '100', 'Sort order of display. Lowest is displayed first.', '6', '4', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
    
    function keys() {
      return array('MODULE_CONTENT_IP_MANUFACTURER_PROMO_STATUS', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_CONTENT_WIDTH', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_CONTENT_WIDTH_EACH', 'MODULE_CONTENT_IP_MANUFACTURER_PROMO_SORT_ORDER');
    }  
  }
  