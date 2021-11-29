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

<div class="form-group">
    <table id="installments_map">
        <thead>
        <tr>
            <th>{l s='Card brand' mod='corvuspaypaymentgateway'}</th>
            <th>{l s='Minimum installments' mod='corvuspaypaymentgateway'}</th>
            <th>{l s='Maximum installments' mod='corvuspaypaymentgateway'}</th>
            <th>{l s='General discount' mod='corvuspaypaymentgateway'}</th>
            <th>{l s='Specific discount' mod='corvuspaypaymentgateway'}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$installments_map_db key=i item=installment name=installments_map}
            <tr>
                <td>
                    <select title="Card brand" name="installments_map_card_brand[{$smarty.foreach.installments_map.index}]"
                            class="selectpicker">
                        {foreach from=$card_brands key=code item=brand}
                            {if $code eq $installment['card_brand']}
                                <option value="{$code}" selected="selected">{$brand}</option>
                            {else}
                                <option value="{$code}">{$brand}</option>
                            {/if}
                        {/foreach}
                    </select>
                </td>
                <td><input type="text" title="Minimum installments" value="{$installment['min_installments']}"
                           name="installments_map_min_installments[{$smarty.foreach.installments_map.index}]">
                </td>
                <td><input type="text" title="Maximum installments" value="{$installment['max_installments']}"
                           name="installments_map_max_installments[{$smarty.foreach.installments_map.index}]">
                </td>
                <td><input type="text" title="General discount" value="{$installment['general_percentage']}"
                            name="installments_map_general_percentage[{$smarty.foreach.installments_map.index}]">
                </td>
                <td><input type="text" title="Specific discount" value="{$installment['specific_percentage']}"
                           name="installments_map_specific_percentage[{$smarty.foreach.installments_map.index}]">
                </td>
                <td><a class="delete" href="#">
                        <i class="material-icons">delete</i></a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <b><a href="#" class="add button">{l s='+ Add installment entry' mod='corvuspaypaymentgateway'}</a></b>
    <p>{l s='Example row: "Visa; 1; 2; 10; 15".' mod='corvuspaypaymentgateway'}</p>
    <p>{l s='Explanation: All Visa cards get a 10% discount if customer pays in one payment or in two installments. Some Visa cards, issued by a specific issuer, get a 15% discount under the same conditions. To setup specific discounts, contact CorvusPay.' mod='corvuspaypaymentgateway'}</p>
</div>