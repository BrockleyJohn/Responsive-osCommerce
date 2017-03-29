<?php
/*
  $Id: Ship2Pay, v1.5 2005/01/07 00:00:00 gjw Exp $
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 Edwin Bekaert (edwin@ednique.com)

  Released under the GNU General Public License

  http://forums.oscommerce.com/viewtopic.php?t=36112
  
  http://www.oscommerce.com/community/contributions,1042
*/

  class shipping {
    var $modules;
    
// class constructor
    function __construct() {

      if (defined('MODULE_SHIPPING_INSTALLED') && tep_not_null(MODULE_SHIPPING_INSTALLED)) {
        $allmods = explode(';', MODULE_SHIPPING_INSTALLED);
        
        $this->modules = array();
        
        for ($i = 0, $n = sizeof($allmods); $i < $n; $i++) {
          $file = $allmods[$i];
          $class = substr($file, 0, strrpos($file, '.'));
          $this->modules[$i] = array();
          $this->modules[$i]['class'] = $class;
          $this->modules[$i]['file'] = $file;
        }
      }
    }
    
    function get_modules(){
      return $this->modules;
    }
    
    function shipping_select($parameters, $selected = '') {
      echo $selected;
      $select_string = '<select ' . $parameters . '>';
      for ($i = 0, $n = sizeof($this->modules); $i < $n; $i++) {
        $select_string .= '<option value="' . $this->modules[$i]['class'] . '"';
        if ($selected == $this->modules[$i]['class']) $select_string .= ' SELECTED';
        $select_string .= '>' . $this->modules[$i]['class'] . '</option>';
      }
      $select_string .= '</select>';
      return $select_string;
    }
  }
?>
