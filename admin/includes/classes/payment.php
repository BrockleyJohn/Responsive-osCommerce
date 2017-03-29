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

  class payment {
    var $modules;
    
// class constructor
    function __construct() {

      if (defined('MODULE_PAYMENT_INSTALLED') && tep_not_null(MODULE_PAYMENT_INSTALLED)) {
        $allmods = explode(';', MODULE_PAYMENT_INSTALLED);
        
        $this->modules = array();
        
        for ($i = 0, $n = sizeof($allmods); $i < $n; $i++) {
          $file = $allmods[$i];
          $class = substr($file, 0, strrpos($file, '.'));
          $this->modules[$i]['class'] = $class;
          $this->modules[$i]['file'] = $file;
        }
      }
    }
    
    function get_modules(){
      return $this->modules;
    }
    
    function payment_multiselect($parameters, $selected = '') {
      $arrSelected = explode(';', $selected);
      $select_string = '<select ' . $parameters . ' multiple size="10">';
      for ($i = 0, $n = sizeof($this->modules); $i < $n; $i++) {
        $sFile = $this->modules[$i]['file'];
        $sClass = $this->modules[$i]['class'];
        $select_string .= '<option value="' . $sFile . '"';
        if (in_array($sFile,$arrSelected)) $select_string .= ' SELECTED';
        $select_string .= '>' . $sClass . '</option>';
      }
      $select_string .= '</select>';
      return $select_string;
    }
    
    function GetModuleName($sSelected){
      $sNames = str_replace(".php", "", $sSelected);
      $sNames = str_replace(";", ",&nbsp;", $sNames);
      return $sNames;
    }
  }
?>
