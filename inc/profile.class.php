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
use DbUtils;
use Html;
use Profile as GlpiProfile;
use ProfileRight;
use Session;

class Profile extends GlpiProfile
{
    public static $rightname = "profile";

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): array|string
    {
        if ($item instanceof GlpiProfile) {
            if ($item->getField('id') && ($item->getField('interface') != 'helpdesk')) {
                return Record::getTypeName(2);
            }
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof GlpiProfile) {
            $ID = $item->getID();

            $prof = new self();
            $prof->showForm($ID);
        }

        return true;
    }

    public function showForm($ID, $options = []): void
    {
        $profile = new GlpiProfile();

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

    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false): void
    {
        $profileRight = new ProfileRight();
        $dbu = new DbUtils();
        foreach ($rights as $right => $value) {
            if (
                $dbu->countElementsInTable('glpi_profilerights', [
                    'profiles_id' => $profiles_id,
                    'name' => $right
                ]) &&
                $drop_existing
            ) {
                $profileRight->deleteByCriteria([
                    'profiles_id' => $profiles_id,
                    'name' => $right
                ]);
            }

            if (
                !$dbu->countElementsInTable('glpi_profilerights', [
                    'profiles_id' => $profiles_id,
                    'name' => $right
                ])
            ) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name'] = $right;
                $myright['rights'] = $value;
                $profileRight->add($myright);

                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    public static function createFirstAccess($ID): void
    {
        self::addDefaultProfileInfos(
            $ID,
            [
                'plugin_gdprropa_record' => CREATE | READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE,
                'plugin_gdprropa_legalbasisact' => CREATE | READ | UPDATE | DELETE | PURGE,
                'plugin_gdprropa_securitymeasure' => CREATE | READ | UPDATE | DELETE | PURGE,
                'plugin_gdprropa_datasubjectscategory' => CREATE | READ | UPDATE | DELETE | PURGE,
                'plugin_gdprropa_controllerinfo' => CREATE | READ | UPDATE,
                'plugin_gdprropa_personaldatacategory' => CREATE | READ | UPDATE | DELETE | PURGE | READNOTE |
                    UPDATENOTE,
                'plugin_gdprropa_createpdf' => CREATE,
            ],
            true
        );
    }

    public static function getAllRights($all = false): array
    {
        return [
            [
                'itemtype' => Record::class,
                'label' => Record::getTypeName(2),
                'field' => Record::$rightname,
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
                'itemtype' => LegalBasisAct::class,
                'label' => LegalBasisAct::getTypeName(2),
                'field' => LegalBasisAct::$rightname,
                'rights' => [
                    CREATE => __("Create"),
                    READ => __("Read"),
                    UPDATE => __("Update"),
                    DELETE => __("Delete"),
                    PURGE => __("Delete permanently")
                ]
            ],
            [
                'itemtype' => SecurityMeasure::class,
                'label' => SecurityMeasure::getTypeName(2),
                'field' => SecurityMeasure::$rightname,
                'rights' => [
                    CREATE => __("Create"),
                    READ => __("Read"),
                    UPDATE => __("Update"),
                    DELETE => __("Delete"),
                    PURGE => __("Delete permanently"),
                ]
            ],
            [
                'itemtype' => DataSubjectsCategory::class,
                'label' => DataSubjectsCategory::getTypeName(2),
                'field' => DataSubjectsCategory::$rightname,
                'rights' => [
                    CREATE => __("Create"),
                    READ => __("Read"),
                    UPDATE => __("Update"),
                    DELETE => __("Delete"),
                    PURGE => __("Delete permanently"),
                ]
            ],
            [
                'itemtype' => ControllerInfo::class,
                'label' => ControllerInfo::getTypeName(2),
                'field' => ControllerInfo::$rightname,
                'rights' => [
                    CREATE => __("Create"),
                    READ => __("Read"),
                    UPDATE => __("Update")
                ]
            ],
            [
                'itemtype' => PersonalDataCategory::class,
                'label' => PersonalDataCategory::getTypeName(2),
                'field' => PersonalDataCategory::$rightname,
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
                'itemtype' => CreatePDF::class,
                'label' => CreatePDF::getTypeName(2),
                'field' => CreatePDF::$rightname,
                'rights' => [
                    CREATE => __("Create"),
                ]
            ],
        ];
    }

    public static function removeRightsFromSession(): void
    {
        foreach (self::getAllRights(true) as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }

    public static function initProfile(): void
    {
        global $DB;

        $profile = new self();
        foreach ($profile->getAllRights() as $data) {
            if (countElementsInTable('glpi_profilerights', ['name' => $data['field']]) == 0) {
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
