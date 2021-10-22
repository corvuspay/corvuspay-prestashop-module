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

require_once _PS_MODULE_DIR_ . 'corvuspaypaymentgateway/services/ServiceCorvusPayVaulting.php';
require_once _PS_MODULE_DIR_ . 'corvuspaypaymentgateway/vendor/autoload.php';

use CorvusPayAddons\services\ServiceCorvusPayVaulting;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CorvusPayPaymentGateway extends PaymentModule
{
    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    const ORDER_NUMBER_DELIMITER = ' - ';
    const ADMIN_DB_PARAMETERS = [
        'environment' => 'test',
        'test_store_id' => '',
        'test_secret_key' => '',
        'test_certificate' => '',
        'prod_store_id' => '',
        'prod_secret_key' => '',
        'prod_certificate' => '',
        'enable' => '1',
        'payment_action' => 'sale',
        'subscription' => '0',
        'autoredirect' => '1',
        'language' => 'auto',
        'send_cardholder_info' => 'both',
        'time_limit_enable' => '0',
        'before_time' => '',
        'installments' => 'disabled',
        'installments_map' => '',
        'pis_payments_enable' => '0',
        'creditor_reference' => 'HR00${orderId}',
        'hide_tabs' => '',
    ];
    const ADMIN_DB_PARAMETER_PREFIX = 'CP_';

    /**
     * List of hooks used in this Module
     */
    public $hooks = [
        'paymentOptions',
        'paymentReturn',
        'backOfficeHeader',
        'displayBackOfficeHeader',
        'header',
        'displayAdminOrder',
        'displayAdminOrderTop',
        'actionOrderSlipAdd',
        'actionOrderStatusPostUpdate',
        'actionOrderStatusUpdate',
        'displayCustomerAccount',
    ];
    /**
     * @var ServiceCorvusPayVaulting
     */
    protected $serviceCorvusPayVaulting;

    public function __construct()
    {
        $this->name = 'corvuspaypaymentgateway';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->author = 'Corvus-Info';
        $this->controllers = ['validation'];
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('CorvusPay');
        $this->description = $this->l('Extends Prestashop with CorvusPay Credit Card payments.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        foreach ($this->hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        // Registration order statuses
        if (!$this->installOrderState('CORVUSPAY_OS_AWAITING_PAYMENT', 'Awaiting for CorvusPay payment', '#4169E1')) {
            return false;
        }

        $result = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'corvuspay_vaulting` ('
            . ' `id_corvuspay_vaulting` INT(9) NOT NULL AUTO_INCREMENT,'
            . ' `id_customer` INT(50) NOT NULL,'
            . ' `account_id` INT(13) NOT NULL,'
            . ' `card_type` NVARCHAR(20) NOT NULL,'
            . ' `last4` INT(4) NOT NULL,'
            . ' `exp_year` INT(4) NOT NULL,'
            . ' `exp_month` INT(2) NOT NULL,'
            . ' `is_default` TINYINT(1) NOT NULL,'
            . ' PRIMARY KEY (`id_corvuspay_vaulting`)'
            . ' ) ENGINE=' . _MYSQL_ENGINE_ . ' default CHARSET=utf8;');

        if ($result === false) {
            return false;
        }

        $this->setDefaultValuesForAdminInputs();

        return true && $this->createTabLink();
    }

    /**
     * Create order state.
     *
     * @param $databaseName string key name in Configuration
     * @param $title string title of the order state
     * @param $color string color in HEX
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installOrderState($databaseName, $title, $color)
    {
        if (!Configuration::get($databaseName)) {
            $order_state = new OrderState();
            $order_state->name = [];
            foreach (Language::getLanguages() as $language) {
                $order_state->name[$language['id_lang']] = $title;
            }
            $order_state->send_email = false;
            $order_state->color = $color;
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->module_name = $this->name;
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_ . 'corvuspaypaymentgateway/views/img/os_corvuspay.png';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
                copy($source, $destination);
            }

            if (Shop::isFeatureActive()) {
                $shops = Shop::getShops();
                foreach ($shops as $shop) {
                    Configuration::updateValue(
                        $databaseName,
                        (int) $order_state->id,
                        false,
                        null,
                        (int) $shop['id_shop']
                    );
                }
            } else {
                Configuration::updateValue($databaseName, (int) $order_state->id);
            }
        }

        return true;
    }

    /**
     * Delete order states
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function uninstallOrderStates()
    {
        /* @var $orderState OrderState */
        $result = true;
        $collection = new PrestaShopCollection('OrderState');
        $collection->where('module_name', '=', $this->name);
        $orderStates = $collection->getResults();

        if ($orderStates == false) {
            return $result;
        }

        foreach ($orderStates as $orderState) {
            $result &= $orderState->delete();
        }
        Configuration::deleteByName('CORVUSPAY_OS_AWAITING_PAYMENT');

        return $result;
    }

    public function hookPaymentOptions($params)
    {
        if (Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . 'ENABLE') === '0') {
            $this->active = false;
        }

        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [
            $this->getExternalPaymentOption(),
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        try {
            $externalOption->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/CorvusPay.svg'));

            if (Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . 'SUBSCRIPTION') === '1') {
                $externalOption->setForm($this->generateForm());
            }
        } catch (SmartyException $e) {
            return '';
        } catch (Exception $e) {
        }

        return $externalOption;
    }

    protected function generateForm()
    {
        $this->serviceCorvusPayVaulting = new ServiceCorvusPayVaulting();

        $vaultings = $this->serviceCorvusPayVaulting->getCorvusPayVaultingsByIdCustomer($this->context->customer->id);

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation'),
            'vaultings' => $vaultings,
            'logged_in' => $this->context->customer->isLogged(),
        ]);

        return $this->context->smarty->fetch('module:corvuspaypaymentgateway/views/templates/front/payment_form.tpl');
    }

    public function hookDisplayAdminOrder($params)
    {
        // Since Ps 1.7.7 this hook is displayed at bottom of a page and we should use a hook DisplayAdminOrderTop
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return false;
        }

        $return = $this->getRefund($params);

        return $return;
    }

    protected function getRefund($params)
    {
        $environment = Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT');
        $config_params = [
            'store_id' => Configuration::get(
                self::ADMIN_DB_PARAMETER_PREFIX . Tools::strtoupper($environment) . '_STORE_ID'
            ),
            'secret_key' => Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . Tools::strtoupper($environment) .
                '_SECRET_KEY'),
            'environment' => $environment,
        ];
        $client = new CorvusPay\CorvusPayClient($config_params);
        $client->setCertificate(Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . Tools::strtoupper($environment) .
            '_CERTIFICATE'));
        $name_shop = $this->context->shop->name;
        $order_number = $name_shop . self::ORDER_NUMBER_DELIMITER . $params['id_order'];

        $status_params = [
            'order_number' => $order_number,
            'currency_code' => $this->context->currency->iso_code,
        ];
        $response_xml = $client->transaction->status($status_params);

        $response = new SimpleXMLElement($response_xml);
        if ($response->getName() === 'errors') {
            return '';
        }

        $this->context->smarty->assign('chb_corvuspay_refund', $this->l('Refund on CorvusPay'));

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/refund.tpl');
    }

    public function hookDisplayAdminOrderTop($params)
    {
        $return = $this->getRefund($params);

        return $return;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminCorvusPayPaymentGateway'));
    }

    public function createTabLink()
    {
        $tab = new Tab();
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('CorvusPayPaymentGateway');
        }
        $tab->class_name = 'AdminCorvusPayPaymentGateway';
        $tab->module = $this->name;
        $tab->id_parent = 0;
        $tab->add();

        return true;
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminCorvusPayPaymentGateway') {
            $this->context->controller->addJS($this->_path . 'views/js/corvuspay_admin.js', 'all');
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/corvuspay_front.js', 'all');
    }

    /**
     * @return array return the unregistered hooks
     */
    public function getHooksUnregistered()
    {
        $hooksUnregistered = [];

        foreach ($this->hooks as $hookName) {
            $hookName = Hook::getNameById(Hook::getIdByName($hookName));

            if (Hook::isModuleRegisteredOnHook($this, $hookName, $this->context->shop->id)) {
                continue;
            }

            $hooksUnregistered[] = $hookName;
        }

        return $hooksUnregistered;
    }

    public function resetHooks()
    {
        //Unregister module hooks
        // Retrieve hooks used by the module
        $query = new DbQuery();
        $query
            ->from('hook_module')
            ->where('id_module = ' . (int) $this->id)
            ->select('id_hook');
        $result = Db::getInstance()->executeS($query);

        if (false === empty($result)) {
            foreach ($result as $row) {
                $this->unregisterHook((int) $row['id_hook']);
                $this->unregisterExceptions((int) $row['id_hook']);
            }
        }

        //Register hooks
        if (false === empty($this->hooks)) {
            foreach ($this->hooks as $hook) {
                $this->registerHook($hook);
            }
        }
    }

    public function hookActionOrderSlipAdd($params)
    {
        $environment = Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT');
        $config_params = [
            'store_id' => Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . Tools::strtoupper($environment)
                . '_STORE_ID'),
            'secret_key' => Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . Tools::strtoupper($environment) .
                '_SECRET_KEY'),
            'environment' => $environment,
        ];
        $client = new CorvusPay\CorvusPayClient($config_params);
        $client->setCertificate(Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . Tools::strtoupper($environment) .
            '_CERTIFICATE'));

        $name_shop = $this->context->shop->name;
        $order_number = $name_shop . self::ORDER_NUMBER_DELIMITER . $params['order']->id;

        if (Tools::isSubmit('doPartialRefundCorvusPay')) {
            $amount = 0;

            foreach ($params['productList'] as $product) {
                $amount += $product['amount'];
            }

            if (\Tools::getValue('partialRefundShippingCost')) {
                $amount += \Tools::getValue('partialRefundShippingCost');
            }
            $amount = (float) $params['order']->total_paid - $amount;
            $refund_params = [
                'order_number' => $order_number,
                'new_amount' => $amount,
            ];

            $res_xml = $client->transaction->partiallyRefund($refund_params, true);
            $response = new SimpleXMLElement($res_xml);

            //if refund failed print error.
            if ($response->getName() === 'errors' || (int)$response->{'response-code'} !== 0) {
                PrestaShopLogger::addLog('Error in partial refund: ' . $res_xml, 3);
                throw new OrderException($this->l('Error occurred during refunding.'));
            }
            $objOrder = new Order((int) $params['order']->id);
            $history = new OrderHistory();
            $history->id_order = (int) $objOrder->id;
            $history->changeIdOrderState(Configuration::get('PS_OS_REFUND'), (int) ($objOrder->id));
            $history->add();
        }
        if (Tools::isSubmit('doRefundCorvusPay')) {
            $refund_params = [
                'order_number' => $order_number,
            ];

            $res_xml = $client->transaction->refund($refund_params, true);
            $response = new SimpleXMLElement($res_xml);

            //if refund failed print error.
            if ($response->getName() === 'errors' || (int)$response->{'response-code'} !== 0 || $response->head) {
                PrestaShopLogger::addLog('Error in refund: ' . $res_xml, 3);
                throw new OrderException($this->l('Error occurred during refunding.'));
            }
            $objOrder = new Order((int) $params['order']->id);
            $history = new OrderHistory();
            $history->id_order = (int) $objOrder->id;
            $history->changeIdOrderState(Configuration::get('PS_OS_REFUND'), (int) ($objOrder->id));
            $history->add();
        }
    }

    protected function redirectOrderDetail($orderId)
    {
        Tools::redirectAdmin('index.php?tab=AdminOrders&id_order=' . (int) $orderId . '&vieworder' . '&token=' .
            Tools::getAdminTokenLite('AdminOrders'));
    }

    protected function removeOrderSlip($productList)
    {
        foreach ($productList as $orderDetailLists) {
            $productQtyRefunded = 0;

            $idOrderDetail = $orderDetailLists['id_order_detail'];

            $countOfOrderSlipDetail = Db::getInstance()->getRow('SELECT COUNT(id_order_slip) as '
                . 'count_of_order_slip_detail from `'
                . _DB_PREFIX_ . 'order_slip_detail` where id_order_detail = '
                . (int) $idOrderDetail);

            if ((int) $countOfOrderSlipDetail['count_of_order_slip_detail'] !== 1) {
                $idOrderSlipDetail = Db::getInstance()->getRow('SELECT max(id_order_slip) as '
                    . ' id_order_slip from `'
                    . _DB_PREFIX_ . 'order_slip_detail` where id_order_detail = '
                    . (int) $idOrderDetail);
            } else {
                $idOrderSlipDetail['id_order_slip'] = 0;
            }

            Db::getInstance()->execute('DELETE from `'
                . _DB_PREFIX_ . 'order_slip_detail` where id_order_slip = '
                . (int) $idOrderSlipDetail['id_order_slip']);
            Db::getInstance()->execute('DELETE from `' . _DB_PREFIX_ . 'order_slip` where id_order_slip = '
                . (int) $idOrderSlipDetail['id_order_slip']);

            $orderDetail = Db::getInstance()->getRow('SELECT * from `'
                . _DB_PREFIX_ . 'order_detail` where id_order_detail = '
                . (int) $idOrderDetail);

            $productQtyRefunded = (int) $orderDetail['product_quantity_refunded'] -
                (int) $orderDetailLists['quantity'];

            Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'order_detail` '
                . ' set product_quantity_refunded = '
                . (int) $productQtyRefunded . ' where id_order_detail = '
                . (int) $idOrderDetail);
        }
    }

    /**
     * @return string
     */
    public function hookDisplayCustomerAccount()
    {
        if (Configuration::get(self::ADMIN_DB_PARAMETER_PREFIX . 'SUBSCRIPTION') === '1') {
            $context = Context::getContext();
            $id_customer = $context->customer->id;

            $url = Context::getContext()->link->getModuleLink($this->name, 'paymentmethods');

            $this->context->smarty->assign([
                'front_controller' => $url,
                'id_customer' => $id_customer,
            ]);

            return $this->display(dirname(__FILE__), '/views/templates/hook/my-account.tpl');
        } else {
            return '';
        }
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        foreach (array_keys(self::ADMIN_DB_PARAMETERS) as $key) {
            if (!Configuration::deleteByName(self::ADMIN_DB_PARAMETER_PREFIX . \Tools::strtoupper($key))) {
                return false;
            }
        }

        $result = Db::getInstance()->execute('DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'corvuspay_vaulting');
        if ($result === false) {
            return false;
        }

        if ($this->uninstallOrderStates() === false) {
            return false;
        }

        return true;
    }

    private function setDefaultValuesForAdminInputs()
    {
        foreach (self::ADMIN_DB_PARAMETERS as $parameter_key => $parameter_value) {
            \Configuration::updateValue(
                self::ADMIN_DB_PARAMETER_PREFIX . \Tools::strtoupper($parameter_key),
                $parameter_value
            );
        }
    }
}
