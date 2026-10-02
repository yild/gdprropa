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

use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;

class Record_Retention extends CommonDBTM
{
    public static $rightname = 'plugin_gdprropa_record';

    public $dohistory = true;

    public const RETENTION_TYPE_NONE = 0;
    public const RETENTION_TYPE_CONTRACT = 1;
    public const RETENTION_TYPE_LEGALBASISACT = 2;
    public const RETENTION_TYPE_OTHER = 99;

    public static function getTypeName($nb = 0): string
    {
        return __("Data Retention", 'gdprropa');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): bool|string
    {
        if (!$item->canView()) {
            return false;
        }

        switch ($item->getType()) {
            case Record::class:
                return self::createTabEntry(Record_Retention::getTypeName());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        switch ($item->getType()) {
            case Record::class:
                $retention = new self();
                $retention->showForRecord($item, $withtemplate);
                break;
        }

        return true;
    }

    public static function canView(): bool
    {
        return Record::canView();
    }

    public static function canCreate(): bool
    {
        return Record::canCreate();
    }

    public static function canUpdate(): bool
    {
        return Record::canUpdate();
    }

    public function showForRecord(Record $record, $withtemplate = 0): bool
    {
        if ($record->fields['id']) {
            $this->getFromDBByCrit(['plugin_gdprropa_records_id' => $record->fields['id']]);
        }

        if (!$record->can($record->fields['id'], READ)) {
            return false;
        }

        if (!isset($this->fields['id'])) {
            $this->fields['id'] = -1;
        }

        $canedit = Record::canUpdate() || ($this->fields['id'] <= 0 && Record::canCreate());

        $options = [];
        $options['canedit'] = $canedit;
        $options['formtitle'] = __("Manage data retention", 'gdprropa');

        $this->initForm($this->fields['id'], $options);

        $this->fields['entities_id'] = $record->fields['entities_id'];
        $this->fields['is_record_recursive'] = $record->fields['is_recursive'];

        $type_form_html = '';
        if ($record->fields['id'] < 1 || array_key_exists('type', $this->fields)) {
            ob_start();
            switch ($this->fields['type']) {
                case Record_Retention::RETENTION_TYPE_CONTRACT:
                    self::showContractInputs($this->fields);
                    break;
                case Record_Retention::RETENTION_TYPE_LEGALBASISACT:
                    self::showLegalBasesInputs($this->fields);
                    break;
                case Record_Retention::RETENTION_TYPE_OTHER:
                    self::showOtherInputs($this->fields);
                    break;
            }
            $type_form_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@gdprropa/record_retention.html.twig', [
            'item'            => $this,
            'params'          => $options,
            'record'          => $record,
            'current_type'    => array_key_exists('type', $this->fields) ? $this->fields['type'] : null,
            'retention_types' => self::getAllTypesArray(),
            'type_form_html'  => $type_form_html,
        ]);

        return true;
    }

    public static function showContractInputs($data = []): void
    {
        $value = array_key_exists('contracts_id', $data) ? $data['contracts_id'] : null;

        $contractTypes = [];
        $contract_types_not_set_info = '';

        if (Config::getConfig('system', 'limit_retention_contracttypes')) {
            $entities_id = $data['entities_id'] ?? 0;
            $contractTypes = ControllerInfo::getContractTypes($entities_id);
            $contract_types_not_set_info = Record_Contract::showContractTypesNotSetInfo($contractTypes, $entities_id);
        }

        $contract_until_is_valid = null;
        if (array_key_exists('contract_until_is_valid', $data)) {
            $contract_until_is_valid = $data['contract_until_is_valid'];
        } elseif (isset($data['id']) && $data['id'] < 0) {
            $contract_until_is_valid = 0;
        }

        $show_contract_until_inputs = (
            (isset($data['id']) && $data['id'] < 1) ||
            (array_key_exists('contract_until_is_valid', $data) && ($data['contract_until_is_valid'] != 1))
        );

        $entities_id = $data['entities_id'] ?? 0;
        $is_recursive = !empty($data['is_record_recursive']);

        TemplateRenderer::getInstance()->display('@gdprropa/record_retention.contract.html.twig', [
            'contract_types_not_set_info' => $contract_types_not_set_info,
            'contracts_dropdown_options'  => [
                'name'          => 'contracts_id',
                'entity'        => $is_recursive ? getSonsOf('glpi_entities', $entities_id) : $entities_id,
                'entity_sons'   => !$is_recursive,
                'expired'       => Config::getConfig('system', 'allow_select_expired_contracts'),
                'nochecklimit'  => true,
                'width'         => '100%',
                'value'         => $value,
                'contracttypes' => array_values($contractTypes),
                'used'          => [],
            ],
            'contract_until_is_valid'     => $contract_until_is_valid,
            'contract_until_data'         => $data,
            'show_contract_until_inputs'  => $show_contract_until_inputs,
        ]);
    }

    public static function showContractUntilIsValidInputs($data = []): void
    {
        TemplateRenderer::getInstance()->display('@gdprropa/record_retention.contract_until.html.twig', [
            'data' => $data,
        ]);
    }

    public static function showLegalBasesInputs($data = []): void
    {
        $value = array_key_exists('plugin_gdprropa_legalbasisacts_id', $data)
            ? $data['plugin_gdprropa_legalbasisacts_id'] : null;

        $entities_id = $data['entities_id'] ?? 0;
        $is_recursive = !empty($data['is_record_recursive']);

        TemplateRenderer::getInstance()->display('@gdprropa/record_retention.legalbasis.html.twig', [
            'legalbasis_options' => [
                'name'   => 'plugin_gdprropa_legalbasisacts_id',
                'value'  => $value,
                'entity' => $is_recursive ? getSonsOf('glpi_entities', $entities_id) : $entities_id,
                'used'   => [],
            ],
        ]);
    }

    public static function showOtherInputs($data = [])
    {
    }

    public static function dropdownTypes($name, $value = 0, $display = true): int|string
    {
        return Dropdown::showFromArray($name, self::getAllTypesArray(), ['value' => $value, 'display' => $display]);
    }

    public static function getAllTypesArray(): array
    {
        return [
            '' => Dropdown::EMPTY_VALUE,
            self::RETENTION_TYPE_NONE => __("Not required", 'gdprropa'),
            self::RETENTION_TYPE_CONTRACT => __("Contractual term", 'gdprropa'),
            self::RETENTION_TYPE_LEGALBASISACT => _n("Legal basis", "Legal bases", 1, 'gdprropa'),
            self::RETENTION_TYPE_OTHER => __("Other regulations", 'gdprropa'),
        ];
    }

    public static function getRetentionPeriodScales($index = null, $nb = 1)
    {
        $options = [
            'y' => _n("Year", "Years", $nb, 'gdprropa'),
            'm' => _n("Month", "Months", $nb, 'gdprropa'),
            'd' => _n("Day", "Days", $nb, 'gdprropa'),
            'h' => _n("Hour", "Hours", $nb, 'gdprropa')
        ];

        if ($index && array_key_exists($index, $options)) {
            return $options[$index];
        }

        return $options;
    }

    public static function dropdownRetentionScheduleScales($name, $options = []): int|string
    {
        $params['value'] = 0;
        $params['toadd'] = [];
        $params['on_change'] = '';
        $params['display'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $items = [];
        if (is_array($params['toadd']) && count($params['toadd'])) {
            $items = $params['toadd'];
        }

        $items += self::getRetentionPeriodScales();

        return Dropdown::showFromArray($name, $items, $params);
    }

    public function prepareInputForAdd($input): bool|array
    {
        switch ($input['type']) {
            case Record_Retention::RETENTION_TYPE_NONE:
            case Record_Retention::RETENTION_TYPE_OTHER:
                $input['plugin_gdprropa_legalbasisacts_id'] = 0;
                $input['contracts_id'] = 0;
                $input['contract_until_is_valid'] = 0;
                $input['contract_after_is_terminated'] = 0;
                $input['contract_retention_value'] = 0;
                $input['contract_retention_scale'] = 'y';
                break;

            case Record_Retention::RETENTION_TYPE_CONTRACT:
                $input['plugin_gdprropa_legalbasisacts_id'] = 0;
                if ($input['contract_until_is_valid']) {
                    $input['contract_retention_value'] = 0;
                    $input['contract_retention_scale'] = 'y';
                }
                break;

            case Record_Retention::RETENTION_TYPE_LEGALBASISACT:
                $input['contracts_id'] = 0;
                $input['contract_until_is_valid'] = 0;
                $input['contract_after_is_terminated'] = 0;
                $input['contract_retention_value'] = 0;
                $input['contract_retention_scale'] = 'y';
                break;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input): bool|array
    {
        $input = self::prepareInputForAdd($input);

        return parent::prepareInputForUpdate($input);
    }
}
