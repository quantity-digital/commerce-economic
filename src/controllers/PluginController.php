<?php

namespace QD\commerce\economic\controllers;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\Json;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Setting;

class PluginController extends Controller
{
    // Public Methods
    // =========================================================================
    public $settings;

    public function actionSettings()
    {
        //Get from db model instead of project config
        $this->settings = Economic::getInstance()->getSettings();

        //Get commerce order statuses
        $statuses = [
            '' => '---'
        ];
        foreach (CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses() as $status) {
            $statuses[$status->id] = $status->name;
        }

        $gateways = [];
        foreach (CommercePlugin::getInstance()->getGateways()->getAllGateways() as $gateway) {
            $gateways[] = [
                'value' => $gateway->id,
                'label' => $gateway->name
            ];
        }

        $taxrates = [];
        foreach (CommercePlugin::getInstance()->getTaxRates()->getAllTaxRates() as $taxrate) {
            $taxrates[] = [
                'value' => $taxrate->id,
                'label' => $taxrate->name
            ];
        }

        $shippingMethods = [];
        foreach (CommercePlugin::getInstance()->getShippingMethods()->getAllShippingMethods() as $shippingMethod) {
            $shippingMethods[] = [
                'value' => $shippingMethod->id,
                'label' => $shippingMethod->name
            ];
        }

        $paymentTerms = $this->getPaymentTerms();
        $layouts = $this->getLayouts();
        $vatZones = $this->getVatZones();
        $customerGroups = $this->getCustomerGroups();
        $productGroups = $this->getProductGroups();
        $overrides = Craft::$app->getConfig()->getConfigFromFile('commerce-economic');

        return $this->renderTemplate('commerce-economic/settings', [
            'settings' => $this->settings,
            'overrides' => $overrides,
            'statusOptions' => $statuses,
            'paymentTerms' => $paymentTerms,
            'layouts' => $layouts,
            'vatZones' => $vatZones,
            'customerGroups' => $customerGroups,
            'gateways' => $gateways,
            'taxrastes' => $taxrates,
            'productGroups' => $productGroups,
            'shippingmethods' => $shippingMethods
        ]);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $setting = Setting::find()->one();

        if ($setting === null) {
            $setting = new Setting();
        }

        $settings = Craft::$app->getRequest()->getBodyParam('settings');

        $setting->secretToken = isset($settings['secretToken']) ? $settings['secretToken'] : null;
        $setting->grantToken = isset($settings['grantToken']) ? $settings['grantToken'] : null;
        $setting->defaultpaymentTermsNumber = isset($settings['defaultpaymentTermsNumber']) ? $settings['defaultpaymentTermsNumber'] : null;
        $setting->defaultLayoutNumber = isset($settings['defaultLayoutNumber']) ? $settings['defaultLayoutNumber'] : null;
        $setting->defaultCustomerGroup = isset($settings['defaultCustomerGroup']) ? $settings['defaultCustomerGroup'] : null;
        $setting->defaultVatZoneNumber = isset($settings['defaultVatZoneNumber']) ? $settings['defaultVatZoneNumber'] : null;
        $setting->defaultProductgroup = isset($settings['defaultProductgroup']) ? $settings['defaultProductgroup'] : null;
        $setting->invoiceEnabled = isset($settings['invoiceEnabled']) ? $settings['invoiceEnabled'] : false;
        $setting->statusIdAfterInvoice = isset($settings['statusIdAfterInvoice']) ? $settings['statusIdAfterInvoice'] : null;
        $setting->invoiceOnStatusId = isset($settings['invoiceOnStatusId']) ? $settings['invoiceOnStatusId'] : null;
        $setting->autoBookInvoice = isset($settings['autoBookInvoice']) ? $settings['autoBookInvoice'] : false;
        $setting->invoiceLayoutNumber = isset($settings['invoiceLayoutNumber']) ? $settings['invoiceLayoutNumber'] : null;
        $setting->gatewayPaymentTerms = $settings['gatewayPaymentTerms'] ? Json::encode($settings['gatewayPaymentTerms']) : '{}';
        $setting->shippingProductnumbers = $settings['shippingProductnumbers'] ? Json::encode($settings['shippingProductnumbers']) : '{}';
        $setting->vatZones = $settings['vatZones'] ? Json::encode($settings['vatZones']) : '{}';
        $setting->syncVariants = isset($settings['syncVariants']) ? $settings['syncVariants'] : false;
        $setting->onlyB2b = isset($settings['onlyB2b']) ? $settings['onlyB2b'] : false;

        if (!Craft::$app->getElements()->saveElement($setting)) {
            exit('Failed to save');
            // return $this->renderTemplate('commerce/store-settings/donation/_edit', compact('donation'));
        }

        $this->setSuccessFlash(Craft::t('commerce-economic', 'Settings saved.'));
        return $this->redirectToPostedUrl();
    }


    // Protected Methods
    // =========================================================================

    protected function getProductGroups()
    {
        $productGroups = [];

        //Check that both tokens have been saved
        if (!$this->settings->grantToken || !$this->settings->secretToken) {
            return $productGroups;
        }

        $response = Economic::getInstance()->getApi()->getAllProductGroups();

        if (!$response) {
            return $productGroups;
        }

        foreach ($response->asObject()->collection as $term) {
            $productGroups[] = [
                'value' => $term->productGroupNumber,
                'label' => $term->name
            ];
        }

        return $productGroups;
    }

    protected function getPaymentTerms()
    {
        $paymentTerms = [];

        //Check that both tokens have been saved
        if (!$this->settings->grantToken || !$this->settings->secretToken) {
            return $paymentTerms;
        }

        $terms = Economic::getInstance()->getApi()->getAllPaymentTerms();

        if (!$terms) {
            return $paymentTerms;
        }

        foreach ($terms->asObject()->collection as $term) {
            $paymentTerms[] = [
                'value' => $term->paymentTermsNumber,
                'label' => $term->name
            ];
        }

        return $paymentTerms;
    }

    protected function getLayouts()
    {
        $data = [];

        //Check that both tokens have been saved
        if (!$this->settings->grantToken || !$this->settings->secretToken) {
            return $data;
        }

        $layouts = Economic::getInstance()->getApi()->getAllLayouts();

        if (!$layouts) {
            return $data;
        }

        foreach ($layouts->asObject()->collection as $layout) {
            $data[] = [
                'value' => $layout->layoutNumber,
                'label' => $layout->name
            ];
        }

        return $data;
    }

    protected function getVatZones()
    {
        $data = [];

        //Check that both tokens have been saved
        if (!$this->settings->grantToken || !$this->settings->secretToken) {
            return $data;
        }

        $layouts = Economic::getInstance()->getApi()->getAllVatZones();

        if (!$layouts) {
            return $data;
        }

        foreach ($layouts->asObject()->collection as $layout) {
            $data[] = [
                'value' => $layout->vatZoneNumber,
                'label' => $layout->name
            ];
        }

        return $data;
    }

    protected function getCustomerGroups()
    {
        $data = [];

        //Check that both tokens have been saved
        if (!$this->settings->grantToken || !$this->settings->secretToken) {
            return $data;
        }

        $groups = Economic::getInstance()->getCustomers()->getAllCustomerGroups();

        if (!$groups) {
            return $data;
        }

        foreach ($groups->asObject()->collection as $group) {
            $data[] = [
                'value' => $group->customerGroupNumber,
                'label' => $group->name
            ];
        }

        return $data;
    }
}
