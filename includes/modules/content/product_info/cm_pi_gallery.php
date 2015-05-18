<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class cm_pi_gallery {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_pi_gallery() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_PRODUCT_INFO_GALLERY_TITLE;
      $this->description = MODULE_CONTENT_PRODUCT_INFO_GALLERY_DESCRIPTION;

      if ( defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_PRODUCT_INFO_GALLERY_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $product_info;
      
      $content_width  = (int)MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_WIDTH;
      $gallery_output = NULL;
      
      if (tep_not_null($product_info['products_image'])) {
        
        $gallery_output .= tep_image(DIR_WS_IMAGES . $product_info['products_image'], NULL, NULL, NULL, 'itemprop="image" style="display:none;"');

        $photoset_layout = '1';

        $pi_query = tep_db_query("select image, htmlcontent from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$product_info['products_id'] . "' order by sort_order");
        $pi_total = tep_db_num_rows($pi_query);

        if ($pi_total > 0) {
            $pi_sub = $pi_total-1;

            while ($pi_sub > 5) {
                $photoset_layout .= 5;
                $pi_sub = $pi_sub-5;
            }

            if ($pi_sub > 0) {
                $photoset_layout .= ($pi_total > 5) ? 5 : $pi_sub;
            }
            
            $gallery_output .= '<div id="piGal" data-imgcount="' . $photoset_layout . '">';
            
            $pi_counter = 0;
            $pi_html = array();

            while ($pi = tep_db_fetch_array($pi_query)) {
                $pi_counter++;

                if (tep_not_null($pi['htmlcontent'])) {
                    $pi_html[] = '<div id="piGalDiv_' . $pi_counter . '">' . $pi['htmlcontent'] . '</div>';
                }

                $gallery_output .= tep_image(DIR_WS_IMAGES . $pi['image'], '', '', '', 'id="piGalImg_' . $pi_counter . '"');
            }
            
            $gallery_output .= '</div>';
            
            if ( !empty($pi_html) ) {
               $gallery_output .= '    <div style="display: none;">' . implode('', $pi_html) . '</div>';
            }
            
        } else {
            
            $gallery_output .= '<div id="piGal">' .
                                    tep_image(DIR_WS_IMAGES . $product_info['products_image'], addslashes($product_info['products_name'])) .
                               '</div>';
        }
        
        ob_start();
        include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/gallery.php');
        $template = ob_get_clean();

        $oscTemplate->addContent($template, $this->group);
      
      }
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Product Image Gallery Module', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS', 'True', 'Should the product image gallery block be shown on the product info page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_WIDTH', '4', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Align-Float', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_ALIGN', 'pull-right', 'How should the content be aligned or float?', '6', '1', 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Vertical Margin', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_VERT_MARGIN', 'VerticalMargin', 'Top and Bottom Margin added to the module? none, VerticalMargin=10px', '6', '1', 'tep_cfg_select_option(array(\'\', \'VerticalMargin\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Horizontal Margin', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_HORIZ_MARGIN', '', 'Left and Right Margin added to the module? none, HorizontalMargin=10px', '6', '1', 'tep_cfg_select_option(array(\'\', \'HorizontalMargin\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_SORT_ORDER', '300', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_WIDTH', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_ALIGN', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_VERT_MARGIN', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CONTENT_HORIZ_MARGIN', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_SORT_ORDER');
    }
  }
