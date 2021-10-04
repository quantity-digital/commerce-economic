<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\helpers\Json;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote as CreditnoteElement;
use QD\commerce\economic\gateways\Ean;

class Creditnote extends Model
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
	public static function transformFromCreditnote(CreditnoteElement $creditnote)
	{
		$order = $creditnote->order();

		$invoice = new self();
		$invoice->currency = $order->paymentCurrency;
		$invoice->date = date('Y-m-d');
		$invoice->paymentTerms = PaymentTerms::transformFromOrder($order);
		$invoice->customer = Customer::transformFromOrder($order);
		$invoice->lines = Economic::getInstance()->getCreditnoteRows()->getOrderLines($creditnote);
		$invoice->recipient = Recipient::transformFromOrder($order);

		//Set layout
		$layout = new Layout();
		$layoutNumber = Json::decode(Economic::getInstance()->getEconomicSettings()->creditnoteLayoutNumber);
		$layout->setLayoutNumber($layoutNumber);

		$invoice->layout = $layout;

		$invoice->notes = (object) [
			'heading' => 'Webshop ' . $order->reference,
		];

		$invoice->references = (object)[];

		if (Ean::class === get_class($order->getGateway())) {
			// Invoice should include recipient contactperson etc.
			$contactData = null;

			$eanContact = $order->eanContact;
			if (!$eanContact) {
				$billing = $order->getBillingAddress();
				$eanContact = $billing->firstName . ' ' . $billing->lastName;
			}

			$response = Economic::getInstance()->getCustomers()->getCustomerContactByName($invoice->customer->customerNumber, $eanContact);
			$responseData = ($response) ? $response->asArray() : $response;

			if ($responseData && isset($responseData['collection'][0])) {
				$contactData = $responseData['collection'][0];
			}

			if (!$contactData) {
				$response = Economic::getInstance()->getCustomers()->createCustomerContact($invoice->customer->customerNumber, $eanContact);
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
