<?php 
/**
 * Novalnet payment plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @author      Novalnet AG
 * @copyright   Copyright (c) Novalnet
 * @license     https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: NovalnetPaymentHelper.php
 *
*/
 
namespace Plugin\jtl_novalnet\src;

use Exception;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use JTL\Session\Frontend;
use JTL\Catalog\Currency;
use JTL\Plugin\Helper;
use JTL\Helpers\Request;

/**
 * Class NovalnetPaymentHelper
 * @package Plugin\jtl_novalnet
 */
class NovalnetPaymentHelper
{
    /**
     * @var object
     */
    public $plugin;

    /**
     * NovalnetPaymentHelper constructor.
     */
    public function __construct()
    {
        $this->plugin = $this->getNovalnetPluginObject();
    }
    
    /**
     * Get plugin object
     * 
     * @return object
     */
    public function getNovalnetPluginObject(): object
    {
        return Helper::getPluginById('jtl_novalnet');
    }
    
    /**
     * Retrieve configuration values stored under Novalnet Plugin 
     *
     * @param  string $configuration
     * @return mixed
     */
    public function getConfigurationValues(string $configuration): mixed
    {
        $configValue = trim($this->plugin->getConfig()->getValue($configuration));
        if (!empty($configValue)) {
            
            // Only for the tariff ID field, we extract the value which is separated by tariff value and type
            if ($configuration == 'novalnet_tariffid') {
                $tariffValue = trim($this->plugin->getConfig()->getValue('novalnet_tariffid'));
                $tariffId = explode('-', $tariffValue);
                return $tariffId[0];
            }
            return $configValue;
        }
        return null;
    }
    
    /**
     * Building the required billing and shipping details from customer session
     * 
     * @param  object $billingAddress
     * @return array
     */
    public function getRequiredBillingShippingDetails(object $billingAddress): array
    {      
        $billingShippingDetails['billing'] = $billingShippingDetails['shipping'] = [
                                               'street'       => $billingAddress->cStrasse,
                                               'house_no'     => $billingAddress->cHausnummer,
                                               'city'         => $billingAddress->cOrt,
                                               'zip'          => $billingAddress->cPLZ,
                                               'country_code' => $billingAddress->cLand
                                             ];
                                             
        // Extracting the shipping address from the session object
        if (($_SESSION['Lieferadresse']) != '') {
            
        $shippingAddress = $_SESSION['Lieferadresse'];
        
        $billingShippingDetails['shipping'] = [
                                                'street'       => $shippingAddress->cStrasse,
                                                'house_no'     => $shippingAddress->cHausnummer,
                                                'city'         => $shippingAddress->cOrt,
                                                'zip'          => $shippingAddress->cPLZ,
                                                'country_code' => $shippingAddress->cLand
                                              ];
        }
        return $billingShippingDetails;
    }
    
    /**
     * Convert the order amount from decimal to integer
     *
     * @return int
     */
    public function getOrderAmount(): int
    {
        $convertedOrderAmount = Currency::convertCurrency(Frontend::getCart()->gibGesamtsummeWaren(true), Frontend::getCurrency()->getCode());
        if (($convertedOrderAmount) == '') {
            $convertedOrderAmount = $_SESSION['Warenkorb']->gibGesamtsummeWaren(true);
        }
        $orderAmount = str_replace(',', '.', sprintf('%.2f', $convertedOrderAmount));
        return $orderAmount * 100;
    }
    
    /**
     * Process the database update
     *
     * @param  string $tableName
     * @param  string $keyName
     * @param  string $keyValue
     * @param  array $updateValues
     * @return none
     */
    public function performDbUpdateProcess(string $tableName, string $keyName, string $keyValue, array $updateValues): void
    {
        Shop::Container()->getDB()->update($tableName , $keyName , $keyValue, (object) $updateValues);
    }
    
    /**
     * Get language texts for the fields
     *
     * @param  array       $languages
     * @param  string|null $langCode
     * @return array
     */
    public function getNnLanguageText(array $languages, ?string $langCode = null): array
    {
        foreach($languages as $lang) {
            $languageTexts[$lang] = $this->plugin->getLocalization()->getTranslation($lang, $langCode);
        }
        return $languageTexts;
    }
    
    /**
     * Get translated text for the provided Novalnet text key 
     *
     * @param  string      $key
     * @param  string|null $langCode
     * @return string
     */
    public function getNnLangTranslationText(string $key, ?string $langCode = null): string
    {
        return $this->plugin->getLocalization()->getTranslation($key, $langCode);
    }
    
    /**
     * Get server or remote address from the global variable
     *
     * @param  string $addressType
     * @return mixed
     */
    public function getNnIpAddress(string $addressType): mixed
    {
        if ($addressType == 'REMOTE_ADDR') {
            # Shop's core function that fetches the remote address
            $remoteIp = Request::getRealIP();
            $remoteAddress = ($remoteIp != '') ? $remoteIp : $_SERVER[$addressType];
            return $remoteAddress;
        }
        return $_SERVER[$addressType];
    }
}
