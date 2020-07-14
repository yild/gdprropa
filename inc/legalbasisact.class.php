<?php
/*
 -------------------------------------------------------------------------
 GDPR Records of Processing Activities plugin for GLPI
 Copyright (C) 2020 by Yild.

 https://github.com/yild/gdprropa
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GDPR Records of Processing Activities.

 GDPR Records of Processing Activities is free software; you can
 redistribute it and/or modify it under the terms of the
 GNU General Public License as published by the Free Software
 Foundation; either version 3 of the License, or (at your option)
 any later version.

 GDPR Records of Processing Activities is distributed in the hope that
 it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GDPR Records of Processing Activities.
 If not, see <http://www.gnu.org/licenses/>.

 Based on DPO Register plugin, by Karhel Tmarr.

 --------------------------------------------------------------------------

  @package   gdprropa
  @author    Yild
  @copyright Copyright (c) 2020 by Yild
  @license   GPLv3+
             http://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/yild/gdprropa
  @since     2020
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginGdprropaLegalBasisAct extends CommonDropdown {

   static $rightname = 'plugin_gdprropa_legalbasisact';

   public $dohistory = true;

   const LEGALBASISACT_BLANK = 0;
   const LEGALBASISACT_GDPR = 1;
   const LEGALBASISACT_NATIONAL = 2;
   const LEGALBASISACT_INTERNATIONAL = 3;
   const LEGALBASISACT_INTERNAL = 4;
   const LEGALBASISACT_OTHER = 5;

   static function getTypeName($nb = 0) {

       return _n("Legal basis", "Legal bases", $nb, 'gdprropa');
   }

   function getAdditionalFields() {

      return [
         [
            'name' => 'type',
            'label' => __("Type"),
            'list' => true,
         ],
         [
            'name' => 'content',
            'label' => __("Content"),
            'type' => 'textarea',
            'rows' => 6
         ]
      ];
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'type' :
            $legalbases = self::getAllTypesArray();

            return $legalbases[$values[$field]];
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'type' :

            return self::dropdownTypes($name, $values[$field], false);
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   function displaySpecificTypeField($ID, $field = []) {

      if ($field['name'] == 'type') {
         self::dropdownTypes($field['name'], $this->fields[$field['name']], true);
      }
   }

   static function dropdownTypes($name, $value = 0, $display = true) {

      return Dropdown::showFromArray($name, self::getAllTypesArray(), [
         'value' => $value, 'display' => $display]);
   }

   static function getAllTypesArray() {

      return [
         self::LEGALBASISACT_BLANK => __("Undefined", 'gdprropa'),
         self::LEGALBASISACT_GDPR => __("GDPR Article", 'gdprropa'),
         self::LEGALBASISACT_NATIONAL => __("Local law regulation", 'gdprropa'),
         self::LEGALBASISACT_INTERNATIONAL => __("International regulation", 'gdprropa'),
         self::LEGALBASISACT_INTERNAL => __("Controller internal regulation", 'gdprropa'),
         self::LEGALBASISACT_OTHER => __("Other regulation", 'gdprropa'),
      ];
   }

   function prepareInputForAdd($input) {

      $input['users_id_creator'] = Session::getLoginUserID();

      return parent::prepareInputForAdd($input);
   }

   function prepareInputForUpdate($input) {

      $input['users_id_lastupdater'] = Session::getLoginUserID();

      return parent::prepareInputForUpdate($input);
   }

   function cleanDBonPurge() {

      $rel = new PluginGdprropaRecord_LegalBasisAct();
      $rel->deleteByCriteria(['plugin_gdprropa_legalbasisacts_id' => $this->fields['id']]);

   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __("Characteristics")
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __("Name"),
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __("ID"),
         'massiveaction'      => false,
         'datatype'           => 'number',
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'type',
         'name'               => __("Type", 'gdprropa'),
         'searchtype'         => 'equals',
         'massiveaction'      => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __("Content"),
         'datatype'           => 'text',
         'toview'             => true,
         'massiveaction'      => true,
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __("Comments"),
         'datatype'           => 'text',
         'toview'             => true,
         'massiveaction'      => true,
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __("Entity"),
         'massiveaction'      => true,
         'datatype'           => 'dropdown',
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __("Child entities"),
         'massiveaction'      => false,
         'datatype'           => 'bool',
      ];

      return $tab;
   }

}
