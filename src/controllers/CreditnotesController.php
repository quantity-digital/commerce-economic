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
class CreditnotesController extends Controller
{

	const EVENT_AFTER_COMPLETE_CREDITNOTE = 'afterCompleteCreditnote';

	public function actionIndex()
	{
		//Return and render admin page
		return $this->renderTemplate('commerce-economic/creditnotes', []);
	}

	public function actionCreate($orderId)
	{
		$order = Order::find()->id($orderId)->one();

		$creditnote = Economic::getInstance()->getCreditnotes()->createFromOrder($order);

		Craft::$app->getResponse()->redirect(
			UrlHelper::cpUrl('commerce/creditnotes/edit/' . $creditnote->id)
		)->send();
	}

	public function actionEdit($creditnoteId)
	{
		$creditnote = Creditnote::find()->id($creditnoteId)->one();

		return $this->renderTemplate('commerce-economic/creditnotes/edit', [
			'creditnote' => $creditnote,
			'rows' => CreditnoteRowRecord::find()->where(['creditnoteId' => $creditnote->id])->all()
		]);
	}

	public function actionSave()
	{
		$request = Craft::$app->getRequest();
		$creditnoteId = $request->getBodyParam('creditnoteId');
		$creditnote = Creditnote::findOne($creditnoteId);

		if ((bool)$request->getBodyParam('reSend')) {
			Craft::$app->getQueue()->push(new SendCreditnote(
				[
					'creditnoteId' => $creditnote->id
				]
			));
		}

		if ($creditnote->isCompleted) {
			return;
		}

		$creditnote->isCompleted = (bool)$request->getBodyParam('send');
		$creditnote->restock = (bool)$request->getBodyParam('restock');

		$rows = $request->getBodyParam('rows') ?: [];
		foreach ($rows as $rowId => $data) {
			$row = CreditnoteRowRecord::findOne($rowId);
			$qty = (int)$data['qty'];

			if (($qty === 0 || !$qty) && $creditnote->isCompleted) {
				$row->delete();
				continue;
			}

			if ($qty > $row->available) {
				$qty = $row->available;
			}

			$row->qty = $qty;
			$row->save();
		}

		Craft::$app->getElements()->saveElement($creditnote);

		//If marked as completed, send it to e-conomic and restock
		if ($creditnote->isCompleted) {
			Craft::$app->getQueue()->delay(10)->push(new CreateCreditnote(
				[
					'creditnoteId' => $creditnote->id,
				]
			));

			$event =  new CreditnoteEvent([
				'creditnote' => $creditnote
			]);

			if ($this->hasEventHandlers(self::EVENT_AFTER_COMPLETE_CREDITNOTE)) {
				$this->trigger(self::EVENT_AFTER_COMPLETE_CREDITNOTE, $event);
			}

			if ($creditnote->restock) {
				Economic::getInstance()->getCreditnotes()->restock($creditnote);
			}
		}
	}
}
