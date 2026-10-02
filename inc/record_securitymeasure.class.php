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
use Glpi\Application\View\TemplateRenderer;
use Toolbox;

class Record_SecurityMeasure extends CommonDBRelation
{
    public static $itemtype_1 = Record::class;
    public static $items_id_1 = 'plugin_gdprropa_records_id';
    public static $itemtype_2 = SecurityMeasure::class;
    public static $items_id_2 = 'plugin_gdprropa_securitymeasures_id';

    public static function getTypeName($nb = 0): string
    {
        return _n("Security Measure", "Security Measures", $nb, 'gdprropa');
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

                return self::createTabEntry(Record_SecurityMeasure::getTypeName($nb), $nb);
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

        $entries = [];
        foreach ($items_list as $data) {
            $link = $data['name'];
            if ($_SESSION['glpiis_ids_visible'] || empty($data['name'])) {
                $link = sprintf(__("%1\$s (%2\$s)"), $link, $data['id']);
            }
            $types = SecurityMeasure::getAllTypesArray();
            $entries[] = [
                'itemtype' => static::class,
                'id'       => $data['linkid'],
                'name'     => '<a href="' . htmlescape(SecurityMeasure::getFormURLWithID($data['id'])) . '">' . htmlescape($link) . '</a>',
                'type'     => $types[$data['type']] ?? '',
                'entity'   => Dropdown::getDropdownName(Entity::getTable(), $data['entities_id']),
                'comment'  => $data['comment'],
            ];
        }

        TemplateRenderer::getInstance()->display('@gdprropa/relation_list.html.twig', [
            'canedit'               => $canedit,
            'form_url'              => Toolbox::getItemTypeFormURL(static::class),
            'record_id'             => $id,
            'add_title'             => __("Add Security Measure", 'gdprropa'),
            'article'               => __("GDPR Article 30 1g", 'gdprropa'),
            'add_dropdown_callable' => SecurityMeasure::class . '::dropdown',
            'add_dropdown_options'  => [
                'addicon'     => SecurityMeasure::canCreate(),
                'name'        => 'plugin_gdprropa_securitymeasures_id',
                'entity'      => $record->fields['entities_id'],
                'entity_sons' => false,
                'used'        => $used,
            ],
            'columns'    => [
                'name'    => __("Name"),
                'type'    => __("Type", 'gdprropa'),
                'entity'  => __("Introduced in", 'gdprropa'),
                'comment' => __("Comment"),
            ],
            'formatters' => ['name' => 'raw_html'],
            'entries'    => $entries,
            'total_number' => $number,
            'showmassiveactions' => ($canedit && $number),
            'massiveactionparams' => [
                'container'     => 'mass' . str_replace('\\', '', static::class) . $rand,
                'num_displayed' => min($_SESSION['glpilist_limit'], $number),
            ],
            'datatable_id' => 'datatable_' . str_replace('\\', '', static::class) . $rand,
        ]);

        return true;
    }

    public static function getListForItem(CommonDBTM $item, int $start = 0, int $limit = 0, array $order = []): DBmysqlIterator
    {
        global $DB;

        $params = static::getListForItemParams($item, true);

        if ($limit > 0) {
            $params['START'] = $start;
            $params['LIMIT'] = $limit;
        }
        if (count($order)) {
            $params['ORDER'] = $order;
        }

        return $DB->request($params);
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
            'id' => 'securitymeasure',
            'name' => SecurityMeasure::getTypeName()
        ];

        $tab[] = [
            'id' => '51',
            'table' => SecurityMeasure::getTable(),
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
