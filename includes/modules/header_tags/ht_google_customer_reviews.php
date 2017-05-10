<?php
/*
  Google Customer Reviews addon for osC 2.3.4BS
	Author: John Ferguson @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  class ht_google_customer_reviews {
    var $code;
    var $group = 'footer_scripts';
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
			$this->title = MODULE_HEADER_TAGS_GCR_TITLE;
      $this->description = MODULE_HEADER_TAGS_GCR_DESCRIPTION;

      if ( defined('MODULE_HEADER_TAGS_GCR_STATUS') ) {
        $this->sort_order = MODULE_HEADER_TAGS_GCR_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_GCR_STATUS == 'True');
      }
    }

    function execute() {
      global $PHP_SELF, $oscTemplate, $customer_id;
			$output = '';

      if (tep_not_null(MODULE_HEADER_TAGS_GCR_BADGE_PAGES)) {
        $pages_array = array();

        foreach (explode(';', MODULE_HEADER_TAGS_GCR_BADGE_PAGES) as $page) {
          $page = trim($page);

          if (!empty($page)) {
            $pages_array[] = $page;
          }
        }

        if (in_array(basename($PHP_SELF), $pages_array)) {
          // on this page we post the badge to tell people we use google reviews and to show seller rating
					if (MODULE_HEADER_TAGS_GCR_BADGE_POSITION <> 'Inline') {
					  $output .= '<!-- BEGIN GCR Badge Code -->
<script src="https://apis.google.com/js/platform.js?onload=renderBadge"
  async defer>
</script>

<script>
  window.renderBadge = function() {
    var ratingBadgeContainer = document.createElement("div");
      document.body.appendChild(ratingBadgeContainer);
      window.gapi.load(\'ratingbadge\', function() {
        window.gapi.ratingbadge.render(
          ratingBadgeContainer, {
            // REQUIRED
            "merchant_id": ' . tep_output_string(MODULE_HEADER_TAGS_GCR_MERCHANT_ID) . ',
            // OPTIONAL
            "position": "' . tep_output_string(MODULE_HEADER_TAGS_GCR_BADGE_POSITION) . '"
          });           
     });
  }
</script>
<!-- END GCR Badge Code -->
';
          } else {
					  $output .= '<!-- BEGIN GCR Badge Script -->
<script src="https://apis.google.com/js/platform.js?onload=renderBadge"
  async defer>
</script>
<!-- END GCR Badge Script -->
';
					}
				}
			}

      if ((basename($PHP_SELF) == 'checkout_success.php') && tep_session_is_registered('customer_id') ) {
        // on this page we post the Opt-in code to invite them to participate in google reviews

          $order_query = tep_db_query("select orders_id, customers_email_address, billing_country from " . TABLE_ORDERS . " where customers_id = '" . (int)$customer_id . "' order by date_purchased desc limit 1");

          if (tep_db_num_rows($order_query) == 1) {
            $order = tep_db_fetch_array($order_query);

            // order id and customer email
						$order_id = $order['orders_id'];
						$email = $order['customers_email_address'];

            // get customer country
						$country_code = '';
						$country_query = tep_db_query("SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " WHERE countries_name = '" . tep_db_input($order['billing_country']) . "'");
						if (tep_db_num_rows($order_query) == 1) {
							$country = tep_db_fetch_array($country_query);
							$country_code = $country['countries_iso_code_2'];
						} else {
						  // use the store country
						  $country_query = tep_db_query("SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " WHERE countries_id = '" . tep_db_input(STORE_COUNTRY) . "'");
						  if (tep_db_num_rows($order_query) == 1) {
							  $country = tep_db_fetch_array($country_query);
							  $country_code = $country['countries_iso_code_2'];
							}
						}
						
						// now the shipping arrival date
						$in_days = (int)MODULE_HEADER_TAGS_GCR_SHIPPING_DEFAULT;
						
						$delivery_date = date("Y-m-d", strtotime("+" . $in_days . " day"));
						
						// finally the style option
						switch(MODULE_HEADER_TAGS_GCR_OPTIN_STYLE) {
						  case 'Bottom Right' :
							  $style = 'BOTTOM_RIGHT_DIALOG';
								break;
						  case 'Bottom Left' :
							  $style = 'BOTTOM_LEFT_DIALOG';
								break;
						  case 'Top Right' :
							  $style = 'TOP_RIGHT_DIALOG';
								break;
						  case 'Top Left' :
							  $style = 'TOP_LEFT_DIALOG';
								break;
						  default : // 'Center'
							  $style = 'CENTER_DIALOG';
								break;
						}

        $output .= '<!-- BEGIN GCR Opt-in Module Code -->
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn"
  async defer>
</script>

<script>
  window.renderOptIn = function() { 
    window.gapi.load(\'surveyoptin\', function() {
      window.gapi.surveyoptin.render(
        {
          // REQUIRED
          "merchant_id": ' . tep_output_string(MODULE_HEADER_TAGS_GCR_MERCHANT_ID) . ',
          "order_id": "' . tep_output_string($order_id) . '",
          "email": "' . tep_output_string($email) . '",
          "delivery_country": "' . tep_output_string($country_code) . '",
          "estimated_delivery_date": "' . tep_output_string($delivery_date) . '",

          // OPTIONAL
          "opt_in_style": "' . tep_output_string($style) . '"
        }); 
     });
  }
</script>
<!-- END GCR Opt-in Module Code -->
';
// ignore the language script and let google use the browser language
$language_script = '<!-- BEGIN GCR Language Code -->
<script>
  window.___gcfg = {
    lang: "LANGUAGE"
  };
</script>
<!-- END GCR Language Code -->
';
        } // endif the order was found
      } // endif page is checkout_success

      if (strlen($output)) {
        $oscTemplate->addBlock($output, $this->group);
			}
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_HEADER_TAGS_GCR_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Google Customer Reviews', 'MODULE_HEADER_TAGS_GCR_STATUS', 'True', 'Do you want to add Google Customer Reviews to your shop?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Google Merchant ID', 'MODULE_HEADER_TAGS_GCR_MERCHANT_ID', '', 'Your Merchant Center ID. You can get this value from the Google Merchant Center.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shipping Default', 'MODULE_HEADER_TAGS_GCR_SHIPPING_DEFAULT', '', 'Default time for shipping in days (used if no match found for shipping method)', '6', '1', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Optin Dialog Position', 'MODULE_HEADER_TAGS_GCR_OPTIN_STYLE', 'Center', 'Choose where on the page the optin overlay appears', '6', '1', 'tep_cfg_select_option(array(\'Center\', \'Bottom Right\', \'Bottom Left\', \'Top Right\', \'Top Left\', \'Bottom Tray\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Times', 'MODULE_HEADER_TAGS_GCR_SHIPPING_TIMES', '', 'Mapping of shipping methods to delivery timescales', '6', '1', 'ht_gcr_show_unpacked', 'ht_gcr_edit_ship_times(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('GCR Badge Position', 'MODULE_HEADER_TAGS_GCR_BADGE_POSITION', 'Bottom Right', 'Choose where on the page the badge appears. NB Inline needs a corresponding box or content module', '6', '1', 'tep_cfg_select_option(array(\'Bottom Right\', \'Bottom Left\', \'Inline\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Badge Pages', 'MODULE_HEADER_TAGS_GCR_BADGE_PAGES', 'index.php', 'Show the GCR badge on these store pages', '6', '1', 'ht_gcr_show_unpacked', 'ht_gcr_edit_badge_pages(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_GCR_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_HEADER_TAGS_GCR_STATUS', 'MODULE_HEADER_TAGS_GCR_MERCHANT_ID', 'MODULE_HEADER_TAGS_GCR_SHIPPING_DEFAULT', 'MODULE_HEADER_TAGS_GCR_OPTIN_STYLE', 'MODULE_HEADER_TAGS_GCR_SHIPPING_TIMES', 'MODULE_HEADER_TAGS_GCR_BADGE_POSITION', 'MODULE_HEADER_TAGS_GCR_BADGE_PAGES', 'MODULE_HEADER_TAGS_GCR_SORT_ORDER');
    }
  }

  function ht_gcr_show_unpacked($text) {
    return nl2br(implode("\n", explode(';', $text)));
  }

  function ht_gcr_edit_ship_times($values, $key) {
		$output = 'not yet implemented';
		return $output;
	}
	
  function ht_gcr_edit_badge_pages($values, $key) {
    global $PHP_SELF;

    $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
    $files_array = array();
	  if ($dir = @dir(DIR_FS_CATALOG)) {
	    while ($file = $dir->read()) {
	      if (!is_dir(DIR_FS_CATALOG . $file)) {
	        if (substr($file, strrpos($file, '.')) == $file_extension) {
            $files_array[] = $file;
          }
        }
      }
      sort($files_array);
      $dir->close();
    }

    $values_array = explode(';', $values);

    $output = '';
    foreach ($files_array as $file) {
      $output .= tep_draw_checkbox_field('ht_grid_list_view_file[]', $file, in_array($file, $values_array)) . '&nbsp;' . tep_output_string($file) . '<br />';
    }

    if (!empty($output)) {
      $output = '<br />' . substr($output, 0, -6);
    }

    $output .= tep_draw_hidden_field('configuration[' . $key . ']', '', 'id="htrn_files"');

    $output .= '<script>
                function htrn_update_cfg_value() {
                  var htrn_selected_files = \'\';

                  if ($(\'input[name="ht_grid_list_view_file[]"]\').length > 0) {
                    $(\'input[name="ht_grid_list_view_file[]"]:checked\').each(function() {
                      htrn_selected_files += $(this).attr(\'value\') + \';\';
                    });

                    if (htrn_selected_files.length > 0) {
                      htrn_selected_files = htrn_selected_files.substring(0, htrn_selected_files.length - 1);
                    }
                  }

                  $(\'#htrn_files\').val(htrn_selected_files);
                }

                $(function() {
                  htrn_update_cfg_value();

                  if ($(\'input[name="ht_grid_list_view_file[]"]\').length > 0) {
                    $(\'input[name="ht_grid_list_view_file[]"]\').change(function() {
                      htrn_update_cfg_value();
                    });
                  }
                });
                </script>';

    return $output;
  }
  
	
?>
