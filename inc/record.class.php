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
use Document_Item;
use Dropdown;
use Glpi\Toolbox\Sanitizer;
use Html;
use Log;
use Notepad;
use Session;
use State;

class Record extends CommonDBTM
{
    public static $rightname = 'plugin_gdprropa_record';

    // TODO this should be remover or set to false - its a hack that remove error that is shown due to 'wrong' itemtype
    //      connection with table names, it has something to do with class names that contains '_' ie. Record_Contract
    //      this example itemtype doesnt map to a valid table name and it generates exception
    //      when rendering twig header.
    protected static $showTitleInNavigationHeader = true;

    public $dohistory = true;

    protected $usenotepad = true;

    public const STORAGE_MEDIUM_UNDEFINED = 0;
    public const STORAGE_MEDIUM_PAPER_ONLY = 1;
    public const STORAGE_MEDIUM_MIXED = 4;
    public const STORAGE_MEDIUM_ELECTRONIC = 8;

    private const PIA_STATUS_UNDEFINED = 0;
    private const PIA_STATUS_TODO = 1;
    private const PIA_STATUS_QUALIFICATION = 2;
    private const PIA_STATUS_APPROVAL = 4;
    private const PIA_STATUS_PENDING = 8;
    private const PIA_STATUS_CLOSED = 16;

    public static function getTypeName($nb = 0): string
    {
        return __("GDPR Record of Processing Activities", 'gdprropa');
    }

    public function showForm($ID, $options = []): bool
    {
        global $CFG_GLPI;

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __("Processing activity", 'gdprropa') . "</td>";
        echo "<td colspan='2'>";
        $processingActivity = Html::cleanInputText($this->fields['name']);
        echo "<input type='text' style='width:98%' maxlength=250 name='name' required value='" .
            $processingActivity . "'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __("Purpose (GDPR Article 30 1b)", 'gdprropa') . "</td>";
        echo "<td colspan='2'>";
        $purpose = Sanitizer::sanitize($this->fields['content']);
        echo "<textarea style='width:98%' name='content' required maxlength='1000' rows='3'>" .
            $purpose . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __("Status") . "</td>";
        echo "<td colspan='2'>";
        State::dropdown([
            'value' => $this->fields['states_id']
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Storage medium", 'gdprropa') . "</td>";
        echo "<td colspan='2'>";
        self::dropdownStorageMedium('storage_medium', $this->fields['storage_medium']);
        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td>" . __("PIA required", 'gdprropa') . "</td>";
        echo "<td>";
        $rand = Dropdown::showYesNo('pia_required', $this->fields['pia_required']);

        $params = [
            'pia_required' => '__VALUE__',
            'pia_status' => $this->fields['pia_status']
        ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_pia_required$rand",
            'pia_status_td',
            $CFG_GLPI['root_doc'] . '/plugins/gdprropa/ajax/record_pia_required_dropdown.php',
            $params
        );

        echo "<td colspan='2' id='pia_status_td'>";
        Record::showPIAStatus($this->fields);
        echo "</td>";

        echo "</tr>";
        echo "<tr class='tab_bg_1'><td>" . __("Consent required", 'gdprropa') . "</td>";
        echo "<td colspan='2'>";
        $rand = Dropdown::showYesNo('consent_required', $this->fields['consent_required']);

        $params = [
            'consent_required' => '__VALUE__',
            'consent_storage' => $this->fields['consent_storage']
        ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_consent_required$rand",
            'consent_storage_tr',
            $CFG_GLPI['root_doc'] . '/plugins/gdprropa/ajax/record_consent_required_dropdown.php',
            $params
        );

        echo "</td></tr>";

        echo "<tr class='tab_bg_1' id='consent_storage_tr'>";
        Record::showConsentRequired($this->fields);
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __("First entry date", 'gdprropa') . "</td>";
        echo "<td colspan='2'>";
        Html::showDateField('first_entry_date', ['value' => $this->fields['first_entry_date']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __("Additional information", 'gdprropa') . "</td>";
        echo "<td colspan='2'>";
        $additional_info = Sanitizer::sanitize($this->fields['additional_info']);
        echo "<textarea style='width: 98%;' name='additional_info' maxlength='1000' rows='3'>" .
            $additional_info . "</textarea>";
        echo "</td></tr>";
        $this->showFormButtons($options);

        return true;
    }

    public static function showPIAStatus($data = []): void
    {
        if ($data['pia_required']) {
            echo "&nbsp;&nbsp;&nbsp;" . __("Status") . "&nbsp;&nbsp;";
            self::dropdownPiaStatus('pia_status', $data['pia_status']);
        }
    }

    public static function showConsentRequired($data = []): void
    {
        if ($data['consent_required']) {
            echo "<td>" . __("Consent storage", 'gdprropa') . "</td>";
            echo "<td colspan='2'>";
            $consent_storage = Sanitizer::sanitize($data['consent_storage']);
            echo "<textarea style='width: 98%;' name='consent_storage' maxlength='1000' rows='3'>" .
                $consent_storage . "</textarea>";
            echo "</td>";
        }
    }

    public function defineTabs($options = []): array
    {
        $ong = [];
//        echo '<pre>';
//print_r($_SESSION['glpimenu']); die;
        $this
            ->addDefaultFormTab($ong)
            ->addStandardTab(__CLASS__, $ong, $options)
            ->addStandardTab(Record_DataSubjectsCategory::class, $ong, $options)
            ->addStandardTab(Record_LegalBasisAct::class, $ong, $options)
            ->addStandardTab(Record_Retention::class, $ong, $options)
            ->addStandardTab(Record_Contract::class, $ong, $options)
            ->addStandardTab(Record_PersonalDataCategory::class, $ong, $options)
            ->addStandardTab(Record_Software::class, $ong, $options)
            ->addStandardTab(Record_SecurityMeasure::class, $ong, $options)
            ->addStandardTab(Document_Item::class, $ong, $options)
            ->addStandardTab(Notepad::class, $ong, $options)
            ->addStandardTab(CreatePDF::class, $ong, $options)
            ->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function cleanDBonPurge(): void
    {
        $this->deleteChildrenAndRelationsFromDb([
            Record_Contract::class,
            Record_DataSubjectsCategory::class,
            Record_LegalBasisAct::class,
            Record_PersonalDataCategory::class,
            Record_Retention::class,
            Record_SecurityMeasure::class,
            Record_Software::class,
        ]);

        $retention = new Record_Retention();
        $retention->deleteByCriteria(['plugin_gdprropa_records_id' => $this->fields['id']]);
    }

    public static function getAllPiaStatusArray($withmetaforsearch = false): array
    {
        $tab = [
            self::PIA_STATUS_UNDEFINED => __("Undefined", 'gdprropa'),
            self::PIA_STATUS_TODO => __("To do"),
            self::PIA_STATUS_QUALIFICATION => __("Qualification"),
            self::PIA_STATUS_APPROVAL => __("Approval"),
            self::PIA_STATUS_PENDING => __("Pending"),
            self::PIA_STATUS_CLOSED => __("Closed")
        ];

        if ($withmetaforsearch) {
            $tab['all'] = __("All");
        }

        return $tab;
    }

    public static function getAllStorageMediumArray($withmetaforsearch = false): array
    {
        $tab = [
            self::STORAGE_MEDIUM_UNDEFINED => __("Undefined", 'gdprropa'),
            self::STORAGE_MEDIUM_PAPER_ONLY => __("Paper only", 'gdprropa'),
            self::STORAGE_MEDIUM_MIXED => __("Paper and electronic", 'gdprropa'),
            self::STORAGE_MEDIUM_ELECTRONIC => __("Electronic only", 'gdprropa'),
        ];

        if ($withmetaforsearch) {
            $tab['all'] = __("All");
        }

        return $tab;
    }

    public static function dropdownStorageMedium($name, $value = 0, $display = true): int|string|null
    {
        return Dropdown::showFromArray($name, self::getAllStorageMediumArray(), [
            'value' => $value,
            'display' => $display
        ]);
    }

    public static function dropdownPiaStatus($name, $value = 0, $display = true): int|string|null
    {
        return Dropdown::showFromArray($name, self::getAllPiastatusArray(), ['value' => $value, 'display' => $display]);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'pia_status':
                if (!$values[$field]) {
                    return '&nbsp;';
                }
                $pia_status = self::getAllPiastatusArray();

                return $pia_status[$values[$field]];
            case 'storage_medium':
                $storage_medium = self::getAllStorageMediumArray();

                return $storage_medium[$values[$field]];
        }

        return '';
    }

    public static function getSpecificValueToSelect(
        $field,
        $name = '',
        $values = '',
        array $options = []
    ): int|string|null {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'pia_status':
                return self::dropdownPiaStatus($name, $values[$field], false);
            case 'storage_medium':
                return self::dropdownStorageMedium($name, $values[$field], false);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function prepareInputForAdd($input): bool|array
    {
        $input['users_id_creator'] = Session::getLoginUserID();

        if (array_key_exists('pia_required', $input) && $input['pia_required'] == 0) {
            $input['pia_status'] = Record::PIA_STATUS_UNDEFINED;
        }

        if (array_key_exists('consent_required', $input) && $input['consent_required'] == 0) {
            $input['consent_storage'] = null;
        }

        if (array_key_exists('first_entry_date', $input) && $input['first_entry_date'] == '') {
            $input['first_entry_date'] = null;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input): bool|array
    {
        $input['users_id_lastupdater'] = Session::getLoginUserID();

        if (array_key_exists('pia_required', $input) && $input['pia_required'] == 0) {
            $input['pia_status'] = Record::PIA_STATUS_UNDEFINED;
        }

        if (array_key_exists('consent_required', $input) && $input['consent_required'] == 0) {
            $input['consent_storage'] = null;
        }

        if (array_key_exists('first_entry_date', $input) && $input['first_entry_date'] == '') {
            $input['first_entry_date'] = null;
        }

        return parent::prepareInputForUpdate($input);
    }

    public function post_updateItem($history = 1): void
    {
        if (
            ($this->fields['storage_medium'] == self::STORAGE_MEDIUM_PAPER_ONLY) &&
            (Config::getConfig('system', 'remove_software_when_paper_only'))
        ) {
            $del = new Record_Software();
            $del->deleteByCriteria(['plugin_gdprropa_records_id' => $this->fields['id']]);
        }
    }

    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => __('Characteristics')
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __("Name"),
            'datatype' => 'itemlink',
            'searchtype' => 'contains',
            'massiveaction' => false
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __("ID"),
            'massiveaction' => false,
            'datatype' => 'number'
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'content',
            'name' => __("Purpose", 'gdprropa'),
            'massiveaction' => false,
            'htmltext' => true
        ];

        $tab[] = [
            'id' => '4',
            'table' => $this->getTable(),
            'field' => 'additional_info',
            'name' => __("Additional information", 'gdprropa'),
            'massiveaction' => true,
            'htmltext' => true
        ];

        $tab[] = [
            'id' => '5',
            'table' => 'glpi_states',
            'field' => 'completename',
            'name' => __("Status"),
            'massiveaction' => true,
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '6',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __("Entity"),
            'massiveaction' => true,
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __("Child entities"),
            'massiveaction' => false,
            'datatype' => 'bool'
        ];

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'pia_required',
            'name' => __("PIA required", 'gdprropa'),
            'massiveaction' => true,
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '9',
            'table' => $this->getTable(),
            'field' => 'pia_status',
            'name' => __("PIA status", 'gdprropa'),
            'searchtype' => ['equals', 'notequals'],
            'massiveaction' => true,
            'datatype' => 'specific',
        ];

        $tab[] = [
            'id' => '10',
            'table' => $this->getTable(),
            'field' => 'storage_medium',
            'name' => __("Storage medium", 'gdprropa'),
            'searchtype' => ['equals', 'notequals'],
            'massiveaction' => true,
            'datatype' => 'specific'
        ];

        $tab = array_merge(
            $tab,
            Record_Contract::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            Record_LegalBasisAct::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            Record_DataSubjectsCategory::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            Record_SecurityMeasure::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            Record_PersonalDataCategory::rawSearchOptionsToAdd()
        );

        return array_merge(
            $tab,
            Record_Software::rawSearchOptionsToAdd()
        );
    }

    public static function dashboardCards($cards = []): array
    {
        if (is_null($cards)) {
            $cards = [];
        }
        $newCards = [
            'plugin_gdprropa_card_with_core_widget' => [
                'widgettype' => ["bigNumber"],
                'label' => self::getTypeName(2),
                'provider' => Record::class . "::cardBigNumberRecordsCount",
            ],
        ];

        return array_merge($cards, $newCards);
    }

    public static function cardBigNumberRecordsCount(array $params = []): array
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'COUNT' => '* as cpt'
            ],
            'FROM' => self::getTable(),
            'WHERE' => ['is_deleted' => '0'] + getEntitiesRestrictCriteria(self::getTable())
        ]);
        $num = $iterator->current()['cpt'];

        return [
            'number' => $num,
            'url' => "/plugins/gdprropa/front/record.php",
            'label' => self::getTypeName($num),
            'alt' => self::getTypeName($num),
            'icon' => "fas ti ti-report",
        ];
    }
}
