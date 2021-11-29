<?php
/**
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use CorvusPayAddons\services\ServiceCorvusPayVaulting;

require_once _PS_MODULE_DIR_ . 'corvuspaypaymentgateway/services/ServiceCorvusPayVaulting.php';

/**
 * @since 1.5.0
 */
class CorvusPayPaymentGatewayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Maximum length for cart description. CorvusPay limits cart description length to 255 characters.
     */
    const CART_MAX_LENGTH = 250;

    /**
     * Delimiter for CorvusPay order_number. CorvusPay requires all test orders to have a unique order_number. Test
     * orders have a prefix to make them unique. Delimiter is used to join and split prefix and Order ID.
     */
    const ORDER_NUMBER_DELIMITER = ' - ';

    /**
     * Prefix to order_number when saving card.
     */
    const CARD_STORAGE_PREFIX = 'cs_';

    /**
     * @var ServiceCorvusPayVaulting
     */
    protected $serviceCorvusPayVaulting;

    public function __construct()
    {
        $this->serviceCorvusPayVaulting = new ServiceCorvusPayVaulting();
        parent::__construct();
    }

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function setMedia()
    {
        return parent::setMedia();
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Tools::getIsset('status') && Tools::getValue('status') === 'success') {
            if (Tools::getIsset('order_number') &&
                Tools::substr(Tools::getValue('order_number'), 0, Tools::strlen(self::CARD_STORAGE_PREFIX))
                === self::CARD_STORAGE_PREFIX &&
                Tools::getIsset('account_id') &&
                Tools::getIsset('subscription_exp_date')) {
                $environment = Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT');
                $config_params = [
                    'store_id' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        Tools::strtoupper($environment) . '_STORE_ID'),
                    'secret_key' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        Tools::strtoupper($environment) . '_SECRET_KEY'),
                    'environment' => $environment,
                ];
                $client = new CorvusPay\CorvusPayClient($config_params);
                $client->setCertificate(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    Tools::strtoupper($environment) . '_CERTIFICATE'));

                $status_params = [
                    'order_number' => Tools::getValue('order_number'),
                    'currency_code' => $this->context->currency->iso_code,
                ];
                $response_xml = $client->transaction->status($status_params);
                $xml = simplexml_load_string($response_xml);
                $response = new SimpleXMLElement($response_xml);
                if ($response->getName() === 'errors') {
                    return '';
                }

                $cancel_params = [
                    'order_number' => Tools::getValue('order_number'),
                ];
                $response = $client->transaction->cancel($cancel_params);
                if ($response !== true) {
                    return '';
                }

                $date = date_parse((string) $xml->{'subscription-exp-date'});
                $last4 = (int) Tools::substr((string) $xml->{'card-details'}, -4);
                //check if it`s duplicate.
                $duplicate = false;
                $vaultings = $this->serviceCorvusPayVaulting->
                getCorvusPayVaultingsByIdCustomer($this->context->customer->id);

                foreach ($vaultings as $vaulting) {
                    if ($vaulting->id_customer === (string) $this->context->customer->id &&
                        $vaulting->exp_year === (string) $date['year'] &&
                        $vaulting->exp_month === (string) $date['month'] &&
                        $vaulting->last4 = (string) $last4 && $vaulting->card_type === (string) $xml->{'cc-type'}) {
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) {
                    $vaulting = new CorvusPayVaulting();
                    $vaulting->account_id = (int) Tools::getValue('account_id');
                    $vaulting->card_type = (string) $xml->{'cc-type'};
                    $vaulting->id_customer = $this->context->customer->id;
                    $vaulting->exp_year = $date['year'];
                    $vaulting->exp_month = $date['month'];
                    $vaulting->last4 = $last4;
                    $vaulting->is_default = false;

                    $this->serviceCorvusPayVaulting->createCorvusPayVaulting($vaulting);
                    $url = Context::getContext()->link->getModuleLink($this->module->name, 'paymentmethods');
                    Tools::redirect($url);
                } else {
                    $this->errors[] = $this->l('Payment method already exists.');
                    $url = Context::getContext()->link->getModuleLink($this->module->name, 'paymentmethods');
                    $this->redirectWithNotifications($url);
                }
            } else {
                $this->check();

                /**
                 * Get current cart object from session
                 */
                $cart = $this->context->cart;

                /** @var CustomerCore $customer */
                $customer = new Customer($cart->id_customer);

                //change order status.
                $isOrderX = Db::getInstance()->getRow(' SELECT * FROM ' . _DB_PREFIX_ .
                    'orders WHERE id_customer = ' . $cart->id_customer . ' ORDER BY id_order DESC ');

                $objOrder = new Order((int) $isOrderX['id_order']);
                $history = new OrderHistory();
                $history->id_order = $objOrder->id;
                $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $objOrder->id);
                $history->add();

                //save card.
                if (Tools::getIsset('account_id') && Tools::getIsset('subscription_exp_date')) {
                    $environment =
                        Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT');
                    $config_params = [
                        'store_id' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                            Tools::strtoupper($environment) . '_STORE_ID'),
                        'secret_key' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                            Tools::strtoupper($environment) . '_SECRET_KEY'),
                        'environment' => $environment,
                    ];
                    $client = new CorvusPay\CorvusPayClient($config_params);
                    $client->setCertificate(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        Tools::strtoupper($environment) . '_CERTIFICATE'));

                    $status_params = [
                        'order_number' => Tools::getValue('order_number'),
                        'currency_code' => $this->context->currency->iso_code,
                    ];
                    $response_xml = $client->transaction->status($status_params);

                    $response = new SimpleXMLElement($response_xml);
                    if ($response->getName() === 'errors') {
                        return '';
                    }
                    $xml = simplexml_load_string($response_xml);

                    $date = date_parse((string) $xml->{'subscription-exp-date'});
                    $last4 = (int) Tools::substr((string) $xml->{'card-details'}, -4);
                    //check if it`s duplicate.
                    $duplicate = false;
                    $vaultings = $this->serviceCorvusPayVaulting->
                    getCorvusPayVaultingsByIdCustomer($this->context->customer->id);

                    foreach ($vaultings as $vaulting) {
                        if ($vaulting->id_customer === (string) $this->context->customer->id &&
                            $vaulting->exp_year === (string) $date['year'] &&
                            $vaulting->exp_month === (string) $date['month'] &&
                            $vaulting->last4 = (string) $last4 && $vaulting->card_type === (string) $xml->{'cc-type'}
                        ) {
                            $duplicate = true;
                            break;
                        }
                    }
                    if (!$duplicate) {
                        $vaulting = new CorvusPayVaulting();
                        $vaulting->account_id = (int) Tools::getValue('account_id');
                        $vaulting->card_type = (string) $xml->{'cc-type'};
                        $vaulting->id_customer = $this->context->customer->id;
                        $vaulting->exp_year = $date['year'];
                        $vaulting->exp_month = $date['month'];
                        $vaulting->last4 = (int) Tools::substr((string) $xml->{'card-details'}, -4);

                        $this->serviceCorvusPayVaulting->createCorvusPayVaulting($vaulting);
                    }
                }
                $this->context->smarty->assign([
                    'params' => $_REQUEST,
                ]);
                $this->setTemplate('module:corvuspaypaymentgateway/views/templates/front/payment-return.tpl');

                /*
                 * Redirect the customer to the order confirmation page
                 */
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $isOrderX['id_cart'] .
                    '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' .
                    $customer->secure_key);
            }
        } elseif (Tools::getIsset('status') && Tools::getValue('status') === 'cancel') {
            if (Tools::getIsset('order_number') &&
                Tools::substr(
                    Tools::getValue('order_number'),
                    0,
                    Tools::strlen(self::CARD_STORAGE_PREFIX)
                )
                === self::CARD_STORAGE_PREFIX
            ) {
                $url = Context::getContext()->link->getModuleLink($this->module->name, 'paymentmethods');
                Tools::redirect($url);
            } else {
                $isOrderX = Db::getInstance()->getRow(' SELECT * FROM ' . _DB_PREFIX_ .
                    'orders WHERE id_customer = ' . $this->context->cart->id_customer . ' ORDER BY id_order DESC ');

                $id_order = (int) $isOrderX['id_order'];

                $objOrder = new Order($id_order);
                $history = new OrderHistory();
                $history->id_order = $objOrder->id;

                $history->changeIdOrderState(Configuration::get('PS_OS_CANCELED'), $objOrder->id);

                //restore cart after cancel.
                $this->restoreCartFromOrderId($objOrder->id);
            }
        } else {
            $this->check();

            /**
             * Get current cart object from session
             */
            $cart = $this->context->cart;
            /** @var CustomerCore $customer */
            $customer = new Customer($cart->id_customer);

            $currency = $this->context->currency;
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $mailVars = [
                '{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
                '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
                '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
            ];

            /**
             * Place the order
             */
            $order_status = Configuration::get('CORVUSPAY_OS_AWAITING_PAYMENT');
            if (Tools::getIsset('vaulting') && Tools::getValue('vaulting') === 'new') {
                $order_status = Configuration::get('CORVUSPAY_OS_AWAITING_PAYMENT');
            } elseif (Tools::getIsset('vaulting') && Tools::getValue('vaulting') !== 'new') {
                $order_status = Configuration::get('PS_OS_PAYMENT');
            }

            $this->module->validateOrder(
                $cart->id,
                $order_status,
                $total,
                $this->module->displayName,
                null,
                $mailVars,
                (int) $currency->id,
                false,
                $customer->secure_key
            );

            /**
             * Get current cart object from session
             */
            $cart = $this->context->cart;
            $id_order = Order::getIdByCartId($cart->id);
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $name_shop = $this->context->shop->name;
            $environment = Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT');

            if ($environment === 'prod') {
                $order_number = (string) $id_order;
            } else {
                $order_number = $name_shop . self::ORDER_NUMBER_DELIMITER . (string) $id_order;
            }

            $address = new Address($cart->id_address_delivery);

            $params = [
                'store_id' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    Tools::strtoupper($environment) . '_STORE_ID'),
                'secret_key' => Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    Tools::strtoupper($environment) . '_SECRET_KEY'),
                'environment' => $environment,
            ];
            $client = new CorvusPay\CorvusPayClient($params);

            if (Tools::getIsset('vaulting') && Tools::getValue('vaulting') !== 'new') {
                $client->setCertificate(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    Tools::strtoupper($environment) . '_CERTIFICATE'));

                $next_payment_params = [
                    'order_number' => $order_number,
                    'new_amount' => (string) $total,
                    'cart' => $this->getParameterCart($cart),
                    'account_id' => Tools::getValue('vaulting'),
                ];
                $response_xml = $client->subscription->pay($next_payment_params);

                if ($response_xml !== true) {
                    $this->module->displayError($this->l('Next payment subscription failed'));
                    PrestaShopLogger::addLog('Next payment subscription failed: ' . $response_xml, 3);

                    return '';
                }
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (string) $this->context->cart->id .
                    '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder .
                    '&key=' . $customer->secure_key);
            } else {
                $payment_action =
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PAYMENT_ACTION');

                $require_complete = 'false';

                if ($payment_action === 'authorize') {
                    $require_complete = 'true';
                }

                $params = [
                    'order_number' => $order_number,
                    'currency' => $this->context->currency->iso_code,
                    'amount' => (string) $total,
                    'cart' => $this->getParameterCart($cart),
                    'require_complete' => $require_complete,
                ];

                if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        'SEND_CARDHOLDER_INFO') === 'both') {
                    $cardholder_info = [
                        'cardholder_name' => $this->context->customer->firstname,
                        'cardholder_surname' => $this->context->customer->lastname,
                        'cardholder_address' => $address->address1,
                        'cardholder_city' => $address->city,
                        'cardholder_zip_code' => $address->postcode,
                        'cardholder_country' => $address->country,
                        'cardholder_phone' => $address->phone_mobile,
                        'cardholder_email' => $this->context->customer->email,
                    ];
                    $params = array_merge($params, $cardholder_info);
                } elseif (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX
                        . 'SEND_CARDHOLDER_INFO')
                    === 'mandatory') {
                    $cardholder_info = [
                        'cardholder_name' => $this->context->customer->firstname,
                        'cardholder_surname' => $this->context->customer->lastname,
                        'cardholder_email' => $this->context->customer->email,
                    ];
                    $params = array_merge($params, $cardholder_info);
                }

                if (Tools::getIsset('save-to-acc') && Tools::getValue('save-to-acc') === 'true') {
                    $params['subscription'] = 'true';
                }

                if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PIS_PAYMENTS_ENABLE')
                    === '1' &&
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'CREDITOR_REFERENCE')
                    !== '') {
                    $params['creditor_reference'] = strtr(
                        Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'CREDITOR_REFERENCE'),
                        [
                            '${orderId}' => (string) ($cart->id),
                        ]
                    );
                }

                if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX
                        . 'INSTALLMENTS') === 'advanced' &&
                    !empty(json_decode(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX
                        . 'INSTALLMENTS_MAP'), true))) {
                    $params['installments_map'] = $this->calculateParameterInstallmentsMap();
                } elseif (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'INSTALLMENTS')
                    === 'simple') {
                    $params['payment_all'] = 'Y0299';
                }

                if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TIME_LIMIT_ENABLE')
                    === '1' &&
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'BEFORE_TIME') !== '') {
                    $unixTimestamp = time() +
                        (int) Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'BEFORE_TIME');
                    $params['best_before'] = (string) $unixTimestamp;
                }

                if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'LANGUAGE') === 'auto') {
                    $params['language'] = $this->context->language->iso_code;
                } elseif (array_key_exists(
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'LANGUAGE'),
                    \CorvusPay\Service\CheckoutService::SUPPORTED_LANGUAGES
                )
                ) {
                    $params['language'] = Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX
                        . 'LANGUAGE');
                } else {
                    PrestaShopLogger::addLog('Language is not supported', 3);
                }

                $hide_tabs = json_decode(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX
                    . 'HIDE_TABS'), true);
                if (count($hide_tabs) > 0) {
                    $params['hide_tabs'] = implode(',', $hide_tabs);
                }

                $redirect = 'auto';

                if (Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'AUTOREDIRECT') === '0') {
                    $redirect = 'CONTINUE TO PAYMENT';
                }

                try {
                    $form = $client->checkout->create($params, $redirect, false);
                    $params_for_confirm_payment = [
                        'Order number' => $params['order_number'],
                        'Date' => date('d/m/Y'),
                        'Total' => $params['amount'] . $this->context->currency->symbol,
                        'Payment method' => 'CorvusPay',
                    ];
                    $this->context->smarty->assign([
                        'params' => $params_for_confirm_payment,
                        'form' => $form,
                    ]);
                    $this->setTemplate('module:corvuspaypaymentgateway/views/templates/front/confirm-payment.tpl');
                } catch (InvalidArgumentException $e) {
                    $this->errors[] = $this->l($e->getMessage());
                    $this->restoreCartFromOrderId($id_order);
                }
            }
        }
    }

    /**
     * Restore Cart for order id.
     */
    private function restoreCartFromOrderId($orderId)
    {
        $previousCartId = Cart::getCartIdByOrderId($orderId);
        $oldCart = new Cart($previousCartId);
        $duplication = $oldCart->duplicate();
        if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
            $this->errors[] = Tools::displayError($this->l('Sorry. We cannot renew your order.'));
        } elseif (!$duplication['success']) {
            $this->errors[] = Tools::displayError(
                $this->l('Some items are no longer available, and we are unable to renew your order.')
            );
        } else {
            $this->context->cookie->id_cart = $duplication['cart']->id;
            $context = $this->context;
            $context->cart = $duplication['cart'];
            CartRule::autoAddToCart($context);
            $this->context->cookie->write();
            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                $this->redirectWithNotifications($this->context->link->getPageLink('order-opc', null, null, array(
                    'step' => '3')));
            }
            $this->redirectWithNotifications($this->context->link->getPageLink('order', null, null, array(
                'step' => '3')));
        }
    }

    /**
     * Sets 'installments_map' parameter. Doesn't do a sanity check.
     */
    private function calculateParameterInstallmentsMap()
    {
        $installments_map_db =
            json_decode(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX
                . 'INSTALLMENTS_MAP'), true);
        $amount = (float) $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $installments_map = [];

        foreach ($installments_map_db as $installment) {
            for ($installments = (int) $installment['min_installments']; $installments <=
            (int) $installment['max_installments']; ++$installments) {
                if ('' !== $installment['general_percentage']) {
                    $installments_map[$installment['card_brand']][$installments]['amount'] =
                        $amount * (100 - (float) $installment['general_percentage']) / 100;
                }

                if ('' !== $installment['specific_percentage']) {
                    $installments_map[$installment['card_brand']][$installments]['discounted_amount'] =
                        $amount * (100 - (float) $installment['specific_percentage']) / 100;
                }
            }
        }

        return json_encode($installments_map, JSON_FORCE_OBJECT);
    }

    /**
     * Gets 'cart' parameter. Doesn't do a sanity check.
     *
     * @param mixed $cart
     *
     * @return string parameter cart
     */
    private function getParameterCart($cart)
    {
        $items = [];

        foreach ($cart->getProducts() as $item) {
            $items[] = $item['name'] . ' × ' . $item['cart_quantity'];
        }

        $parameter_cart = implode(', ', $items);

        $ellipsis = '...';

        if (mb_strlen($parameter_cart) > self::CART_MAX_LENGTH) {
            if (function_exists('mb_strimwidth')) {
                $parameter_cart = mb_strimwidth($parameter_cart, 0, self::CART_MAX_LENGTH, $ellipsis);
            } else {
                $parameter_cart =
                    Tools::substr($parameter_cart, 0, self::CART_MAX_LENGTH - Tools::strlen($ellipsis))
                    . $ellipsis;
            }
        }

        return $parameter_cart;
    }

    private function check()
    {
        /**
         * Get current cart object from session
         */
        $cart = $this->context->cart;

        /*
         * Verify if this module is enabled and if the cart has
         * a valid customer, delivery address and invoice address
         */
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 ||
            !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
         * Verify if this payment module is authorized
         */
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'corvuspaypaymentgateway') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            exit($this->module->l('This payment method is not available.', 'validation'));
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);

        /*
         * Check if this is a valid customer account
         */
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
    }
}
