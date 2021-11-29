<?php
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

use CorvusPayAddons\services\ServiceCorvusPayVaulting;

require_once _PS_MODULE_DIR_ . 'corvuspaypaymentgateway/services/ServiceCorvusPayVaulting.php';

class CorvuspaypaymentgatewayPaymentMethodsModuleFrontController extends ModuleFrontController
{
    const ORDER_NUMBER_DELIMITER = ' - ';
    const CARD_STORAGE_PREFIX = 'cs_';

    /**
     * @var ServiceCorvusPayVaulting
     */
    protected $serviceCorvusPayVaulting;

    public function initContent()
    {
        parent::initContent();
    }

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();
        if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'SUBSCRIPTION') !== '1') {
            Tools::redirect('');
        }

        $this->serviceCorvusPayVaulting = new ServiceCorvusPayVaulting();

        $vaultings = $this->serviceCorvusPayVaulting->getCorvusPayVaultingsByIdCustomer($this->context->customer->id);
        $this->context->smarty->assign(
            [
                'vaultings' => $vaultings,
            ]
        );
        $this->setTemplate('module:corvuspaypaymentgateway/views/templates/front/payment-methods.tpl');

        if (Tools::getValue('process') === 'delete') {
            $this->serviceCorvusPayVaulting->deleteCorvusPayVaultingById((int) Tools::getValue('id_method'));
            $url = Context::getContext()->link->getModuleLink($this->module->name, 'paymentmethods');
            $this->info[] = $this->l('Payment method deleted.');
            $this->redirectWithNotifications($url);
        } elseif (Tools::getValue('process') === 'make_default') {
            $this->serviceCorvusPayVaulting->makeDefaultCorvusPayVaultingById(
                (int) Tools::getValue('id_method'),
                $this->context->customer->id
            );
            $url = Context::getContext()->link->getModuleLink($this->module->name, 'paymentmethods');
            $this->success[] = $this->l('Payment method successfully set as default. ');
            $this->redirectWithNotifications($url);
        } elseif (Tools::getValue('process') === 'add') {
            $this->setTemplate('module:corvuspaypaymentgateway/views/templates/front/confirm-save-card.tpl');
            $customer = $this->context->customer;
            $name_shop = $this->context->shop->name;
            $environment = Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT');

            if ($environment === 'prod') {
                $order_number = self::CARD_STORAGE_PREFIX . (string) time();
            } else {
                $order_number = self::CARD_STORAGE_PREFIX . $name_shop . self::ORDER_NUMBER_DELIMITER . (string) time();
            }

            //If the store name is large.
            if (Tools::strlen($order_number) > 30) {
                $name_shop = Tools::substr($name_shop, 0, 30 - Tools::strlen($order_number));
                $order_number = self::CARD_STORAGE_PREFIX . $name_shop . self::ORDER_NUMBER_DELIMITER . (string) time();
            }

            $address = new Address((int) (Address::getFirstCustomerAddressId($customer->id)));

            $params = [
                'store_id' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    Tools::strtoupper($environment) . '_STORE_ID'),
                'secret_key' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    Tools::strtoupper($environment) . '_SECRET_KEY'),
                'environment' => $environment,
            ];
            $client = new CorvusPay\CorvusPayClient($params);

            $params = [
                'order_number' => $order_number,
                'currency' => Currency::getDefaultCurrency()->iso_code,
                'amount' => '1.00',
                'cart' => 'Card storage',
                'require_complete' => 'true',
                'subscription' => 'true',
                'hide_tabs' => 'pis,wallet,paysafecard',
            ];

            if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'SEND_CARDHOLDER_INFO')
                === 'both') {
                $cardholder_info = [
                    'cardholder_name' => $customer->firstname,
                    'cardholder_surname' => $customer->lastname,
                    'cardholder_address' => $address->address1,
                    'cardholder_city' => $address->city,
                    'cardholder_zip_code' => $address->postcode,
                    'cardholder_country' => $address->country,
                    'cardholder_phone' => $address->phone_mobile,
                    'cardholder_email' => $customer->email,
                ];
                $params = array_merge($params, $cardholder_info);
            } elseif (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'SEND_CARDHOLDER_INFO')
                === 'mandatory') {
                $cardholder_info = [
                    'cardholder_name' => $customer->firstname,
                    'cardholder_surname' => $customer->lastname,
                    'cardholder_email' => $customer->email,
                ];
                $params = array_merge($params, $cardholder_info);
            }

            if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TIME_LIMIT_ENABLE') === '1' &&
                Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'BEFORE_TIME') !== '') {
                $unixTimestamp = time() + (int) Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        'BEFORE_TIME');
                $params['best_before'] = (string) $unixTimestamp;
            }

            if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'LANGUAGE') === 'auto') {
                $params['language'] = $this->context->language->iso_code;
            } elseif (array_key_exists(
                Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'LANGUAGE'),
                \CorvusPay\Service\CheckoutService::SUPPORTED_LANGUAGES
            )
            ) {
                $params['language'] =
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'LANGUAGE');
            } else {
                PrestaShopLogger::addLog('Language is not supported', 3);
            }

            $redirect = 'auto';

            if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'AUTOREDIRECT') === '0') {
                $redirect = 'CONTINUE TO PAYMENT';
            }

            try {
                $form = $client->checkout->create($params, $redirect, false);
                $this->context->smarty->assign([
                    'form' => $form,
                ]);
            } catch (InvalidArgumentException $e) {
                $this->errors[] = $this->l($e->getMessage());
            }
        }
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();

        return $breadcrumb;
    }
}
