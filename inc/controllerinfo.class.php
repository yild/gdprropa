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
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Session;

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
        $this->getFromDBByCrit(['entities_id' => $entity->fields['id']]);

        if (!isset($this->fields['id'])) {
            $this->fields['id'] = -1;
        }

        $this->fields['entities_id'] = $entity->fields['id'];

        $canedit = $this->can($this->fields['id'], UPDATE);

        if ($this->fields['id'] <= 0 && !ControllerInfo::canCreate()) {
            echo "<br><br><span class='b'>" .
                __("Controller information not set.", 'gdprropa') . "</span><br><br>";

            return;
        }

        if ($this->fields['id'] <= 0) {
            $this->fields['controllername'] = '';
        }

        $options['canedit'] = $canedit;
        $options['formtitle'] = __("Manage entity Controller information", 'gdprropa');
        $options['entities_id'] = $entity->fields['id'];

        $this->initForm($this->fields['id'], $options);

        $config = null;
        if (Session::haveRight('plugin_gdprropa_createpdf', CREATE)) {
            $config = CreatePDF::getDefaultPrintOptions();
            $config['report_type'] = CreatePDF::REPORT_FOR_ENTITY;
        }

        TemplateRenderer::getInstance()->display('@gdprropa/controllerinfo.html.twig', [
            'item'   => $this,
            'params' => $options,
            'entity' => $entity,
            'config' => $config,
        ]);
    }

    public static function getFirstControllerInfo($entity_id, $allow_from_ancestors = true)
    {
        $controllerInfo = new ControllerInfo();
        $controllerInfo->getFromDBByCrit(['entities_id' => $entity_id]);

        if (!$allow_from_ancestors) {
            return $controllerInfo;
        }

        if (
            (isset($controllerInfo->fields['id'])) &&
            (
                $controllerInfo->fields['is_recursive'] &&
                ($controllerInfo->fields['entities_id'] == $entity_id)
            )
        ) {
            return $controllerInfo;
        }

        $ancestors = getAncestorsOf('glpi_entities', $entity_id);
        foreach (array_reverse($ancestors) as $ancestor) {
            if ((int) $ancestor === (int) $entity_id) {
                continue;
            }
            return ControllerInfo::getFirstControllerInfo($ancestor);
        }

        return $controllerInfo;
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
