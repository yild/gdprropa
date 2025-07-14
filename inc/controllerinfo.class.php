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

use CommonDBChild;
use CommonGLPI;
use ContractType;
use Entity;
use Html;
use Session;
use User;

class ControllerInfo extends CommonDBChild
{
    public static $itemtype = 'Entity';
    public static $items_id = 'entities_id';

    public static $logs_for_parent = true;
    public static $checkParentRights = true;

    public static $rightname = 'plugin_gdprropa_controllerinfo';

    public static function getTypeName($nb = 0): string
    {
        return __("GDPR Controller Info", 'gdprropa');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): bool|string
    {
        if (!ControllerInfo::canView()) {
            return false;
        }

        switch ($item->getType()) {
            case Entity::class:
                return self::createTabEntry(ControllerInfo::getTypeName());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        switch ($item->getType()) {
            case Entity::class:
                $info = new self();
                $info->showForEntity($item);
                break;
        }

        return true;
    }

    public function prepareInputForAdd($input): bool|array
    {
        $input['users_id_creator'] = Session::getLoginUserID();

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input): bool|array
    {
        $input['users_id_lastupdater'] = Session::getLoginUserID();

        return parent::prepareInputForUpdate($input);
    }

    public function showForEntity(Entity $entity, $options = []): void
    {
        global $CFG_GLPI;

        $colsize1 = '15%';
        $colsize2 = '35%';
        $colsize3 = '15%';
        $colsize4 = '35%';

        $this->getFromDBByCrit(['entities_id' => $entity->fields['id']]);

        if (!isset($this->fields['id'])) {
            $this->fields['id'] = -1;
        }

        $canedit = $this->can($this->fields['id'], UPDATE);

        if ($this->fields['id'] <= 0 && !ControllerInfo::canCreate()) {
            echo "<br><br><span class='b'>" .
                __("Controller information not set.", 'gdprropa') . "</span><br><br>";

            return;
        }

        $options['canedit'] = $canedit;
        $options['formtitle'] = __("Manage entity Controller information", 'gdprropa');

        $this->initForm($this->fields['id'], $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_3'><td colspan='4'><center><strong>";
        echo __("GDPR Article 30 1a", 'gdprropa');
        echo "</strong></center></td></tr>";

        echo "<tr class='tab_bg_2'><td width='$colsize1'>";
        echo __("Legal representative", 'gdprropa');
        echo "</td><td width='$colsize2'>";
        User::dropdown([
            'right' => 'all',
            'name' => 'users_id_representative',
            'value' => array_key_exists(
                'users_id_representative',
                $this->fields
            ) ? $this->fields['users_id_representative'] : null
        ]);

        echo "</td><td width='$colsize3'>";
        echo __("Data Protection Officer", 'gdprropa');
        echo "</td><td  width='$colsize4'>";
        User::dropdown([
            'right' => 'all',
            'name' => 'users_id_dpo',
            'value' => array_key_exists('users_id_dpo', $this->fields) ? $this->fields['users_id_dpo'] : null
        ]);
        echo "</td></tr>";

        echo "</td><td width='$colsize1'>";
        echo __("Controller Name", 'gdprropa');
        echo "</td><td colspan='3'>";
        if ($this->fields['id'] <= 0) {
            $this->fields['controllername'] = '';
        }
        $controller_name = Html::cleanInputText($this->fields['controllername']);
        echo "<input type='text' style='width:98%' maxlength=250 name='controllername' required value='" .
            $controller_name . "'/>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_3'><td colspan='4'><center><strong>";
        echo __("Controller configuration - contract types", 'gdprropa');
        echo "</strong></center></td></tr>";

        echo "<tr class='tab_bg_2'><td width='$colsize1'>";
        echo __("Joint Controller Contract Type", 'gdprropa');
        echo "</td><td width='$colsize2'>";
        ContractType::dropdown([
            'width' => '75%',
            'name' => 'contracttypes_id_jointcontroller',
            'value' => array_key_exists('contracttypes_id_jointcontroller', $this->fields) ?
                $this->fields['contracttypes_id_jointcontroller'] : null
        ]);
        echo "</td><td width='$colsize3'>";
        echo __("Processor Contract Type", 'gdprropa');
        echo "</td><td width='$colsize4'>";
        ContractType::dropdown([
            'width' => '75%',
            'name' => 'contracttypes_id_processor',
            'value' => array_key_exists('contracttypes_id_processor', $this->fields) ?
                $this->fields['contracttypes_id_processor'] : null
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td width='$colsize1'>";
        echo __("Thirdparty Contract Type", 'gdprropa');
        echo "</td><td width='$colsize2'>";
        ContractType::dropdown([
            'width' => '75%',
            'name' => 'contracttypes_id_thirdparty',
            'value' => array_key_exists('contracttypes_id_thirdparty', $this->fields) ?
                $this->fields['contracttypes_id_thirdparty'] : null
        ]);
        echo "</td><td width='$colsize3'>";
        echo __("Internal Contract Type", 'gdprropa');
        echo "</td><td width='$colsize4'>";
        ContractType::dropdown([
            'width' => '75%',
            'name' => 'contracttypes_id_internal',
            'value' => array_key_exists('contracttypes_id_internal', $this->fields) ?
                $this->fields['contracttypes_id_internal'] : null
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td width='$colsize1'>";
        echo __("Other Contract Type", 'gdprropa');
        echo "</td><td width='$colsize2'>";
        ContractType::dropdown([
            'width' => '75%',
            'name' => 'contracttypes_id_other',
            'value' => array_key_exists('contracttypes_id_other', $this->fields) ?
                $this->fields['contracttypes_id_other'] : null
        ]);
        echo "</td><td width='$colsize3'>";
        echo "</td><td width='$colsize4'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center' colspan='4'>";
        echo "<input type='hidden' name='entities_id' value='" . $entity->fields['id'] . "'/>";
        echo "</td></tr>";

        $this->showFormButtons($options);
        Html::closeForm();

        if ((Session::haveRight('plugin_gdprropa_createpdf', CREATE))) {
            echo "<div class='firstbloc'>";
            echo "<form method='GET' action=\"" . $CFG_GLPI['root_doc'] . "/plugins/gdprropa/front/createpdf.php\">";
            echo "<table class='tab_cadre_fixe' id='mainformtable'>";
            echo "<tbody>";
            echo "<tr class='headerRow'>";
            echo "<th colspan='3' class=''>" . __('PDF creation settings', 'gdprropa') . "</th>";
            echo "</tr>";

            $_config = CreatePDF::getDefaultPrintOptions();
            $_config['report_type'] = CreatePDF::REPORT_FOR_ENTITY;
            CreatePDF::showConfigFormElements($_config);

            echo "</table>";
            echo "<input type='hidden' name='report_type' value=\"" . CreatePDF::REPORT_FOR_ENTITY . "\">";
            echo "<input type='hidden' name='entities_id' value='" . $entity->fields['id'] . "'>";
            echo "<input type='hidden' name='action' value=\"print\">";
            echo "<input type='submit' class='submit' name='createpdf' value='" .
                __("Create Controller RoPA PDF for Entity", 'gdprropa') . "' />";
            Html::closeForm();
            echo "</div>";
        }

        echo "<p></p>";
    }

    public static function getFirstControllerInfo($entity_id, $allow_from_ancestors = true)
    {
        $controllerInfo = new ControllerInfo();
        $controllerInfo->getFromDBByCrit(['entities_id' => $entity_id]);

        if (!$allow_from_ancestors) {
            return $controllerInfo;
        } else {
            if (
                (isset($controllerInfo->fields['id'])) &&
                (
                    $controllerInfo->fields['is_recursive'] &&
                    ($controllerInfo->fields['entities_id'] == $entity_id)
                )
            ) {
                return $controllerInfo;
            } else {
                $ancestors = getAncestorsOf('glpi_entities', $entity_id);
                foreach (array_reverse($ancestors) as $ancestor) {
                    return ControllerInfo::getFirstControllerInfo($ancestor);
                }
            }
        }
    }

    public static function getContractTypes($entity_id, $compact = false): array
    {
        $controllerInfo = ControllerInfo::getFirstControllerInfo(
            $entity_id,
            Config::getConfig('system', 'allow_controllerinfo_from_ancestor')
        );

        $out = [
            'contracttypes_id_jointcontroller' => -1,
            'contracttypes_id_processor' => -1,
            'contracttypes_id_thirdparty' => -1,
            'contracttypes_id_internal' => -1,
            'contracttypes_id_other' => -1,
        ];

        foreach ($out as $key => $value) {
            if (isset($controllerInfo->fields[$key])) {
                $out[$key] = $controllerInfo->fields[$key];
            }
        }

        if ($compact) {
            $out = array_values($out);
        }

        return $out;
    }

    public static function getSearchOptionsControllerInfo(): array
    {
        $options = [];

        $options[5601] = [
            'id' => '5601',
            'table' => 'glpi_users',
            'field' => 'name',
            'linkfield' => 'users_id_representative',
            'name' => __("Legal representative", 'gdprropa'),
            'massiveaction' => false,
            'datatype' => 'dropdown',
            'joinparams' => [
                'beforejoin' => [
                    'table' => self::getTable(),
                    'joinparams' => [
                        'jointype' => 'child'
                    ]
                ]
            ]
        ];

        $options[5602] = [
            'id' => '5602',
            'table' => 'glpi_users',
            'field' => 'name',
            'linkfield' => 'users_id_dpo',
            'name' => __("Data Protection Officer", 'gdprropa'),
            'massiveaction' => false,
            'datatype' => 'dropdown',
            'joinparams' => [
                'beforejoin' => [
                    'table' => self::getTable(),
                    'joinparams' => [
                        'jointype' => 'child'
                    ]
                ]
            ]
        ];

        $options[5603] = [
            'id' => '5603',
            'table' => self::getTable(),
            'field' => 'controllername',
            'name' => __("Controller Name", 'gdprropa'),
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'child'
            ],
        ];

        return $options;
    }

    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = [
            'id' => '11',
            'table' => $this->getTable(),
            'field' => 'controllername',
            'name' => __("Controller Name", 'gdprropa'),
            'massiveaction' => false,
            'datatype' => 'text',
        ];

        return array_merge(parent::rawSearchOptions(), $tab);
    }
}
