<?php

namespace QD\commerce\economic;

use Craft;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\services\OrderHistories;
use craft\events\DefineBehaviorsEvent;
use craft\events\PluginEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use QD\commerce\economic\behaviors\OrderBehavior;
use QD\commerce\economic\behaviors\OrderQueryBehavior;
use QD\commerce\economic\models\Settings;
use QD\commerce\economic\plugin\Services;
use QD\commerce\economic\queue\jobs\CreateInvoice;
use yii\base\Event;

class Economic extends \craft\base\Plugin
{
	// Static Properties
	// =========================================================================

	public static $plugin;
	public static $commerceInstalled = false;

	// Public Properties
	// =========================================================================

	/**
	 * @inheritDoc
	 */
	public $schemaVersion = '1.0.0';
	public $hasCpSettings = true;
	public $hasCpSection = false;

	// Public Methods
	// =========================================================================

	use Services;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		self::$plugin = $this;
		self::$commerceInstalled = class_exists(CommercePlugin::class);

		$fileTarget = new \craft\log\FileTarget([
			'logFile' => __DIR__ . '/economic.log', // <--- path of the log file
			'categories' => ['commerce-economic'] // <--- categories in the file
		]);
		// include the new target file target to the dispatcher
		Craft::getLogger()->dispatcher->targets[] = $fileTarget;

		$this->initComponents();
		$this->registerBehaviors();
		$this->registerEventListeners();

		Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
			if ($event->plugin === $this) {
				if (Craft::$app->getRequest()->isCpRequest) {
					Craft::$app->getResponse()->redirect(
						UrlHelper::cpUrl('settings/plugins/commerce-economic')
					)->send();
				}
			}
		});
	}

	// Protected Methods
	// =========================================================================

	protected function registerBehaviors()
	{
		/**
		 * Order element behaviours
		 */
		Event::on(
			Order::class,
			Order::EVENT_DEFINE_BEHAVIORS,
			function (DefineBehaviorsEvent $e) {
				$e->behaviors['commerce-economic.attributes'] = OrderBehavior::class;
			}
		);
		Event::on(
			OrderQuery::class,
			OrderQuery::EVENT_DEFINE_BEHAVIORS,
			function (DefineBehaviorsEvent $e) {
				$e->behaviors['commerce-economic.queryparams'] = OrderQueryBehavior::class;
			}
		);
	}

	protected function registerEventListeners()
	{
		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_LOAD_PLUGINS,
			function () {
				// register these only after all other plugins have loaded
				$request = Craft::$app->getRequest();

				$this->registerGlobalEventListeners();

				if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
					$this->registerSiteEventListeners();
				}

				if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
					$this->registerCpEventListeners();
				}
			}
		);
	}

	protected function registerGlobalEventListeners()
	{
		//Invoicing
		if ($this->getSettings()->invoiceEnabled) {
			Event::on(OrderHistories::class, OrderHistories::EVENT_ORDER_STATUS_CHANGE, [$this->getInvoices(), 'addCreateInvoiceJob']);
		}
	}

	protected function registerSiteEventListeners()
	{
	}

	protected function registerCpEventListeners()
	{
	}

	/**
	 * Settings
	 */

	protected function createSettingsModel()
	{
		return new Settings();
	}

	protected function settingsHtml()
	{
		$statusOptions = [];
		foreach (CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses() as $status) {
			$statusOptions[] = [
				'value' => $status->id,
				'label' => $status->displayName
			];
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

		$paymentTerms = $this->getPaymentTerms();
		$layouts = $this->getLayouts();
		$vatZones = $this->getVatZones();
		$customerGroups = $this->getCustomerGroups();



		return \Craft::$app->getView()->renderTemplate(
			'commerce-economic/settings',
			[
				'settings' => $this->getSettings(),
				'statusOptions' => $statusOptions,
				'paymentTerms' => $paymentTerms,
				'layouts' => $layouts,
				'vatZones' => $vatZones,
				'customerGroups' => $customerGroups,
				'gateways' => $gateways,
				'taxrastes' => $taxrates
			]
		);
	}

	protected function getPaymentTerms()
	{
		$paymentTerms = [];

		//Check that both tokens have been saved
		if (!$this->getSettings()->grantToken || !$this->getSettings()->secretToken) {
			return $paymentTerms;
		}

		$terms = $this->getApi()->getAllPaymentTerms();

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
		if (!$this->getSettings()->grantToken || !$this->getSettings()->secretToken) {
			return $data;
		}

		$layouts = $this->getApi()->getAllLayouts();

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
		if (!$this->getSettings()->grantToken || !$this->getSettings()->secretToken) {
			return $data;
		}

		$layouts = $this->getApi()->getAllVatZones();

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
		if (!$this->getSettings()->grantToken || !$this->getSettings()->secretToken) {
			return $data;
		}

		$groups = $this->getCustomers()->getAllCustomerGroups();

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

	/**
	 * Logging
	 */

	public static function log($message)
	{
		Craft::getLogger()->log($message, \yii\log\Logger::LEVEL_INFO, 'commerce-economic');
	}
}
