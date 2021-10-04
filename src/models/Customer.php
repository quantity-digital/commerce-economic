<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;

class Customer extends Model
{

	public $currency;
	public $customerNumber;
	public $vatZone;
	public $paymentTerms;
	public $name;
	public $corporateIdentificationNumber;
	public $email;
	public $address;
	public $city;
	public $zip;
	public $telephoneAndFaxNumber;
	public $country;
	public $customerGroup;

	public static function transformFromOrder($order)
	{
		$billingAddress = $order->getBillingAddress();
		$customerData = null;

		//Check for business customer
		$response = Economic::getInstance()->getCustomers()->getCustomerByVatNumber($billingAddress->businessTaxId);
		if ($response && isset($response->asObject()->collection[0])) {
			$customerData = $response->asObject()->collection[0];
		}

		//Check for private customer
		if (!$customerData && !$billingAddress->businessTaxId) {
			$response = Economic::getInstance()->getCustomers()->getCustomerByEmail($order->email);
			if ($response && isset($response->asObject()->collection[0])) {
				$customerData = $response->asObject()->collection[0];
			}
		}

		//Customer not created, do it now
		if (!$customerData) {
			$response = Economic::getInstance()->getCustomers()->createCustomerFromOrder($order);
			$customerData = $response->asObject();
		}

		$customer = self::transform($customerData);
		return $customer;
	}

	public static function transform($object)
	{
		$customer = new self();
		$customer->currency  = $object->currency;
		$customer->customerNumber  = $object->customerNumber;
		$customer->vatZone  = VatZone::transform($object->vatZone);
		$customer->name  = $object->name;
		$customer->customerGroup  = CustomerGroup::transform($object->customerGroup);
		$customer->paymentTerms  = PaymentTerms::transform($object->paymentTerms);
		$customer->corporateIdentificationNumber  = isset($object->corporateIdentificationNumber) ? $object->corporateIdentificationNumber : '';
		$customer->address  = $object->address;
		$customer->country  = $object->country;
		$customer->city  = $object->city;
		$customer->zip  = $object->zip;
		$customer->telephoneAndFaxNumber  = $object->telephoneAndFaxNumber;

		if (isset($object->email)) {
			$customer->email = $object->email;
		}

		return $customer;
	}

	/** Helpers */
	public function asArray()
	{
		return (array) $this;
	}
}
