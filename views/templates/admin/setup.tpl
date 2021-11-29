{*
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
*}
<div class="row">
    <div class="col-lg-12">
        {if $formPaymentSettings}
            {$formPaymentSettings nofilter} {* the variable contains html code, we must use nofilter because we want to render html *}
        {/if}
    </div>
</div>

<script type="text/javascript">
    var options = "";
    var js_array_card_brands = new Object();

    {foreach from=$card_brands item=array_item key=id}
    js_array_card_brands['{$id}'] = '{$array_item}';
    options += "<option value='" + '{$id}' + "'>" + '{$array_item}' + "</option>"
    {/foreach}

    $(function () {
        var size = $('#installments_map').find('tbody tr').length;
        if (size === 0) $('#installments_map').hide();

        $('a.add').on('click', function () {
            var size = $('#installments_map').find('tbody tr').length;
            $('<tr><td><select title="Card brand" name="installments_map_card_brand[' + size + ']"> ' + options + '</select></td>\
        <td><input type="text" name="installments_map_min_installments[' + size + ']" /></td>\
        <td><input type="text" name="installments_map_max_installments[' + size + ']" /></td>\
        <td><input type="text" name="installments_map_general_percentage[' + size + ']" value="0" /></td>\
        <td><input type="text" name="installments_map_specific_percentage[' + size + ']" value="0" /></td>\
        <td><a class="delete" href="#"><i class="material-icons">delete</i></a></td>\
        </tr>').appendTo('#installments_map tbody');
            $('#installments_map').show();
            return false;
        });

        $('#installments_map').on('click', 'a.delete', function (e) {
            $(this).closest('tr').remove()
            var size = $('#installments_map').find('tbody tr').length;
            if (size === 0) $('#installments_map').hide();
            return false;
        });
    });
</script>