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

use CommonGLPI;
use Plugin;
use TCPDF;

if (!defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', GLPI_ROOT . '/plugins/gdprropa/images/');
}

class CreatePDFBase extends CommonGLPI
{
    protected TCPDF $pdf;

    protected $system_config;
    protected array $print_options;

    public static function getTypeName($nb = 0): string
    {
        return __("Create PDF", 'gdprropa');
    }

    public function showPDF(): void
    {
        ob_end_clean();

        $this->pdf->Output('glpi.pdf');
    }

    protected function setHeader($title, $content): void
    {
        $this->pdf->resetHeaderTemplate();

        if ($this->system_config['print']['logo_show']) {
            $this->pdf->SetHeaderData($this->system_config['print']['logo_image'], 15, $title, $content);
        } else {
            $this->pdf->SetHeaderData(null, 0, $title, $content);
        }

        $this->pdf->SetTitle($title);
        $this->pdf->SetY($this->pdf->GetY() + $this->pdf->getLastH());
    }

    protected function printPageTitle($html): void
    {
        $this->writeInternal(
            $html,
            [
                'fillcolor' => [50, 50, 50],
                'fill' => 1,
                'textcolor' => [255, 255, 255],
                'align' => 'C'
            ]
        );
    }

    protected function insertNewPageIfBottomSpaceLeft($bottom_space = 20): void
    {
        $pd = $this->pdf->getPageDimensions();
        if ($this->pdf->getY() + $this->pdf->getFooterMargin() + $bottom_space > $pd['hk']) {
            $this->pdf->addPage($this->print_options['page_orientation'], 'A4');
        }
    }

    protected function writeHtml($html, $params = [], $end_line = true): void
    {
        $options = [
            'fillcolor' => [255, 255, 255],
            'textcolor' => [0, 0, 0],
            'linebefore' => 0,
            'lineafter' => 0,
            'ln' => true,
            'fill' => false,
            'reseth' => false,
            'align' => 'L',
            'autopadding' => true
        ];

        foreach ($params as $key => $value) {
            $options[$key] = $value;
        }

        $this->pdf->SetFillColor($options['fillcolor'][0], $options['fillcolor'][1], $options['fillcolor'][2]);
        $this->pdf->SetTextColor($options['textcolor'][0], $options['textcolor'][1], $options['textcolor'][2]);

        if ($options['linebefore'] > 0) {
            $this->pdf->Ln($options['linebefore']);
        }

        $this->pdf->writeHTML(
            $html,
            $options['ln'],
            $options['fill'],
            $options['reseth'],
            $options['autopadding'],
            $options['align']
        );

        if ($end_line) {
            if ($options['lineafter'] > 0) {
                $this->pdf->Ln($options['lineafter']);
            }
            $this->pdf->SetY($this->pdf->GetY() + $this->pdf->getLastH());
        }
    }

    protected function writeInternal($html, $params = [], $end_line = true): void
    {
        $options = [
            'fillcolor' => [255, 255, 255],
            'textcolor' => [0, 0, 0],
            'cellpading' => 1,
            'linebefore' => 0,
            'lineafter' => 0,
            'cellwidth' => 0,
            'cellheight' => 1,
            'xoffset' => '',
            'yoffset' => '',
            'border' => 0,
            'ln' => 0,
            'fill' => false,
            'reseth' => true,
            'align' => 'L',
            'autopadding' => true
        ];

        foreach ($params as $key => $value) {
            $options[$key] = $value;
        }

        $this->pdf->SetFillColor($options['fillcolor'][0], $options['fillcolor'][1], $options['fillcolor'][2]);
        $this->pdf->SetTextColor($options['textcolor'][0], $options['textcolor'][1], $options['textcolor'][2]);
        $this->pdf->SetCellPadding($options['cellpading']);

        if ($options['linebefore'] > 0) {
            $this->pdf->Ln($options['linebefore']);
        }

        $this->pdf->writeHTMLCell(
            $options['cellwidth'],
            $options['cellheight'],
            $options['xoffset'],
            $options['yoffset'],
            $html,
            $options['border'],
            $options['ln'],
            $options['fill'],
            $options['reseth'],
            $options['align'],
            $options['autopadding']
        );

        if ($end_line) {
            if ($options['lineafter'] > 0) {
                $this->pdf->Ln($options['lineafter']);
            }
            $this->pdf->SetY($this->pdf->GetY() + $this->pdf->getLastH());
        }
    }

    protected function write2ColsRow($col1_html = '', $col1_params = [], $col2_html = '', $col2_params = []): void
    {
        $height = 0;

        $this->pdf->startTransaction();
        $this->writeInternal($col1_html, $col1_params, false);

        $height = ($height < $this->pdf->getLastH() ? $this->pdf->getLastH() : $height);

        $this->writeInternal($col2_html, $col2_params);

        $height = ($height < $this->pdf->getLastH() ? $this->pdf->getLastH() : $height);

        $this->pdf = $this->pdf->rollbackTransaction();

        $col1_params['cellheight'] = $height;
        $col2_params['cellheight'] = $height;

        $this->writeInternal($col1_html, $col1_params, false);
        $this->writeInternal($col2_html, $col2_params);
    }

    protected function arrayOrderBy()
    {
        $args = func_get_args();
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    protected function preparePrintOptions($print_options = []): void
    {
        $this->system_config = Config::getConfig();
        $this->print_options = $print_options;
    }

    protected function preparePDF(): void
    {
        $this->pdf = new TCPDF(
            $this->print_options['page_orientation'],
            'mm',
            'A4',
            true,
            $this->system_config['print']['codepage'],
            false
        );

        $this->pdf->setHeaderFont([$this->system_config['print']['font_name'], 'B', 8]);
        $this->pdf->setFooterFont([$this->system_config['print']['font_name'], 'B', 8]);

        $this->pdf->SetMargins(
            $this->system_config['print']['margin_left'],
            $this->system_config['print']['margin_top'],
            $this->system_config['print']['margin_right'],
            true
        );

        $this->pdf->SetAutoPageBreak(true, $this->system_config['print']['margin_footer']);

        $this->pdf->SetFont($this->system_config['print']['font_name'], '', $this->system_config['print']['font_size']);

        $this->pdf->setHeaderMargin($this->system_config['print']['margin_header']);
        $this->pdf->setFooterMargin($this->system_config['print']['margin_footer']);
    }

    protected static function isGdprownerPluginActive(): bool
    {
        $plugin = new Plugin();

        return $plugin->isActivated('gdprowner');
    }
}
