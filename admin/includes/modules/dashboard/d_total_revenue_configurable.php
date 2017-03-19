<?php
/*
  Total Revenue Configurable Admin Dashboard Module
	- choose which order statuses count towards total revenue

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

  class d_total_revenue_configurable {
    var $code;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
			$days = (defined('MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DAYS') ? MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DAYS : 'n');
      $this->title = sprintf(MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_TITLE,$days);
      $this->description = MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DESCRIPTION;

      if ( defined('MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUS == 'True');
      }
    }

    function getOutput() {
      $period = MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DAYS;
			if (!(is_numeric($period) && $period > 0)) return MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DAYS_ERROR;
			$days = array();
      for($i = 0; $i < $period; $i++) {
        $days[date('Y-m-d', strtotime('-'. $i .' days'))] = 0;
      }

      $in = implode("','",explode(',',MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUSES));
      $orders_query = tep_db_query("select date_format(o.date_purchased, '%Y-%m-%d') as dateday, sum(ot.value) as total from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_TOTAL . " ot where date_sub(curdate(), interval $period day) <= o.date_purchased and o.orders_id = ot.orders_id and ot.class = 'ot_total' and orders_status in ('".$in."') group by dateday");
      while ($orders = tep_db_fetch_array($orders_query)) {
        $days[$orders['dateday']] = $orders['total'];
      }

      $days = array_reverse($days, true);

      $js_array = '';
      foreach ($days as $date => $total) {
        $js_array .= '[' . (mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4))*1000) . ', ' . $total . '],';
      }

      if (!empty($js_array)) {
        $js_array = substr($js_array, 0, -1);
      }

      $chart_label = tep_output_string(MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_CHART_LINK);
      $chart_label_link = tep_href_link('orders.php');

      $output = <<<EOD
<div id="d_total_revenue_cfg" style="width: 100%; height: 150px;"></div>
<script type="text/javascript">
$(function () {
  var plot30 = [$js_array];
  $.plot($('#d_total_revenue_cfg'), [ {
    label: '$chart_label',
    data: plot30,
    lines: { show: true, fill: true },
    points: { show: true },
    color: '#66CC33'
  }], {
    xaxis: {
      ticks: 4,
      mode: 'time'
    },
    yaxis: {
      ticks: 3,
      min: 0
    },
    grid: {
      backgroundColor: { colors: ['#fff', '#eee'] },
      hoverable: true
    },
    legend: {
      labelFormatter: function(label, series) {
        return '<a href="$chart_label_link">' + label + '</a>';
      }
    }
  });
});

function showTooltip(x, y, contents) {
  $('<div id="tooltip">' + contents + '</div>').css( {
    position: 'absolute',
    display: 'none',
    top: y + 5,
    left: x + 5,
    border: '1px solid #fdd',
    padding: '2px',
    backgroundColor: '#fee',
    opacity: 0.80
  }).appendTo('body').fadeIn(200);
}

var monthNames = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

var previousPoint = null;
$('#d_total_revenue_cfg').bind('plothover', function (event, pos, item) {
  if (item) {
    if (previousPoint != item.datapoint) {
      previousPoint = item.datapoint;

      $('#tooltip').remove();
      var x = item.datapoint[0],
          y = item.datapoint[1],
          xdate = new Date(x);

      showTooltip(item.pageX, item.pageY, y + ' for ' + monthNames[xdate.getMonth()] + '-' + xdate.getDate());
    }
  } else {
    $('#tooltip').remove();
    previousPoint = null;
  }
});
</script>
EOD;

      return $output;
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Configurable Total Revenue Module', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUS', 'True', 'Do you want to show the total revenue chart on the dashboard?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Days', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DAYS', '30', 'Period for the report in days - suggested values 30, 60, 90 etc', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Order Statuses', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUSES', '1,2,3', 'Count revenue for these statuses', '6', '0', 'd_revenue_config_show_statuses', 'd_revenue_config_edit_statuses(', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUS', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_SORT_ORDER', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_DAYS', 'MODULE_ADMIN_DASHBOARD_TOTAL_REVENUE_CONFIG_STATUSES');
    }
  }

  function d_revenue_config_show_statuses($text) {
    global $languages_id;
    $return = array();
    if (! empty($text)) {
      $in = implode("','",explode(',',$text));
      $string = "SELECT orders_status_name FROM orders_status WHERE language_id = '".$languages_id."' AND orders_status_id IN ('".$in."')";
      $query = tep_db_query($string);
      while ($status = tep_db_fetch_array($query)) {
        $return[] = $status['orders_status_name'];
      }
    }
    return nl2br(implode("\n", $return));
  }

  function d_revenue_config_edit_statuses($values, $key) {
    global $languages_id;
    $values_array = explode(',', $values);
    $output = '';

    $query = tep_db_query('SELECT orders_status_id, orders_status_name FROM orders_status WHERE language_id = "'.$languages_id.'"');
    while ($status = tep_db_fetch_array($query)) {
      $output .= tep_draw_checkbox_field('d_revenue_config_status[]', $status['orders_status_id'], in_array($status['orders_status_id'], $values_array)) . '&nbsp;' . tep_output_string($status['orders_status_name']) . '<br />';
    }

    if (!empty($output)) {
      $output = '<br />' . substr($output, 0, -6);
    }

    $output .= tep_draw_hidden_field('configuration[' . $key . ']', '', 'id="drev_statuses"');

    $output .= '<script>
                function drev_update_cfg_value() {
                  var drev_selected_statuses = \'\';

                  if ($(\'input[name="d_revenue_config_status[]"]\').length > 0) {
                    $(\'input[name="d_revenue_config_status[]"]:checked\').each(function() {
                      drev_selected_statuses += $(this).attr(\'value\') + \',\';
                    });

                    if (drev_selected_statuses.length > 0) {
                      drev_selected_statuses = drev_selected_statuses.substring(0, drev_selected_statuses.length - 1);
                    }
                  }

                  $(\'#drev_statuses\').val(drev_selected_statuses);
                }

                $(function() {
                  drev_update_cfg_value();

                  if ($(\'input[name="d_revenue_config_status[]"]\').length > 0) {
                    $(\'input[name="d_revenue_config_status[]"]\').change(function() {
                      drev_update_cfg_value();
                    });
                  }
                });
                </script>';

    return $output;
  }