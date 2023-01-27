<?php
/*
 -------------------------------------------------------------------------
 GDPR Records of Processing Activities plugin for GLPI
 Copyright (C) 2020 by Yild.

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
 If not, see <http://www.gnu.org/licenses/>.

 Based on DPO Register plugin, by Karhel Tmarr.

 --------------------------------------------------------------------------

  @package   gdprropa
  @author    Yild
  @copyright Copyright (c) 2020 by Yild
  @license   GPLv3+
             http://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/yild/gdprropa
  @since     2020
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginGdprropaConfig extends CommonDBTM {

   static $rightname = 'plugin_gdprropa_record';

   protected $core_tcpdf_fonts = [
      'courier'=>'Courier',
      'courierB'=>'Courier-Bold',
      'courierI'=>'Courier-Oblique',
      'courierBI'=>'Courier-BoldOblique',
      'dejavusans' => 'DejaVu Sans',
      'helvetica'=>'Helvetica',
      'helveticaB'=>'Helvetica-Bold',
      'helveticaI'=>'Helvetica-Oblique',
      'helveticaBI'=>'Helvetica-BoldOblique',
      'times'=>'Times-Roman',
      'timesB'=>'Times-Bold',
      'timesI'=>'Times-Italic',
      'timesBI'=>'Times-BoldItalic',
      'symbol'=>'Symbol',
      'zapfdingbats'=>'ZapfDingbats'
   ];

   public static function getTypeName($nb = 0) {

      return _n("Config", "Config", $nb, 'gdprropa');
   }

   static function getConfigDefault() {

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

   static function getConfig($key = '', $key2 = '') {

      $config = new PluginGdprropaConfig();
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
         $default = PluginGdprropaConfig::getConfigDefault();

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

   private function prepareJSON($input) {

      $array = [];

      foreach (PluginGdprropaConfig::getConfigDefault() as $key => $value) {
         $array[$key] = $input[$key];
         unset($input[$key]);
      }

      $input['config'] = exportArrayToDB($array);

      return $input;
   }

   function prepareInputForAdd($input) {

      $input = $this->prepareJSON($input);
      $input['users_id_creator'] = Session::getLoginUserID();

      return parent::prepareInputForAdd($input);
   }

   function prepareInputForUpdate($input) {

      $input = $this->prepareJSON($input);
      $input['users_id_lastupdater'] = Session::getLoginUserID();

      return parent::prepareInputForUpdate($input);
   }

   public function showForm($ID = null, array $options = [] ) {

      if (!self::canUpdate()) {
         return;
      }

      $this->getFromDBByCrit(['entities_id' => 0]);

      $config = self::getConfig();

      echo "<div class='center' width='50%'>";
      echo "<form method='post' action='./config.form.php'>";
      echo "<table class='tab_cadre' cellpadding='5' width='50%'>";
      echo "<tr>";
      echo "<th colspan='2'>" . __("Manage GDPR RoPA configuration", 'gdprropa') . "</th>";
      echo "</tr>";
      echo "<tr>";
      echo "<td colspan='2' class='center b'>" . __("System configuration", 'gdprropa') . "</td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td width='80%'>" . __("Keep 'is special category' strict", 'gdprropa') . "</td>";
      echo "<td width='20%'>";

      dropdown::showYesNo('system[keep_is_special_category_strict]', $config['system']['keep_is_special_category_strict']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Limit retention contracts list to those selected in controller info", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showYesNo('system[limit_retention_contracttypes]', $config['system']['limit_retention_contracttypes']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Remove software when record storage medium set to Paper only if any was assigned previously", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showYesNo('system[remove_software_when_paper_only]', $config['system']['remove_software_when_paper_only']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Allow add expired contract (show expired on dropdown list)", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showYesNo('system[allow_select_expired_contracts]', $config['system']['allow_select_expired_contracts']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Allow add software from any entity", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showYesNo('system[allow_software_from_every_entity]', $config['system']['allow_software_from_every_entity']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Allow retrieve controller info from ancestor entity (set as recursive) when current entity controller info is not set", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showYesNo('system[allow_controllerinfo_from_ancestor]', $config['system']['allow_controllerinfo_from_ancestor']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='center b'>" . __("PDF creating configuration", 'gdprropa') . "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Codepage", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showFromArray('print[codepage]', ['UTF-8', 'ISO-8859-1', 'ISO-8859-2'], [
         'value' => $config['print']['codepage']
      ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Font name", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showFromArray('print[font_name]', $this->core_tcpdf_fonts, [
         'value' => $config['print']['font_name']
      ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Font size", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[font_size]', [
         'value' => $config['print']['font_size'],
         'min' => 8,
         'max' => 16,
         'step' => 1,
         'unit' => 'pt',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Margin left", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[margin_left]', [
         'value' => $config['print']['margin_left'],
         'min' => 5,
         'max' => 50,
         'unit' => 'mm',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Margin top", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[margin_top]', [
         'value' => $config['print']['margin_top'],
         'min' => 5,
         'max' => 50,
         'unit' => 'mm',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Margin right", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[margin_right]', [
         'value' => $config['print']['margin_right'],
         'min' => 5,
         'max' => 50,
         'unit' => 'mm',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Margin bottom", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[margin_bottom]', [
         'value' => $config['print']['margin_bottom'],
         'min' => 5,
         'max' => 50,
         'unit' => 'mm',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Header margin (from top)", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[margin_header]', [
         'value' => $config['print']['margin_header'],
         'min' => 10,
         'max' => 30,
         'unit' => 'mm',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Footer margin (from bottom)", 'gdprropa') . "</td>";
      echo "<td>";

      Dropdown::showNumber('print[margin_footer]', [
         'value' => $config['print']['margin_footer'],
         'min' => 10,
         'max' => 30,
         'unit' => 'mm',
         ]);
      echo "</td>";

      echo "<tr>";
      echo "<td>" . __("Show logo", 'gdprropa') . "</td>";
      echo "<td>";

      dropdown::showYesNo('print[logo_show]', $config['print']['logo_show']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Logo image filename (located in /plugins/gdprropa/images/)", 'gdprropa') . "</td>";
      echo "<td>";

      echo "<input type='text' maxlength='254' name='print[logo_image]' value=\"" . $config['print']['logo_image']  . "\">";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      echo "<div class='center'>";

      echo "<input type='hidden' name='entities_id' value=\"" . 0 . "\">";

      $value = 0;
      if (isset($this->fields['id'])) {
         $value = $this->fields['id'];
      }
      echo "<input type='hidden' name='id' value=\"" . $value . "\">";
      if (isset($this->fields['id'])) {
         $action = 'update';
      } else {
         $action = 'add';
      }
      echo "<input type='submit' name='" . $action . "' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
      echo "</div>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
      echo "<br><br>";

      echo "<form method='post' action='./config.form.php'>";
      echo "<table class='tab_cadre' cellpadding='5' width='50%'>";
      echo "<tr>";
      echo "<th colspan='2'>" . __("Install sample data", 'gdprropa') . "</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'><strong>" . __("Sample data will be installed for current active entity.", 'gdprropa') . "</strong></td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Categories of data subjects", 'gdprropa') . "</td>";
      echo "<td>";
      Html::showCheckbox([
         'name'  => 'install_categories_of_data_subjects',
         'title' => __("Categories of data subjects", 'gdprropa'),
         'checked' => 1]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Legal bases", 'gdprropa') . "</td>";
      echo "<td>";
      Html::showCheckbox([
         'name'  => 'install_legal_bases',
         'title' => __("Legal bases", 'gdprropa'),
         'checked' => 1]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Security measures", 'gdprropa') . "</td>";
      echo "<td>";
      Html::showCheckbox([
         'name'  => 'install_security_measures',
         'title' => __("Security measures", 'gdprropa'),
         'checked' => 1]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Contract types", 'gdprropa') . "</td>";
      echo "<td>";
      Html::showCheckbox([
         'name'  => 'install_contract_types',
         'title' => __("Contract types", 'gdprropa'),
         'checked' => 1]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Personal data types", 'gdprropa') . "</td>";
      echo "<td>";
      Html::showCheckbox([
         'name'  => 'install_personal_data_types',
         'title' => __("Personal data types", 'gdprropa'),
         'checked' => 1]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      echo "<div class='center'>";
      echo "<input type='submit' name='sampledata' value=\"" . __("Install", 'gdprropa') . "\" class='submit'>";
      echo "</div>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";

   }

   function installSampleData($data = []) {

      if (isset($data['install_categories_of_data_subjects']) && ($data['install_categories_of_data_subjects'] == 1)) {
         $sample_data = new PluginGdprropaDataSubjectsCategory();
         $sample_data->add([
            'name' => __("Employées", 'gdprropa'),
            'comment' => __("Employés de l\'entreprise", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         $sample_data = new PluginGdprropaDataSubjectsCategory();
         $sample_data->add([
            'name' => __("Consommateurs", 'gdprropa'),
            'comment' => __("Consommateurs/clients de l\'entreprise", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
      }

      if (isset($data['install_legal_bases']) && ($data['install_legal_bases'] == 1)) {
         $sample_data = new PluginGdprropaLegalBasisAct();
         $sample_data->add([
            'name' => __("Indéfini", 'gdprropa'),
            'description' => __("N/A", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_BLANK,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
         $sample_data->add([
            'name' => __("Article 6-1a", 'gdprropa'),
            'comment' => __("Consentement", 'gdprropa'),
            'description' =>  __("La personne concernée a donné son consentement au traitement de ses données personnelles pour une ou plusieurs finalités spécifiques.", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_GDPR,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
         $sample_data->add([
            'name' => __("Article 6-1b", 'gdprropa'),
            'comment' => __("Exécution d\'un contrat", 'gdprropa'),
            'description' => __("Le traitement est nécessaire à l\'exécution d\'un contrat auquel la personne concernée est partie ou pour prendre des mesures à la demande de la personne concernée avant la conclusion d\'un contrat.", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_GDPR,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
         $sample_data->add([
            'name' => __("Article 6-1c", 'gdprropa'),
            'comment' => __("Respect d\'une obligation légale", 'gdprropa'),
            'description' => __("Le traitement est nécessaire au respect d\'une obligation légale à laquelle le responsable du traitement est soumis.", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_GDPR,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
         $sample_data->add([
            'name' => __("Article 6-1d", 'gdprropa'),
            'comment' => __("Sauvegarde des intérêts vitaux de la personne", 'gdprropa'),
            'description' =>  __("Le traitement est nécessaire pour protéger les intérêts vitaux de la personne concernée ou d\'une autre personne physique.", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_GDPR,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
         $sample_data->add([
            'name' => __("Article 6-1e", 'gdprropa'),
            'comment' => __("Exécution d\'une mission d\'intérêt public", 'gdprropa'),
            'description' => __("Le traitement est nécessaire à l\'exécution d\'une mission d\'intérêt public ou relevant de l\'exercice de l\'autorité publique dont est investi le responsable du traitement.", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_GDPR,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
         $sample_data->add([
            'name' => __("Article 6-1f", 'gdprropa'),
            'comment' => __("Intérêts légitimes", 'gdprropa'),
            'description' => __("Le traitement est nécessaire aux fins des intérêts légitimes poursuivis par le responsable du traitement ou par un tiers, à moins que ne prévalent les intérêts ou les libertés et droits fondamentaux de la personne concernée qui exigent la protection des données à caractère personnel, notamment lorsque la personne concernée est un enfant.", 'gdprropa'),
            'type' => PluginGdprropaLegalBasisAct::LEGALBASISACT_GDPR,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'injected' => 1,
         ]);
      }

      if (isset($data['install_security_measures']) && ($data['install_security_measures'] == 1)) {
         $sample_data = new PluginGdprropaSecurityMeasure();
         $sample_data->add([
            'name' => __("Le DPD a été nommé", 'gdprropa'),
            'type' => PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_ORGANIZATION,
            'comment' => __("Le délégué à la protection des données a été nommé", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         $sample_data->add([
            'name' => __("Politique d\'utilisation des ordinateurs", 'gdprropa'),
            'type' => PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_ORGANIZATION,
            'comment' => __("Politique interne concernant l\'utilisation des ordinateurs", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         $sample_data->add([
            'name' => __("Sécurité 24h/24", 'gdprropa'),
            'type' => PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_PHYSICAL,
            'comment' => __("Personnel de sécurité sur place pendant 24 heures", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         $sample_data->add([
            'name' => __("Système d\'onduleurs", 'gdprropa'),
            'type' => PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_PHYSICAL,
            'comment' => __("Une alimentation sans interruption est installée", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         $sample_data->add([
            'name' => __("Application Antivirus", 'gdprropa'),
            'type' => PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_IT,
            'comment' => __("Les ordinateurs ont une application antivirus installée", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         $sample_data->add([
            'name' => __("Pare-feu", 'gdprropa'),
            'type' => PluginGdprropaSecurityMeasure::SECURITYMEASURE_TYPE_IT,
            'comment' => __("Le pare-feu protège le réseau interne", 'gdprropa'),
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
      }

      

      if (isset($data['install_contract_types']) && ($data['install_contract_types'] == 1)) {

         global $DB;

         $contractnames = [
            'Contrat de contrôleur commun',
            'Contrat du processeur',
            'Contrat avec un tiers',
            'Contrat interne',
            'Autre contrat',
         ];
         $contract_row = count($contractnames);
         $verifs = [];
         $i = 0;

         $result = $DB->query("SELECT * FROM `glpi_contracttypes` WHERE `glpi_contracttypes`.`comment` LIKE '%(Créé via plugin GDPRRoPA)%';");

         if(!empty($result)){
            foreach($contractnames as $contractname){           
               foreach($result as $item){
                  if(($item['name'] == $contractname) && ($verifs[$i] != true)){
                     $verifs[$i] = true;
                  }
                  elseif(($item['name'] != $contractname) && ($verifs[$i] != true)){
                     $verifs[$i] = false;
                  }
                  elseif($verifs[$i] != true){
                     $verifs[$i] = false;
                  }
               }
               $i++;        
            }
         }
         else{
            for($i = 0; $i < $contract_row; ){
               $verifs[$i] = false;
               $i++;
            }
         }

         $sample_data = new ContractType();
         $contractcomments = [
            'Contrat de contrôleur commun (Créé via plugin GDPRRoPA)',
            'Contrat du processeur (Créé via plugin GDPRRoPA)',
            'Contrat avec un tiers (Créé via plugin GDPRRoPA)',
            'Contrat interne (Créé via plugin GDPRRoPA)',
            'Autre contrat (Créé via plugin GDPRRoPA)',
         ];

         for($i = 0; $i < $contract_row; ){
            if($verifs[$i] == false){
               $sample_data->add([
                  'name' => $contractnames[$i],
                     'comment' => $contractcomments[$i],
               ]);
            }
            $i++;
         }
      }

      if (isset($data['install_personal_data_types']) && ($data['install_personal_data_types'] == 1)) {
         $sample_data = new PluginGdprropaPersonalDataCategory();
         $parent_id = $sample_data->add([
            'name' => __("Dossier des employés", 'gdprropa'),
            'comment' => __("Données personnelles des employés", 'gdprropa'),
            'is_special_category' => false,
            'entities_id' => $_SESSION['glpiactive_entity'],
         ]);
         if ($parent_id) {
            $sample_data->add([
               'name' => __("Prénom", 'gdprropa'),
               'comment' => __("Prénom de l\'employé", 'gdprropa'),
               'entities_id' => $_SESSION['glpiactive_entity'],
               'is_special_category' => false,
               'plugin_gdprropa_personaldatacategories_id' => $parent_id
            ]);
            $sample_data->add([
               'name' => __("Nom de famille", 'gdprropa'),
               'comment' => __("Nom de famille de l\'employé", 'gdprropa'),
               'entities_id' => $_SESSION['glpiactive_entity'],
               'is_special_category' => false,
               'plugin_gdprropa_personaldatacategories_id' => $parent_id
            ]);
            $sample_data->add([
               'name' => __("ID personnel", 'gdprropa'),
               'comment' => __("ID personnel de l\'employé", 'gdprropa'),
               'entities_id' => $_SESSION['glpiactive_entity'],
               'is_special_category' => true,
               'plugin_gdprropa_personaldatacategories_id' => $parent_id
            ]);
         }
      }
   }

}
