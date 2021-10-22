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

class AdminCorvusPayPaymentGatewayController extends \ModuleAdminController
{
    protected $headerToolBar = true;
    protected $parameters = [];

    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;

        $this->parameters = array_keys(CorvusPayPaymentGateway::ADMIN_DB_PARAMETERS);
    }

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
        $tpl_vars = [];

        $this->initPaymentSettingsBlock();
        $formPaymentSettings = $this->renderForm();
        $this->clearFieldsForm();
        $tpl_vars['formPaymentSettings'] = $formPaymentSettings;
        $tpl_vars['card_brands'] = \CorvusPay\Service\CheckoutService::CARD_BRANDS;
        $this->context->smarty->assign($tpl_vars);
        $this->content = $this->context->smarty->fetch($this->getTemplatePath() . 'setup.tpl');
        $this->context->smarty->assign('content', $this->content);
    }

    public function renderForm($fields_form = null)
    {
        if ($fields_form === null) {
            $fields_form = $this->fields_form;
        }
        $helper = new \HelperForm();
        $helper->token = \Tools::getAdminTokenLite($this->controller_name);
        $helper->currentIndex = \AdminController::$currentIndex;
        $helper->submit_action = $this->controller_name . '_config';
        $default_lang = (int) \Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->tpl_vars = [
            'fields_value' => $this->tpl_form_vars,
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm($fields_form);
    }

    public function clearFieldsForm()
    {
        $this->fields_form = [];
        $this->tpl_form_vars = [];
    }

    public function initPaymentSettingsBlock()
    {
        $inputGroup = [];

        $paymentModeInput = [
            'label' => $this->l('Environment'),
            'type' => 'select',
            'name' => 'environment',
            'desc' => $this->l('This setting specifies whether you will process live transactions,
             or whether you will process simulated transactions.'),
            'options' => [
                'query' => [
                    [
                        'id' => 'test',
                        'name' => $this->l('Test'),
                    ],
                    [
                        'id' => 'prod',
                        'name' => $this->l('Production'),
                    ],
                ],
                'id' => 'id',
                'name' => 'name',
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Test Store ID'),
            'type' => 'text',
            'name' => 'test_store_id',
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Test Secret Key'),
            'type' => 'password',
            'name' => 'test_secret_key',
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Test API Certificate'),
            'type' => 'file',
            'name' => 'test_certificate',
            'multiple' => false,
            'desc' => $this->certificateInfo('test'),
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Test API Certificate Password'),
            'type' => 'password',
            'name' => 'test_certificate_password',
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Production Store ID'),
            'type' => 'text',
            'name' => 'prod_store_id',
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Production Secret Key'),
            'type' => 'password',
            'name' => 'prod_secret_key',
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Production API Certificate'),
            'type' => 'file',
            'name' => 'prod_certificate',
            'multiple' => false,
            'desc' => $this->certificateInfo('prod'),
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Production API Certificate Password	'),
            'type' => 'password',
            'name' => 'prod_certificate_password',
        ];
        $inputGroup[] = $paymentModeInput;

        $successLink = $this->context->link->getModuleLink(
            'corvuspaypaymentgateway',
            'validation',
            ['status' => 'success']
        );
        $cancelLink = $this->context->link->getModuleLink(
            'corvuspaypaymentgateway',
            'validation',
            ['status' => 'cancel']
        );

        $inputGroup[] = [
            'label' => $this->l('Success URL'),
            'type' => 'html',
            'name' => 'success_url',
            'html_content' => '<p>' . $successLink . '</p>',
            'desc' => $this->l('Copy Success URL to the CorvusPay Merchant Center'),
        ];

        $inputGroup[] = [
            'label' => $this->l('Cancel URL'),
            'type' => 'html',
            'name' => 'cancel_url',
            'html_content' => '<p>' . $cancelLink . '</p>',
            'desc' => $this->l('Copy Cancel URL to the CorvusPay Merchant Center'),
        ];

        $paymentModeInput = [
            'label' => $this->l('Enable/Disable'),
            'type' => 'switch',
            'name' => 'cp_enable',
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'enable',
                    'value' => 1,
                    'label' => $this->l('Enable'),
                ],
                [
                    'id' => 'disable',
                    'value' => 0,
                    'label' => $this->l('Disable'),
                ],
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Payment action'),
            'type' => 'select',
            'name' => 'payment_action',
            'desc' => $this->l('Sale is a one step transaction in which customer\'s card is charged immediately.
Authorize is a two step transaction (pre-autorized) - transaction must be captured (completed) by the merchant.'),
            'options' => [
                'query' => [
                    [
                        'id' => 'sale',
                        'name' => $this->l('Sale'),
                    ],
                    [
                        'id' => 'authorize',
                        'name' => $this->l('Authorize'),
                    ],
                ],
                'id' => 'id',
                'name' => 'name',
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Tokenization'),
            'type' => 'switch',
            'name' => 'subscription',
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'enable',
                    'value' => 1,
                    'label' => $this->l('Enable'),
                ],
                [
                    'id' => 'disable',
                    'value' => 0,
                    'label' => $this->l('Disable'),
                ],
            ],
            'desc' => $this->l('Enable/disable card storage.'),
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Payment Form Auto-redirect'),
            'type' => 'switch',
            'name' => 'autoredirect',
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'yes',
                    'value' => 1,
                    'label' => $this->l('Yes'),
                ],
                [
                    'id' => 'no',
                    'value' => 0,
                    'label' => $this->l('No'),
                ],
            ],
            'desc' => $this->l('Automatically redirect user to CorvusPay payment form.'),
        ];
        $inputGroup[] = $paymentModeInput;

        //Supported languages
        $options_query = [];
        $options_query[] = [
            'id' => 'auto',
            'name' => $this->l('Autodetect'),
        ];
        foreach (\CorvusPay\Service\CheckoutService::SUPPORTED_LANGUAGES as $code => $name) {
            $options_query[] = [
                'id' => $code,
                'name' => $this->l($name),
            ];
        }
        $paymentModeInput = [
            'label' => $this->l('Payment Form Language'),
            'type' => 'select',
            'name' => 'language',
            'options' => [
                'query' => $options_query,
                'id' => 'id',
                'name' => 'name',
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Send Cardholder Information'),
            'type' => 'select',
            'name' => 'send_cardholder_info',
            'desc' => $this->l('Send customer information to CorvusPay to speed up payment process. 
            Can include name, address and contact details.'),
            'options' => [
                'query' => [
                    [
                        'id' => 'none',
                        'name' => $this->l('None'),
                    ],
                    [
                        'id' => 'mandatory',
                        'name' => $this->l('Mandatory'),
                    ],
                    [
                        'id' => 'both',
                        'name' => $this->l('Both mandatory and optional'),
                    ],
                ],
                'id' => 'id',
                'name' => 'name',
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Time Limit'),
            'type' => 'switch',
            'name' => 'time_limit_enable',
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'enable',
                    'value' => 1,
                    'label' => $this->l('Enable'),
                ],
                [
                    'id' => 'disable',
                    'value' => 0,
                    'label' => $this->l('Disable'),
                ],
            ],
            'desc' => $this->l('Limit payment time. Make sure PrestaShop keeps accurate time.'),
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Time Limit in seconds'),
            'type' => 'text',
            'name' => 'before_time',
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Installments'),
            'type' => 'select',
            'name' => 'installments',
            'options' => [
                'query' => [
                    [
                        'id' => 'disabled',
                        'name' => $this->l('Disabled'),
                    ],
                    [
                        'id' => 'simple',
                        'name' => $this->l('Simple'),
                    ],
                    [
                        'id' => 'advanced',
                        'name' => $this->l('Advanced'),
                    ],
                ],
                'id' => 'id',
                'name' => 'name',
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $options = '';
        foreach (\CorvusPay\Service\CheckoutService::CARD_BRANDS as $code => $brand) {
            $options .= '<option value="' . $code . '"> ' . $brand . '</option>';
        }

        $rows = '';
        if (\Tools::isSubmit($this->controller_name . '_config') && Tools::getValue('installments') === 'advanced') {
            $installments_map_db =
                json_decode($this->generateInstallmentsMapFromPost(), true);
        } else {
            $installments_map_db =
                json_decode(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    'INSTALLMENTS_MAP'), true);
        }
        if ($installments_map_db == null) {
            $installments_map_db = [];
        }

        $i = 0;
        foreach ($installments_map_db as $installment) {
            $options = '';
            foreach (\CorvusPay\Service\CheckoutService::CARD_BRANDS as $code => $brand) {
                if ($code === $installment['card_brand']) {
                    $options .= '<option selected="selected" value="' . $code . '"> ' . $brand . '</option>';
                } else {
                    $options .= '<option value="' . $code . '"> ' . $brand . '</option>';
                }
            }

            $rows .= ' <tr>
                                        <td>
                                            <select title="Card brand" name="installments_map_card_brand[' . $i . ']"
                                                    class="selectpicker">' .
                $options
                . '
                                            </select>
                                        </td>
                                        <td><input type="text" title="Minimum installments" value="'
                . $installment['min_installments'] . '"
                                                   name="installments_map_min_installments[' . $i . ']">
                                        </td>
                                        <td><input type="text" title="Maximum installments" value="'
                . $installment['max_installments'] . '"
                                                   name="installments_map_max_installments[' . $i . ']">
                                        </td>
                                        <td><input type="text" title="General discount" value="'
                . $installment['general_percentage'] . '"
                                                   name="installments_map_general_percentage[' . $i . ']">
                                        </td>
                                        <td><input type="text" title="Specific discount" value="'
                . $installment['specific_percentage'] . '"
                                                   name="installments_map_specific_percentage[' . $i . ']">
                                        </td>
                                        <td><a class="delete" href="#"> 
                                        <i class="material-icons">delete</i></a>
                                        </td>
                                    </tr>';
            ++$i;
        }

        $html_map = ' <div class="form-group"> 
                                <table id="installments_map">
                                    <thead>
                                    <tr>
                                        <th>' . $this->l('Card brand') . '</th>
                                        <th>' . $this->l('Minimum installments') . '</th>
                                        <th>' . $this->l('Maximum installments') . '</th>
                                        <th>' . $this->l('General discount') . '</th>
                                        <th>' . $this->l('Specific discount') . '</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                   ' . $rows . '
                                    </tbody>
                                </table>
                                <b><a href="#" class="add button">' . $this->l('+ Add installment entry') . '</a></b>
                                <p>' . $this->l('Example row: "Visa; 1; 2; 10; 15".') . '</p>
                                <p>' . $this->l('Explanation: All Visa cards get a 10% discount if customer pays 
                                in one payment or in two installments. Some Visa cards, issued by a specific issuer, 
                                get a 15% discount under the same conditions. 
                                To setup specific discounts, contact CorvusPay.') . '</p>
                            </div>';

        $inputGroup[] = [
            'type' => 'html',
            'name' => 'installments_map',
            'html_content' => $html_map,
        ];

        $paymentModeInput = [
            'label' => $this->l('PIS Payments'),
            'type' => 'switch',
            'name' => 'pis_payments_enable',
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'enable',
                    'value' => 1,
                    'label' => $this->l('Enable'),
                ],
                [
                    'id' => 'disable',
                    'value' => 0,
                    'label' => $this->l('Disable'),
                ],
            ],
            'desc' => $this->l('Enable Pay by IBAN functionality.'),
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Creditor Reference'),
            'type' => 'text',
            'name' => 'creditor_reference',
            'desc' => $this->l('Payee model and reference number for PIS payments. 
            Sequence "${orderId}" is replaced with PrestaShop Order ID.'),
        ];
        $inputGroup[] = $paymentModeInput;

        $paymentModeInput = [
            'label' => $this->l('Hide payment methods'),
            'type' => 'select',
            'name' => 'hide_tabs[]',
            'desc' => $this->l('Hide payment methods during checkout.'),
            'multiple' => true,
            'options' => [
                'query' => [
                    [
                        'id' => 'checkout',
                        'name' => $this->l('CorvusPay by Card'),
                    ],
                    [
                        'id' => 'pis',
                        'name' => $this->l('CorvusPay by IBAN'),
                    ],
                    [
                        'id' => 'wallet',
                        'name' => $this->l('CorvusWallet'),
                    ],
                    [
                        'id' => 'paysafecard',
                        'name' => $this->l('paysafecard'),
                    ],
                ],
                'id' => 'id',
                'name' => 'name',
            ],
        ];
        $inputGroup[] = $paymentModeInput;

        $this->fields_form['form']['form'] = [
            'legend' => [
                'title' => $this->l('Payment settings'),
                'icon' => 'icon-cogs',
            ],
            'input' => $inputGroup,
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right button',
            ],
            'id_form' => 'cp_config_payment',
        ];

        if (\Tools::isSubmit($this->controller_name . '_config')) {
            $values = [
                'environment' => Tools::getValue('environment'),
                'test_store_id' => Tools::getValue('test_store_id'),
                'test_secret_key' => Tools::getValue('test_secret_key'),
                'test_certificate_password' => Tools::getValue('test_certificate_password'),
                'prod_store_id' => Tools::getValue('prod_store_id'),
                'prod_secret_key' => Tools::getValue('prod_secret_key'),
                'prod_certificate_password' => Tools::getValue('prod_certificate_password'),
                'cp_enable' => Tools::getValue('cp_enable'),
                'payment_action' => Tools::getValue('payment_action'),
                'subscription' => Tools::getValue('subscription'),
                'autoredirect' => Tools::getValue('autoredirect'),
                'language' => Tools::getValue('language'),
                'send_cardholder_info' => Tools::getValue('send_cardholder_info'),
                'time_limit_enable' => Tools::getValue('time_limit_enable'),
                'before_time' => Tools::getValue('before_time'),
                'installments' => Tools::getValue('installments'),
                'pis_payments_enable' => Tools::getValue('pis_payments_enable'),
                'creditor_reference' => Tools::getValue('creditor_reference'),
            ];
            $hide_tabs_value = Tools::getValue('hide_tabs');
            if (is_array($hide_tabs_value)) {
                $values['hide_tabs[]'] = $hide_tabs_value;
            } else {
                $values['hide_tabs[]'] = [];
            }
        } else {
            $values = [
                'environment' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'ENVIRONMENT'),
                'test_store_id' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TEST_STORE_ID'),
                'test_secret_key' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TEST_SECRET_KEY'),
                'test_certificate' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TEST_CERTIFICATE'),
                'prod_store_id' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PROD_STORE_ID'),
                'prod_secret_key' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PROD_SECRET_KEY'),
                'prod_certificate' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PROD_CERTIFICATE'),
                'cp_enable' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'ENABLE'),
                'payment_action' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PAYMENT_ACTION'),
                'subscription' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'SUBSCRIPTION'),
                'autoredirect' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'AUTOREDIRECT'),
                'language' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'LANGUAGE'),
                'send_cardholder_info' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    'SEND_CARDHOLDER_INFO'),
                'time_limit_enable' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TIME_LIMIT_ENABLE'),
                'before_time' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'BEFORE_TIME'),
                'installments' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'INSTALLMENTS'),
                'pis_payments_enable' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    'PIS_PAYMENTS_ENABLE'),
                'creditor_reference' =>
                    Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    'CREDITOR_REFERENCE'),
                'hide_tabs[]' =>
                    json_decode(Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        'HIDE_TABS'), true),
            ];
        }

        $this->tpl_form_vars = array_merge($this->tpl_form_vars, $values);
    }

    public function postProcess()
    {
        if (\Tools::isSubmit($this->controller_name . '_config')) {
            if ($this->saveForm()) {
                $this->confirmations[] =
                    $this->module->l('Successful update.', 'AdminCorvusPayPaymentGatewayController');
            }
        }
        parent::postProcess();
    }

    public function saveForm()
    {
        $result = true;

        $environment = Tools::getValue('environment');

        if (array_key_exists($environment . '_certificate', $_FILES)
            && $_FILES[$environment . '_certificate']['name'] !== '') {
            // Check if the uploaded file has (*.p12) extension.
            $fileExtensionAllowed = 'p12';
            $fileName = $_FILES[$environment . '_certificate']['name'];
            $fileTmpName = $_FILES[$environment . '_certificate']['tmp_name'];
            $exploded = explode('.', $fileName);
            $fileExtension = Tools::strtolower(end($exploded));

            if ($fileExtension === $fileExtensionAllowed) {
                // Configuration.
                $fp = fopen($fileTmpName, 'r');
                // If a certificate has been sent, read the contents and save Base64 encoded data instead.
                $pem_array = [];
                $content = '';

                while (!feof($fp)) {
                    $content .= fread($fp, 8192);
                }

                $passwordCertificate = Tools::getValue($environment . '_certificate_password');
                $read = openssl_pkcs12_read($content, $pem_array, $passwordCertificate);
                if ($read) {
                    PrestaShopLogger::addLog('openssl_pkcs12_read: ' . json_encode($pem_array), 1);
                } else {
                    PrestaShopLogger::addLog('Parsing the PKCS#12 Certificate Store into an array failed.', 3);
                    $this->errors[] = $this->l('Incorrect certificate or certificate password.');

                    return false;
                }

                $pkcs12_string = '';
                if (openssl_pkcs12_export($pem_array['cert'], $pkcs12_string, $pem_array['pkey'], '', $pem_array)) {
                    PrestaShopLogger::addLog('Exporting the PKCS#12 Compatible Certificate Store File
                     to a variable succeeded.', 1);
                    $result &= \Configuration::updateValue(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        \Tools::strtoupper($environment) . '_CERTIFICATE', pSQL(base64_encode($pkcs12_string)));
                // It is a third party system requirement to use base64 encoded data.
                } else {
                    PrestaShopLogger::addLog('Unable to store certificate', 3);
                    $this->errors[] = $this->l('Incorrect certificate or certificate password.');

                    return false;
                }
            } else {
                Configuration::updateValue(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                    \Tools::strtoupper($environment) . '_CERTIFICATE', '');
                $this->errors[] = $this->l('Only certificates in PKCS#12 (*.p12) format are supported.');

                return false;
            }
        }
        if (!$this->validate()) {
            return false;
        }

        foreach (\Tools::getAllValues() as $fieldName => $fieldValue) {
            if (in_array($fieldName, $this->parameters)) {
                if (is_string($fieldValue)) {
                    if ($fieldName === 'test_certificate' || $fieldName === 'prod_certificate' ||
                        (($fieldName === 'test_secret_key' || $fieldName === 'prod_secret_key')
                            && $fieldValue === '')) {
                        // skip if password is not set or certificate name.
                        continue;
                    }
                    $result &= \Configuration::updateValue(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                        \Tools::strtoupper($fieldName), pSQL($fieldValue));
                }
            }
        }
        $hide_tabs_value = Tools::getValue('hide_tabs');
        if (is_array($hide_tabs_value)) {
            $result &= \Configuration::updateValue(
                CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'HIDE_TABS',
                json_encode($hide_tabs_value)
            );
        } else {
            $result &= \Configuration::updateValue(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
                'HIDE_TABS', json_encode([]));
        }

        if (Tools::getValue('installments') === 'advanced') {
            $result &= \Configuration::updateValue(
                CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'INSTALLMENTS_MAP',
                $this->generateInstallmentsMapFromPost()
            );
        }

        return $result;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns human readable certificate description with validity.
     *
     * @param string $environment Payment environment. One of 'test'|'prod'.
     *
     * @return string certificate description
     */
    private function certificateInfo($environment)
    {
        $certificate = Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX .
            Tools::strtoupper($environment) . '_CERTIFICATE');

        if (empty($certificate)) {
            return $this->l('No certificate stored. Only certificates in PKCS#12 (*.p12) format are supported.');
        }

        $certificate = base64_decode($certificate);
        // It is a third party system requirement to use base64 encoded data.

        $pem_array = [];
        if (!openssl_pkcs12_read($certificate, $pem_array, '')) {
            return $this->l('Invalid certificate.');
        }

        $certificate = openssl_x509_parse($pem_array['cert']);

        if (!$certificate) {
            return $this->l('Invalid certificate.');
        }

        $expiration = date('Y-m-d', $certificate['validTo_time_t']);
        if ($certificate['validTo_time_t'] < time()) { // Already expired.
            return $this->l('Certificate ') . $certificate['subject']['CN'] . $this->l(' expired on ') .
                $expiration;
        } elseif ($certificate['validTo_time_t'] < (time() - 30 * 24 * 60 * 60)) { // Expires soon.
            return $this->l('Certificate ') . $certificate['subject']['CN'] . $this->l(' will expire on ') .
                $expiration;
        } else { // Valid.
            return $this->l('Certificate ') . $certificate['subject']['CN'] . $this->l(' is valid until ') .
                $expiration;
        }
    }

    /**
     * Returns whether the parameters are valid.
     *
     * @return bool
     */
    private function validate()
    {
        $are_valid = true;

        $isValidProductionStoreId = preg_match(
            "/^\d+$/",
            Tools::getValue('prod_store_id')
        );
        if (Tools::getValue('environment') === 'prod' &&
            (Tools::getValue('prod_store_id') === '' || $isValidProductionStoreId === 0)
        ) {
            $this->errors[] = $this->l('Production store id is mandatory and should contain only numbers.');

            $are_valid = false;
        }
        $isValidTestStoreId = preg_match(
            "/^\d+$/",
            Tools::getValue('test_store_id')
        );
        if (Tools::getValue('environment') === 'test' &&
            (Tools::getValue('test_store_id') === '' || $isValidTestStoreId === 0)
        ) {
            $this->errors[] = $this->l('Test store id is mandatory and should contain only numbers.');

            $are_valid = false;
        }
        if (Tools::getValue('environment') === 'prod' &&
            Tools::getValue('prod_secret_key') === '' &&
            Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PROD_SECRET_KEY') === '') {
            $this->errors[] = $this->l('Production secret key is mandatory');

            $are_valid = false;
        }
        if (Tools::getValue('environment') === 'test' &&
            Tools::getValue('test_secret_key') === '' &&
            Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TEST_SECRET_KEY') === '') {
            $this->errors[] = $this->l('Test secret key is mandatory');

            $are_valid = false;
        }
        if (Tools::getValue('time_limit_enable') === '1' &&
            (Tools::getValue('before_time') === '' ||
                preg_match("/^\d+$/", Tools::getValue('before_time')) === 0
                || (int) Tools::getValue('before_time') < 1 || (int) Tools::getValue('before_time') > 900)) {
            $this->errors[] = $this->l('Time limit must be number and should be in range 1-900.');

            $are_valid = false;
        }
        if (Tools::getValue('environment') === 'test' &&
            Tools::getValue('subscription') === '1' &&
            Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'TEST_CERTIFICATE') === '') {
            $this->errors[] =
                $this->l('When tokenization is enabled, certificate and certificate password are required.');

            $are_valid = false;
        }
        if (Tools::getValue('environment') === 'prod' &&
            Tools::getValue('subscription') === '1' &&
            Configuration::get(CorvusPayPaymentGateway::ADMIN_DB_PARAMETER_PREFIX . 'PROD_CERTIFICATE') === '') {
            $this->errors[] =
                $this->l('When tokenization is enabled, certificate and certificate password are required.');

            $are_valid = false;
        }

        return $are_valid;
    }

    /**
     * Generates installments map from POST and returns a storable representation.
     *
     * @return string
     */
    private function generateInstallmentsMapFromPost()
    {
        $installments_map = [];
        if (Tools::getIsset('installments_map_card_brand') &&
            Tools::getIsset('installments_map_min_installments') &&
            Tools::getIsset('installments_map_max_installments') &&
            Tools::getIsset('installments_map_general_percentage') &&
            Tools::getIsset('installments_map_specific_percentage')) {
            $card_brand = Tools::getValue('installments_map_card_brand');
            $min_installments = Tools::getValue('installments_map_min_installments');
            $max_installments = Tools::getValue('installments_map_max_installments');
            $general_percentage = Tools::getValue('installments_map_general_percentage');
            $specific_percentage = Tools::getValue('installments_map_specific_percentage');

            foreach ($card_brand as $i => $brand) {
                if (!array_key_exists($i, $card_brand)) {
                    continue;
                }
                $installments_map[] = [
                    'card_brand' => $brand,
                    'min_installments' => $min_installments[$i],
                    'max_installments' => $max_installments[$i],
                    'general_percentage' => $general_percentage[$i],
                    'specific_percentage' => $specific_percentage[$i],
                ];
            }
        }

        return json_encode($installments_map);
    }
}
