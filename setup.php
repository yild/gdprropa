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

use GlpiPlugin\Gdprropa\ControllerInfo;
use GlpiPlugin\Gdprropa\Profile as GdprropaProfile;
use GlpiPlugin\Gdprropa\Record;
use GlpiPlugin\Gdprropa\Menu;

define('PLUGIN_GDPRROPA_VERSION', '1.0');
define('PLUGIN_GDPRROPA_ROOT', __DIR__);

// Minimal GLPI version, inclusive
define('PLUGIN_GDPRROPA_MIN_GLPI', '10.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_GDPRROPA_MAX_GLPI', '10.0.99');

function plugin_init_gdprropa()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['gdprropa'] = true;

    if (Session::getLoginUserID()) {
        Plugin::registerClass(GdprropaProfile::class, ['addtabon' => Profile::class]);
        Plugin::registerClass(Record::class);

        $PLUGIN_HOOKS['change_profile']['gdprropa'] = ['Profile', 'initProfile'];

        $plugin = new Plugin();
        if (
            $plugin->isActivated('gdprropa') &&
            Session::haveRight('plugin_gdprropa_record', READ)
        ) {
            $PLUGIN_HOOKS['menu_toadd']['gdprropa'] = ['management' => Menu::class];
        }

        if (
            Session::haveRight('plugin_gdprropa_record', UPDATE) ||
            Session::haveRight('config', UPDATE)
        ) {
            $PLUGIN_HOOKS['config_page']['gdprropa'] = 'front/config.form.php';
        }

        Plugin::registerClass(ControllerInfo::class, ['addtabon' => Entity::class]);

        $PLUGIN_HOOKS['post_init']['gdprropa'] = 'plugin_gdprropa_postinit';
    }
}

function plugin_version_gdprropa()
{
    return [
        'name' => __('GDPR Record of Processing Activities', 'gdprropa'),
        'version' => PLUGIN_GDPRROPA_VERSION,
        'author' => "<a href='https://github.com/yild/'>Yild</a>",
        'license' => 'GPLv3',
        'homepage' => 'https://github.com/yild/gdprropa',
        'minGlpiVersion' => PLUGIN_GDPRROPA_MIN_GLPI,
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_GDPRROPA_MIN_GLPI,
                'max' => PLUGIN_GDPRROPA_MAX_GLPI,
            ]
        ]
    ];
}

function plugin_gdprropa_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, '10.0', 'lt')) {
        if (method_exists('Plugin', 'messageIncompatible')) {
            echo Plugin::messageIncompatible('core', '10.0');
        } else {
            echo "This plugin requires GLPI >= 10.0";
        }
        return false;
    }

    return true;
}

function plugin_gdprropa_check_config($verbose = false)
{
    if (true) {
        return true;
    }

    if ($verbose) {
        echo __("Installed / not configured");
    }

    return false;
}
