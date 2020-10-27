<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use Lenius\Economic\RestClient;
use QD\commerce\economic\Economic;
use QD\commerce\economic\models\Invoice;

class Api extends Component
{

	public $client;

	// Public Methods
	// =========================================================================

	public function __construct()
	{
		$settings = Economic::getInstance()->getSettings();
		$grantToken = Craft::parseEnv($settings->grantToken);
		$secretToken = Craft::parseEnv($settings->secretToken);
		$this->client = new RestClient($secretToken, $grantToken);
	}


	//Move out into own seperate services
	public function getAllPaymentTerms()
	{
		$response = $this->client->request->get('payment-terms');

		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

	public function getAllLayouts()
	{
		$response = $this->client->request->get('layouts');

		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

	public function getAllVatZones()
	{
		$response = $this->client->request->get('vat-zones');

		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}
}
