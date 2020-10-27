<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;

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

	public static function transformFromOrder($order)
	{
		$billingAddress = $order->getBillingAddress();
		$customerData = null;

		//Check for business customer
		$response = Economic::getInstance()->getCustomers()->getCustomerByVatNumber($billingAddress->businessTaxId);

		if($response && isset($response->asObject()->collection[0])){
			$customerData = $response->asObject()->collection[0];
		}

		//TODO add default customer for private customers etc.

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
		$customer->setCurrency($object->currency);
		$customer->setCustomerNumber($object->customerNumber);
		$customer->setVatZone(VatZone::transform($object->vatZone));
		$customer->setName($object->name);
		$customer->setCustomerGroup(CustomerGroup::transform($object->customerGroup));
		$customer->setPaymentTerms(PaymentTerms::transform($object->paymentTerms));
		$customer->setCorporateIdentificationNumber($object->corporateIdentificationNumber);
		$customer->setAddress($object->address);
		$customer->setCountry($object->country);
		$customer->setCity($object->city);
		$customer->setZip($object->zip);
		$customer->setTelephoneAndFaxNumber($object->telephoneAndFaxNumber);

		if(isset($object->email)){
			$customer->setEmail($object->email);
		}

		return $customer;
	}

	public function setCountry($value){
		$this->country = $value;
		return $this;
	}
	public function getCountry(){
		return $this->country;
	}

	public function setAddress($value){
		$this->address = $value;
		return $this;
	}
	public function getAddress(){
		return $this->address;
	}

	public function setCity($value){
		$this->city = $value;
		return $this;
	}
	public function getCity(){
		return $this->city;
	}

	public function setZip($value){
		$this->zip = $value;
		return $this;
	}
	public function getZip(){
		return $this->zip;
	}

	public function setTelephoneAndFaxNumber($value){
		$this->telephoneAndFaxNumber = $value;
		return $this;
	}
	public function getTelephoneAndFaxNumber(){
		return $this->telephoneAndFaxNumber;
	}

	public function setCorporateIdentificationNumber($value){
		$this->corporateIdentificationNumber = $value;
		return $this;
	}
	public function getCorporateIdentificationNumber(){
		return $this->corporateIdentificationNumber;
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

	public function setEmail($value)
	{
		$this->email = $value;
		return $this;
	}

	public function getEmail()
	{
		return $this->email;
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

	public function setCurrency(string $value)
	{
		$this->currency = $value;
		return $this;
	}
	public function getCurrency()
	{
		return $this->currency;
	}

	/** Helpers */
	public function asArray()
	{
		return (array) $this;
	}
}
