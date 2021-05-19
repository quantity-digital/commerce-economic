<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\commerce\elements\Order;
use craft\helpers\Json;
use QD\commerce\economic\Economic;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\helpers\Log;

class Invoice extends Model
{
	/** @var Customer $customer */
	public $customer;

	/** @var Recipient $recipient */
	public $recipient;

	/** @var Layout $layout */
	public $layout;

	/** @var Lines $lines */
	public $lines;

	/** @var PaymentTerm $paymentTerms */
	public $paymentTerms;

	/** @var string $currency */
	public $currency;

	/** @var string $date */
	public $date;

	public $references;

	public $notes;

	/**
	 * Transforms order into Invoice model to be use to create draft invoice
	 *
	 * @param \craft\commerce\elements\Order $order
	 *
	 * @return Invoice
	 */
	public static function transformFromOrder(Order $order)
	{
		$invoice = new self();
		$invoice->currency = $order->paymentCurrency;
		$invoice->date = date('Y-m-d');
		$invoice->paymentTerms = PaymentTerms::transformFromOrder($order);
		$invoice->customer = Customer::transformFromOrder($order);
		$invoice->lines = Economic::getInstance()->getOrders()->getOrderLines($order);
		$invoice->recipient = Recipient::transformFromOrder($order);

		//Get layout based on gateway
		$layout = new Layout();
		$gatewayId = $order->gatewayId;

		$gatewayRelations = Json::decode(Economic::getInstance()->getSettings()->gatewayPaymentTerms);

		foreach ($gatewayRelations as $gatewayRelation) {
			if ($gatewayRelation[0] == $gatewayId) {
				$layout->setLayoutNumber($gatewayRelation[2]);
			}
		}

		$invoice->layout = $layout;

		$invoice->notes = (object) [
			'heading' => 'Webshop ' . $order->reference,
		];

		$invoice->references = (object)[];

		if (Ean::class === get_class($order->getGateway())) {
			// Invoice should include recipient contactperson etc.
			$contactData = null;
			$response = Economic::getInstance()->getCustomers()->getCustomerContactByName($invoice->customer->customerNumber, $order->eanContact);
			$responseData = $response->asArray();

			if ($responseData['collection']) {
				$contactData = $responseData['collection'][0];
			}

			if (!$contactData) {
				$response = Economic::getInstance()->getCustomers()->createCustomerContact($invoice->customer->customerNumber, $order->eanContact);
				$contactData = $response->asArray();
			}

			$invoice->references = (object)[
				'other' => $order->eanReference ? $order->eanReference : '',
				'customerContact' => (object)[
					'customerContactNumber' => $contactData['customerContactNumber']
				]
			];
		}

		return $invoice;
	}

	public function rules()
	{
		return [
			[['customer', 'recipient', 'layout', 'currency', 'date', 'lines'], 'required'],
		];
	}

	public function asArray()
	{
		return (array) $this;
	}
}
