<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote;
use QD\commerce\economic\events\ApiResponseEvent;
use QD\commerce\economic\events\RestockEvent;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\helpers\Log;
use QD\commerce\economic\helpers\Stock;
use QD\commerce\economic\models\Creditnote as ModelsCreditnote;

class Creditnotes extends Component
{
	//Events
	const EVENT_AFTER_CREDITNOTE_BOOKING = 'afterCreditnoteBooking';
	const EVENT_BEFORE_RESTOCK = 'eventBeforeRestock';


	public function createFromOrder(Order $order)
	{
		$creditnote = new Creditnote();
		$creditnote->orderId = $order->id;
		$creditnote->isCompleted = false;

		$gateway = $order->getGateway();

		if ($gateway instanceof Ean) {
			$creditnote->isEan = true;
		}

		if (!Craft::$app->getElements()->saveElement($creditnote)) {
			return false;
		}

		$saved = Economic::getInstance()->getCreditnoteRows()->createFromOrder($order, $creditnote);

		return $creditnote;
	}

	public function createCreditnoteDraft(Creditnote $creditnote)
	{
		//Get base invoice model
		$creditnoteModel = ModelsCreditnote::transformFromCreditnote($creditnote);

		$response = Economic::getInstance()->getApi()->client->request->post('/invoices/drafts', $creditnoteModel->asArray());

		$status = $response->httpStatus();

		if ($status == 201) {
			return $response;
		}

		//Log error
		Log::error(\print_r($response, true));
		return false;
	}

	public function getInvoiceDraft($draftNumber)
	{
		$response = Economic::getInstance()->getApi()->client->request->get('/invoices/drafts/' . $draftNumber);
		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		Log::error(\print_r($response, true));
		return false;
	}

	public function bookCreditnoteDraft($draftNumber, $sendByEan = false)
	{
		$params = [
			"draftInvoice" => [
				'draftInvoiceNumber' => (int)$draftNumber,
				'self' => 'https://restapi.e-conomic.com/invoices/drafts/' . $draftNumber
			]
		];

		if ($sendByEan) {
			$params['sendBy'] = 'ean';
		}

		$response = Economic::getInstance()->getApi()->client->request->post('/invoices/booked/', $params);

		$status = $response->httpStatus();

		if ($status == 201 || $status == 201) {

			$event =  new ApiResponseEvent([
				'response' => $response,
				'sendByEan' => $sendByEan
			]);

			if ($this->hasEventHandlers(self::EVENT_AFTER_CREDITNOTE_BOOKING)) {
				$this->trigger(self::EVENT_AFTER_CREDITNOTE_BOOKING, $event);
			}

			return $event->response;
		}

		Log::error(\print_r($response, true));
		return false;
	}

	public function restock($creditnote)
	{
		$event =  new RestockEvent([
			'creditnote' => $creditnote
		]);

		if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTOCK)) {
			$this->trigger(self::EVENT_BEFORE_RESTOCK, $event);
		}

		if (!$event->isValid) {
			return;
		}

		foreach ($creditnote->rows as $row) {
			$lineItem = $row->lineItem;

			if (!$lineItem || !Stock::isRestockableLineItem($lineItem)) continue;
			$purchasable = Variant::findOne($event->lineItem->purchasableId);
			$purchasable->stock = $purchasable->stock += abs($row->qty);

			Craft::$app->getElements()->saveElement($purchasable);
		}
	}
}
