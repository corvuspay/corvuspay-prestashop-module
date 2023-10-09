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

namespace CorvusPayAddons\services;

require_once _PS_MODULE_DIR_ . 'corvuspaypaymentgateway/classes/CorvusPayVaulting.php';

class ServiceCorvusPayVaulting
{
    /**
     * @param $corvusPayVaulting \CorvusPayVaulting
     *
     * @return bool
     *
     * @throws \PrestaShopException
     */
    public function createCorvusPayVaulting($corvusPayVaulting)
    {
        $corvusPayVaultingObject = $this->getCorvusPayVaultingById($corvusPayVaulting->id_corvuspay_vaulting);

        if (is_object($corvusPayVaultingObject) == false
            || \Validate::isLoadedObject($corvusPayVaultingObject) == false) {
            $corvusPayVaultingObject = new \CorvusPayVaulting();
            $corvusPayVaultingObject->id_customer = $corvusPayVaulting->id_customer;
            $corvusPayVaultingObject->last4 = $corvusPayVaulting->last4;
            $corvusPayVaultingObject->exp_month = $corvusPayVaulting->exp_month;
            $corvusPayVaultingObject->exp_year = $corvusPayVaulting->exp_year;
            $corvusPayVaultingObject->account_id = $corvusPayVaulting->account_id;
            $corvusPayVaultingObject->card_type = $corvusPayVaulting->card_type;
        } else {
            return false;
        }

        try {
            return $corvusPayVaultingObject->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $idCustomer integer id of the Prestashop Customer object
     *
     * @return array result of the query
     *
     * @throws \PrestaShopException
     */
    public function getCorvusPayVaultingsByIdCustomer($idCustomer)
    {
        $collection = new \PrestaShopCollection(\CorvusPayVaulting::class);
        $collection->where('id_customer', '=', (int) $idCustomer);

        return $collection->getAll()->getResults();
    }

    /**
     * @param $id integer id of the CorvusPayVaulting object
     * @param $idCustomer integer id of the Prestashop Customer object
     * @param $last4 integer
     * @param $exp_year integer
     * @param $exp_month integer
     *
     * @return \CorvusPayVaulting|bool object or false
     *
     * @throws \PrestaShopException
     */
    public function getCorvusPayVaulting($id, $idCustomer, $last4, $exp_year, $exp_month)
    {
        $collection = new \PrestaShopCollection(\CorvusPayVaulting::class);
        $collection->where('id', '=', (int) $id);
        $collection->where('id_customer', '=', (int) $idCustomer);
        $collection->where('last4', '=', (int) $last4);
        $collection->where('exp_year', '=', (int) $exp_year);
        $collection->where('exp_month', '=', (int) $exp_month);

        return $collection->getFirst();
    }

    /**
     * @param $id integer id of the CorvusPayVaulting object
     *
     * @return \CorvusPayVaulting|bool object or false
     *
     * @throws \PrestaShopException
     */
    public function getCorvusPayVaultingById($id)
    {
        $collection = new \PrestaShopCollection(\CorvusPayVaulting::class);
        $collection->where('id_corvuspay_vaulting', '=', (int) $id);

        return $collection->getFirst();
    }

    /**
     * @param $corvusPayVaultingId integer id of CorvusPayVaulting
     *
     * @return bool true or false
     */
    public function deleteCorvusPayVaultingById($corvusPayVaultingId)
    {
        try {
            return \Db::getInstance()->delete('corvuspay_vaulting', 'id_corvuspay_vaulting = ' . $corvusPayVaultingId);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $corvusPayVaultingId integer id of CorvusPayVaulting
     * @param $idCustomer integer id of the Prestashop Customer object
     *
     * @return bool true or false
     */
    public function makeDefaultCorvusPayVaultingById($corvusPayVaultingId, $idCustomer)
    {
        try {
            \Db::getInstance()->update(
                'corvuspay_vaulting',
                ['is_default' => 0],
                'id_customer = ' . (int) $idCustomer
            );
            \Db::getInstance()->update(
                'corvuspay_vaulting',
                ['is_default' => 1],
                'id_corvuspay_vaulting = ' . (int) $corvusPayVaultingId
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
