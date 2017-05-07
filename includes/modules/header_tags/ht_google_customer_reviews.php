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
			$this->title = MODULE_HEADER_TAGS_GOOGLE_REVIEWS_TITLE;
      $this->description = MODULE_HEADER_TAGS_GOOGLE_REVIEWS_DESCRIPTION;

      if ( defined('MODULE_HEADER_TAGS_GOOGLE_REVIEWS_STATUS') ) {
        $this->sort_order = MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_GOOGLE_REVIEWS_STATUS == 'True');
      }
    }

    function execute() {
      global $PHP_SELF, $oscTemplate, $customer_id;
			$output = '';

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
						$in_days = (int)MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SHIPPING_DEFAULT;
						
						$delivery_date = date("Y-m-d", strtotime("+" . $in_days . " day"));
						
						// finally the style option
						switch(MODULE_HEADER_TAGS_GOOGLE_REVIEWS_OPTIN_STYLE) {
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
          "merchant_id": ' . tep_output_string(MODULE_HEADER_TAGS_GOOGLE_REVIEWS_MERCHANT_ID) . ',
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
<!-- END GCR Opt-in Module Code -->';
// ignore the language script and let google use the browser language
$language_script = '<!-- BEGIN GCR Language Code -->
<script>
  window.___gcfg = {
    lang: "LANGUAGE"
  };
</script>
<!-- END GCR Language Code -->';
        } // endif the order was found
      } // endif page is checkout_success

      if (strlen($output)) {
        $oscTemplate->addBlock($output, $this->group);
			}
    }

    function format_raw($number, $currency_code = '', $currency_value = '') {
      global $currencies, $currency;

      if (empty($currency_code) || !$currencies->is_set($currency_code)) {
        $currency_code = $currency;
      }

      if (empty($currency_value) || !is_numeric($currency_value)) {
        $currency_value = $currencies->currencies[$currency_code]['value'];
      }

      return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_HEADER_TAGS_GOOGLE_REVIEWS_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Google Customer Reviews', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_STATUS', 'True', 'Do you want to add Google Customer Reviews to your shop?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Google Merchant ID', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_MERCHANT_ID', '', 'Your Merchant Center ID. You can get this value from the Google Merchant Center.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shipping default', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SHIPPING_DEFAULT', '', 'Default time for shipping in days (used if no match found for shipping method)', '6', '1', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Optin dialog position', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_OPTIN_STYLE', 'Center', 'Choose where on the page the optin overlay appears', '6', '1', 'tep_cfg_select_option(array(\'Center\', \'Bottom Right\', \'Bottom Left\', \'Top Right\', \'Top Left\', \'Bottom Tray\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Times', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SHIPPING_TIMES', '', 'Mapping of shipping texts to delivery timescales', '6', '1', 'ht_gcr_show_ship_times', 'ht_gcr_edit_ship_times(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_HEADER_TAGS_GOOGLE_REVIEWS_STATUS', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_MERCHANT_ID', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SHIPPING_DEFAULT', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_OPTIN_STYLE', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SHIPPING_TIMES', 'MODULE_HEADER_TAGS_GOOGLE_REVIEWS_SORT_ORDER');
    }
  }

  function ht_gcr_show_ship_times($text) {
    return nl2br(implode("\n", explode(';', $text)));
  }

  function ht_gcr_edit_ship_times($values, $key) {
	  $ship_texts = array();
		$ship_modules = explode(';',MODULE_SHIPPING_INSTALLED);
	  $languages = tep_get_languages();
		if (sizeof($languages) == 1) { // in this case we can just load up the modules to get the text used in order totals
      foreach($ship_modules as $shipping) {
				if (strlen($shipping)) {
				// load shipping module
        include(DIR_FS_CATALOG_LANGUAGES . $languages[0]['directory'] . '/modules/shipping/' . $shipping);
				include(DIR_FS_CATALOG_MODULES . 'shipping/' . $shipping);
        $class = substr($shipping, 0, strrpos($shipping, '.'));
				$module = new $class;
			/*	$quote = $module->quote();
				if (array_key_exists('methods',$quote) && is_array($quotes['methods'])) {
				  $method_texts = array();
					foreach($quotes['methods'] as $method) {
					  $method_texts[] = $method['title'];
					}
					$ship_texts[$class] = $method_texts;
				} */
				}
		  }
			$output = '';
			foreach($ship_texts as $ship_class => $texts) {
			  $output .= $ship_class . "<br /><ul>\n";
				foreach($texts as $text) {
				  $output .= "<li>" . $text . "</li>\n";
				}
				$output .= "</ul>\n";
			}
		} else {
		}
		return $output;
	}
?>
