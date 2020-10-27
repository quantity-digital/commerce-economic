<?php

namespace QD\commerce\economic\services;

use craft\base\Component;
use craft\commerce\Plugin as CommercePlugin;
use QD\commerce\economic\Economic;
use QD\commerce\economic\models\Customer;
use QD\commerce\economic\models\CustomerGroup;
use QD\commerce\economic\models\PaymentTerms;
use QD\commerce\economic\models\VatZone;

class Customers extends Component
{

	public function getCustomerByVatNumber($vatNumber)
	{
		$response = Economic::getInstance()->getApi()->client->request->get('customers?filter=corporateIdentificationNumber$eq:' . $vatNumber);
		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

	public function getCustomerByEmail($email)
	{
		$response = Economic::getInstance()->getApi()->client->request->get('customers?filter=email$eq:' . $email);

		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

	public function createCustomerFromOrder($order)
	{
		$billingAddress = $order->getBillingAddress();

		$customer = new Customer();
		$customer->setCurrency(CommercePlugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso());
		$customer->setCustomerGroup(new CustomerGroup());
		$customer->setName(($billingAddress->businessName) ? $billingAddress->businessName : $billingAddress->firstName . ' ' . $billingAddress->lastName);
		$customer->setAddress($billingAddress->address1);
		$customer->setCity($billingAddress->city);
		$customer->setZip($billingAddress->zipCode);
		$customer->setCountry($billingAddress->country->name);
		$customer->setTelephoneAndFaxNumber($billingAddress->phone);
		$customer->setPaymentTerms(PaymentTerms::transformFromOrder($order));
		$customer->setVatZone(VatZone::transformFromOrder($order));
		$customer->setCorporateIdentificationNumber($order->getBillingAddress()->businessTaxId);
		$customer->setEmail($order->email);

		//Removing empty custonerNumber
		unset($customer->customerNumber);

		$response = Economic::getInstance()->getApi()->client->request->post('customers',$customer->asArray());

		$status = $response->httpStatus();

		if ($status == 201) {
			return $response;
		}

		return false;
	}

	public function getAllCustomerGroups()
	{
		$response = Economic::getInstance()->getApi()->client->request->get('customer-groups');

		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}
}
