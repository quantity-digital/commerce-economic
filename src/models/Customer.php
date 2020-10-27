<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;

class Customer extends Model
{

	public $customerNumber;
	public $vatZone;
	public $paymentTerms;
	public $name;

	public static function transformFromOrder($order)
	{
		$billingAddress = $order->getBillingAddress();

		//Check for business customer
		$customerData = Economic::getInstance()->getCustomers()->getCustomerByVatNumber($billingAddress->businessTaxId);

		//Check for private customer if no business
		if (!$customerData) {
			$customerData = Economic::getInstance()->getCustomers()->getCustomerByEmail($order->email);
		}

		//Customer not created, do it now
		if (!$customerData) {
			$customerData = Economic::getInstance()->getCustomers()->createCustomerFromOrder($order);
		}

		$customer = self::transform($customerData->asObject()->collection[0]);

		return $customer;
	}

	public static function transform($object)
	{
		$customer = new self();
		$customer->setCustomerNumber($object->customerNumber);
		$customer->setVatZone(VatZone::transform($object->vatZone));
		$customer->setName($object->name);
		$customer->setCustomerGroup(CustomerGroup::transform($object->customerGroup));
		$customer->setPaymentTerms(PaymentTerms::transform($object->paymentTerms));
		return $customer;
	}

	public function setCustomerNumber($value)
	{
		$this->customerNumber = $value;
		return $this;
	}

	public function getCustomerNumber()
	{
		return $this->customerNumber;
	}

	public function setCustomerGroup(CustomerGroup $value)
	{
		$this->customerGroup = $value;
		return $this;
	}
	public function getCustomerGroup()
	{
		return $this->customerGroup;
	}

	public function setName(string $value)
	{
		$this->name = $value;
		return $this;
	}
	public function getName()
	{
		return $this->name;
	}

	public function setPaymentTerms(PaymentTerms $value)
	{
		$this->paymentTerms = $value;
		return $this;
	}
	public function getPaymentTerms()
	{
		return $this->paymentTerms;
	}

	public function setVatZone(VatZone $value)
	{
		$this->vatZone = $value;
		return $this;
	}
	public function getVatZone()
	{
		return $this->vatZone;
	}

	public function asArray()
	{
		return (array) $this;
	}
}
