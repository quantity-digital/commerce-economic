<?php

namespace QD\commerce\economic;

use Craft;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\services\Gateways;
use craft\commerce\services\OrderHistories;
use craft\events\DefineBehaviorsEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use QD\commerce\economic\behaviors\OrderBehavior;
use QD\commerce\economic\behaviors\OrderQueryBehavior;
use QD\commerce\economic\elements\Setting;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\models\Settings;
use QD\commerce\economic\plugin\Services;
use QD\commerce\economic\variables\Economic as VariablesEconomic;
use QD\commerce\economic\fields\ProductGroup;
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
	public $schemaVersion = '1.0.3';
	public $hasCpSettings = true;
	public $hasCpSection = true;

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

		$this->initComponents();
		$this->registerVariables();
		$this->registerBehaviors();
		$this->registerEventListeners();
		$this->registerFieldTypes();
		$this->registerElementTypes();

		Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
			if ($event->plugin === $this) {
				if (Craft::$app->getRequest()->isCpRequest) {
					Craft::$app->getResponse()->redirect(
						UrlHelper::cpUrl('economic/settings#tab-authorization')
					)->send();
				}
			}
		});
	}

	public function getPluginName()
	{
		return 'E-conomic';
	}

	public function getSettings()
	{
		$setting = Setting::find()->one();
		if (!$setting) {
			$setting = new Setting();
		}

		return $setting;
	}

	// public function getSettingsResponse()
	// {
	//     Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('economic/settings'));
	// }

	private function registerVariables()
	{
		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
			$event->sender->set('economic', VariablesEconomic::class);
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
		// EAN payment gateway
		Event::on(
			Gateways::class,
			Gateways::EVENT_REGISTER_GATEWAY_TYPES,
			function (RegisterComponentTypesEvent $event) {
				$event->types[] = Ean::class;
			}
		);

		//Invoicing
		if ($this->getSettings() && $this->getSettings()->invoiceEnabled) {
			Event::on(OrderHistories::class, OrderHistories::EVENT_ORDER_STATUS_CHANGE, [$this->getInvoices(), 'addCreateInvoiceJob']);
		}

		Event::on(OrderHistories::class, OrderHistories::EVENT_ORDER_STATUS_CHANGE, [$this->getOrders(), 'addAutoCaptureJob']);
	}

	protected function registerSiteEventListeners()
	{
	}

	protected function registerCpEventListeners()
	{
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
			$event->rules = array_merge($event->rules, [
				'economic/settings' => 'commerce-economic/plugin/settings',
			]);
		});

		if ($this->getSettings() && $this->getSettings()->syncVariants) {
			// Ads job to queue when variant is save for product syncing
			// Event::on(Variant::class, Variant::EVENT_BEFORE_SAVE, [$this->getVariants(), 'addSyncVariantJob']);
			Event::on(Variant::class, Variant::EVENT_AFTER_SAVE, [$this->getVariants(), 'addSyncVariantJob']);
		}
	}

	protected function registerFieldTypes()
	{
		Event::on(
			Fields::class,
			Fields::EVENT_REGISTER_FIELD_TYPES,
			function (RegisterComponentTypesEvent $event) {
				$event->types[] = ProductGroup::class;
			}
		);
	}

	protected function registerElementTypes()
	{
		Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function (RegisterComponentTypesEvent $e) {
			$e->types[] = Setting::class;
		});
	}
}
