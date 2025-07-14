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

use Ajax;
use CommonDBTM;
use CommonGLPI;
use Dropdown;

class Record_Retention extends CommonDBTM
{
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
        global $CFG_GLPI;

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
        $rand = mt_rand(1, mt_getrandmax());

        $options['canedit'] = $canedit;
        $options['formtitle'] = __("Manage data retention", 'gdprropa');

        $this->initForm($this->fields['id'], $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_3'><td colspan='4'><center><strong>";
        echo __("GDPR Article 30 1f", 'gdprropa');
        echo "</strong></center></td></tr>";

        echo "<tr class='tab_bg_2'><td width='16%'>";
        echo __("Retention regulated by", 'gdprropa');
        echo "</td><td width='84%'>";
        $rand = self::dropdownTypes(
            'type',
            array_key_exists('type', $this->fields) ? $this->fields['type'] : null
        );
        echo "</td></tr>";
        echo "<tr><td colspan='2'></td></tr>";

        echo "<tr><td></td>";
        echo "<td>";

        $contract_id = 0;
        if (array_key_exists('contracts_id', $this->fields)) {
            $contract_id = $this->fields['contracts_id'];
        }

        $this->fields['entities_id'] = $record->fields['entities_id'];
        $this->fields['is_record_recursive'] = $record->fields['is_recursive'];

        $params = [
            'type' => '__VALUE__',
            'contracts_id' => $contract_id,
            'id' => $this->fields['id'],
            'entities_id' => $record->fields['entities_id'],
            'is_record_recursive' => $record->fields['is_recursive'],
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_type$rand",
            'retention_type_div',
            $CFG_GLPI['root_doc'] . '/plugins/gdprropa/ajax/record_retention_retention_type_dropdown.php',
            $params
        );

        echo "<div id='retention_type_div'>";
        if ($record->fields['id'] < 1 || array_key_exists('type', $this->fields)) {
            switch ($this->fields['type']) {
                case Record_Retention::RETENTION_TYPE_NONE:
                    break;
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
        }
        echo "</div>";

        echo "</td></tr>";

        echo "<tr><td></td><td>" . __("Additional information", 'gdprropa') . "<br>";
        echo "";
        $additional_info = '';
        if (array_key_exists('additional_info', $this->fields)) {
            $additional_info = $this->fields['additional_info'];
        }
        echo "<textarea style='width:98%' name='additional_info' maxlength='1000' rows='3'>" .
            $additional_info . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center' colspan='4'>";
        echo "<input type='hidden' name='plugin_gdprropa_records_id' value='" . $record->fields['id'] . "' />";
        echo "<input type='hidden' name='is_record_recursive' value='" . $record->fields['is_recursive'] . "' />";
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }

    public static function showContractInputs($data = []): void
    {
        global $CFG_GLPI;

        $rand = mt_rand(1, mt_getrandmax());

        $value = null;
        if (array_key_exists('contracts_id', $data)) {
            $value = $data['contracts_id'];
        }

        echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>";
        echo "<tr>";
        echo "<td width='24%'>" . __("Contract name", 'gdprropa') . "</td>";
        echo "<td width='76%'>";

        $contractTypes = [];

        if (Config::getConfig('system', 'limit_retention_contracttypes')) {
            $contractTypes = ControllerInfo::getContractTypes($data['entities_id']);

            $info = Record_Contract::showContractTypesNotSetInfo($contractTypes, $data['entities_id']);
            if (!empty($info)) {
                echo $info;
            }
        }

        Record_Contract::getContractsDropdown([
            'name' => 'contracts_id',
            'entity' => $data['is_record_recursive']
                ? getSonsOf('glpi_entities', $data['entities_id']) : $data['entities_id'],
            'entity_sons' => !$data['is_record_recursive'],
            'expired' => Config::getConfig('system', 'allow_select_expired_contracts'),
            'nochecklimit' => true,
            'width' => '100%',
            'value' => $value,
            'contracttypes' => array_values($contractTypes),
            'used' => [],
        ]);

        echo "</td></tr>";
        echo "<tr>";
        echo "<td>" . __("Until contract is valid", 'gdprropa') . "</td>";
        echo "<td>";

        $value = null;
        if (array_key_exists('contract_until_is_valid', $data)) {
            $value = $data['contract_until_is_valid'];
        } else {
            if ($data['id'] < 0) {
                $value = 0;
            }
        }

        $rand = Dropdown::showYesNo('contract_until_is_valid', $value);
        echo "</td></tr>";
        echo "<tr>";
        echo "<td colspan='2' style='padding-left: 50px;'>";

        $params = ['checked' => '__VALUE__'];
        Ajax::updateItemOnEvent(
            "dropdown_contract_until_is_valid$rand",
            'retention_contract_after',
            $CFG_GLPI['root_doc'] . '/plugins/gdprropa/ajax/record_retention_contract_until_is_valid.php',
            $params
        );

        echo "<div id='retention_contract_after'>";
        if (
            ($data['id'] < 1) ||
            (array_key_exists('contract_until_is_valid', $data) && ($data['contract_until_is_valid'] != 1))
        ) {
            self::showContractUntilIsValidInputs($data);
        }
        echo "</div>";
        echo "</tr></tr>";
        echo "</table>";
    }

    public static function showContractUntilIsValidInputs($data = []): void
    {
        echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>";
        echo "<tr>";
        echo "<td>" . __("Period", 'gdprropa') . "</td>";

        echo "<td>";

        $value = null;
        if (array_key_exists('contract_retention_value', $data)) {
            $value = $data['contract_retention_value'];
        }
        echo "<input type='number' name='contract_retention_value' value='$value'>&nbsp;&nbsp;";

        $params = [];
        if (array_key_exists('contract_retention_scale', $data)) {
            $params['value'] = $data['contract_retention_scale'];
        }
        self::dropdownRetentionScheduleScales('contract_retention_scale', $params);
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __("After contract is terminated", 'gdprropa') . "</td>";
        echo "<td>";

        $value = null;
        if (array_key_exists('contract_after_end_of', $data)) {
            $value = $data['contract_after_end_of'];
        }
        Dropdown::showYesNo('contract_after_end_of', $value);
        echo "</td></tr>";
        echo "</table>";
    }

    public static function showLegalBasesInputs($data = []): void
    {
        $value = null;
        if (array_key_exists('plugin_gdprropa_legalbasisacts_id', $data)) {
            $value = $data['plugin_gdprropa_legalbasisacts_id'];
        }

        echo __("Legal bases", 'gdprropa') . "&nbsp;&nbsp;&nbsp;";
        LegalBasisAct::dropdown([
            'name' => 'plugin_gdprropa_legalbasisacts_id',
            'value' => $value,
            'entity' => $data['is_record_recursive']
                ? getSonsOf('glpi_entities', $data['entities_id']) : $data['entities_id'],
            //         'entity_sons' => $data['is_record_recursive'],
            'used' => [],
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
