{*
* 2021 CorvusPay
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author 2021 Corvus-Info
*  @copyright Corvus-Info
*  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*
*}
<script type="text/javascript">
    {literal}
    // add checkbox
    $(document).ready(() => {
        var chb_corvuspay_refund = '{/literal}{$chb_corvuspay_refund}{literal}';

        // Make partial order refund
        $(document).on('click', '#desc-order-partial_refund', function () {
            $('button[name=partialRefund]').parent('.partial_refund_fields')
            // Create checkbox and insert for CorvusPay refund
            if ($('#doPartialRefundCorvusPay').length == 0) {
                let newCheckBox = `<p class="checkbox"><label for="doPartialRefundCorvusPay">
                        <input type="checkbox" id="doPartialRefundCorvusPay" name="doPartialRefundCorvusPay" value="1">
                          ${chb_corvuspay_refund}</label></p>`;
                $('button[name=partialRefund]').parent('.partial_refund_fields').prepend(newCheckBox);
            }
        });

        $(document).on('click', '.partial-refund-display', function () {
            $('.refund-checkboxes-container:has(#doRefundCorvusPay)').find('div:first').remove();
            // Create checkbox and insert for CorvusPay refund
            if ($('#doPartialRefundCorvusPay').length == 0) {
                let newCheckBox = `
                        <div class="cancel-product-element form-group" style="display: block;">
                                <div class="checkbox">
                                    <div class="md-checkbox md-checkbox-inline">
                                      <label>
                                          <input type="checkbox" id="doPartialRefundCorvusPay" name="doPartialRefundCorvusPay" material_design="material_design" value="1">
                                          <i class="md-checkbox-control"></i>
                                            ${chb_corvuspay_refund}
                                        </label>
                                    </div>
                                </div>
                         </div>`;

                $('.refund-checkboxes-container').prepend(newCheckBox);
            }
        });

        // Make order refund
        $(document).on('click', '#desc-order-refund', function () {
            // Create checkbox and insert for CorvusPay refund
            if ($('#doRefundCorvusPay').length == 0) {
                let newCheckBox = `<p class="checkbox"><label for="doRefundCorvusPay">
                        <input type="checkbox" id="doRefundCorvusPay" name="doRefundCorvusPay" value="1">
                          ${chb_corvuspay_refund}</label></p>`;
                $('button[name=refund]').parent('.refund_fields').prepend(newCheckBox);
            }
        });

        $(document).on('click', '.standard-refund-display', function () {
            $('.refund-checkboxes-container:has(#doPartialRefundCorvusPay)').find('div:first').remove();
            // Create checkbox and insert for CorvusPay refund
            if ($('#doRefundCorvusPay').length == 0) {
                let newCheckBox = `
                        <div class="cancel-product-element form-group" style="display: block;">
                                <div class="checkbox">
                                    <div class="md-checkbox md-checkbox-inline">
                                      <label>
                                          <input type="checkbox" id="doRefundCorvusPay" name="doRefundCorvusPay" material_design="material_design" value="1">
                                          <i class="md-checkbox-control"></i>
                                            ${chb_corvuspay_refund}
                                        </label>
                                    </div>
                                </div>
                         </div>`;

                $('.refund-checkboxes-container').prepend(newCheckBox);
            }
        });

    });
    {/literal}
</script>
