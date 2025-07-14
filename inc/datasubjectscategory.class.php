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

use CommonDropdown;
use Session;

class DataSubjectsCategory extends CommonDropdown
{
    public static $rightname = 'plugin_gdprropa_datasubjectscategory';

    // TODO check description in Record class
    protected static $showTitleInNavigationHeader = true;

    public $dohistory = true;

    protected $usenotepad = true;

    public static function getTypeName($nb = 0): string
    {
        return _n("Category of data subjects", "Categories of data subjects", $nb, 'gdprropa');
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

    public function cleanDBonPurge(): void
    {
        $rel = new Record_DataSubjectsCategory();
        $rel->deleteByCriteria(['plugin_gdprropa_datasubjectscategories_id' => $this->fields['id']]);
    }

    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __("Characteristics")
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __("Name"),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __("ID"),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __("Comments"),
            'datatype'           => 'text',
            'toview'             => true,
            'massiveaction'      => true,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => __("Entity"),
            'datatype'           => 'dropdown',
            'massiveaction'      => true,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __("Child entities"),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        return $tab;
    }
}
