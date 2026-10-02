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

        if (Config::getConfig('system', 'allow_software_from_every_entity')) {
            $entity = 0;
            $entity_sons = true;
        } else {
            $entity = $record->fields['is_recursive']
                ? getSonsOf('glpi_entities', $record->fields['entities_id']) : $record->fields['entities_id'];
            $entity_sons = $record->fields['is_recursive'];
        }

        $entries = [];
        foreach ($items_list as $data) {
            $link = $data['name'];
            if ($_SESSION['glpiis_ids_visible'] || empty($data['name'])) {
                $link = sprintf(__("%1\$s (%2\$s)"), $link, $data['id']);
            }
            $entries[] = [
                'itemtype'  => static::class,
                'id'        => $data['linkid'],
                'name'      => '<a href="' . htmlescape(Software::getFormURLWithID($data['id'])) . '">' . htmlescape($link) . '</a>',
                'entity'    => Dropdown::getDropdownName(Entity::getTable(), $data['entities_id']),
                'publisher' => Dropdown::getDropdownName(Manufacturer::getTable(), $data['manufacturers_id']),
                'category'  => Dropdown::getDropdownName(SoftwareCategory::getTable(), $data['softwarecategories_id']),
            ];
        }

        TemplateRenderer::getInstance()->display('@gdprropa/relation_list.html.twig', [
            'canedit'               => $canedit,
            'form_url'              => Toolbox::getItemTypeFormURL(static::class),
            'record_id'             => $id,
            'add_title'             => __("Add a software", 'gdprropa'),
            'article'               => null,
            'add_dropdown_callable' => Software::class . '::dropdown',
            'add_dropdown_options'  => [
                'addicon' => Software::canCreate(),
                'name'    => 'softwares_id',
                'entity'  => $entity_sons,
                'used'    => $used,
            ],
            'columns'    => [
                'name'      => __("Name"),
                'entity'    => __("Entity"),
                'publisher' => __("Publisher"),
                'category'  => __("Category"),
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
