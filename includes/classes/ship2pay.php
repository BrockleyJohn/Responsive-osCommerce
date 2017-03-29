<?php
/*
  $Id: Ship2Pay, v1.5 2005/01/07 00:00:00 gjw Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 Edwin Bekaert (edwin@ednique.com)

  Released under the GNU General Public License

  http://forums.oscommerce.com/index.php?showtopic=36112
  
  http://www.oscommerce.com/community/contributions,1042
*/

////
// Class to handle links between shipping and payment

  class ship2pay {
    var $modules;

// class constructor
    function __construct() {
      global $language, $PHP_SELF,$shipment,$GLOBALS;
      $this->modules = array();
      $q_ship2pay = tep_db_query("SELECT shipment, payments_allowed FROM ship2pay where status=1");
      while ($mods = tep_db_fetch_array($q_ship2pay)) {
        $this->modules[$mods['shipment']] = $mods['payments_allowed'];
      }
    }
    
    function get_pay_modules($ship_module){
      return $this->modules[$ship_module];
    }
   }
?>
