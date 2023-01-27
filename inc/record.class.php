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
/*
// TODO sprawdzic:
      RECORD przypisany do ENTITIES_ID,
      SOFTWARE, CONTRACT przypisany do ENTITIES_ID_2 (może być recursive)

      RECORD widzi SOFTWARE, CONTRACT (przez jedną z opcji: entities i sons, recursive)

      zmieniamy SOFTWARE ENTITIES_ID_2 na inny nie będący son wczesniejszego entity,
      jak reagować:
         czy ignorowac? (będzie widoczne póki powiązane, bedzie mozna usunąć ale poźniej nie będzie tego widać)
         czy odpiąć od RECORD?
*/

use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginGdprropaRecord extends CommonDBTM {
   
   static $rightname = 'plugin_gdprropa_record';

   public $dohistory = true;

   protected $usenotepad = true;

   const STORAGE_MEDIUM_UNDEFINED = 0;
   const STORAGE_MEDIUM_PAPER_ONLY = 1;
   const STORAGE_MEDIUM_MIXED = 4;
   const STORAGE_MEDIUM_ELECTRONIC = 8;

   const PIA_STATUS_UNDEFINED = 0;
   const PIA_STATUS_TODO = 1;
   const PIA_STATUS_QUALIFICATION = 2;
   const PIA_STATUS_APPROVAL = 4;
   const PIA_STATUS_PENDING = 8;
   const PIA_STATUS_CLOSED = 16;

   static function getTypeName($nb = 0) {

      return _n("GDPR Record of Processing Activity", "GDPR Records of Processing Activities", $nb, 'gdprropa');
   }

   function showForm($id, $options = []) {

      global $CFG_GLPI;

      $this->initForm($id, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Title", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";
      $title = Html::cleanInputText($this->fields['name']);
      echo "<input type='text' style='width:98%' maxlength=250 name='name' required value='" . $title . "'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Purpose (GDPR Article 30 1b)", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";
      // $purpose = Html::setSimpleTextContent($this->fields['content']);
      // $purpose = RichText::normalizeHtmlContent($this->fields['content']);
      $purpose = Sanitizer::sanitize($this->fields['content']);
      echo "<textarea style='width:98%' name='content' required maxlength='1000' rows='3'>". $purpose ."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Status") . "</td>";
      echo "<td colspan='2'>";
      self::DropDownState('record_state');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Storage medium", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";
      self::dropdownStorageMedium('storage_medium', $this->fields['storage_medium']);
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __("PIA required", 'gdprropa') . "</td>";
      echo "<td>";
      $rand = Dropdown::showYesNo('pia_required', $this->fields['pia_required']);

      $params = [
         'pia_required' => '__VALUE__',
         'pia_status' => $this->fields['pia_status']
      ];
      Ajax::updateItemOnSelectEvent(
         "dropdown_pia_required$rand",
         'pia_status_td',
         $CFG_GLPI['root_doc'] . '/plugins/gdprropa/ajax/record_pia_required_dropdown.php',
         $params
      );

      echo "<td colspan='2' id='pia_status_td'>";
      PluginGdprropaRecord::showPIAStatus($this->fields);
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'><td>" . __("Consent required", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";
      $rand = Dropdown::showYesNo('consent_required', $this->fields['consent_required']);

      $params = [
         'consent_required' => '__VALUE__',
         'consent_storage' => $this->fields['consent_storage']
      ];
      Ajax::updateItemOnSelectEvent(
         "dropdown_consent_required$rand",
         'consent_storage_tr',
         $CFG_GLPI['root_doc'] . '/plugins/gdprropa/ajax/record_consent_required_dropdown.php',
         $params
      );

      echo "</td></tr>";

      echo "<tr class='tab_bg_1' id='consent_storage_tr'>";
      PluginGdprropaRecord::showConsentRequired($this->fields);
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("First entry date", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";
      Html::showDateField('first_entry_date', ['value' => $this->fields['first_entry_date'], 'required' => true, 'placeholder' => date("Y-m-d")]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Additional information", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";
      // $additional_info = Html::setSimpleTextContent($this->fields['additional_info']);
      // $additional_info = RichText::normalizeHtmlContent($this->fields['additional_info']);
      $additional_info = Sanitizer::sanitize($this->fields['additional_info']);
      echo "<textarea style='width: 98%;' name='additional_info' maxlength='1000' rows='3'>" . $additional_info . "</textarea>";
      echo "</td></tr>";
      $this->showFormButtons($options);

      return true;
   }

   static function showPIAStatus($data = []) {

      if ($data['pia_required']) {
         echo "&nbsp;&nbsp;&nbsp;" . __("Status") . "&nbsp;&nbsp;";
         self::dropdownPiaStatus('pia_status', $data['pia_status']);
      }

   }

   static function showConsentRequired($data = []) {

      if ($data['consent_required']) {
         echo "<td>" . __("Consent storage", 'gdprropa') . "</td>";
         echo "<td colspan='2'>";
         // $consent_storage = Html::setSimpleTextContent($data['consent_storage']);
         // $consent_storage = RichText::normalizeHtmlContent($data['consent_storage']);
         $consent_storage = Sanitizer::sanitize($data['consent_storage']);
         echo "<textarea style='width: 98%;' name='consent_storage' maxlength='1000' rows='3'>" . $consent_storage . "</textarea>";
         echo "</td>";
      }

   }

   public function defineTabs($options = []) {

      $ong = [];

      $this
         ->addDefaultFormTab($ong)
         ->addStandardTab(__CLASS__, $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_DataSubjectsCategory', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_LegalBasisAct', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_DataVisibility', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_Retention', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_Contract', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_PersonalDataCategory', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_Software', $ong, $options)
         ->addStandardTab('PluginGdprropaRecord_SecurityMeasure', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('PluginGdprropaCreatePDF', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            PluginGdprropaRecord_Contract::class,
            PluginGdprropaRecord_DataSubjectsCategory::class,
            PluginGdprropaRecord_LegalBasisAct::class,
            PluginGdprropaRecord_DataVisibility::class,
            PluginGdprropaRecord_PersonalDataCategory::class,
            PluginGdprropaRecord_Retention::class,
            PluginGdprropaRecord_SecurityMeasure::class,
            PluginGdprropaRecord_Software::class,
         ]
      );

      $retention = new PluginGdprropaRecord_Retention();
      $retention->deleteByCriteria(['plugin_gdprropa_records_id' => $this->fields['id']]);

   }

   static function getAllPiaStatusArray($withmetaforsearch = false) {

      $tab = [
         self::PIA_STATUS_UNDEFINED => __("Undefined", 'gdprropa'),
         self::PIA_STATUS_TODO => __("To do"),
         self::PIA_STATUS_QUALIFICATION => __("Qualification"),
         self::PIA_STATUS_APPROVAL => __("Approval"),
         self::PIA_STATUS_PENDING => __("Pending"),
         self::PIA_STATUS_CLOSED => __("Closed")
      ];

      if ($withmetaforsearch) {
         $tab['all'] = __("All");
      }

      return $tab;
   }

   static function getAllStateArray(){
      global $DB;
      $tab = [];

      $result = $DB->queryOrDie("SELECT `glpi_states`.`name` FROM `glpi_states` WHERE `glpi_states`.`name` NOT LIKE 'Traitement RGPD'  AND `glpi_states`.`comment` LIKE 'Créé via plugin GDPRRoPA';");
      

      if($result){
         foreach ($result as $item)
         {
            $tab[] = $item['name'];
         }
      }
      else{
         $tab = ['indéfini'];
      }

      return $tab;
   }
   static function DropDownState($name, $value = 0, $display = true){
      return DropDown::ShowFromArray($name, self::getAllStateArray(), ['value' => $value, 'display' => $display]);
   }

   static function getAllStorageMediumArray($withmetaforsearch = false) {

      $tab = [
         self::STORAGE_MEDIUM_UNDEFINED => __("Undefined", 'gdprropa'),
         self::STORAGE_MEDIUM_PAPER_ONLY => __("Paper only", 'gdprropa'),
         self::STORAGE_MEDIUM_MIXED => __("Paper and electronic", 'gdprropa'),
         self::STORAGE_MEDIUM_ELECTRONIC => __("Electronic only", 'gdprropa'),
      ];

      if ($withmetaforsearch) {
         $tab['all'] = __("All");
      }

      return $tab;
   }

   static function dropdownStorageMedium($name, $value = 0, $display = true) {

      return Dropdown::showFromArray($name, self::getAllStorageMediumArray(), [
         'value' => $value, 'display' => $display]);
   }

   static function dropdownPiaStatus($name, $value = 0, $display = true) {

      return Dropdown::showFromArray($name, self::getAllPiastatusArray(), [
         'value' => $value, 'display' => $display]);
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'status' :

            return self::getStatusIcon($values[$field]) . '&nbsp;' . self::getStatus($values[$field]);
         case 'pia_status' :
            if (!$values[$field]) {
               return '&nbsp;';
            }
            $pia_status = self::getAllPiastatusArray();

            return $pia_status[$values[$field]];
         case 'storage_medium' :
            $storage_medium = self::getAllStorageMediumArray();

            return $storage_medium[$values[$field]];
      }
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'pia_status' :

            return self::dropdownPiaStatus($name, $values[$field], false);
         case 'storage_medium' :

            return self::dropdownStorageMedium($name, $values[$field], false);
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   function prepareInputForAdd($input) {

      $input['users_id_creator'] = Session::getLoginUserID();

      if (array_key_exists('pia_required', $input) && $input['pia_required'] == 0) {
         $input['pia_status'] = PluginGdprropaRecord::PIA_STATUS_UNDEFINED;
      }

      if (array_key_exists('consent_required', $input) && $input['consent_required'] == 0) {
         $input['consent_storage'] = null;
      }

      return parent::prepareInputForAdd($input);
   }

   function prepareInputForUpdate($input) {

      $input['users_id_lastupdater'] = Session::getLoginUserID();

      if (array_key_exists('pia_required', $input) && $input['pia_required'] == 0) {
         $input['pia_status'] = PluginGdprropaRecord::PIA_STATUS_UNDEFINED;
      }

      if (array_key_exists('consent_required', $input) && $input['consent_required'] == 0) {
         $input['consent_storage'] = null;
      }

      return parent::prepareInputForUpdate($input);
   }

   function post_updateItem($history = 1) {

      if (($this->fields['storage_medium'] == self::STORAGE_MEDIUM_PAPER_ONLY)
         && (PluginGdprropaConfig::getConfig('system', 'remove_software_when_paper_only'))) {
         $del = new PluginGdprropaRecord_Software();
         $del->deleteByCriteria(['plugin_gdprropa_records_id' => $this->fields['id']]);
      }
   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id' => 'common',
         'name' => __('Characteristics')
      ];

      $tab[] = [
         'id' => '1',
         'table' => $this->getTable(),
         'field' => 'name',
         'name' => __("Name"),
         'datatype' => 'itemlink',
         'searchtype' => 'contains',
         'massiveaction' => false
      ];

      $tab[] = [
         'id' => '2',
         'table' => $this->getTable(),
         'field' => 'id',
         'name' => __("ID"),
         'massiveaction' => false,
         'datatype' => 'number'
      ];

      $tab[] = [
         'id' => '3',
         'table' => $this->getTable(),
         'field' => 'content',
         'name' => __("Purpose", 'gdprropa'),
         'massiveaction' => false,
         'htmltext' => true
      ];

      $tab[] = [
         'id' => '4',
         'table' => $this->getTable(),
         'field' => 'additional_info',
         'name' => __("Additional information", 'gdprropa'),
         'massiveaction' => true,
         'htmltext' => true
      ];

      $tab[] = [
         'id' => '5',
         'table' => 'glpi_states',
         'field' => 'completename',
         'name' => __("Status"),
         'massiveaction' => true,
         'datatype' => 'dropdown',
      ];
      $tab[] = [
         'id' => '6',
         'table' => 'glpi_entities',
         'field' => 'completename',
         'name' => __("Entity"),
         'massiveaction' => true,
         'datatype' => 'dropdown',
      ];
      $tab[] = [
         'id' => '7',
         'table' => $this->getTable(),
         'field' => 'is_recursive',
         'name' => __("Child entities"),
         'massiveaction' => false,
         'datatype' => 'bool'];
      $tab[] = [
         'id' => '8',
         'table' => $this->getTable(),
         'field' => 'pia_required',
         'name' => __("PIA required", 'gdprropa'),
         'massiveaction' => true,
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id' => '9',
         'table' => $this->getTable(),
         'field' => 'pia_status',
         'name' => __("PIA status", 'gdprropa'),
         'searchtype' => ['equals', 'notequals'],
         'massiveaction' => true,
         'datatype' => 'specific',
      ];

      $tab[] = [
         'id' => '10',
         'table' => $this->getTable(),
         'field' => 'storage_medium',
         'name' => __("Storage medium", 'gdprropa'),
         'searchtype' => ['equals', 'notequals'],
         'massiveaction' => true,
         'datatype' => 'specific'
      ];

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_Contract::rawSearchOptionsToAdd()
      );

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_LegalBasisAct::rawSearchOptionsToAdd()
      );

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_DataVisibility::rawSearchOptionsToAdd()
      );

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_DataSubjectsCategory::rawSearchOptionsToAdd()
      );

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_SecurityMeasure::rawSearchOptionsToAdd()
      );

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_PersonalDataCategory::rawSearchOptionsToAdd()
      );

      $tab = array_merge(
         $tab,
         PluginGdprropaRecord_Software::rawSearchOptionsToAdd()
      );

      return $tab;
   }

}
