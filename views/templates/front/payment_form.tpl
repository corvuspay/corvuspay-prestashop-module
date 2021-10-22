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
<form id="payment-form" method="POST" action="{$action}" >
    {if $logged_in}
    {if $vaultings}

        {foreach from=$vaultings key=key  item=value}
            <input type="radio" name="vaulting" value= {$value->account_id|escape:'htmlall':'UTF-8'|capitalize}  onClick="hide()"
            {if $value->is_default} checked {/if}>
            {$value->card_type|escape|capitalize:'htmlall':'UTF-8'} {l s='ending in' mod='corvuspaypaymentgateway'} {$value->last4|escape:'htmlall':'UTF-8'}
            ({l s='Expires' mod='corvuspaypaymentgateway'} {$value->exp_month|escape:'htmlall':'UTF-8'}\{$value->exp_year|escape:'htmlall':'UTF-8'}) <br>
        {/foreach}
        <input type="radio" name="vaulting" value="new" onClick="show()"> {l s='Use a new payment method' mod='corvuspaypaymentgateway'}
        <p id="save-to-acc" style="display: none">
            <label><input type="checkbox" name="save-to-acc" value="true"/> {l s='Save to account' mod='corvuspaypaymentgateway'} </label>
        </p>
    {else}
        <p>
            <label><input type="checkbox" name="save-to-acc" value="true"/> {l s='Save to account' mod='corvuspaypaymentgateway'} </label>
        </p>
    {/if}
    {/if}
</form>

<script type="text/javascript">
    function hide() {
        var el = document.getElementById("save-to-acc");
        el.style.display = 'none';
        el.firstChild.firstChild.value = "false";
    }
    function show() {
        var el = document.getElementById("save-to-acc");
        el.style.display = 'block';
    }
</script>
