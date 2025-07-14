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

use CommonDBRelation;
use CommonDBTM;
use CommonGLPI;
use DBmysqlIterator;
use Dropdown;
use Entity;
use Html;
use Manufacturer;
use Software;
use SoftwareCategory;
use Toolbox;

class Record_Software extends CommonDBRelation
{
    public static $rightname = 'plugin_gdprropa_record';

    public static $itemtype_1 = Record::class;
    public static $items_id_1 = 'plugin_gdprropa_records_id';
    public static $itemtype_2 = Software::class;
    public static $items_id_2 = 'softwares_id';

    public function canPurgeItem(): bool
    {
        return true;
    }

    public function canDeleteItem(): bool
    {
        return true;
    }

    public static function canPurge(): bool
    {
        return true;
    }

    public static function canDelete(): bool
    {
        return true;
    }

    public static function getTypeName($nb = 0): string
    {
        return __("Software", 'gdprropa');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): bool|string
    {
        if (!Software::canView() || !$item->canView()) {
            return false;
        }

        switch ($item->getType()) {
            case Record::class:
                if ($item->fields['storage_medium'] == Record::STORAGE_MEDIUM_PAPER_ONLY) {
                    return false;
                }

                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countForItem($item);
                }

                return self::createTabEntry(Record_Software::getTypeName($nb), $nb);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        switch ($item->getType()) {
            case Record::class:
                return self::showForRecord($item, $withtemplate);
        }

        return true;
    }

    public static function showForRecord(Record $record, $withtemplate = 0): bool
    {
        $id = $record->fields['id'];
        if (!Software::canView() || !$record->can($id, READ)) {
            return false;
        }

        $canedit = $record->can($id, UPDATE);
        $rand = mt_rand(1, mt_getrandmax());

        $iterator = self::getListForItem($record);
        $number = count($iterator);

        $items_list = [];
        $used = [];
        foreach ($iterator as $data) {
            $items_list[$data['id']] = $data;
            $used[$data['id']] = $data['id'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='ticketitem_form$rand' id='ticketitem_form$rand' method='post'
                    action='" . Toolbox::getItemTypeFormURL(__class__) . "'>";
            echo "<input type='hidden' name='plugin_gdprropa_records_id' value='$id' />";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th>" . __("Add a software", 'gdprropa') . "</th></tr>";
            echo "<tr class='tab_bg_1'><td width='80%' class='center'>";

            if (Config::getConfig('system', 'allow_software_from_every_entity')) {
                $entity = 0;
                $entity_sons = true;
            } else {
                $entity = $record->fields['is_recursive']
                    ? getSonsOf('glpi_entities', $record->fields['entities_id']) : $record->fields['entities_id'];
                $entity_sons = $record->fields['is_recursive'];
            }

            Software::dropdown([
                'addicon' => Software::canCreate(),
                'name' => 'softwares_id',
                'entity' => $entity_sons,
                //            'entity_sons' => $entity_sons,
                'used' => $used,
            ]);
//         Software::dropdown([
//            'addicon'  => Software::canCreate(),
//            'name' => 'softwares_id',
//            'entity' => $entity,
//            'entity_sons' => $entity_sons,
//            'used' => $used,
//         ]);
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
                $massive_action_form_id = 'mass' . str_replace('\\', '', static::class) . $rand;
                Html::openMassiveActionsForm($massive_action_form_id);
                $massive_action_params = [
                    'container' => 'mass' . __class__ . $rand,
                    'num_displayed' => min($_SESSION['glpilist_limit'], $number)
                ];
                Html::showMassiveActions($massive_action_params);
            }
            echo "<table class='tab_cadre_fixehov'>";

            $header_begin = "<tr>";
            $header_top = '';
            $header_bottom = '';
            $header_end = '';

            if ($canedit && $number) {
                $header_begin .= "<th width='10'>";
                $header_top .= Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
                $header_end .= "</th>";
            }

            $header_end .= "<th>" . __("Name") . "</th>";
            $header_end .= "<th>" . __("Entity") . "</th>";
            $header_end .= "<th>" . __("Publisher") . "</th>";
            $header_end .= "<th>" . __("Category") . "</th>";
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
                $name = "<a href=\"" . Software::getFormURLWithID($data['id']) . "\">" . $link . "</a>";

                echo "<td class='left" . (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                echo ">" . $name . "</td>";

                echo "<td class='left'>";
                echo Dropdown::getDropdownName
                    (
                        Entity::getTable(),
                        $data['entities_id']
                    ) . "</td>";

                echo "<td class='center'>";
                echo Dropdown::getDropdownName
                    (
                        Manufacturer::getTable(),
                        $data['manufacturers_id']
                    ) . "</td>";

                echo "<td class='center'>";
                echo Dropdown::getDropdownName
                    (
                        SoftwareCategory::getTable(),
                        $data['softwarecategories_id']
                    ) . "</td>";

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

        return true;
    }

    public static function countForItem(CommonDBTM $item): int
    {
        return countElementsInTable(
            Record_Software::getTable(),
            ['plugin_gdprropa_records_id' => $item->getID()]
        );
    }

    public static function cleanForItem(CommonDBTM $item): void
    {
        $rel = new Record_Software();
        $rel->deleteByCriteria([
            'itemtype' => $item->getType(),
            'softwares_id' => $item->fields['id']
        ]);
    }

    public static function getListForItem(CommonDBTM $item): DBmysqlIterator
    {
        global $DB;

        $params = static::getListForItemParams($item, true);

        return $DB->request($params);
    }

    public function prepareInputForUpdate($input): bool|array
    {
        // override hack - there was a problem in CommonDBConnexity.checkAttachedItemChangesAllowed
        //  while purging item lined to master table - permissions errors
        return $input;
    }

    public function getForbiddenStandardMassiveAction(): array
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';

        return $forbidden;
    }

    public static function rawSearchOptionsToAdd(): array
    {
        $tab = [];

        $tab[] = [
            'id' => 'software',
            'name' => Software::getTypeName()
        ];

        $tab[] = [
            'id' => '71',
            'table' => Software::getTable(),
            'field' => 'name',
            'name' => __("Name"),
            'forcegroupby' => true,
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

        return $tab;
    }
}
