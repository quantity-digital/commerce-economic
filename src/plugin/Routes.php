<?php

namespace QD\commerce\economic\plugin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

trait Routes
{

	private function registerSiteRoutes()
	{
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) {
		});
	}

	private function registerCpRoutes()
	{
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
			$event->rules = array_merge($event->rules, [
				//Creditnotes
				'commerce/creditnotes' => 'commerce-economic/creditnotes/index',
				'commerce/creditnotes/new/<orderId>' => 'commerce-economic/creditnotes/create',
				'commerce/creditnotes/edit/<creditnoteId>' => 'commerce-economic/creditnotes/edit',
			]);
		});
	}
}
