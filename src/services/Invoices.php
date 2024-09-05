<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use Exception;
use QD\commerce\economic\Economic;
use QD\commerce\economic\events\ApiResponseEvent;
use QD\commerce\economic\events\InvoiceEvent;
use QD\commerce\economic\helpers\Log;
use QD\commerce\economic\models\Invoice;
use QD\commerce\economic\queue\jobs\CreateInvoice;

class Invoices extends Component
{

	const EVENT_BEFORE_CREATE_INVOICE_DRAFT = 'beforeCrateInvoiceDraft';
	const EVENT_AFTER_INVOICE_BOOKING = 'afterInvoiceBooking';

	public function createFromOrder(Order $order)
	{
		//Get base invoice model
		$invoice = Invoice::transformFromOrder($order);
		return $this->createInvoiceDraft($invoice, $order);
	}

	public function createInvoiceDraft(Invoice $invoice, Order $order)
	{
		//Make it possible to modify Invoice model before creating invoice in e-conomic
		$event =  new InvoiceEvent([
			'invoice' => $invoice,
			'order' => $order
		]);

		$this->trigger(self::EVENT_BEFORE_CREATE_INVOICE_DRAFT, $event);
		if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_INVOICE_DRAFT)) {
			$this->trigger(self::EVENT_BEFORE_CREATE_INVOICE_DRAFT, $event);
		}

		$response = Economic::getInstance()->getApi()->client->request->post('/invoices/drafts', $event->invoice->asArray());

		$status = $response->httpStatus();

		if ($status == 201) {
			return $response;
		}

		if($status == 400)
		{
				$object = $response->asObject();
				throw new Exception(json_encode($object->errors,JSON_UNESCAPED_UNICODE), 1);
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

	public function bookInvoiceDraft($draftNumber, $sendByEan = false)
	{
		$draftInvoice = $this->getInvoiceDraft($draftNumber);

		if (!$draftInvoice) {
			return false;
		}

		$params = [
			"draftInvoice" => $draftInvoice->asObject()
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

			if ($this->hasEventHandlers(self::EVENT_AFTER_INVOICE_BOOKING)) {
				$this->trigger(self::EVENT_AFTER_INVOICE_BOOKING, $event);
			}

			return $event->response;
		}

		Log::error(\print_r($response, true));
		return false;
	}

	public function getInvoice($invoicenumber)
	{
		$response = Economic::getInstance()->getApi()->client->request->get('/invoices/booked/' . $invoicenumber);
		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

	public function getInvoicePdf($invoicenumber)
	{
		$response = Economic::getInstance()->getApi()->client->request->get('/invoices/booked/' . $invoicenumber . '/pdf');
		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

	/**
	 * Jobs
	 */
	public function addCreateInvoiceJob($event)
	{
		$order = $event->order;

		if ($order->draftInvoiceNumber) {
			return;
		}

		if ($order->invoiceNumber) {
			return;
		}


		$billingAddress = $order->getBillingAddress();
		$settings = Economic::getInstance()->getEconomicSettings();

		if ($settings->onlyB2b && !$billingAddress->businessTaxId) {
			return;
		}

		if ($event->orderHistory->newStatusId != Economic::getInstance()->getEconomicSettings()->invoiceOnStatusId) {
			return;
		}

		Craft::$app->getQueue()->delay(10)->push(new CreateInvoice(
			[
				'orderId' => $order->id,
			]
		));
	}
}
