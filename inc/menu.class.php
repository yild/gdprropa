<?php

/*
 -------------------------------------------------------------------------
 GDPR Records of Processing Activities plugin for GLPI
 Copyright © 2020-2025 by Yild.

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
 If not, see <https://www.gnu.org/licenses/>.

 Based on DPO Register plugin, by Karhel Tmarr.

 --------------------------------------------------------------------------

  @package   gdprropa
  @author    Yild
  @copyright Copyright © 2020-2025 by Yild
  @license   GPLv3+
             https://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/yild/gdprropa
  @since     1.0.0
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Gdprropa;

use CommonGLPI;

class Menu extends CommonGLPI
{
    public static $rightname = 'plugin_gdprropa_record';

    public static function getMenuName(): string
    {
        return Record::getTypeName(2);
    }

    public static function getMenuContent(): array
    {
        $image = "<i class='fas fa-print fa-2x' title='" .
            __("Create PDF for all records within active entity and its sons", 'gdprropa') . "'></i>";

        $menu = [];
        $menu['title'] = Menu::getMenuName();
        $menu['page'] = Record::getSearchURL(false);
        $menu['icon'] = 'fas ti ti-report';
        $menu['links']['search'] = Record::getSearchURL(false);
        $menu['links'][$image] = CreatePDF::getSearchURL(false) .
            '?createpdf&action=prepare&type=' . CreatePDF::REPORT_ALL;
        if (Record::canCreate()) {
            $menu['links']['add'] = Record::getFormURL(false);
        }

        $menu['options']['record']['title'] = Menu::getMenuName();
        $menu['options']['record']['page'] = Record::getSearchURL(false);
        $menu['options']['record']['links']['search'] = Record::getSearchURL(false);
        $menu['options']['record']['links'][$image] = CreatePDF::getSearchURL(false) .
            '?createpdf&action=prepare&type=' . CreatePDF::REPORT_ALL;
        if (Record::canCreate()) {
            $menu['options']['record']['links']['add'] = Record::getFormURL(false);
        }

        return $menu;
    }

    public static function removeRightsFromSession(): void
    {
        if (isset($_SESSION['glpimenu']['admin']['types']['Menu'])) {
            unset($_SESSION['glpimenu']['admin']['types']['Menu']);
        }
        if (isset($_SESSION['glpimenu']['admin']['content']['Menu'])) {
            unset($_SESSION['glpimenu']['admin']['content']['Menu']);
        }
    }
}
