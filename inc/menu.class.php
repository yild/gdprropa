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

class PluginGdprropaMenu extends CommonGLPI
{
   static $rightname = 'plugin_gdprropa_record';

   static function getMenuName() {

      return PluginGdprropaRecord::getTypeName(2);

   }

   static function getMenuContent() {

      $image = "<i class='fas fa-print fa-2x' title='" . __("Create PDF for all records within active entity and its sons", 'gdprropa') . "'></i>";

      $menu = [];
      $menu['title'] = PluginGdprropaMenu::getMenuName();
      $menu['page'] = '/plugins/gdprropa/front/record.php';
      $menu['links']['search'] = PluginGdprropaRecord::getSearchURL(false);
      $menu['links'][$image] = PluginGdprropaCreatepdf::getSearchURL(false) . '?createpdf&action=prepare&type=' . PluginGdprropaCreatePDF::REPORT_ALL;
      if (PluginGdprropaRecord::canCreate()) {
         $menu['links']['add'] = PluginGdprropaRecord::getFormURL(false);
      }

      $menu['options']['gdprropa']['title'] = PluginGdprropaMenu::getMenuName();
      $menu['options']['gdprropa']['page'] = PluginGdprropaRecord::getSearchURL(false);
      $menu['options']['gdprropa']['links']['search'] = PluginGdprropaRecord::getSearchURL(false);
      $menu['options']['gdprropa']['links'][$image] = PluginGdprropaCreatepdf::getSearchURL(false) . '?createpdf&action=prepare&type=' . PluginGdprropaCreatePDF::REPORT_ALL;
      if (PluginGdprropaRecord::canCreate()) {
         $menu['options']['gdprropa']['links']['add'] = PluginGdprropaRecord::getFormURL(false);
      }

      return $menu;
   }

   static function removeRightsFromSession() {

      if (isset($_SESSION['glpimenu']['admin']['types']['PluginGdprropaMenu'])) {
         unset($_SESSION['glpimenu']['admin']['types']['PluginGdprropaMenu']);
      }
      if (isset($_SESSION['glpimenu']['admin']['content']['PluginGdprropaMenu'])) {
         unset($_SESSION['glpimenu']['admin']['content']['PluginGdprropaMenu']);
      }

   }
}
