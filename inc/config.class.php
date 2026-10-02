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
use ContractType;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Config extends CommonDBTM
{
    public const PLUGIN_VERSION = '1.1.0';

    public const PLUGIN_MIN_GLPI_VERSION = '11.0.0';
    public const PLUGIN_MAX_GLPI_VERSION = '11.99.99';

    public static $rightname = 'plugin_gdprropa_record';

    protected array $core_tcpdf_fonts = [
        'courier' => 'Courier',
        'courierB' => 'Courier-Bold',
        'courierI' => 'Courier-Oblique',
        'courierBI' => 'Courier-BoldOblique',
        'dejavusans' => 'DejaVu Sans',
        'helvetica' => 'Helvetica',
        'helveticaB' => 'Helvetica-Bold',
        'helveticaI' => 'Helvetica-Oblique',
        'helveticaBI' => 'Helvetica-BoldOblique',
        'times' => 'Times-Roman',
        'timesB' => 'Times-Bold',
        'timesI' => 'Times-Italic',
        'timesBI' => 'Times-BoldItalic',
        'symbol' => 'Symbol',
        'zapfdingbats' => 'ZapfDingbats'
    ];

    public static function getTypeName($nb = 0): string
    {
        return _n("Config", "Config", $nb, 'gdprropa');
    }

    public static function getConfigDefault(): array
    {
        $config = [];

        $config['system'] = [
            'keep_is_special_category_strict' => 1,
            'limit_retention_contracttypes' => 1,
            'remove_software_when_paper_only' => 1,
            'allow_select_expired_contracts' => 1,
            'allow_software_from_every_entity' => 0,
            'allow_controllerinfo_from_ancestor' => 1,
        ];
        $config['print'] = [
            'codepage' => 'UTF-8',
            'font_name' => 'dejavusans',
            'font_size' => 8,
            'margin_left' => 10,
            'margin_top' => 30,
            'margin_right' => 10,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'logo_show' => 1,
            'logo_image' => 'gdprropa_logo.png',
        ];

        return $config;
    }

    public static function getConfig($key = '', $key2 = '')
    {
        $config = new Config();
        $config->getFromDBByCrit(['entities_id' => 0]);

        if (isset($config->fields['id'])) {
            $config = importArrayFromDB($config->fields['config']);

            if (!empty($key)) {
                if (!empty($key2)) {
                    return $config[$key][$key2];
                } else {
                    return $config[$key];
                }
            } else {
                return $config;
            }
        } else {
            // get default config
            $default = Config::getConfigDefault();

            if (!empty($key)) {
                if (!empty($key2)) {
                    return $default[$key][$key2];
                } else {
                    return $default[$key];
                }
            } else {
                return $default;
            }
        }
    }

    private function prepareJSON($input)
    {
        $array = [];

        foreach (Config::getConfigDefault() as $key => $value) {
            $array[$key] = $input[$key];
            unset($input[$key]);
        }

        $input['config'] = exportArrayToDB($array);

        return $input;
    }

    public function prepareInputForAdd($input): bool|array
    {
        $input = $this->prepareJSON($input);
        $input['users_id_creator'] = Session::getLoginUserID();

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input): bool|array
    {
        $input = $this->prepareJSON($input);
        $input['users_id_lastupdater'] = Session::getLoginUserID();

        return parent::prepareInputForUpdate($input);
    }

    public function showForm($ID, $options = []): void
    {
        if (!self::canUpdate()) {
            return;
        }

        $this->getFromDBByCrit(['entities_id' => 0]);

        $config = self::getConfig();

        TemplateRenderer::getInstance()->display('@gdprropa/config.html.twig', [
            'config'           => $config,
            'config_id'        => $this->fields['id'] ?? 0,
            'core_tcpdf_fonts' => $this->core_tcpdf_fonts,
        ]);
    }

    public function installSampleData($data = []): void
    {
        if (
            isset($data['install_categories_of_data_subjects']) &&
            ($data['install_categories_of_data_subjects'] == 1)
        ) {
            $sample_data = new DataSubjectsCategory();
            $sample_data->add([
                'name' => __("Employees", 'gdprropa'),
                'comment' => __("Company employees", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            $sample_data = new DataSubjectsCategory();
            $sample_data->add([
                'name' => __("Consumers", 'gdprropa'),
                'comment' => __("Company consumers/clients", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
        }

        if (isset($data['install_legal_bases']) && ($data['install_legal_bases'] == 1)) {
            $sample_data = new LegalBasisAct();
            $sample_data->add([
                'name' => __("Undefined", 'gdprropa'),
                'content' => __("N/A", 'gdprropa'),
                'type' => LegalBasisAct::LEGALBASISACT_BLANK,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
            $sample_data->add([
                'name' => __("Article 6-1a", 'gdprropa'),
                'content' => __(
                    "The data subject has given consent to the processing of his or her " .
                    "personal data for one or more specific purposes.",
                    'gdprropa'
                ),
                'type' => LegalBasisAct::LEGALBASISACT_GDPR,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
            $sample_data->add([
                'name' => __("Article 6-1b", 'gdprropa'),
                'content' => __(
                    "Processing is necessary for the performance of a contract to which the data subject is " .
                    "party or in order to take steps at the request of the data subject prior to entering into a " .
                    "contract.",
                    'gdprropa'
                ),
                'type' => LegalBasisAct::LEGALBASISACT_GDPR,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
            $sample_data->add([
                'name' => __("Article 6-1c", 'gdprropa'),
                'content' => __(
                    "Processing is necessary for compliance with a legal obligation to which " .
                    "the controller is subject.",
                    'gdprropa'
                ),
                'type' => LegalBasisAct::LEGALBASISACT_GDPR,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
            $sample_data->add([
                'name' => __("Article 6-1d", 'gdprropa'),
                'content' => __(
                    "Processing is necessary in order to protect the vital interests of the " .
                    "data subject or of another natural person.",
                    'gdprropa'
                ),
                'type' => LegalBasisAct::LEGALBASISACT_GDPR,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
            $sample_data->add([
                'name' => __("Article 6-1e", 'gdprropa'),
                'content' => __(
                    "Processing is necessary for the performance of a task carried out in the " .
                    "public interest or in the exercise of official authority vested in the " .
                    "controller.",
                    'gdprropa'
                ),
                'type' => LegalBasisAct::LEGALBASISACT_GDPR,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
            $sample_data->add([
                'name' => __("Article 6-1f", 'gdprropa'),
                'content' => __(
                    "Processing is necessary for the purposes of the legitimate interests pursued " .
                    "by the controller or by a third party, except where such interests are overridden by the " .
                    "interests or fundamental rights and freedoms of the data subject which require protection of " .
                    "personal data, in particular where the data subject is a child.",
                    'gdprropa'
                ),
                'type' => LegalBasisAct::LEGALBASISACT_GDPR,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'injected' => 1,
            ]);
        }

        if (isset($data['install_security_measures']) && ($data['install_security_measures'] == 1)) {
            $sample_data = new SecurityMeasure();
            $sample_data->add([
                'name' => __("DPO was appointed", 'gdprropa'),
                'type' => SecurityMeasure::SECURITYMEASURE_TYPE_ORGANIZATION,
                'comment' => __("Data Protection Officer was appointed", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            $sample_data->add([
                'name' => __("Computers Usage Policy", 'gdprropa'),
                'type' => SecurityMeasure::SECURITYMEASURE_TYPE_ORGANIZATION,
                'comment' => __("Internal policy regarding usage of computers", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            $sample_data->add([
                'name' => __("24h Security", 'gdprropa'),
                'type' => SecurityMeasure::SECURITYMEASURE_TYPE_PHYSICAL,
                'comment' => __("Securtiy personel on site for 24h", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            $sample_data->add([
                'name' => __("UPS system", 'gdprropa'),
                'type' => SecurityMeasure::SECURITYMEASURE_TYPE_PHYSICAL,
                'comment' => __("Uninterruptidle power supply is installed", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            $sample_data->add([
                'name' => __("Antivirus App", 'gdprropa'),
                'type' => SecurityMeasure::SECURITYMEASURE_TYPE_IT,
                'comment' => __("Computers have Antivirus app installed", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            $sample_data->add([
                'name' => __("Firewall", 'gdprropa'),
                'type' => SecurityMeasure::SECURITYMEASURE_TYPE_IT,
                'comment' => __("Firewall protects internal network", 'gdprropa'),
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
        }

        if (isset($data['install_contract_types']) && ($data['install_contract_types'] == 1)) {
            $sample_data = new ContractType();
            $sample_data->add([
                'name' => __("GDPR Joint Controller Contract", 'gdprropa'),
                'comment' => __("GDPR Joint Controller Contract", 'gdprropa'),
            ]);
            $sample_data->add([
                'name' => __("GDPR Processor Contract", 'gdprropa'),
                'comment' => __("GDPR Processor Contract", 'gdprropa'),
            ]);
            $sample_data->add([
                'name' => __("GDPR Thirdparty Contract", 'gdprropa'),
                'comment' => __("GDPR Thirdparty Contract", 'gdprropa'),
            ]);
            $sample_data->add([
                'name' => __("GDPR Internal Contract", 'gdprropa'),
                'comment' => __("GDPR Internal Contract", 'gdprropa'),
            ]);
            $sample_data->add([
                'name' => __("GDPR Other Contract", 'gdprropa'),
                'comment' => __("GDPR Other Contract", 'gdprropa'),
            ]);
        }

        if (isset($data['install_personal_data_types']) && ($data['install_personal_data_types'] == 1)) {
            $sample_data = new PersonalDataCategory();
            $parent_id = $sample_data->add([
                'name' => __("Employees record", 'gdprropa'),
                'comment' => __("Employee personal data", 'gdprropa'),
                'is_special_category' => false,
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]);
            if ($parent_id) {
                $sample_data->add([
                    'name' => __("First name", 'gdprropa'),
                    'comment' => __("Employee first name", 'gdprropa'),
                    'entities_id' => $_SESSION['glpiactive_entity'],
                    'is_special_category' => false,
                    'plugin_gdprropa_personaldatacategories_id' => $parent_id
                ]);
                $sample_data->add([
                    'name' => __("Last name", 'gdprropa'),
                    'comment' => __("Employee last name", 'gdprropa'),
                    'entities_id' => $_SESSION['glpiactive_entity'],
                    'is_special_category' => false,
                    'plugin_gdprropa_personaldatacategories_id' => $parent_id
                ]);
                $sample_data->add([
                    'name' => __("Personal ID", 'gdprropa'),
                    'comment' => __("Employee personal ID", 'gdprropa'),
                    'entities_id' => $_SESSION['glpiactive_entity'],
                    'is_special_category' => true,
                    'plugin_gdprropa_personaldatacategories_id' => $parent_id
                ]);
            }
        }
    }
}
