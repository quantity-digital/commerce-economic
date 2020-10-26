<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;

class Customer extends Model
{

	public $customerNumber;

	public static function transformFromOrder($order){
		$billingAddress = $order->getBillingAddress();

		//Check for business customer
		$customerData = Economic::getInstance()->getCustomers()->getCustomerByVatNumber($billingAddress->businessTaxId);

		//Check for private customer if no business
		if(!$customerData){
			$customerData = Economic::getInstance()->getCustomers()->getCustomerByEmail($order->email);
		}

		//TODO Customer doesn't exists in E-conomic, create new one
		if(!$customerData){
			$customerData = Economic::getInstance()->getCustomers()->createCustomerFromOrder($order);
		}

		$customer = self::transform($customerData->asObject()->collection[0]);

		return $customer;
	}

	public static function transform($object){

		$customer = new self();

		$customer->setCustomerNumber($object->customerNumber);

		return $customer;
	}

	public function setCustomerNumber($value){

		$this->customerNumber = $value;

		return $this;

	}
}
