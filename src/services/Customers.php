<?php

namespace QD\commerce\economic\services;

use craft\base\Component;
use QD\commerce\economic\Economic;

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
