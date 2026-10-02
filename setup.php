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

use Glpi\Plugin\Hooks;
use GlpiPlugin\Gdprropa\ControllerInfo;
use GlpiPlugin\Gdprropa\Profile as GdprropaProfile;
use GlpiPlugin\Gdprropa\Record;
use GlpiPlugin\Gdprropa\Menu;

define('GDPRROPA_PLUGIN_VERSION', '1.1.0');

// Minimal GLPI version, inclusive
define('GDPRROPA_PLUGIN_MIN_GLPI_VERSION', '11.0.0');
// Maximum GLPI version, exclusive
define('GDPRROPA_PLUGIN_MAX_GLPI_VERSION', '11.99.99');

function plugin_init_gdprropa()
{
    global $PLUGIN_HOOKS;

    if (Session::getLoginUserID()) {
        Plugin::registerClass(GdprropaProfile::class, ['addtabon' => Profile::class]);
        Plugin::registerClass(Record::class);

        $PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['gdprropa'] = [GdprropaProfile::class, 'initProfile'];

        $plugin = new Plugin();
        if (
            $plugin->isActivated('gdprropa') &&
            Session::haveRight('plugin_gdprropa_record', READ)
        ) {
            $PLUGIN_HOOKS[Hooks::MENU_TOADD]['gdprropa'] = ['management' => Menu::class];
        }

        if (
            Session::haveRight('plugin_gdprropa_record', UPDATE) ||
            Session::haveRight('config', UPDATE)
        ) {
            $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['gdprropa'] = 'front/config.form.php';
        }

        Plugin::registerClass(ControllerInfo::class, ['addtabon' => Entity::class]);

        $PLUGIN_HOOKS[Hooks::POST_INIT]['gdprropa'] = 'plugin_gdprropa_postinit';

        $PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS]['gdprropa'] = [Record::class, 'dashboardCards'];

        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_MODULE]['gdprropa'][] = 'js/gdprropa.js';
    }
}

function plugin_version_gdprropa()
{
    return [
        'name' => __('GDPR Record of Processing Activities', 'gdprropa'),
        'version' => GDPRROPA_PLUGIN_VERSION,
        'author' => "<a href='https://github.com/yild/'>Yild</a>",
        'license' => 'GPLv3',
        'homepage' => 'https://github.com/yild/gdprropa',
        'requirements' => [
            'glpi' => [
                'min' => GDPRROPA_PLUGIN_MIN_GLPI_VERSION,
                'max' => GDPRROPA_PLUGIN_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

function plugin_gdprropa_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, '11.0', 'lt')) {
        if (method_exists('Plugin', 'messageIncompatible')) {
            echo Plugin::messageIncompatible('core', '11.0');
        } else {
            echo "This plugin requires GLPI >= 11.0";
        }
        return false;
    }

    return true;
}

function plugin_gdprropa_check_config($verbose = false)
{
    return true;
}
