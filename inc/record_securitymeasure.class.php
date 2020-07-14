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

class PluginGdprropaRecord_SecurityMeasure extends CommonDBRelation {

   static public $itemtype_1 = 'PluginGdprropaRecord';
   static public $items_id_1 = 'plugin_gdprropa_records_id';
   static public $itemtype_2 = 'PluginGdprropaSecurityMeasure';
   static public $items_id_2 = 'plugin_gdprropa_securitymeasures_id';

   static function getTypeName($nb = 0) {

      return _n("Security Measure", "Security Measures", $nb, 'gdprropa');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$item->canView()) {
         return false;
      }

      switch ($item->getType()) {
         case PluginGdprropaRecord::class :

            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = self::countForItem($item);
            }

            return self::createTabEntry(PluginGdprropaRecord_SecurityMeasure::getTypeName($nb), $nb);
      }

      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case PluginGdprropaRecord::class :
            self::showForRecord($item, $withtemplate);
            break;
      }

      return true;
   }

   static function showForRecord(PluginGdprropaRecord $record, $withtemplate = 0) {

      $id = $record->fields['id'];
      if (!$record->can($id, READ)) {
         return false;
      }

      $canedit = $record->can($id, UPDATE);
      $rand = mt_rand(1, mt_getrandmax());

      $iterator = self::getListForItem($record);
      $number = count($iterator);

      $items_list = [];
      $used = [];
      while ($data = $iterator->next()) {
         $items_list[$data['id']] = $data;
         $used[$data['id']] = $data['id'];
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='ticketitem_form$rand' id='ticketitem_form$rand' method='post'
            action='" . Toolbox::getItemTypeFormURL(__class__) . "'>";
         echo "<input type='hidden' name='plugin_gdprropa_records_id' value='$id' />";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th>" . __("Add Security Measure", 'gdprropa') . "</th></tr>";
         echo "<tr class='tab_bg_3'><td><center><strong>";
         echo __("GDPR Article 30 1g", 'gdprropa');
         echo "</strong></center></td></tr>";
         echo "<tr class='tab_bg_1'><td width='80%' class='center'>";

         PluginGdprropaSecurityMeasure::dropdown([
            'addicon'  => PluginGdprropaSecurityMeasure::canCreate(),
            'name' => 'plugin_gdprropa_securitymeasures_id',
            'entity' => $record->fields['entities_id'],
            'entity_sons' => false,
            'used' => $used,
         ]);
         echo "</td></tr><tr><td width='20%' class='center'>";
         echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      if ($iterator) {

         echo "<div class='spaced'>";
         if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __class__ . $rand);
            $massive_action_params = ['container' => 'mass' . __class__ . $rand,
               'num_displayed' => min($_SESSION['glpilist_limit'], $number)];
            Html::showMassiveActions($massive_action_params);
         }
         echo "<table class='tab_cadre_fixehov'>";

         $header_begin = "<tr>";
         $header_top = '';
         $header_bottom = '';
         $header_end = '';

         if ($canedit && $number) {

            $header_begin   .= "<th width='10'>";
            $header_top     .= Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
            $header_bottom  .= Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
            $header_end     .= "</th>";
         }

         $header_end .= "<th>" . __("Name") . "</th>";
         $header_end .= "<th>" . __("Type", 'gdprropa') . "</th>";
         $header_end .= "<th>" . __("Introduced in", 'gdprropa') . "</th>";
         $header_end .= "<th>" . __("Comment") . "</th>";
         $header_end .= "</tr>";

         echo $header_begin . $header_top . $header_end;

         foreach ($items_list as $data) {

            echo "<tr class='tab_bg_1'>";

            if ($canedit && $number) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__class__, $data['linkid']);
               echo "</td>";
            }

            $link = $data['name'];
            if ($_SESSION['glpiis_ids_visible'] || empty($data['name'])) {
               $link = sprintf(__("%1\$s (%2\$s)"), $link, $data['id']);
            }
            $name = "<a href=\"" . PluginGdprropaSecurityMeasure::getFormURLWithID($data['id']) . "\">" . $link . "</a>";

            echo "<td class='left" . (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
            echo ">" . $name . "</td>";

            $types = PluginGdprropaSecurityMeasure::getAllTypesArray();

            echo "<td class='center'>" . $types[$data['type']] . " </td>";

            echo "<td class='left'>";
            echo Dropdown::getDropdownName(
               Entity::getTable(),
               $data['entities_id']);
            echo "</td>";

            echo "<td class='center'>" . $data['comment'] . "</td>";
            echo "</tr>";
         }

         if ($iterator->count() > 10) {
            echo $header_begin . $header_bottom . $header_end;
         }
         echo "</table>";

         if ($canedit && $number) {
            $massive_action_params['ontop'] = false;
            Html::showMassiveActions($massive_action_params);
            Html::closeForm();
         }

         echo "</div>";
      }
   }

   static function getListForItem(CommonDBTM $item) {

      global $DB;

      $params = static::getListForItemParams($item, true);
      $iterator = $DB->request($params);

      return $iterator;
   }

   function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';

      return $forbidden;
   }

   static function rawSearchOptionsToAdd() {

      $tab = [];

      $tab[] = [
         'id' => 'securitymeasure',
         'name' => PluginGdprropaSecurityMeasure::getTypeName(0)
      ];

      $tab[] = [
         'id' => '51',
         'table' => PluginGdprropaSecurityMeasure::getTable(),
         'field' => 'name',
         'name' => __("Name"),
         'forcegroupby' => true,
         'massiveaction' => false,
         'datatype' => 'dropdown',
         'searchtype' => ['equals', 'notequals'],
         'joinparams' => [
            'beforejoin' => [
               'table' => self::getTable(),
               'joinparams' => [
                  'jointype' => 'child'
               ]
            ]
         ]
      ];

      return $tab;
   }

}
