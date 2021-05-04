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
// TODO ADDITIONAL FUTURE - save last print settings, per user, same code for PluginGdprropaControllerInfo and PluginGdprropaCreatePDF print execution
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

if (!defined('K_PATH_IMAGES')) {
   define('K_PATH_IMAGES', GLPI_ROOT . '/plugins/gdprropa/images/');
}

class PluginGdprropaCreatePDF extends PluginGdprropaCreatePDFBase {

   static $rightname = 'plugin_gdprropa_createpdf';

   const REPORT_SINGLE_RECORD = 1;
   const REPORT_FOR_ENTITY = 2;
   const REPORT_ALL = 3;


   protected $entity;
   protected $controller_info;

   static protected $default_print_options = [
      'show_representative' => [
         'show' => 1,
         'show_title' => 1,
         'show_address' => 1,
         'show_phone' => 1,
         'show_email' => 1,
      ],
      'show_dpo' => [
         'show' => 1,
         'show_title' => 1,
         'show_address' => 1,
         'show_phone' => 1,
         'show_email' => 1,
      ],
      'page_orientation' => 'P',
      'show_inherited_from' => false,
      'show_comments' => true,
      'show_print_date_time' => true,
      'show_is_deleted_header' => 1,
      'show_status_in_header' => 1,
      'show_full_personaldatacategorylist' => 1,
      'show_expired_contracts' => 1,
      'show_contracs_types_header_if_empty' => 0,
      'show_record_owner' => 1,
      'show_assets_owners' => 1,
      'show_deleted_records_for_entity' => 0,
      'show_representative_dpo_per_record' => 0,
   ];

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$item->canView()) {
         return false;
      }

      switch ($item->getType()) {
         case PluginGdprropaRecord::class :

            return self::createTabEntry(PluginGdprropaCreatePDF::getTypeName(0), 0);
      }

      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case PluginGdprropaRecord::class :
            self::showForm(PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD, $item->fields['id']);
            break;
      }

      return true;
   }

   protected function setEntityAndControllerInfo($entity_id) {

      $this->entity = new Entity();

      $this->controller_info = PluginGdprropaControllerInfo::getFirstControllerInfo($entity_id);
      if (!is_null($this->controller_info)) {
         $this->entity->getFromDB($this->controller_info->fields['entities_id']);
      }

   }

   static function showConfigFormElements($config = []) {

      echo "<tr class='tab_bg_1'>";
      echo "<td width='25%'>" . __("Show 'introduced in'", 'gdprropa') . "</td>";
      echo "<td width='75%'>";
      Dropdown::showYesNo('show_inherited_from', $config['show_inherited_from']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show comments", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_comments', $config['show_comments']);
      echo "</td>";
      echo "</tr>";

      if (!isset($config['report_type'])) {
         $config['report_type'] = PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD;
      }

      if ($config['report_type'] != PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Show deleted records", 'gdprropa') . "</td>";
         echo "<td>";
         Dropdown::showYesNo('show_deleted_records_for_entity', $config['show_deleted_records_for_entity']);
         echo "</td>";
         echo "</tr>";
         // TODO tutaj cos nie tak jest... czy to potrzebne?, moze olac i tylko TOP entity wyswietlac na poczatku? usunac z default_print_config
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Show legal representative and DPO for each record", 'gdprropa') . "</td>";
         echo "<td>";
         Dropdown::showYesNo('show_representative_dpo_per_record', $config['show_representative_dpo_per_record']);
         echo "</td>";
         echo "</tr>";
      }

      if (PluginGdprropaCreatePDFBase::isGdprownerPluginActive()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Show record owner information", 'gdprropa') . "</td>";
         echo "<td>";
         Dropdown::showYesNo('show_record_owner', $config['show_record_owner']);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Show assets owners", 'gdprropa') . "</td>";
         echo "<td>";
         Dropdown::showYesNo('show_assets_owners', $config['show_assets_owners']);
         echo "</td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show full personal data category list", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_full_personaldatacategorylist', $config['show_full_personaldatacategorylist']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show expired contracts", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_expired_contracts', $config['show_expired_contracts']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show contracts types header if empty list", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_contracs_types_header_if_empty', $config['show_contracs_types_header_if_empty']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show 'is deleted' information in info header", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_is_deleted_header', $config['show_is_deleted_header']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show print date/time", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_print_date_time', $config['show_print_date_time']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Page orientation", 'gdprropa') . "</td>";
      echo "<td colspan='2'>";

      $orientation = [];
      $orientation['P'] = __("Portrait", 'gdprropa');
      $orientation['L'] = __("Landscape", 'gdprropa');
      Dropdown::showFromArray('page_orientation', $orientation,
         ['value' => $config['page_orientation'], 'display' => true]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Show record status in info header", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_status_in_header', $config['show_status_in_header']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";

      echo "<table width='100%' align='left'><tbody>";
      echo "<tr>";
      echo "<td width='50%'>";

      echo "<table><tbody>";
      echo "<tr class='tab_bg_1'>";
      echo "<td width='75%'>" . __("Show legal representative", 'gdprropa') . "</td>";
      echo "<td width='25%'>";
      Dropdown::showYesNo('show_representative[show]', $config['show_representative']['show']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show title", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_representative[show_title]', $config['show_representative']['show_title']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show address", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_representative[show_address]', $config['show_representative']['show_address']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show phone", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_representative[show_phone]', $config['show_representative']['show_phone']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show email", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_representative[show_email]', $config['show_representative']['show_email']);
      echo "</td>";
      echo "</tr>";
      echo "</tbody></table>";

      echo "</td>";
      echo "<td width='50%'>";

      echo "<table><tbody>";
      echo "<tr class='tab_bg_1'>";
      echo "<td width='75%'>" . __("Show DPO", 'gdprropa') . "</td>";
      echo "<td width='25%'>";
      Dropdown::showYesNo('show_dpo[show]', $config['show_dpo']['show']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show title", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_dpo[show_title]', $config['show_dpo']['show_title']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show address", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_dpo[show_address]', $config['show_dpo']['show_address']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show phone", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_dpo[show_phone]', $config['show_dpo']['show_phone']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='padding-left: 50px;'>" . __("Show email", 'gdprropa') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_dpo[show_email]', $config['show_dpo']['show_email']);
      echo "</td>";
      echo "</tr>";
      echo "</tbody></table>";

      echo "</td>";
      echo "</tr>";
      echo "</tbody></table>";

      echo "</td>";
      echo "</tr>";

   }

   static function showPrepareForm($report_type) {

      echo "<div class='glpi_tabs'>";
      echo '<div class="center vertical ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-vertical ui-helper-clearfix ui-corner-left">';
      echo '<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" aria-live="polite" role="tabpanel" aria-expanded="true" aria-hidden="false">';
      echo '<div class="firstbloc">';

      self::showForm($report_type);

      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      Html::footer();
   }

   static function showForm($report_type, $record_id = -1) {

      global $CFG_GLPI;

      echo "<form name='form' method='GET' action=\"" . $CFG_GLPI['root_doc'] . "/plugins/gdprropa/front/createpdf.php\" enctype='multipart/form-data'>";

      echo "<div class='spaced' id='tabsbody'>";

      echo "<table class='tab_cadre_fixe' id='mainformtable'>";
      echo "<tbody>";
      echo "<tr class='headerRow'>";
      echo "<th colspan='3' class=''>" . __("PDF creation settings", 'gdprropa') . "</th>";
      echo "</tr>";

      $_config = PluginGdprropaCreatePDF::getDefaultPrintOptions();
      $_config['report_type'] = $report_type;
      PluginGdprropaCreatePDF::showConfigFormElements($_config);

      echo "</table>";
      echo "</div>";
      echo "<input type='hidden' name='report_type' value=\"" . $report_type . "\">";
      if ($report_type == PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD) {
         echo "<input type='hidden' name='record_id' value=\"" . $record_id . "\">";
      }
      echo "<input type='hidden' name='action' value=\"print\">";
      echo "<input type='submit' class='submit' name='createpdf' value='" . PluginGdprropaCreatePDF::getTypeName(1) . "' />";
      Html::closeForm();
   }

   protected function prepareControllerInfo() {

      $controller_name = __("Controller name not set.", 'gdprropa');
      $info = "<strong>" . $controller_name . "</strong>";
      if (isset($this->controller_info->fields['id'])) {

         $controller_name = trim($this->controller_info->fields['controllername']);
         if (!empty($controller_name)) {

            $info = "<ul>";
            $info .= "<li>" . __("Name") . ": <strong>" . $controller_name . "</strong>" . "</li>";

            $address = trim($this->entity->fields['address']);
            if (empty($address)) {
               $address = __("N/A", 'gdprropa');
            }
            $postcode = trim($this->entity->fields['postcode']);
            if (empty($postcode)) {
               $postcode = __("N/A", 'gdprropa');
            }
            $town = trim($this->entity->fields['town']);
            if (empty($town)) {
               $town = __("N/A", 'gdprropa');
            }

            $state = trim($this->entity->fields['state']);
            $country = trim($this->entity->fields['country']);

            $phone = trim($this->entity->fields['phonenumber']);
            if (empty($phone)) {
               $town = __("N/A", 'gdprropa');
            }
            $fax = trim($this->entity->fields['fax']);
            if (empty($fax)) {
               $town = __("N/A", 'gdprropa');
            }
            $email = trim($this->entity->fields['email']);
            if (empty($email)) {
               $town = __("N/A", 'gdprropa');
            }
            $web = trim($this->entity->fields['website']);
            if (empty($web)) {
               $town = __("N/A", 'gdprropa');
            }

            $address_full = $address . ", " . $postcode . " " . $town;
            if ($state) {
               $address_full .= " " . $state;
            }
            if ($country) {
               $address_full .= " " . $country;
            }

            $info .= "<li>" . __("Address") . ": <strong>" . $address_full . "</strong></li>";
            $info .= "<li>" . __("Phone") . ": <strong>" . $phone . "</strong></li>";
            $info .= "<li>" . __("Fax") . ": <strong>" . $fax . "</strong></li>";
            $info .= "<li>" . __("Email") . ": <strong>" . $email . "</strong></li>";
            $info .= "<li>" . __("Website") . ": <strong>" . $web . "</strong></li>";

            $info .= "</ul>";

         }
      }

      $result = [
         'section' => "<h3>" . __("Controller", 'gdprropa') . "</h3>",
         'value' => $info
      ];

      return $result;
   }

   protected function preparePersonelInfo($person, $caption_not_set, $section_caption) {

      $info = '';

      if ((isset($this->controller_info->fields['users_id_' . $person]) && !$this->controller_info->fields['users_id_' . $person]) ||
          (!isset($this->controller_info->fields['users_id_' . $person]))) {
         $info = "<strong>" . $caption_not_set . "</strong>";
      } else {

         $user = new User();
         $user->getFromDB($this->controller_info->fields['users_id_' . $person]);

         $email = new UserEmail();
         $email->getFromDBByCrit(['users_id' => $user->fields['id'], 'is_default' => 1]);

         $location = new Location();
         $location->getFromDB($user->fields['locations_id']);

         $realname = trim($user->fields['realname']);
         if (empty($realname)) {
            $realname = __("N/A", 'gdprropa');
         }
         $firstname = trim($user->fields['firstname']);
         if (empty($firstname)) {
            $firstname = __("N/A", 'gdprropa');
         }

         $info = "<ul>";
         $info .= "<li>" . __("Surname") . ": <strong>" . $realname . "</strong>; " . __("First name") . ": <strong>" . $firstname . "</strong>" . "</li>";

         if ($this->print_options['show_' . $person]['show_title']) {
            $title = trim(Dropdown::getDropdownName('glpi_usertitles', $user->fields['usertitles_id']));
            if (empty($title) || ($title == '&nbsp;')) {
               $title = __("N/A", 'gdprropa');
            }
            $info .= "<li>" . _x('person', "Title") . " : <strong>" . $title . "</strong></li>";
         }
         if ($this->print_options['show_' . $person]['show_address']) {

            $address = isset($location->fields['address']) ? trim($location->fields['address']) : $address = __("N/A", 'gdprropa');

            $postcode = isset($location->fields['postcode']) ? trim($location->fields['postcode']) : $postcode = __("N/A", 'gdprropa');

            $town = isset($location->fields['town']) ? trim($location->fields['town']) : __("N/A", 'gdprropa');

            $state = isset($location->fields['state']) ? trim($location->fields['state']) : '';

            $country = isset($location->fields['country']) ? trim($location->fields['country']) : '';

            $address_full = $address . ", " . $postcode . " " . $town;
            if ($state) {
               $address_full .= " " . $state;
            }
            if ($country) {
               $address_full .= " " . $country;
            }

            $info .= "<li>" . __("Address") . ": <strong>" . $address_full . "</strong></li>";
         }

         if ($this->print_options['show_' . $person]['show_phone']) {
            $phone = trim($user->fields['phone']);
            if (empty($phone)) {
               $phone = __("N/A", 'gdprropa');
            }
            $info .= "<li>" . __("Phone") . ": <strong>" . $phone . "</strong>" . "</li>";
         }
         if ($this->print_options['show_' . $person]['show_email']) {
            $email = isset($email->fields['email']) ? trim($email->fields['email']) : '';
            if (empty($email)) {
               $email = __("N/A", 'gdprropa');
            }
            $info .= "<li>" . __("Email") . ": <strong>" . $email . "</strong>" . "</li>";
         }

         $info .= "</ul>";

      }

      $result = [
         'section' => "<h3>" . $section_caption . "</h3>",
         'value' => $info,
      ];

      return $result;
   }

   protected function getRecordsForEntity($entity_id, $print_options = null, $include_recursive = false) {

      global $DB;

      if ($include_recursive) {
         $entities = getAncestorsOf('glpi_entities', $entity_id);
      } else {
         $entities = getSonsOf('glpi_entities', $entity_id);
      }
      array_push($entities, $entity_id);

      if ($print_options['show_deleted_records_for_entity']) {
         $include_deleted = [0, 1];
      } else {
         $include_deleted = [0];
      }

      if ($include_recursive) {
         $include = '
                  (`glpi_plugin_gdprropa_records`.`is_recursive` = 1 AND
                   `glpi_plugin_gdprropa_records`.`entities_id` IN (' . implode(',', $entities) . ')
                  ) OR (
                   `glpi_plugin_gdprropa_records`.`entities_id` = ' . $entity_id . '
                  )
         ';
      } else {
         $include = '`glpi_plugin_gdprropa_records`.`entities_id` IN (' . implode(',', $entities) . ')';
      }
      $query = '
         SELECT
            `glpi_plugin_gdprropa_records`.*
         FROM
            `glpi_plugin_gdprropa_records` 
         LEFT JOIN
            `glpi_entities` ON (`glpi_plugin_gdprropa_records`.`entities_id` = `glpi_entities`.`id`)
         WHERE
            ('
               . $include .
            ') AND (
               `glpi_plugin_gdprropa_records`.`is_deleted` IN (' . implode(',', $include_deleted) . ')
            )
         ORDER BY
            `glpi_plugin_gdprropa_records`.`name`';

      $records_list = $DB->request($query);

      return $records_list;
   }

   protected function getControllerName() {

      if (isset($this->controller_info->fields['controllername']) && !empty(trim($this->controller_info->fields['controllername']))) {

         return trim($this->controller_info->fields['controllername']);
      } else {

         return __("Controller information not set.", 'gdprropa');
      }

   }

   function generateReport($generator_options, $print_options) {

      $this->preparePrintOptions($print_options);
      $this->preparePDF();

      switch ($generator_options['report_type']) {
         case PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD:
            $record_id = $generator_options['record_id'];
            $record = new PluginGdprropaRecord();
            $record->getFromDB($record_id);
            $entities_id = $record->fields['entities_id'];

            break;
         case PluginGdprropaCreatePDF::REPORT_FOR_ENTITY:
            $entities_id = $generator_options['entities_id'];
            $record = PluginGdprropaCreatePDF::getRecordsForEntity($entities_id, $print_options, true);
            break;
         case PluginGdprropaCreatePDF::REPORT_ALL:
            $entities_id = $_SESSION['glpiactive_entity'];
            $record = PluginGdprropaCreatePDF::getRecordsForEntity($entities_id, $print_options);
            break;
      }

      $this->setEntityAndControllerInfo($entities_id);

      $this->printHeader();
      $this->printCoverPage($generator_options['report_type'], $record, $entities_id);

      if ($record instanceof DBmysqlIterator) {
         foreach ($record as $item) {
            $rec = new PluginGdprropaRecord();
            $rec->getFromDB($item['id']);
            $this->addPageForRecord($rec, $generator_options['report_type']);
         }
      } else if ($record instanceof PluginGdprropaRecord) {
            $rec = new PluginGdprropaRecord();
            $rec->getFromDB($record->fields['id']);
            $this->addPageForRecord($rec, $generator_options['report_type']);
      } else {
         $this->addPageForRecord($record, $generator_options['report_type']);
      }
   }

   protected function printHeader() {

      $header = __("GDPR Record of Processing Activities", 'gdprropa');
      if ($this->print_options['show_print_date_time']) {
         $header .= ",   " . sprintf(__("print date/time: %1s", 'gdprropa'), Html::convDateTime($_SESSION["glpi_currenttime"]));
      }

      $name = $this->getControllerName();

      $this->setHeader($header, $name);
   }

   protected function printActivitiesList($records) {

      if ($records) {

         $display_introduced_in = $this->print_options['show_inherited_from'];
         $col_width = 42 + 50 * (int)!$display_introduced_in;

         $this->writeInternal(
            "<h2>" . __("List of processing activities for which entity deals with personal data", 'gdprropa') . "</h2>", [
               'linebefore' => 8
            ]);

         if (!count($records)) {
            $this->writeInternal(__("There are no activities.", 'gdprropa'), [
               'border' => 1,
               'linebefore' => 1
            ]);
         } else {
            $tbl = '<table border="1" cellpadding="3" cellspacing="0">' .
               '<thead><tr>' .
               '<th width="8%" style="background-color:#323232;color:#FFF;text-align:center;"><h3>' . __("No", 'gdprropa') . '</h3></th>' .
               '<th width="' . $col_width . '%" style="background-color:#323232;color:#FFF;"><h3>' . __("Description of the activity", 'gdprropa') . '</h3></th>';
            if ($display_introduced_in) {
               $tbl .= '<th width="50%" style="background-color:#323232;color:#FFF;"><h3>' . __("Introduced in", 'gdprropa') . '</h3></th>';
            }
            $tbl .= '</tr></thead><tbody>';

            $i = 1;
            foreach ($records as $item) {
               $entity = new Entity();
               $entity->getFromDB($item['entities_id']);
               $tbl .= '<tr>' .
                  '<td width="8%" align="center">' . $i++ . ' </td>' .
                  '<td width="' . $col_width . '%">' . $item['name'] . '</td>';
               if ($display_introduced_in) {
                  $tbl .= '<td width="50%">' . $entity->fields['completename'] . '</td>';
               }
               $tbl .= '</tr>';
            }
            $tbl .= "</tbody></table>";

            $this->writeHtml($tbl);
         }
      }

   }

   protected function printCoverPage($type, $records, $entities_id = -1) {

      $this->pdf->addPage($this->print_options['page_orientation'], 'A4');

      switch ($type) {
         case PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD:
            $this->printPageTitle("<h1><small>" . __("GDPR Record of Processing Activity", 'gdprropa') ."</small><br/>" . $records->fields['name'] . "</h1>");
            $this->printRecordStatusInfo($records);
            break;
         case PluginGdprropaCreatePDF::REPORT_FOR_ENTITY:
            $entity = new Entity();
            $entity->getFromDB($entities_id);
            $this->printPageTitle("<h1><small>" . sprintf(__("GDPR Records of Processing Activity for entity:<br/>%1s", 'gdprropa'), $entity->fields['name']) ."</small><br/>" . '' . "</h1>");
            break;
         case PluginGdprropaCreatePDF::REPORT_ALL:
            $this->printPageTitle("<h1><small>" . __("GDPR Records of Processing Activity", 'gdprropa') ."</small><br/>" . '' . "</h1>");
            break;
      }

      $datas = [];
      $datas[] = $this->prepareControllerInfo();

      $datas[] = $this->preparePersonelInfo('representative', __("Legal representative not set.", 'gdprropa'), __("Legal representative", 'gdprropa'));
      $datas[] = $this->preparePersonelInfo('dpo', __("DPO not set.", 'gdprropa'), __("Data Protection Officer", 'gdprropa'));

      foreach ($datas as $d) {
         $this->write2ColsRow(
            $d['section'], [
               'fillcolor' => [175, 175, 175],
               'fill' => 1,
               'linebefore' => 4,
               'border' => 1,
               'cellwidth' => 50,
               'align' => 'R'
            ],
            $d['value'], [
               'border' => 1
            ]
         );
      }

      switch ($type) {
         case PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD:
            break;
         case PluginGdprropaCreatePDF::REPORT_FOR_ENTITY:
            $this->printActivitiesList($records);
            break;
         case PluginGdprropaCreatePDF::REPORT_ALL:
            $this->printActivitiesList($records);
            break;
      }

      $this->pdf->lastPage();
   }

   protected function printRecordStatusInfo(PluginGdprropaRecord $record) {

      if ($this->print_options['show_is_deleted_header']) {
         if ($record->fields['is_deleted']) {
            $this->writeInternal(
               '<h3>' . __("The record is marked as DELETED", 'gdprropa') . '</h3>', [
                  'fillcolor' => [80, 80, 80],
                  'fill' => 1,
                  'textcolor' => [255, 255, 255],
                  'align' => 'C'
               ]);
         }
      }

      if ($this->print_options['show_status_in_header']) {

         $status = dropdown::getDropdownName('glpi_states', $record->fields['states_id']);
         $this->writeInternal(
            '<h3>' . sprintf(__("Record status: %s", 'gdprropa'), $status) . '</h3>', [
               'fillcolor' => [100, 100, 100],
               'fill' => 1,
               'textcolor' => [255, 255, 255],
               'align' => 'C'
            ]);
      }
   }

   protected function addPageForRecord(PluginGdprropaRecord $record, $reporty_type) {

      $this->pdf->addPage('P', 'A4');

      if ($reporty_type != PluginGdprropaCreatePDF::REPORT_SINGLE_RECORD) {
         $this->printPageTitle("<h1><small>" . __("Sheet for GDPR Record of Processing Activities", 'gdprropa') .":</small><br/>" . $record->fields['name'] . "</h1>");
         $this->printRecordStatusInfo($record);
      }

      /*
      if (!$for_entity || $this->print_options['show_representative_dpo_per_record']) {
         $this->addControllerInfo();
      }
      */
      $this->printRecordInformation($record);

      $this->printLegalBasisActs($record);

      $this->printDataSubjectsCategories($record);

      $this->printDataRetention($record);

      $this->printPersonalDataCategories($record);

      $contracts = 0;
      $contracts += $this->printContracts($record, PluginGdprropaRecord_Contract::CONTRACT_JOINTCONTROLLER, $contracts);
      $contracts += $this->printContracts($record, PluginGdprropaRecord_Contract::CONTRACT_PROCESSOR, $contracts);
      $contracts += $this->printContracts($record, PluginGdprropaRecord_Contract::CONTRACT_THIRDPARTY, $contracts);
      $contracts += $this->printContracts($record, PluginGdprropaRecord_Contract::CONTRACT_INTERNAL, $contracts);
      $contracts += $this->printContracts($record, PluginGdprropaRecord_Contract::CONTRACT_OTHER, $contracts);

      $this->printSoftware($record);

      $this->printSecurityMeasures($record, PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_ORGANIZATION, true);
      $this->printSecurityMeasures($record, PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_PHYSICAL);
      $this->printSecurityMeasures($record, PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_IT);

   }

   protected function printRecordInformation(PluginGdprropaRecord $record) {

      $this->writeInternal(
         '<h2>' . __("Processing Activity information", 'gdprropa') . '</h2>', [
            'linebefore' => 1
         ]);

      $rows = [];
      $rows[] = [
         'section' => __("Name", 'gdprropa'),
         'value' => $record->fields['name']
      ];
      $rows[] = [
         'section' => __("Purpose", 'gdprropa'),
         'value' => $record->fields['content']
      ];
      if (!empty($record->fields['additional_info'])) {
         $rows[] = [
            'section' => __("Additional information", 'gdprropa'),
            'value' => nl2br($record->fields['additional_info'])
         ];
      }
      $status = dropdown::getDropdownName('glpi_states', $record->fields['states_id']);
      $rows[] = [
         'section' => __("Status"),
         'value' => $status
      ];
      $storage_medium = PluginGdprropaRecord::getAllStorageMediumArray();
      $rows[] = [
         'section' => __("Storage medium", 'gdprropa'),
         'value' => $storage_medium[$record->fields['storage_medium']]
      ];
      $rows[] = [
         'section' => __("PIA required", 'gdprropa'),
         'value' => Dropdown::getYesNo($record->fields['pia_required'])
      ];
      if ($record->fields['pia_required']) {
         $pia_status = PluginGdprropaRecord::getAllPiaStatusArray();
         $rows[] = [
            'section' => __("PIA status", 'gdprropa'),
            'value' => $pia_status[$record->fields['pia_status']]
         ];
      }
      if (empty($record->fields['first_entry_date'])) {
         $first_entry_date = Html::convDate($record->fields['first_entry_date']);
      } else {
         $first_entry_date = __("N/A", 'gdprropa');
      }
      $rows[] = [
         'section' => __("Consent required", 'gdprropa'),
         'value' => Dropdown::getYesNo($record->fields['consent_required'])
      ];
      if ($record->fields['consent_required']) {
         $rows[] = [
            'section' => __("Consent storage", 'gdprropa'),
            'value' => nl2br($record->fields['consent_storage'])
         ];
      }
      $rows[] = [
         'section' => __("First entry date", 'gdprropa'),
         'value' => $first_entry_date
      ];

      if (PluginGdprropaCreatePDFBase::isGdprownerPluginActive()) {
         if ($this->print_options['show_record_owner']) {

            $owner_info = PluginGdprownerOwner::getOwnerInfo($record->fields['id'], PluginGdprropaRecord::class);
            $owner = $owner_info['owner_type_name'] . ': ' . $owner_info['owner_name'];

            $rows[] = [
               'section' => __("Owner", 'gdprropa'),
               'value' => $owner,
            ];
         }
      }

      foreach ($rows as $item) {
         $this->write2ColsRow(
            $item['section'], [
               'fillcolor' => [175, 175, 175],
               'fill' => 1,
               'linebefore' => 0,
               'border' => 1,
               'cellwidth' => 50,
               'align' => 'R'
            ],
            $item['value'], [
               'border' => 1
            ]
         );
      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   protected function printLegalBasisActs(PluginGdprropaRecord $record) {

      global $DB;

      $this->writeInternal(
         '<h2>' . PluginGdprropaLegalBasisAct::getTypeName(1) . '</h2>', [
            'linebefore' => 1
         ]);

      $result = $DB->request([
         'FROM' => [PluginGdprropaRecord_LegalBasisAct::getTable(), PluginGdprropaLegalBasisAct::getTable()],
         'WHERE' => [
            PluginGdprropaRecord_LegalBasisAct::getTable().'.`plugin_gdprropa_records_id`' => $record->fields['id'],
            PluginGdprropaRecord_LegalBasisAct::getTable().'.`plugin_gdprropa_legalbasisacts_id`' => '`' . PluginGdprropaLegalBasisAct::getTable().'`.`id`'
          ],
         'ORDER' => ['type'],
      ], "", true);

      if (!count($result)) {

         $this->writeInternal(
            __("No legal basis act(s).", 'gdprropa'), [
               'border' => 1,
               'linebefore' => 1
            ]);

      } else {

         if ($this->print_options['show_inherited_from']) {
            if ($this->print_options['show_comments']) {
               $cols_width = ['20', '15', '25', '25', '15'];
            } else {
               $cols_width = ['20', '15', '30', '35', '0'];
            }
         } else {
            if ($this->print_options['show_comments']) {
               $cols_width = ['20', '20', '0', '40', '20'];
            } else {
               $cols_width = ['20', '20', '0', '60', '0'];
            }
         }
         $tbl =
            '<table border="1" cellpadding="3" cellspacing="0">' .
            '<thead><tr>' .
            '<th width="'. $cols_width[0] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Name", 'gdprropa') . '</h4></th>' .
            '<th width="'. $cols_width[1] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Type", 'gdprropa') . '</h4></th>';
         if ($this->print_options['show_inherited_from']) {
            $tbl .=
               '<th width="'. $cols_width[2] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Introduced in", 'gdprropa') . '</h4></th>';
         }
         $tbl .=
            '<th width="'. $cols_width[3] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Content", 'gdprropa') . '</h4></th>';
         if ($this->print_options['show_comments']) {
            $tbl .=
               '<th width="'. $cols_width[4] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Comment") . '</h4></th>';
         }
         $tbl .=
            '</tr></thead>' .
            '<tbody>';

         $type = PluginGdprropaLegalBasisAct::getAllTypesArray();
         while ($item = $result->next()) {
            $tbl .=
               '<tr>' .
               '<td width="'. $cols_width[0] . '%">' . $item['name'] . '</td>' .
               '<td width="'. $cols_width[1] . '%">' . $type[$item['type']] . '</td>';
            if ($this->print_options['show_inherited_from']) {
               $tbl .=
                  '<td width="'. $cols_width[2] . '%">' . Dropdown::getDropdownName(Entity::getTable(), $item['entities_id']) . '</td>';
            }
            $tbl .=
               '<td width="'. $cols_width[3] . '%">' . $item['content'] . '</td>';
            if ($this->print_options['show_comments']) {
               $tbl .=
                  '<td width="'. $cols_width[4] . '%">' . nl2br($item['comment']) . '</td>';
            }
            $tbl .=
               '</tr>';
         }

         $tbl .=
            '</tbody>' .
            '</table>';

         $this->writeHtml($tbl);

      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   protected function printDataSubjectsCategories(PluginGdprropaRecord $record) {

      $data_subjects = (new PluginGdprropaRecord_DataSubjectsCategory())
         ->find([PluginGdprropaRecord::getForeignKeyField() => $record->fields['id']]);

      $this->writeInternal(
         '<h2>' . PluginGdprropaRecord_DataSubjectsCategory::getTypeName(3) . '</h2>', [
            'linebefore' => 1
         ]);

      if (!count($data_subjects)) {

         $this->writeInternal(__("No data subjects category/ies assigned.", 'gdprropa'), [
               'border' => 1,
               'linebefore' => 1
            ]);

      } else {

         if ($this->print_options['show_inherited_from']) {
            if ($this->print_options['show_comments']) {
               $cols_width = ['30', '50', '20'];
            } else {
               $cols_width = ['40', '60', '0'];
            }
         } else {
            if ($this->print_options['show_comments']) {
               $cols_width = ['40', '0', '60'];
            } else {
               $cols_width = ['100', '0', '0'];
            }
         }

         $tbl =
            '<table border="1" cellpadding="3" cellspacing="0">' .
            '<thead><tr>' .
            '<th width="' . $cols_width[0]. '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Category") . '</h4></th>';
         if ($this->print_options['show_inherited_from']) {
            $tbl .=
               '<th width="' . $cols_width[1] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Introduced in", 'gdprropa') . '</h4></th>';
         }
         if ($this->print_options['show_comments']) {
            $tbl .=
               '<th width="'. $cols_width[2] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Comment") . '</h4></th>';
         }
         $tbl .=
            '</tr></thead><tbody>';

         foreach ($data_subjects as $item) {

            $dsc = new PluginGdprropaDataSubjectsCategory();
            $dsc->getFromDB($item['plugin_gdprropa_datasubjectscategories_id']);

            $tbl .=
               '<tr>' .
               '<td width="'. $cols_width[0] . '%">' . $dsc->fields['name'] . '</td>';
            if ($this->print_options['show_inherited_from']) {
               $tbl .=
                  '<td width="' . $cols_width[1] . '%">' . Dropdown::getDropdownName(Entity::getTable(), $dsc->fields['entities_id']) . '</td>';
            }
            if ($this->print_options['show_comments']) {
               $tbl .=
                  '<td width="'. $cols_width[2] . '%">' . nl2br($dsc->fields['comment']) . '</td>';
            }
            $tbl .=
               '</tr>';
         }

         $tbl .= '</tbody></table>';

         $this->pdf->SetTextColor(0, 0, 0);
         $this->pdf->writeHTML($tbl, true, false, false, true, '');

      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   protected function printDataRetention(PluginGdprropaRecord $record) {

      $retention = (new PluginGdprropaRecord_Retention())
         ->find([PluginGdprropaRecord::getForeignKeyField() => $record->fields['id']]);

      $this->writeInternal('<h2>' . PluginGdprropaRecord_Retention::getTypeName(0) . '</h2>', [
            'linebefore' => 1
         ]);

      if (!count($retention)) {
         $this->writeInternal(__("Data retention not set.", 'gdprropa'), [
               'border' => 1,
               'linebefore' => 1,
            ]);
      } else {
         foreach ($retention as $item) {

            $type = PluginGdprropaRecord_Retention::getAllTypesArray();

            $tbl =
               '<table border="1" cellpadding="3" cellspacing="0">' .
               '<tbody><tr>' .
               '<td width="25%" style="background-color:#AFAFAF;color:#FFF;">' . __("Retention regulated by", 'gdprropa') . '</td>' .
               '<td width="75%"><strong>' . $type[$item['type']] . '</strong></td>';

            switch ($item['type']) {
               case PluginGdprropaRecord_Retention::RETENTION_TYPE_CONTRACT:

                  $contract = new Contract();
                  $contract->getFromDB($item['contracts_id']);

                  $period = '';
                  if (!$item['contract_until_is_valid']) {
                     $scale = PluginGdprropaRecord_Retention::getRetentionPeriodScales($item['contract_retention_scale'], $item['contract_retention_value']);
                     $period = $item['contract_retention_value'] . ' ' . $scale;

                     if ($item['contract_after_end_of']) {
                        $period = sprintf(__("Data retention: %1\$s after contract is terminated", 'gdprropa'), $period);
                     }
                  } else {
                     $period = __("Until contract is valid", 'gdprropa');
                  }

                  $name = $contract->fields['name'];
                  if ($name == null) {
                     $name = ' ';
                  }
                  $num = $contract->fields['num'];
                  if ($num == null || empty($num)) {
                     $num = '';
                  } else {
                     $num = ' ' . $num;
                  }

                  $s_names = PluginGdprropaRecord_Contract::getSuppliersNamesNoIds($item['contracts_id'], ', ');
                  if (empty($s_names)) {
                     $s_names = __("N/A", 'gdprropa');
                  }

                  $c_name = trim(sprintf(__("Contract name/number: %1\$s %2\$s", 'gdprropa'), $name, $num));
                  $s_names = trim($s_names);
                  $begin_date = trim(sprintf(__("Begin date: %1\$s", 'gdprropa'), Html::convDate($contract->fields['begin_date'])));
                  $comment = trim(sprintf(__("Comment: %1\$s", 'gdprropa'), $contract->fields['comment']));
                  $period = trim($period);
                  $comment = trim($comment);

                  $tbl .=
                     '</tr><tr>' .
                     '<td width="25%" style="background-color:#AFAFAF;color:#FFF;">' . __("Contract info", 'gdprropa') . '</td>' .
                     '<td width="75%">' .
                     '<ul>' .
                     '<li><strong>' . $c_name . '</strong></li>' .
                     '<li>' . __("Supplier", 'gdprropa') . ': ' . $s_names . '</li>' .
                     '<li>' . $begin_date . '</li>' .
                     '<li>' . $period . '</li>';
                  if ($this->print_options['show_comments']) {
                     $tbl .=
                        '<li>' . nl2br($comment) . '</li>';
                  }
                  $tbl .=
                     '</ul>' .
                     '</td>';

                  break;
               case PluginGdprropaRecord_Retention::RETENTION_TYPE_LEGALBASISACT:

                  $legal_basis = new PluginGdprropaLegalBasisAct();
                  $legal_basis->getFromDB($item['plugin_gdprropa_legalbasisacts_id']);

                  $name = __("N/A", 'gdprropa');
                  if (isset($legal_basis->fields['id'])) {
                     $name = $legal_basis->fields['name'];
                  }
                  $tbl .=
                     '</tr><tr>' .
                     '<td width="25%" style="background-color:#AFAFAF;color:#FFF;">' . '' . '</td>' .
                     '<td width="75%">' . sprintf(__("Name: %1\$s", 'gdprropa'), $name) . '</td>';

                  break;
               case PluginGdprropaRecord_Retention::RETENTION_TYPE_NONE:
                  $tbl .=
                     '</tr><tr>' .
                     '<td width="25%" style="background-color:#AFAFAF;color:#FFF;">' . '' . '</td>' .
                     '<td width="75%">' . __("Data retention is not required", 'gdprropa') . '</td>';
                  break;
               case PluginGdprropaRecord_Retention::RETENTION_TYPE_OTHER:
                  break;
            }

            $tbl .=
               '</tr><tr>' .
               '<td width="25%" style="background-color:#AFAFAF;color:#FFF;">' . __("Additional information", 'gdprropa') . '</td>' .
               '<td width="75%">' . nl2br($item['additional_info']) . '</td>' .
               '</tr></tbody>' .
               '</table>';

         }

         $this->pdf->writeHTML($tbl, true, false, false, true, '');

      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   protected function printContracts(PluginGdprropaRecord $record, $type, $print_header) {

      $get_expired = $this->print_options['show_expired_contracts'];
      $iterator = PluginGdprropaRecord_Contract::getContracts($record, $type, $get_expired);
      $number = count($iterator);

      if (!$number && !$this->print_options['show_contracs_types_header_if_empty']) {
         $this->insertNewPageIfBottomSpaceLeft();

         return $number;
      }

      if (!$print_header) {
         $this->writeInternal('<h2>' . __("Contracts related to processing activity", 'gdprropa') . '</h2>', [
            'linebefore' => 1
         ]);
      }

      $subtitle = PluginGdprropaRecord_Contract::getContractTypeStr($type);

      $this->writeInternal('<h3>' . $subtitle . '</h3>', [
         'linebefore' => 1
      ]);

      if (!$number) {
         $this->writeInternal(__("None.", 'gdprropa'), [
            'border' => 1,
            'linebefore' => 1
         ]);
      } else {

         if ($this->print_options['show_comments']) {
            $cols_width = ['13', '23', '13', '18', '10', '23'];
         } else {
            $cols_width = ['18', '28', '18', '28', '10', '0'];
         }

         $tbl = '<table border="1" cellpadding="3" cellspacing="0">
            <thead><tr>
            <th width="' . $cols_width[0] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Supplier") . '</h4></th>
            <th width="' . $cols_width[1] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Location") . '</h4></th>
            <th width="' . $cols_width[2] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Contact") . '</h4></th>
            <th width="' . $cols_width[3] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Contract info", 'gdprropa') . '</h4></th>
            <th width="' . $cols_width[4] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Expiry", 'gdprropa') . '</h4></th>';
         if ($this->print_options['show_comments']) {
            $tbl .=
               '<th width="' . $cols_width[5] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Comment") . '</h4></th>';
         }
         $tbl .=
            '</tr></thead><tbody>';

         while ($data = $iterator->next()) {

            $supplier_name = '';
            $supplier_name .= $data['suppliers_name'];

            $location = '';
            if ($data['suppliers_address']) {
               $location .= $data['suppliers_address'];
            }
            if ($data['suppliers_postcode']) {
               $location .= ', ' . $data['suppliers_postcode'];
            }
            if ($data['suppliers_town']) {
               $location .= ' ' . $data['suppliers_town'];
            }
            if ($data['suppliers_state']) {
               $location .= ', ' . $data['suppliers_state'];
            }
            if ($data['suppliers_country']) {
               $location .= ', ' . $data['suppliers_country'];
            }

            $contact = '';
            if ($data['suppliers_phonenumber']) {
               $contact .= $data['suppliers_phonenumber'];
            }
            if ($data['suppliers_fax']) {
               $contact .= ' ' . __("fax") . ': ' . $data['suppliers_fax'];
            }
            if ($data['suppliers_email']) {
               $contact .= ' ' . __("email") . ': ' . $data['suppliers_email'];
            }

            $contract_info = '';
            if ($data['contracts_name']) {
               $contract_info .= $data['contracts_name'];
            }
            if ($data['contracts_num']) {
               $contract_info .= ' ' . $data['contracts_num'];
            }
            if ($data['contracts_begin_date']) {
               $contract_info .= '<br>' . __("Begin date:", 'gdprropa') . '' . $data['contracts_begin_date'];
            }

            $expiry = Infocom::getWarrantyExpir($data['contracts_begin_date'], $data['contracts_duration'], $data['contracts_notice'], false);
            $expiry_bkg = '#FFFFFF';
            if (new DateTime($expiry) < new DateTime()) {
                $expiry_bkg = '#FF0000';
            }

            $comments = '';
            if ($data['contracts_comment']) {
               $comments = $data['contracts_comment'];
            }

            $tbl .= '<tr>
               <td width="' . $cols_width[0] . '%">' . $supplier_name . '</td>
               <td width="' . $cols_width[1] . '%">' . $location . '</td>
               <td width="' . $cols_width[2] . '%">' . $contact . '</td>
               <td width="' . $cols_width[3] . '%">' . $contract_info . '</td>
               <td width="' . $cols_width[4] . '%" style="background-color:' . $expiry_bkg . '">' . $expiry . '</td>';
            if ($this->print_options['show_comments']) {
               $tbl .=
                  '<td width="' . $cols_width[5] . '%">' . nl2br($comments) . '</td>';
            }
            $tbl .=
               '</tr>';

         }

         $tbl .= '</tbody></table>';

         $this->pdf->SetTextColor(0, 0, 0);
         $this->pdf->writeHTML($tbl, true, false, false, true, '');

      }

      $this->insertNewPageIfBottomSpaceLeft();

      return $number;

   }

   protected function printPersonalDataCategories(PluginGdprropaRecord $record) {

      $this->writeInternal(
         '<h2>' . PluginGdprropaRecord_PersonalDataCategory::getTypeName(1) . '</h2>', [
            'linebefore' => 1
         ]);

      $pdc_list = (new PluginGdprropaRecord_PersonalDataCategory())
         ->find([PluginGdprropaRecord::getForeignKeyField() => $record->fields['id']]);

      if (!count($pdc_list)) {
         $this->writeInternal(__("None assigned.", 'gdprropa'), [
            'border' => 1,
            'linebefore' => 1
         ]);
      } else {

         if ($this->print_options['show_inherited_from']) {
            if ($this->print_options['show_comments']) {
               $cols_width = ['35', '10', '35', '20'];
            } else {
               $cols_width = ['50', '10', '40', '0'];
            }
         } else {
            if ($this->print_options['show_comments']) {
               $cols_width = ['60', '10', '0', '30'];
            } else {
               $cols_width = ['90', '10', '0', '0'];
            }
         }

         $tbl = '<table border="1" cellpadding="3" cellspacing="0">';
         $tbl .=
            '<thead><tr>' .
            '<th width="' . $cols_width[0]. '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Complete name", 'gdprropa') . '</h4></th>' .
            '<th width="' . $cols_width[1]. '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Special category", 'gdprropa') . '</h4></th>';
         if ($this->print_options['show_inherited_from']) {
            $tbl .=
               '<th width="' . $cols_width[2] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Introduced in", 'gdprropa') . '</h4></th>';
         }
         if ($this->print_options['show_comments']) {
            $tbl .=
               '<th width="' . $cols_width[3]. '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Comment") . '</h4></th>';
         }
         $tbl .=
            '</tr></thead><tbody>';

         foreach ($pdc_list as $pdc_item) {

            if ($this->print_options['show_full_personaldatacategorylist']) {
               $sons = getSonsOf(PluginGdprropaPersonalDataCategory::getTable(), $pdc_item['plugin_gdprropa_personaldatacategories_id']);
            } else {
               $sons = [];
               $sons[] = $pdc_item['plugin_gdprropa_personaldatacategories_id'];
            }

            foreach ($sons as $son_item) {
               $pdc = new PluginGdprropaPersonalDataCategory();
               $pdc->getFromDB($son_item);

               $tbl .=
                  '<tr> ' .
                  '<td width="' . $cols_width[0]. '%">' . $pdc->fields['completename'] . '</td>' .
                  '<td width="' . $cols_width[1]. '%" style="text-align:center;">' . Dropdown::getYesNo($pdc->fields['is_special_category']) . '</td>';
               if ($this->print_options['show_inherited_from']) {
                  $tbl .=
                     '<td width="' . $cols_width[2] . '%">' . Dropdown::getDropdownName(Entity::getTable(), $pdc->fields['entities_id']) . '</td>';
               }
               if ($this->print_options['show_comments']) {
                  $tbl .=
                     '<td width="' . $cols_width[3]. '%">' . nl2br($pdc->fields['comment']) . '</td>';
               }
               $tbl .=
                  '</tr>';
            }
         }

         $tbl .= '</tbody></table>';

         $this->pdf->SetTextColor(0, 0, 0);
         $this->pdf->writeHTML($tbl, true, false, false, true, '');

      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   protected function printSoftware(PluginGdprropaRecord $record) {

      global $DB;

      if (($record->fields['storage_medium'] == PluginGdprropaRecord::STORAGE_MEDIUM_PAPER_ONLY)
         || ($record->fields['storage_medium'] == PluginGdprropaRecord::STORAGE_MEDIUM_UNDEFINED)) {
         return;
      }

      $query = '
         SELECT
            glpi_softwares.id AS software_id,
             glpi_softwares.name AS software_name,
             glpi_softwares.comment AS software_comment,
             glpi_softwares.entities_id AS software_entities_id,
             glpi_softwarecategories.name AS software_category_name,
             glpi_softwarecategories.id AS softwarecategories_id,
             glpi_softwarecategories.completename AS sotwarecategories_completename,
             glpi_softwarecategories.comment AS softwarecategories_comment,
             glpi_manufacturers.id AS manufacturer_id,
             glpi_manufacturers.name AS manufacturer_name,
             glpi_manufacturers.comment AS manufacturer_comment
         FROM
            glpi_plugin_gdprropa_records_softwares
         LEFT JOIN
            glpi_softwares
             ON (glpi_plugin_gdprropa_records_softwares.softwares_id = glpi_softwares.id)
         LEFT JOIN
            glpi_manufacturers
            ON (glpi_softwares.manufacturers_id = glpi_manufacturers.id)
         LEFT JOIN
            glpi_softwarecategories
             ON (glpi_softwares.softwarecategories_id = glpi_softwarecategories.id)
         WHERE
            glpi_plugin_gdprropa_records_softwares.plugin_gdprropa_records_id = ' . $record->fields['id'] . ' AND
             glpi_softwares.is_deleted = 0
      ';
      $software_list = $DB->request($query);

      $this->writeInternal('<h2>' . __("Software", 'gdprropa') . '</h2>', [
         'linebefore' => 1
         ]);

      if (!count($software_list)) {
         $this->writeInternal(__("Software not assigned.", 'gdprropa'), ['border' => 1]);
      } else {
         if ($this->print_options['show_inherited_from']) {
            if ($this->print_options['show_assets_owners']) {
               if ($this->print_options['show_comments']) {
                  $cols_width = [26, 12, 12, 20, 15, 15];
               } else {
                  $cols_width = [26, 12, 12, 30, 20, 0];
               }
            } else {
               if ($this->print_options['show_comments']) {
                  $cols_width = [25, 12, 13, 35, 0, 15];
               } else {
                  $cols_width = [25, 15, 15, 45, 0, 0];
               }
            }
         } else {
            if ($this->print_options['show_assets_owners']) {
               if ($this->print_options['show_comments']) {
                  $cols_width = [30, 20, 20, 0, 15, 15];
               } else {
                  $cols_width = [40, 20, 20, 0, 20, 0];
               }
            } else {
               if ($this->print_options['show_comments']) {
                  $cols_width = [40, 20, 20, 0, 0, 20];
               } else {
                  $cols_width = [60, 20, 20, 0, 0, 0];
               }
            }
         }

         $tbl =
            '<table border="1" cellpadding="3" cellspacing="0">' .
            '<thead><tr>' .
            '<th width="' . $cols_width[0] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Name", 'gdprropa') . '</h4></th>' .
            '<th width="' . $cols_width[1] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Type", 'gdprropa') . '</h4></th>' .
            '<th width="' . $cols_width[2] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Publisher", 'gdprropa') . '</h4></th>';

         if ($this->print_options['show_inherited_from']) {
            $tbl .=
               '<th width="' . $cols_width[3] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Introduced in", 'gdprropa') . '</h4></th>';
         }
         if ($this->print_options['show_assets_owners']) {
            $tbl .=
               '<th width="' . $cols_width[4] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Owner", 'gdprropa') . '</h4></th>';
         }
         if ($this->print_options['show_comments']) {
            $tbl .=
               '<th width="' . $cols_width[5] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Comment") . '</h4></th>';
         }
         $tbl .= '</tr></thead><tbody>';

         foreach ($software_list as $item) {
            $tbl .=
               '<tr>' .
               '<td width="' . $cols_width[0] . '%">' . $item['software_name'] . '</td>' .
               '<td width="' . $cols_width[1] . '%">' . $item['software_category_name'] . '</td>' .
               '<td width="' . $cols_width[2] . '%">' . $item['manufacturer_name'] . '</td>';

            if ($this->print_options['show_inherited_from']) {
               $tbl .=
                  '<td width="'. $cols_width[3] . '%">' . Dropdown::getDropdownName(Entity::getTable(), $item['software_entities_id']) . '</td>';
            }
            if ($this->print_options['show_assets_owners']) {
               $owner_info = PluginGdprownerOwner::getOwnerInfo($item['software_id'], Software::class);
               if (empty($owner_info)) {
                  $owner = __("Not assigned", 'gdprropa');
               } else {
                  $owner = $owner_info['owner_type_name'] . ': ' . $owner_info['owner_name'];
               }
               $tbl .=
                  '<td width="' . $cols_width[4] . '%">' . $owner . '</td>';

            }
            if ($this->print_options['show_comments']) {
               $tbl .=
                  '<td width="' . $cols_width[5] . '%">' . $item['software_comment'] . '</td>';
            }
            $tbl .= '</tr>';
         }

         $tbl .= "</tbody></table>";

         $this->writeHtml($tbl);
      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   protected function printSecurityMeasures(PluginGdprropaRecord $record, $type, $header = false) {

      global $DB;

      $this->insertNewPageIfBottomSpaceLeft(30);

      $types = PluginGdprropaSecurityMeasure::getAllTypesArray();
      $sub_caption = $types[$type];

      if ($header) {
         $this->writeInternal(
            '<h2>' . PluginGdprropaSecurityMeasure::getTypeName(2) . '</h2>', [
               'linebefore' => 1
            ]);
      }

      $this->writeInternal(
         '<h3>' . $sub_caption . '</h3>', [
            'linebefore' => 1
         ]);

      $result = $DB->request([
         'FROM' => [PluginGdprropaRecord_SecurityMeasure::getTable(), PluginGdprropaSecurityMeasure::getTable()],
            'FKEY' => [
               PluginGdprropaRecord_SecurityMeasure::getTable() => 'plugin_gdprropa_securitymeasures_id',
               PluginGdprropaSecurityMeasure::getTable() => 'id'
            ],
            'ORDER' => ['name'],
            'AND' => [
               'plugin_gdprropa_records_id' => $record->fields['id'],
               'type' => $type
            ]
      ]);

      if (!count($result)) {
         $this->writeInternal(
            __("None implemented.", 'gdprropa'), [
               'border' => 1,
               'linebefore' => 1
            ]);
      } else {

         if ($this->print_options['show_inherited_from']) {
            if ($this->print_options['show_comments']) {
               $cols_width = [30, 24, 26, 20];
            } else {
               $cols_width = [40, 30, 30, 0];
            }
         } else {
            if ($this->print_options['show_comments']) {
               $cols_width = [40, 30, 0, 30];
            } else {
               $cols_width = [40, 60, 0, 0];
            }
         }

         $tbl =
            '<table border="1" cellpadding="3" cellspacing="0">' .
            '<thead><tr>' .
            '<th width="' . $cols_width[0] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Name") . '</h4></th>' .
            '<th width="' . $cols_width[1] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Description") . '</h4></th>';

         if ($this->print_options['show_inherited_from']) {
            $tbl .=
               '<th width="' . $cols_width[2] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Introduced in", 'gdprropa') . '</h4></th>';
         }

         if ($this->print_options['show_comments']) {
            $tbl .=
               '<th width="' . $cols_width[3] . '%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __("Comment") . '</h4></th>';
         }
         $tbl .=
            '</tr></thead><tbody>';

         while ($item = $result->next()) {
            $tbl .=
               '<tr>' .
               '<td width="' . $cols_width[0] . '%">' . $item['name'] . '</td>' .
               '<td width="' . $cols_width[1] . '%">' . $item['content'] . '</td>';

            if ($this->print_options['show_inherited_from']) {
               $tbl .=
                  '<td width="'. $cols_width[2] . '%">' . Dropdown::getDropdownName(Entity::getTable(), $item['entities_id']) . '</td>';
            }
            if ($this->print_options['show_comments']) {
               $tbl .=
                  '<td width="' . $cols_width[3] . '%">' . nl2br($item['comment']) . '</td>';
            }
            $tbl .=
               '</tr>';
         }

         $tbl .=
            '</tbody></table>';

         $this->pdf->SetTextColor(0, 0, 0);
         $this->pdf->writeHTML($tbl, true, false, false, true, '');

      }

      $this->insertNewPageIfBottomSpaceLeft();

   }

   static function preparePrintOptionsFromForm($config = []) {

      $mod_config = self::getDefaultPrintOptions();

      if (is_array($config) && count($config)) {
         foreach ($config as $key => $val) {
            if (is_array($val) && count($val)) {
               foreach ($val as $key2 => $val2) {
                  $mod_config[$key][$key2] = $val2;
               }
            } else {
               $mod_config[$key] = $val;
            }
         }
      }

      return $mod_config;
   }

   static function getDefaultPrintOptions() {

      $opt = self::$default_print_options;

      if (PluginGdprropaCreatePDFBase::isGdprownerPluginActive()) {
         $opt['show_assets_owners'] = 1;
      } else {
         $opt['show_assets_owners'] = 0;
      }
      $opt['show_record_owner'] = $opt['show_assets_owners'];

      return $opt;
   }
}
