<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\commerce\elements\Order;
use QD\commerce\economic\Economic;

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
		$invoice->setCurrency($order->paymentCurrency);
		$invoice->setDate(date('Y-m-d'));
		$invoice->setLayout(new Layout());
		$invoice->setPaymentTerms(PaymentTerms::transformFromOrder($order));
		$invoice->setRecipient(Recipient::transformFromOrder($order));
		$invoice->setLines(Economic::getInstance()->getOrders()->getOrderLines($order));
		$invoice->setCustomer(Customer::transformFromOrder($order));

		$invoice->setReferences([
			'other' => $order->reference
		]);



		return $invoice;
	}

	public function setCurrency($value){
		$this->currency = $value;
		return $this;
	}

	public function getCurrency(){
		return $this->currency;
	}

	public function setCustomer(Customer $value){
		$this->customer = $value;
		return $this;
	}

	public function getCustomer(){
		return $this->customer;
	}

	public function setDate($value){
		$this->date = $value;
		return $this;
	}

	public function getDate(){
		return $this->date;
	}

	public function setLayout(Layout $value){
		$this->layout = $value;
		return $this;
	}

	public function getLayout(){
		return $this->layout;
	}

	public function setPaymentTerms($value){
		$this->paymentTerms = $value;
		return $this;
	}

	public function getPaymentTerms(){
		return $this->paymentTerms;
	}

	public function setRecipient(Recipient $value){
		$this->recipient = $value;
		return $this;
	}

	public function getRecipient(){
		return $this->recipient;
	}

	public function setLines(array $value){
		$this->lines = $value;
		return $this;
	}

	public function getLines(array $value){
		return $this->lines;
	}

	public function setReferences(array $value){
		$this->references = $value;
		return $this;
	}

	public function getReferences(array $value){
		return $this->references;
	}


	public function rules()
	{
		return [
			[['customer', 'recipient', 'layout', 'currency','date', 'lines'], 'required'],
		];
	}

	public function asArray(){
		return (array) $this;
	}

}
