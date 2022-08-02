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
use craft\helpers\Db;
use craft\helpers\Template;
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
use yii\helpers\Markdown;

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

		if (!$creditnote->isRefunded) {
			$refundDate = $request->getBodyParam('dateRefunded');
			$creditnote->isRefunded = (bool)$request->getBodyParam('isRefunded');
			$creditnote->dateRefunded = (isset($refundDate['date']) && $refundDate['date']) ? date('Y-m-d', strtotime($refundDate['date'])) : null;
		}

		//Credit note is already completed, so only save refund settings
		if ($creditnote->isCompleted) {
			Craft::$app->getElements()->saveElement($creditnote);
			return;
		}


		$creditnote->restock = (bool)$request->getBodyParam('restock');
		$creditnote->regNr = $request->getBodyParam('regNr');
		$creditnote->accountNumber = $request->getBodyParam('accountNumber');


		$completeCreditnote = (bool)$request->getBodyParam('send');
		if ($completeCreditnote && $creditnote->isEan && $creditnote->regNr && $creditnote->accountNumber) {
			$creditnote->isCompleted = true;
		}

		if ($completeCreditnote && $creditnote->isEan && (!$creditnote->regNr || !$creditnote->accountNumber)) {
			Craft::$app->getSession()->setError('Reg. nr. & account number is required to complete an EAN order');
		}

		if ($completeCreditnote && !$creditnote->isEan) {
			$creditnote->isCompleted = true;
		}

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

			if ($creditnote->isEan) {

				$settings = Economic::getInstance()->getEconomicSettings();
				$to = Craft::parseEnv($settings['creditnoteNotificationEmail']);

				if ($to) {
					$template = Craft::$app->getMailer()->template;
					$mailer = Economic::getInstance()->getEmails()->setupMail('New creditnote for EAN order', $to, [
						'body' => Template::raw(Markdown::process('<h1>New creditnote</h1><p>A new creditnote for EAN order has been created.</p><p><a href="' . UrlHelper::cpUrl() . '/commerce/creditnotes/edit/' . $creditnote->id . '">Clike here to go to creditnote</a></p>')),
					], $template);

					$mailer->send();
				}
			}
		}
	}
}
