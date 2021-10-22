/**
 * 2021 Corvus-Info
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/afl-3.0.php
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to license@prestashop.com so we can send you a copy immediately.
 *
 *  DISCLAIMER
 *
 *  Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 *  versions in the future. If you wish to customize PrestaShop for your
 *  needs please refer to http://www.prestashop.com for more information.
 *
 * @author 2021 Corvus-Info
 * @copyright Corvus-Info
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
$(document).ready(function () {
    toggleTimeLimit();
    $('input[name="time_limit_enable"]').on('change', function (e) {
        toggleTimeLimit();
    });
    toggleInstallmentsMap();
    $('select[name="installments"]').on('change', function (e) {
        toggleInstallmentsMap();
    });
    toggleCreditorReference();
    $('input[name="pis_payments_enable"]').on('change', function (e) {
        toggleCreditorReference();
    });
    toggleEnvironment();
    $('select[name="environment"]').on('change', function (e) {
        toggleEnvironment();
    });
});

function toggleTimeLimit() {
    var time_limit = $('input[name="time_limit_enable"]:checked').val();
    if (time_limit === "1") {
        $('input[name="before_time"]').parent().parent().show();
    } else {
        $('input[name="before_time"]').parent().parent().hide();
    }
}

function toggleInstallmentsMap() {
    var installments = $('select[name="installments"]').val();
    if (installments === "advanced") {
        $('#installments_map').parent().show();
    } else {
        $('#installments_map').parent().hide();
    }
}

function toggleCreditorReference() {
    var pis = $('input[name="pis_payments_enable"]:checked').val();
    if (pis === "1") {
        $('input[name="creditor_reference"]').parent().parent().show();
    } else {
        $('input[name="creditor_reference"]').parent().parent().hide();
    }
}

function toggleEnvironment() {
    var environment = $('select[name="environment"]').val();
    if (environment === "test") {
        $('input[name="test_store_id"]').parent().parent().show();
        $('input[name="test_secret_key"]').parent().parent().parent().show();
        $('input[name="test_certificate"]').parent().parent().parent().parent().show();
        $('input[name="test_certificate_password"]').parent().parent().parent().show();

        $('input[name="prod_store_id"]').parent().parent().hide();
        $('input[name="prod_secret_key"]').parent().parent().parent().hide();
        $('input[name="prod_certificate"]').parent().parent().parent().parent().hide();
        $('input[name="prod_certificate_password"]').parent().parent().parent().hide();
    } else {
        $('input[name="test_store_id"]').parent().parent().hide();
        $('input[name="test_secret_key"]').parent().parent().parent().hide();
        $('input[name="test_certificate"]').parent().parent().parent().parent().hide();
        $('input[name="test_certificate_password"]').parent().parent().parent().hide();

        $('input[name="prod_store_id"]').parent().parent().show();
        $('input[name="prod_secret_key"]').parent().parent().parent().show();
        $('input[name="prod_certificate"]').parent().parent().parent().parent().show();
        $('input[name="prod_certificate_password"]').parent().parent().parent().show();
    }
}