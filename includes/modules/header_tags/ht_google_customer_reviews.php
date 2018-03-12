<?php
/*
  Google Customer Reviews addon for osC 2.3.4BS
	Author: John Ferguson @BrockleyJohn john@sewebsites.net

  v1.1 Add option for submitting products too
	     (and restructure module to add options without uninstall/reinstall)
	
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

      if (tep_not_null(MODULE_HEADER_TAGS_GCR_MERCHANT_ID)) {
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
							
							// product GTINs if required
							$products = '';
							if (MODULE_HEADER_TAGS_GCR_REVIEW_PRODUCTS == 'True') {
								$gtins = array();
								$op_query = tep_db_query("SELECT products_gtin FROM products p, orders_products op WHERE p.products_id = op.products_id and op.orders_id = '" . (int)$order_id . "'");
								while ($op = tep_db_fetch_array($op_query)) {
									if (tep_not_null($op['products_gtin'])) {
										$gtins[] = $op['products_gtin'];
									}
								}
								if ($n = count($gtins)) {
									$products = ', "products": [';
									for ($i = 0; $i < $n; $i++) {
										$products .= '{"gtin":"' . tep_output_string($gtins[$i]) . '"}, ';
									}
									$products = substr($products,0, -2);
									$products .= ']';
								}
							}
							
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
          ' . $products . '
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
			} // endif Merchant ID is set
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_HEADER_TAGS_GCR_STATUS');
    }

    function install($parameter = null) {
      $params = $this->getParams();

      if (isset($parameter)) {
        if (isset($params[$parameter])) {
          $params = array($parameter => $params[$parameter]);
        } else {
          $params = array();
        }
      }

      foreach ($params as $key => $data) {
        $sql_data_array = array('configuration_title' => $data['title'],
                                'configuration_key' => $key,
                                'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
                                'configuration_description' => $data['desc'],
                                'configuration_group_id' => '6',
                                'sort_order' => '0',
                                'date_added' => 'now()');

        if (isset($data['set_func'])) {
          $sql_data_array['set_function'] = $data['set_func'];
        }

        if (isset($data['use_func'])) {
          $sql_data_array['use_function'] = $data['use_func'];
        }

        tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
      }
    }

    function keys() {
      $keys = array_keys($this->getParams());

      if ($this->check()) {
        foreach ($keys as $key) {
          if (!defined($key)) {
            $this->install($key);
          }
        }
      }

      return $keys;
    }

    function getParams() {
      $params = array('MODULE_HEADER_TAGS_GCR_STATUS' => array('title' => 'Enable Google Customer Reviews',
                                                                'desc' => 'Do you want to add Google Customer Reviews to your shop?',
                                                               'value' => 'True',
                                                            'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                 'MODULE_HEADER_TAGS_GCR_MERCHANT_ID' => array('title' => 'Google Merchant ID',
                                                                'desc' => 'Your Merchant Center ID. You can get this value from the Google Merchant Center.',
																															 'value' => ''),
            'MODULE_HEADER_TAGS_GCR_SHIPPING_DEFAULT' => array('title' => 'Shipping Default',
                                                                'desc' => 'Default time for shipping in days (used if no match found for shipping method)',
                                                               'value' => ''),
             'MODULE_HEADER_TAGS_GCR_REVIEW_PRODUCTS' => array('title' => 'Send Products for Review',
                                                                'desc' => 'Send a list of product GTINs for requested review too.',
                                                               'value' => 'False',
                                                            'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
          'MODULE_HEADER_TAGS_GCR_CHECK_PRODUCT_GTIN' => array('title' => 'GTIN field exists?',
                                                                'desc' => 'Checks if GTIN field exists in product table.',
                                                            'use_func' => 'ht_gcr_check_gtin',
                                                            'set_func' => 'tep_cfg_do_nothing('),
                 'MODULE_HEADER_TAGS_GCR_OPTIN_STYLE' => array('title' => 'Optin Dialog Position',
                                                                'desc' => 'Choose where on the page the optin overlay appears',
                                                               'value' => 'Center',
                                                            'set_func' => 'tep_cfg_select_option(array(\'Center\', \'Bottom Right\', \'Bottom Left\', \'Top Right\', \'Top Left\', \'Bottom Tray\'), '),
              'MODULE_HEADER_TAGS_GCR_SHIPPING_TIMES' => array('title' => 'Shipping Times',
                                                                'desc' => 'Mapping of shipping methods to delivery timescales',
                                                               'value' => '',
                                                            'use_func' => 'ht_gcr_show_ship_times',
                                                            'set_func' => 'ht_gcr_edit_ship_times('),
              'MODULE_HEADER_TAGS_GCR_BADGE_POSITION' => array('title' => 'GCR Badge Position',
                                                                'desc' => 'Choose where on the page the badge appears. NB Inline needs a corresponding box or content module',
                                                               'value' => 'Bottom Right',
                                                            'set_func' => 'tep_cfg_select_option(array(\'Bottom Right\', \'Bottom Left\', \'Inline\'), '),
                 'MODULE_HEADER_TAGS_GCR_BADGE_PAGES' => array('title' => 'Badge Pages',
                                                                'desc' => 'Show the GCR badge on these store pages',
                                                               'value' => '',
                                                            'use_func' => 'ht_gcr_show_unpacked',
                                                            'set_func' => 'ht_gcr_edit_badge_pages('),
                  'MODULE_HEADER_TAGS_GCR_SORT_ORDER' => array('title' => 'Sort Order',
                                                                'desc' => 'Sort order of display. Lowest is displayed first.',
																															 'value' => '0'));

      return $params;
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

  }
	
	if (! function_exists('tep_cfg_do_nothing')) {
		function tep_cfg_do_nothing() {
      return '';
		}
	}
	
	function ht_gcr_check_gtin($value) {
		$chk_query = tep_db_query('show columns from products like "products_gtin"');
		return tep_image( 'images/icons/' . (tep_db_num_rows($chk_query) ? 'tick.gif' : 'cross.gif'), '', '16', '16', 'style="vertical-align:middle;"' );
	}

  function ht_gcr_show_unpacked($text) {
    return nl2br(implode("\n", explode(';', $text)));
  }

  function ht_gcr_show_ship_times($text) {
    return ht_gcr_show_unpacked($text);
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
