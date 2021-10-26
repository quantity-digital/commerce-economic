<?php

/**
 * Commerce Invoices plugin for Craft CMS 3.x
 *
 * A pdf of an orders does not equal an invoice, invoices should be: Immutable, sequential in order.  Commerce Invoices allows you to create moment-in-time snapshots of a order to create a invoice or credit invoice
 *
 * @link      wndr.digital
 * @copyright Copyright (c) 2021 Len van Essen
 */

namespace QD\commerce\economic\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote;
use QD\commerce\economic\events\CreditnoteEvent;
use QD\commerce\economic\events\RestockEvent;
use QD\commerce\economic\helpers\Stock;
use QD\commerce\economic\queue\jobs\CreateCreditnote;
use QD\commerce\economic\queue\jobs\SendCreditnote;
use QD\commerce\economic\records\CreditnoteRowRecord;

/**
 * @author    Len van Essen
 * @package   CommerceInvoices
 * @since     1.0.0
 */
class TestController extends Controller
{

	public function actionTest()
	{
		$order = Order::find()->id(84)->one();

		$response = Economic::getInstance()->getOrders()->getOrderLines($order);

		echo '<pre>';
		print_r($response);
		echo '</pre>';
		die;
	}
}
