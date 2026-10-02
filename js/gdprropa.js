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
  @since     1.1.0
 --------------------------------------------------------------------------
 */

const rootDoc = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc) ? CFG_GLPI.root_doc : '';
const pluginBase = `${rootDoc}/plugins/gdprropa/ajax`;

function csrfToken() {
    if (typeof getAjaxCsrfToken === 'function') {
        return getAjaxCsrfToken();
    }
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');

    return meta !== null ? meta.getAttribute('content') : '';
}

function postFragment(url, data, targetSelector) {
    const target = document.querySelector(targetSelector);
    if (target === null) {
        return;
    }

    const body = new URLSearchParams();
    Object.entries(data).forEach(([key, value]) => {
        body.append(key, value === null || value === undefined ? '' : value);
    });

    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Glpi-Csrf-Token': csrfToken(),
        },
        body: body.toString(),
    })
        .then((response) => response.text())
        .then((html) => {
            target.innerHTML = html;
        });
}

function formValue(element, name) {
    const form = element.closest('form');
    if (form === null) {
        return '';
    }
    const field = form.querySelector(`[name="${name}"]`);

    return field !== null ? field.value : '';
}

const jq = window.jQuery;
if (jq !== undefined) {
    jq(document).on('change', 'select[name="pia_required"]', function () {
        postFragment(`${pluginBase}/record_pia_required_dropdown.php`, {
            pia_required: this.value,
            pia_status: formValue(this, 'pia_status'),
        }, '#gdprropa-pia-status');
    });

    jq(document).on('change', 'select[name="consent_required"]', function () {
        postFragment(`${pluginBase}/record_consent_required_dropdown.php`, {
            consent_required: this.value,
            consent_storage: formValue(this, 'consent_storage'),
        }, '#gdprropa-consent-storage');
    });

    jq(document).on('change', '#gdprropa-record-retention select[name="type"]', function () {
        postFragment(`${pluginBase}/record_retention_retention_type_dropdown.php`, {
            type: this.value,
            id: formValue(this, 'id'),
            contracts_id: formValue(this, 'contracts_id'),
            entities_id: formValue(this, 'entities_id'),
            is_record_recursive: formValue(this, 'is_record_recursive'),
        }, '#gdprropa-retention-type');
    });

    jq(document).on('change', '#gdprropa-retention-type select[name="contract_until_is_valid"]', function () {
        postFragment(`${pluginBase}/record_retention_contract_until_is_valid.php`, {
            checked: this.value,
            contract_retention_value: formValue(this, 'contract_retention_value'),
            contract_retention_scale: formValue(this, 'contract_retention_scale'),
            contract_after_end_of: formValue(this, 'contract_after_end_of'),
        }, '#gdprropa-retention-contract-after');
    });
}
