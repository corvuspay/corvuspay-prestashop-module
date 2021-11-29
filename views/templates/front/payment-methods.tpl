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

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='My payment methods' mod='corvuspaypaymentgateway'}
{/block}

{block name='page_content'}
    <!-- Page content -->
    <p>
        <a class="btn btn-primary" type="button"
           href="{$link->getModuleLink('corvuspaypaymentgateway', 'paymentmethods', ['process' => 'add'])}">
            {l s='Add payment method' mod='corvuspaypaymentgateway'} <i class="material-icons">add</i>
        </a>
    </p>
    {if $vaultings}
        <form action="{$link->getModuleLink('corvuspaypaymentgateway', 'paymentmethods', ['process' => 'save'])}" method="post">
            <table class="table">
                <tr>
                    <th>
                        {l s='Method' mod='corvuspaypaymentgateway'}
                    </th>
                    <th>
                        {l s='Expires' mod='corvuspaypaymentgateway'}
                    </th>
                    <th>
                    </th>
                </tr>
                {foreach from=$vaultings key=key  item=value}
                    <tr>
                        <td>
                            {$value->card_type|escape:'htmlall':'UTF-8'} {l s='ending in' mod='corvuspaypaymentgateway'} {$value->last4|escape:'htmlall':'UTF-8'}
                        </td>
                        <td>
                            {$value->exp_month|escape:'htmlall':'UTF-8'}\{$value->exp_year|escape:'htmlall':'UTF-8'}
                        </td>
                        <td>
                            <a class="btn btn-outline-danger" href="{$link->getModuleLink('corvuspaypaymentgateway', 'paymentmethods', ['process' => 'delete', 'id_method' => {$value->id_corvuspay_vaulting|escape:'htmlall':'UTF-8'}])}">
                                {l s='Delete' mod='corvuspaypaymentgateway'} </a>
                            {if !$value->is_default}
                            <a class="btn btn-outline-primary" href="{$link->getModuleLink('corvuspaypaymentgateway', 'paymentmethods', ['process' => 'make_default', 'id_method' => {$value->id_corvuspay_vaulting|escape:'htmlall':'UTF-8'}])}">
                                {l s='Make default' mod='corvuspaypaymentgateway'} </a>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </table>
        </form>
    {else}
        {l s='You don\'t have saved payment methods from CorvusPay' mod='corvuspaypaymentgateway'}
    {/if}

{/block}
