<?php
/*
  $Id: 

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  class cm_ip_manufacturer_image {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_IP_MANUFACTURER_IMAGE_TITLE;
      $this->description = MODULE_CONTENT_IP_MANUFACTURER_IMAGE_DESCRIPTION;
      $this->description .= '<div class="secWarning">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_CONTENT_IP_MANUFACTURER_IMAGE_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_IP_MANUFACTURER_IMAGE_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_IP_MANUFACTURER_IMAGE_STATUS == 'True');
      }      
    }

    function execute() {
      global $oscTemplate, $image, $focus, $languages_id, $catname;
      
      $content_width  = MODULE_CONTENT_IP_MANUFACTURER_IMAGE_CONTENT_WIDTH;
      $promo = MODULE_CONTENT_IP_MANUFACTURER_IMAGE_CONTENT_WIDTH_EACH;
			$pull = (MODULE_CONTENT_IP_MANUFACTURER_IMAGE_PULL_RIGHT == 'True' ? ' pull-right-sm' : '');
      
      if (isset($_GET['manufacturers_id']) && !empty($_GET['manufacturers_id'])) {
			
        $pic = '';
				if (isset($focus) && is_array($focus) && isset($focus['image'])) {
				  $pic = $focus['image'];
					$name = $focus['name'];
				} elseif (isset($image) && is_array($image) && isset($image['manufacturers_image'])) {
				  $pic = $image['manufacturers_image'];
					$name = $catname;
				}
				  
				if (strlen($pic)) {
				  ob_start();
          include('includes/modules/content/' . $this->group . '/templates/manufacturer_image.php');
          $template = ob_get_clean();

          $oscTemplate->addContent($template, $this->group);
				}
			}
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_IP_MANUFACTURER_IMAGE_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Manufacturer Image Module', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_STATUS', 'True', 'Should this module be enabled?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_CONTENT_WIDTH', '4', 'What width container should the content be shown in?', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Image Width', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_PULL_RIGHT', 'True', 'Do you want the image on the right if there\'s room?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_SORT_ORDER', '100', 'Sort order of display. Lowest is displayed first.', '6', '4', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
    
    function keys() {
      return array('MODULE_CONTENT_IP_MANUFACTURER_IMAGE_STATUS', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_CONTENT_WIDTH', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_PULL_RIGHT', 'MODULE_CONTENT_IP_MANUFACTURER_IMAGE_SORT_ORDER');
    }  
  }
  