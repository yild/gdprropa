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

class PluginGdprropaProfile extends Profile {

   static $rightname = "profile";

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == Profile::class) {
         if ($item->getField('id')
            && ($item->getField('interface') != 'helpdesk')) {
            return PluginGdprropaRecord::getTypeName(2);
         }
      }

      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == Profile::class) {

         $ID = $item->getID();

         $prof = new self();
         $prof->showForm($ID);
      }

      return true;
   }

   public function showForm($ID, $options = []) {

      $profile = new Profile();

      if (($can_update = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))) {
          echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $profile->getFromDB($ID);
      if ($profile->getField('interface') == 'central') {

         $rights = $this->getAllRights();
         $profile->displayRightsChoiceMatrix($rights, [
            'canedit' => $can_update,
            'default_class' => 'tab_bg_2',
            'title' => __("General")
         ]);

      }

      if ($can_update) {

         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();

      }
   }

   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

      $profileRight = new ProfileRight();
      $dbu          = new DbUtils();
      foreach ($rights as $right => $value) {
         if ($dbu->countElementsInTable('glpi_profilerights', [
            'profiles_id' => $profiles_id,
             'name' => $right
             ])
             && $drop_existing) {

            $profileRight->deleteByCriteria([
               'profiles_id' => $profiles_id,
               'name' => $right]);
         }

         if (!$dbu->countElementsInTable('glpi_profilerights', [
            'profiles_id' => $profiles_id,
             'name' => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   static function createFirstAccess($ID) {

      self::addDefaultProfileInfos($ID, [
         'plugin_gdprropa_record' => CREATE | READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE,
         'plugin_gdprropa_legalbasisact' => CREATE | READ | UPDATE | DELETE | PURGE,
         'plugin_gdprropa_securitymeasure' => CREATE | READ | UPDATE | DELETE | PURGE,
         'plugin_gdprropa_datasubjectscategory' => CREATE | READ | UPDATE | DELETE | PURGE,
         'plugin_gdprropa_controllerinfo' => CREATE | READ | UPDATE,
         'plugin_gdprropa_personaldatacategory' => CREATE | READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE,
         'plugin_gdprropa_createpdf' => CREATE,
      ], true);
   }

   static function getAllRights($all = false) {

      $rights = [
         [
            'itemtype' => PluginGdprropaRecord::class,
            'label' => PluginGdprropaRecord::getTypeName(2),
            'field' => PluginGdprropaRecord::$rightname,
            'rights' => [
               CREATE => __("Create"),
               READ => __("Read"),
               UPDATE => __("Update"),
               DELETE => __("Delete"),
               PURGE => __("Delete permanently"),
               READNOTE => __("Read notes"),
               UPDATENOTE => __("Update notes"),
            ]
         ],
         [
            'itemtype' => PluginGdprropaLegalBasisAct::class,
            'label' => PluginGdprropaLegalBasisAct::getTypeName(2),
            'field' => PluginGdprropaLegalBasisAct::$rightname,
            'rights' => [
               CREATE => __("Create"),
               READ => __("Read"),
               UPDATE => __("Update"),
               DELETE => __("Delete"),
               PURGE => __("Delete permanently")
            ]
         ],
         [
            'itemtype' => PluginGdprropaSecurityMeasure::class,
            'label' => PluginGdprropaSecurityMeasure::getTypeName(2),
            'field' => PluginGdprropaSecurityMeasure::$rightname,
            'rights' => [
               CREATE => __("Create"),
               READ => __("Read"),
               UPDATE => __("Update"),
               DELETE => __("Delete"),
               PURGE => __("Delete permanently"),
            ]
         ],
         [
            'itemtype' => PluginGdprropaDataSubjectsCategory::class,
            'label' => PluginGdprropaDataSubjectsCategory::getTypeName(2),
            'field' => PluginGdprropaDataSubjectsCategory::$rightname,
            'rights' => [
               CREATE => __("Create"),
               READ => __("Read"),
               UPDATE => __("Update"),
               DELETE => __("Delete"),
               PURGE => __("Delete permanently"),
            ]
         ],
         [
            'itemtype' => PluginGdprropaControllerInfo::class,
            'label' => PluginGdprropaControllerInfo::getTypeName(2),
            'field' => PluginGdprropaControllerInfo::$rightname,
            'rights' => [
               CREATE => __("Create"),
               READ => __("Read"),
               UPDATE => __("Update")
            ]
         ],
         [
            'itemtype' => PluginGdprropaPersonalDataCategory::class,
            'label' => PluginGdprropaPersonalDataCategory::getTypeName(2),
            'field' => PluginGdprropaPersonalDataCategory::$rightname,
            'rights' => [
               CREATE => __("Create"),
               READ => __("Read"),
               UPDATE => __("Update"),
               DELETE => __("Delete"),
               PURGE => __("Delete permanently"),
               READNOTE => __("Read notes"),
               UPDATENOTE => __("Update notes")
            ]
         ],
         [
            'itemtype' => PluginGdprropaCreatePDF::class,
            'label' => PluginGdprropaCreatePDF::getTypeName(2),
            'field' => PluginGdprropaCreatePDF::$rightname,
            'rights' => [
               CREATE => __("Create"),
            ]
         ],
      ];

      return $rights;
   }

   static function removeRightsFromSession() {

      foreach (self::getAllRights(true) as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

   static function initProfile() {

      global $DB;

      $profile = new self();
      foreach ($profile->getAllRights() as $data) {

         if (countElementsInTable('glpi_profilerights', ['name' => $data['field'] ]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      $profiles = $DB->request(
         "SELECT *
          FROM `glpi_profilerights`
          WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "'
            AND `name` LIKE 'plugin_gdprropa_%'"
      );

      foreach ($profiles as $prof) {
          $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }

}
