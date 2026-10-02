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

// TODO record search option by contract - items doesnt show suppliers names (getSpecificValueToSelect field doesnt
//      work here
//       - it should be overwritten in 'Contract' class) - or make pull request to original glpi code

namespace GlpiPlugin\Gdprropa;

use CommonDBRelation;
use CommonDBTM;
use CommonGLPI;
use Contract;
use DBmysqlIterator;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use Infocom;
use Supplier;
use Toolbox;

class Record_Contract extends CommonDBRelation
{
    public const CONTRACT_ALL = 0;
    public const CONTRACT_JOINTCONTROLLER = 1;
    public const CONTRACT_PROCESSOR = 2;
    public const CONTRACT_THIRDPARTY = 3;
    public const CONTRACT_INTERNAL = 4;
    public const CONTRACT_OTHER = 5;

    public static $itemtype_1 = Record::class;
    public static $items_id_1 = 'plugin_gdprropa_records_id';
    public static $itemtype_2 = Contract::class;
    public static $items_id_2 = 'contracts_id';

    public static function getTypeName($nb = 0): string
    {
        return _n("Contract", "Contracts", $nb, 'gdprropa');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): bool|string
    {
        if (!Contract::canView() || !$item->canView()) {
            return false;
        }

        switch ($item->getType()) {
            case Record::class:
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countForItem($item);
                }

                return self::createTabEntry(Record_Contract::getTypeName($nb), $nb);
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

    public static function showContractTypesNotSetInfo($contracttypes = [], $entity_id = -1): string
    {
        $output = '';

        if (($contracttypes['contracttypes_id_jointcontroller'] < 1)) {
            $output .= '<br>' . __("Joint controller contract type not set.", 'gdprropa') . '<br>';
        }
        if ($contracttypes['contracttypes_id_processor'] < 1) {
            $output .= '<br>' . __("Processor contract type not set.", 'gdprropa') . '<br>';
        }
        if ($contracttypes['contracttypes_id_thirdparty'] < 1) {
            $output .= '<br>' .
                __(
                    "Third country/internalional organization contract type not set.",
                    'gdprropa'
                ) . '<br>';
        }
        if ($contracttypes['contracttypes_id_internal'] < 1) {
            $output .= '<br>' . __("Internal contract type not set.", 'gdprropa') . '<br>';
        }
        if ($contracttypes['contracttypes_id_other'] < 1) {
            $output .= '<br>' . __("Other contract type not set.", 'gdprropa') . '<br>';
        }

        if (!empty($output)) {
            $link = "<a href='" . Entity::getFormURLWithID($entity_id) . "'>Entity</a>";
            $str = sprintf(
                __("Go to %s tab at %s and assign contract type.", 'gdprropa'),
                ControllerInfo::getTypeName(),
                $link
            );

            $output .= '<p>' . $str . '</p><br>';
        }

        return $output;
    }

    public static function showForRecord(Record $record, $withtemplate = 0): bool
    {
        global $DB;

        $id = $record->fields['id'];
        if (!$record->can($id, READ)) {
            return false;
        }

        $canedit = Record::canUpdate();
        $rand = mt_rand(1, mt_getrandmax());

        $iterator = self::getListForItem($record);
        $number = count($iterator);

        $items_list = [];
        $used = [];
        foreach ($iterator as $data) {
            $items_list[$data['id']] = $data;
            $used[$data['id']] = $data['id'];
        }

        $contracttypes = ControllerInfo::getContractTypes($record->fields['entities_id']);

        $entries = [];
        foreach ($items_list as $data) {
            $link = $data['name'];
            if ($_SESSION['glpiis_ids_visible'] || empty($data['name'])) {
                $link = sprintf(__("%1\$s (%2\$s)"), $link, $data['id']);
            }
            $name = "<a href=\"" . Contract::getFormURLWithID($data['id']) . "\">" .
                htmlescape($link) . "</a>";

            $iterator_2 = $DB->request([
                'SELECT' => ['glpi_suppliers.id', 'glpi_suppliers.name'],
                'FROM' => 'glpi_suppliers',
                'INNER JOIN' => [
                    'glpi_contracts_suppliers' => [
                        'ON' => [
                            'glpi_contracts_suppliers' => 'suppliers_id',
                            'glpi_suppliers' => 'id'
                        ]
                    ]
                ],
                'WHERE' => ['contracts_id' => $data['id']]
            ]);

            $suppliers = "";

            foreach ($iterator_2 as $data_2) {
                $link_2 = $data_2['name'];

                if ($_SESSION['glpiis_ids_visible'] || empty($data_2['name'])) {
                    $link_2 = sprintf(__("%1\$s (%2\$s)"), $link_2, $data_2['id']);
                }

                $suppliers .= "<a href=\"" . Supplier::getFormURLWithID($data_2['id']) . "\">" .
                    htmlescape($link_2) . "</a><br>";
            }

            $expiry = '';
            if ($data['notice'] > 0) {
                $expiry = Infocom::getWarrantyExpir(
                    $data['begin_date'],
                    $data['duration'],
                    $data['notice'],
                    true
                );
            }

            $entries[] = [
                'itemtype'   => self::class,
                'id'         => $data['linkid'],
                'row_class'  => !empty($data['is_deleted']) ? 'tab_bg_2_2' : '',
                'name'       => $name,
                'supplier'   => $suppliers,
                'type'       => Dropdown::getDropdownName('glpi_contracttypes', $data['contracttypes_id']),
                'num'        => $data['num'],
                'begin_date' => $data['begin_date'],
                'entity'     => Dropdown::getDropdownName(Entity::getTable(), $data['entities_id']),
                'comment'    => $data['comment'],
                'expiry'     => $expiry,
            ];
        }

        TemplateRenderer::getInstance()->display('@gdprropa/record_contract.html.twig', [
            'canedit'                    => $canedit,
            'rand'                       => $rand,
            'record_id'                  => $id,
            'form_url'                   => Toolbox::getItemTypeFormURL(self::class),
            'add_label'                  => _sx('button', 'Add'),
            'contracttypes_not_set_info' => self::showContractTypesNotSetInfo(
                $contracttypes,
                $record->fields['entities_id']
            ),
            'dropdown_options'           => [
                'name'          => 'contracts_id',
                'entity'        => $record->fields['is_recursive'] ?
                    getSonsOf('glpi_entities', $record->fields['entities_id']) : $record->fields['entities_id'],
                'entity_sons'   => !$record->fields['is_recursive'],
                'used'          => $used,
                'expired'       => Config::getConfig('system', 'allow_select_expired_contracts'),
                'nochecklimit'  => true,
                'contracttypes' => $contracttypes,
            ],
            'columns'                    => [
                'name'       => __("Name"),
                'supplier'   => __("Supplier"),
                'type'       => __("Type"),
                'num'        => __("Number"),
                'begin_date' => __("Begin date"),
                'entity'     => __("Introduced in", 'gdprropa'),
                'comment'    => __("Comment"),
                'expiry'     => __("Expiry"),
            ],
            'entries'                    => $entries,
            'formatters'                 => [
                'name'     => 'raw_html',
                'supplier' => 'raw_html',
                'expiry'   => 'raw_html',
            ],
            'total_number'               => $number,
            'showmassiveactions'         => $canedit && $number,
            'massiveactionparams'        => [
                'container'     => 'mass' . str_replace('\\', '', self::class) . $rand,
                'num_displayed' => min($_SESSION['glpilist_limit'], $number),
            ],
        ]);

        return true;
    }

    public static function cleanForItem(CommonDBTM $item): void
    {
        $rel = new Record_Contract();
        $rel->deleteByCriteria([
            'itemtype' => $item->getType(),
            'contracts_id' => $item->fields['id']
        ]);
    }

    public function prepareInputForUpdate($input): bool|array
    {
        // override hack - there was a problem in CommonDBConnexity.checkAttachedItemChangesAllowed
        // while purging item lined to master table - permissions errors
        return $input;
    }

    public function getForbiddenStandardMassiveAction(): array
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';

        return $forbidden;
    }

    public static function getContractTypeStr($type): string
    {
        switch ($type) {
            case self::CONTRACT_JOINTCONTROLLER:
                return __("Joint controller contract", 'gdprropa');
            case self::CONTRACT_PROCESSOR:
                return __("Processor contract", 'gdprropa');
            case self::CONTRACT_THIRDPARTY:
                return __("Thirdparty contract", 'gdprropa');
            case self::CONTRACT_INTERNAL:
                return __("Internal contract", 'gdprropa');
            case self::CONTRACT_OTHER:
                return __("Other contract", 'gdprropa');
        }

        return "???";
    }

    public static function getContractsDropdown($options = []): int|string|null
    {
        global $DB;

        $p = [
            'name'          => 'contracts_id',
            'value'         => '',
            'entity'        => -1,
            'width'         => '80%',
            'rand'          => mt_rand(),
            'entity_sons'   => false,
            'used'          => [],
            'nochecklimit'  => false,
            'on_change'     => '',
            'display'       => true,
            'expired'       => false,
            'contracttypes' => [],
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if (!($p['entity'] < 0) && $p['entity_sons']) {
            if (is_array($p['entity'])) {
                echo "entity_sons options is not available with array of entity";
            } else {
                $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
            }
        }

        $query = [
            'SELECT' => [
                'glpi_contracts.id AS id',
                'glpi_contracts.name AS contracts_name',
                'glpi_contracts.num AS contracts_num',
                'glpi_contracts.entities_id AS entities_id',
                'glpi_contracts.begin_date AS contracts_begin_date',
                'glpi_contracts.max_links_allowed AS max_links_allowed',
                'glpi_contracttypes.name AS contracttypes_names',
                new QueryExpression(
                    'GROUP_CONCAT(DISTINCT `glpi_suppliers`.`name`) AS `suppliers_names`'
                ),
            ],
            'FROM' => 'glpi_contracts',
            'INNER JOIN' => [
                'glpi_contracttypes' => [
                    'ON' => [
                        'glpi_contracttypes' => 'id',
                        'glpi_contracts'     => 'contracttypes_id',
                    ],
                ],
            ],
            'LEFT JOIN' => [
                'glpi_entities' => [
                    'ON' => [
                        'glpi_contracts' => 'entities_id',
                        'glpi_entities'  => 'id',
                    ],
                ],
                'glpi_contracts_suppliers' => [
                    'ON' => [
                        'glpi_contracts'           => 'id',
                        'glpi_contracts_suppliers' => 'contracts_id',
                    ],
                ],
                'glpi_suppliers' => [
                    'ON' => [
                        'glpi_contracts_suppliers' => 'suppliers_id',
                        'glpi_suppliers'           => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_contracts.is_deleted'  => 0,
                'glpi_contracts.is_template' => 0,
            ],
            'GROUPBY' => 'glpi_contracts.id',
            'ORDER' => [
                'glpi_entities.completename',
                'glpi_contracts.name ASC',
                'glpi_contracts.begin_date DESC',
            ],
        ];

        if (!($p['entity'] < 0)) {
            $query['WHERE'][] = getEntitiesRestrictCriteria(
                'glpi_contracts',
                'entities_id',
                $p['entity'],
                true
            );
        }
        if (count($p['used'])) {
            $query['WHERE'][] = ['NOT' => ['glpi_contracts.id' => array_values($p['used'])]];
        }
        if (!$p['expired']) {
            $query['WHERE'][] = new QueryExpression(
                "DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                        `glpi_contracts`.`duration` MONTH), CURDATE()) > '0'
                        OR `glpi_contracts`.`begin_date` IS NULL
                        OR (`glpi_contracts`.`duration` = 0
                        AND DATEDIFF(`glpi_contracts`.`begin_date`, CURDATE() ) < '0' )
                        OR `glpi_contracts`.`renewal` = 1"
            );
        }
        if (isset($p['contracttypes']) && count($p['contracttypes'])) {
            $query['WHERE'][] = [
                'glpi_contracts.contracttypes_id' => array_values($p['contracttypes'])
            ];
        }

        $result = $DB->request($query);

        $group = '';
        $prev = -1;
        $values = [];
        foreach ($result as $data) {
            if (
                $p['nochecklimit'] ||
                ($data['max_links_allowed'] == 0) ||
                ($data['max_links_allowed'] >
                    countElementsInTable('glpi_contracts_items', ['contracts_id' => $data['id']]))
            ) {
                if ($data['entities_id'] != $prev) {
                    $group = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
                    $prev = $data['entities_id'];
                }

                $name = $data['contracts_name'];
                if ($_SESSION['glpiis_ids_visible'] || empty($data['contracts_name'])) {
                    $name = sprintf(__('%1$s (%2$s)'), $name, $data['id']);
                }

                $tmp = sprintf(__('%1$s - %2$s'), $name, $data['contracts_num']);
                $tmp = sprintf(__('%1$s - %2$s'), $tmp, Html::convDateTime($data['contracts_begin_date']));

                $tmp .= Record_Contract::getSuppliersNames($data['id'], ', ');
                $tmp .= ' - ' . $data['contracttypes_names'];

                $values[$group][$data['id']] = $tmp;
            }
        }

        return Dropdown::showFromArray($p['name'], $values, [
            'value' => $p['value'],
            'on_change' => $p['on_change'],
            'display' => $p['display'],
            'width' => $p['width'],
            'display_emptychoice' => true,
        ]);
    }

    public static function getContracts($record, $type = null, $get_expired = false): DBmysqlIterator
    {
        global $DB;

        $ids = ControllerInfo::getContractTypes($record->getEntityID());

        $contract_type = [];
        switch ($type) {
            case self::CONTRACT_JOINTCONTROLLER:
                $contract_type[] = $ids['contracttypes_id_jointcontroller'];
                break;
            case self::CONTRACT_PROCESSOR:
                $contract_type[] = $ids['contracttypes_id_processor'];
                break;
            case self::CONTRACT_THIRDPARTY:
                $contract_type[] = $ids['contracttypes_id_thirdparty'];
                break;
            case self::CONTRACT_INTERNAL:
                $contract_type[] = $ids['contracttypes_id_internal'];
                break;
            case self::CONTRACT_OTHER:
                $contract_type[] = $ids['contracttypes_id_other'];
                break;
            case self::CONTRACT_ALL:
                $contract_type[] = $ids['contracttypes_id_jointcontroller'];
                $contract_type[] = $ids['contracttypes_id_processor'];
                $contract_type[] = $ids['contracttypes_id_thirdparty'];
                $contract_type[] = $ids['contracttypes_id_internal'];
                $contract_type[] = $ids['contracttypes_id_other'];
                break;
        }

        $query['SELECT'] = [
            'glpi_contracts.name AS contracts_name',
            'glpi_contracts.num AS contracts_num',
            'glpi_contracts.begin_date AS contracts_begin_date',
            'glpi_contracts.duration AS contracts_duration',
            'glpi_contracts.periodicity AS contracts_periodicity',
            'glpi_contracts.notice AS contracts_notice',
            'glpi_contracts.comment AS contracts_comment',
            'glpi_contracts.contracttypes_id AS contracttypes_id',
            'glpi_suppliers.name AS suppliers_name',
            'glpi_suppliers.fax AS suppliers_fax',
            'glpi_suppliers.phonenumber AS suppliers_phonenumber',
            'glpi_suppliers.email AS suppliers_email',
            'glpi_suppliers.website AS suppliers_website',
            'glpi_suppliers.postcode AS suppliers_postcode',
            'glpi_suppliers.state AS suppliers_state',
            'glpi_suppliers.country AS suppliers_country',
            'glpi_suppliers.address AS suppliers_address',
            'glpi_suppliers.town AS suppliers_town',
            'glpi_suppliers.comment AS suppliers_comment',
            'glpi_contracttypes.name AS contractypes_name',
            'glpi_contracttypes.comment AS contractypes_comment',
        ];
        $query['FROM'] = ['glpi_contracts'];
        $query['LEFT JOIN'] = [
            'glpi_plugin_gdprropa_records_contracts' => [
                'FKEY' => [
                    'glpi_contracts' => 'id',
                    'glpi_plugin_gdprropa_records_contracts' => 'contracts_id'
                ]
            ],
            'glpi_contracts_suppliers' => [
                'FKEY' => [
                    'glpi_contracts' => 'id',
                    'glpi_contracts_suppliers' => 'contracts_id'
                ]
            ],
            'glpi_suppliers' => [
                'FKEY' => [
                    'glpi_suppliers' => 'id',
                    'glpi_contracts_suppliers' => 'suppliers_id'
                ]
            ],
            'glpi_contracttypes' => [
                'FKEY' => [
                    'glpi_contracttypes' => 'id',
                    'glpi_contracts' => 'contracttypes_id'
                ]
            ],
        ];
        $query['WHERE'] = [
            'glpi_plugin_gdprropa_records_contracts.plugin_gdprropa_records_id' => $record->fields['id'],
            'glpi_contracttypes.id' => $contract_type,
            'glpi_contracts.is_deleted' => 0,
        ];

        if (!$get_expired) {
            $query['WHERE'][] = new QueryExpression(
                "DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`,
                INTERVAL `glpi_contracts`.`duration` MONTH), CURDATE()) > '0' OR
                `glpi_contracts`.`begin_date` IS NULL OR (`glpi_contracts`.`duration` = 0 AND
                DATEDIFF(`glpi_contracts`.`begin_date`, CURDATE() ) < '0' ) OR
                `glpi_contracts`.`renewal` = 1"
            );
        }
        return $DB->request($query);
    }

    public static function rawSearchOptionsToAdd(): array
    {
        $tab = [];

        $tab[] = [
            'id' => 'jointcontroller',
            'name' => Record_Contract::getTypeName()
        ];
        $tab[] = [
            'id' => '151',
            'table' => 'glpi_contracts',
            'field' => 'name',
            'name' => __("Name (select from list)", 'gdprropa'),
            'forcegroupby' => true,
            'massiveaction' => false,
            'datatype' => 'dropdown',
            'searchtype' => ['equals', 'notequals'],
            'joinparams' => [
                'beforejoin' => [
                    'table' => 'glpi_plugin_gdprropa_records_contracts',
                    'joinparams' => [
                        'jointype' => 'child',
                        'beforejoin' => [
                            'table' => 'glpi_plugin_gdprropa_records',
                            'joinparams' => [
                                'jointype' => 'child',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id' => '152',
            'table' => 'glpi_contracts',
            'field' => 'name',
            'name' => __("Name"),
            'forcegroupby' => true,
            'massiveaction' => false,
            'datatype' => 'string',
            'joinparams' => [
                'beforejoin' => [
                    'table' => 'glpi_plugin_gdprropa_records_contracts',
                    'joinparams' => [
                        'jointype' => 'child',
                        'beforejoin' => [
                            'table' => 'glpi_plugin_gdprropa_records',
                            'joinparams' => [
                                'jointype' => 'child',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $tab;
    }

    public static function getSuppliersNamesNoIds($contract_id, $separator = '<br>', $trimlast = true): string
    {
        return self::getSuppliersNames($contract_id, $separator, $trimlast, true);
    }

    public static function getSuppliersNames(
        $contract_id,
        $separator = '<br>',
        $trimlast = true,
        $no_ids = false
    ): string {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'glpi_suppliers.id',
            'FROM' => 'glpi_suppliers',
            'INNER JOIN' => [
                'glpi_contracts_suppliers' => [
                    'ON' => [
                        'glpi_contracts_suppliers' => 'suppliers_id',
                        'glpi_suppliers' => 'id'
                    ]
                ]
            ],
            'WHERE' => ['contracts_id' => $contract_id]
        ]);

        $out = "";
        foreach ($iterator as $data) {
            $name = Dropdown::getDropdownName('glpi_suppliers', $data['id']);

            if ((!$no_ids && $_SESSION['glpiis_ids_visible']) || empty($name)) {
                $name = sprintf(__("%1\$s (%2\$s)"), $name, $data['id']);
            }
            $out .= $name . $separator;
        }

        if ($trimlast) {
            return substr($out, 0, -strlen($separator));
        } else {
            return $out;
        }
    }
}
