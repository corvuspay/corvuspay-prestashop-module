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

/**
 * Class CorvusPayVaulting.
 */
class CorvusPayVaulting extends ObjectModel
{
    /** @var int Id of the CorvusPayVaulting object. */
    public $id_corvuspay_vaulting;

    /** @var int Id of the Prestashop Customer object. */
    public $id_customer;

    /** @var int Parameter account_id. */
    public $account_id;

    /** @var string Card brand. One of 'amex'|'dina'|'diners'|'discover'|'maestro'|'master'|'visa'. */
    public $card_type;

    /** @var int Last 4 PAN digits. */
    public $last4;

    /** @var int Expiration year. */
    public $exp_year;

    /** @var int Expiration month. */
    public $exp_month;

    /** @var bool Expiration month. */
    public $is_default;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'corvuspay_vaulting',
        'primary' => 'id_corvuspay_vaulting',
        'multilang' => false,
        'fields' => [
            'id_corvuspay_vaulting' => ['type' => self::TYPE_INT],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'account_id' => ['type' => self::TYPE_INT],
            'card_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'last4' => ['type' => self::TYPE_INT],
            'exp_year' => ['type' => self::TYPE_INT],
            'exp_month' => ['type' => self::TYPE_INT],
            'is_default' => ['type' => self::TYPE_BOOL],
        ],
        'collation' => 'utf8_general_ci',
    ];
}
