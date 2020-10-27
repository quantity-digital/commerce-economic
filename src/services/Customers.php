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
		$customer = new Customer();
		$customer->setCurrency(CommercePlugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso());
		$customer->setCustomerGroup(new CustomerGroup());
		$customer->setName(($order->getBillingAddress()->businessName) ? $order->getBillingAddress()->businessName : $order->getBillingAddress()->firstName . ' ' . $order->getBillingAddress()->lastName);
		$customer->setPaymentTerms(PaymentTerms::transformFromOrder($order));
		$customer->setVatZone(VatZone::transformFromOrder($order));

		//Removing empty custonerNumber
		unset($customer->customerNumber);

		$response = Economic::getInstance()->getApi()->client->request->post('customers',$customer->asArra());

		$status = $response->httpStatus();

		if ($status == 200) {
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
