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
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Plugins;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use QD\commerce\economic\behaviors\OrderBehavior;
use QD\commerce\economic\behaviors\OrderQueryBehavior;
use QD\commerce\economic\elements\Creditnote;
use QD\commerce\economic\elements\Setting;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\plugin\Services;
use QD\commerce\economic\variables\Economic as VariablesEconomic;
use QD\commerce\economic\fields\ProductGroup;
use QD\commerce\economic\plugin\Routes;
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
	public $schemaVersion = '1.1.1';
	public $hasCpSettings = false;
	public $hasCpSection = true;

	// Public Methods
	// =========================================================================

	use Services;
	use Routes;

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
		$this->registerCpRoutes();
		$this->registerSiteRoutes();

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

	public function getEconomicSettings()
	{
		$setting = false;
		try {
			//code...
			$setting = Setting::find()->one();
			if (!$setting) {
				$setting = new Setting();
			}
		} catch (\Throwable $th) {
			//throw $th;
		}

		return $setting;
	}

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
		if ($this->getEconomicSettings() && $this->getEconomicSettings()->invoiceEnabled) {
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


		Craft::$app->view->hook('cp.commerce.order.edit.order-secondary-actions', function (&$context) {
			return Craft::$app->view->renderTemplate('commerce-economic/order/secondary-actions', $context);
		});

		Craft::$app->view->hook('cp.commerce.order.edit.details', function (&$context) {
			$order = $context['order'];

			$context['creditnotes'] = Creditnote::find()->orderId($order->id)->all();

			return Craft::$app->view->renderTemplate('commerce-economic/order/details', $context);
		});

		//Add creditnote menu
		Event::on(
			Cp::class,
			Cp::EVENT_REGISTER_CP_NAV_ITEMS,
			function (RegisterCpNavItemsEvent $event) {

				$menuKey = 0;
				foreach ($event->navItems as $key => $navitem) {
					if ($navitem['url'] === 'commerce') {
						$menuKey = $key;
						break;
					}
				}

				$commerceNav = $event->navItems[$menuKey];

				$navItem = ['creditnotes' => ['label' => 'Credit notes', 'url' => 'commerce/creditnotes']];

				// array_splice($commerceNav['subnav'], 1, 0, $navItem);

				$commerceNav['subnav'] = array_slice($commerceNav['subnav'], 0, 1, true) +
					$navItem +
					array_slice($commerceNav['subnav'], 1, count($commerceNav['subnav']) - 1, true);

				$event->navItems[$menuKey] = $commerceNav;
			}
		);
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
			$e->types[] = Creditnote::class;
		});
	}
}
