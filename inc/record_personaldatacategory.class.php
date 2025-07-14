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
use CommonGLPI;
use Dropdown;
use Entity;
use Html;
use Session;
use Toolbox;

class Record_PersonalDataCategory extends CommonDBRelation
{
    public static $itemtype_1 = Record::class;
    public static $items_id_1 = 'plugin_gdprropa_records_id';
    public static $itemtype_2 = PersonalDataCategory::class;
    public static $items_id_2 = 'plugin_gdprropa_personaldatacategories_id';

    public static function getTypeName($nb = 0): string
    {
        return _n("Personal Data Category", "Personal Data Categories", $nb, 'gdprropa');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): bool|string
    {
        if (!$item->canView()) {
            return false;
        }

        switch ($item->getType()) {
            case Record::class:
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countForItem($item);
                }

                return self::createTabEntry(Record_PersonalDataCategory::getTypeName($nb), $nb);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        switch ($item->getType()) {
            case Record::class:
                self::showForRecord($item, $withtemplate);
                break;
        }

        return true;
    }

    public static function showForRecord(Record $record, $withtemplate = 0): bool
    {
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
            echo "<tr class='tab_bg_2'><th>" . __("Add a personal data category", 'gdprropa') . "</th></tr>";
            echo "<tr class='tab_bg_3'><td><center><strong>";
            echo __("GDPR Article 30 1c", 'gdprropa');
            echo "</strong></center></td></tr>";
            echo "<tr class='tab_bg_1'><td width='80%' class='center'>";
            PersonalDataCategory::dropdownLimitLevel([
                'addicon' => PersonalDataCategory::canCreate(),
                'name' => 'plugin_gdprropa_personaldatacategories_id',
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

                $link = $data['completename'];
                if ($_SESSION['glpiis_ids_visible'] || empty($data['name'])) {
                    $link = sprintf(__("%1\$s (%2\$s)"), $link, $data['id']);
                }
                $name = "<a href=\"" .
                    PersonalDataCategory::getFormURLWithID($data['id']) . "\">" . $link . "</a>";

                echo "<td class='left" . (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                echo ">" . $name . "</td>";

                $is_special_category = '';

                if ($data['is_special_category']) {
                    $is_special_category = __("Special category", 'gdprropa');
                }

                echo "<td class='center'>" . $is_special_category . " </td>";

                echo "<td class='left'>";
                echo Dropdown::getDropdownName(
                    Entity::getTable(),
                    $data['entities_id']
                );
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

        return true;
    }

    public function getForbiddenStandardMassiveAction(): array
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';

        return $forbidden;
    }

    public function isAllowedToAdd($data): bool
    {
        global $DB;

        $result = 0;
        $msg = '';

        $ancestors = getAncestorsOf(
            PersonalDataCategory::getTable(),
            $data['plugin_gdprropa_personaldatacategories_id']
        );
        $sons = getSonsOf(
            PersonalDataCategory::getTable(),
            $data['plugin_gdprropa_personaldatacategories_id']
        );
        array_shift($sons);

        $pdc = $DB->query(
            'SELECT `plugin_gdprropa_personaldatacategories_id` FROM `' .
            $this->getTable() . '` WHERE `plugin_gdprropa_records_id` = ' . $data['plugin_gdprropa_records_id'] . ' '
        );
        while ($item = $DB->fetch_assoc($pdc)) {
            if (
                $data['plugin_gdprropa_personaldatacategories_id'] == $item['plugin_gdprropa_personaldatacategories_id']
            ) {
                $result = 1;
                $msg = __('Selected item is already on list.', 'gdprropa');
                break;
            } else {
                if (in_array($item['plugin_gdprropa_personaldatacategories_id'], $ancestors)) {
                    $result = 2;
                    $msg = __('Cannot add child item if parent is already on the list.', 'gdprropa');
                    break;
                } else {
                    if (count($sons) && in_array($item['plugin_gdprropa_personaldatacategories_id'], $sons)) {
                        $result = 3;
                        $msg = __(
                            'Cannot add Parent item if child is already on the list.' .
                            '<br>Remove child items before adding parent.',
                            'gdprropa'
                        );
                        break;
                    }
                }
            }
        }

        if ($result) {
            Session::addMessageAfterRedirect($msg, true);
        }

        return $result == 0;
    }

    public static function rawSearchOptionsToAdd(): array
    {
        $tab = [];

        $tab[] = [
            'id' => 'personaldatacategory',
            'name' => Record_PersonalDataCategory::getTypeName()
        ];

        $tab[] = [
            'id' => '221',
            'table' => PersonalDataCategory::getTable(),
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
        $tab[] = [
            'id' => '222',
            'table' => PersonalDataCategory::getTable(),
            'field' => 'is_special_category',
            'name' => __("Any special category", 'gdprropa'),
            'forcegroupby' => true,
            'massiveaction' => false,
            'datatype' => 'bool',
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
