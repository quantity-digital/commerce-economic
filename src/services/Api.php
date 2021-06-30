<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use Lenius\Economic\RestClient;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;
use QD\commerce\economic\models\Product;

class Api extends Component
{

	public $client;

	// Public Methods
	// =========================================================================

	public function __construct()
	{
		$settings = Economic::getInstance()->getPlugin()->getEconomicSettings();
		$grantToken = Craft::parseEnv($settings->grantToken);
		$secretToken = Craft::parseEnv($settings->secretToken);
		$this->client = new RestClient($secretToken, $grantToken);
	}


	//Move out into own seperate services
	public function getAllProductGroups()
	{
		$response = $this->client->request->get('product-groups');

		$status = $response->httpStatus();

		if ($status == 200) {
			return $response;
		}

		return false;
	}

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

	public function syncVariant(Product $variant)
	{
		$testResponse = $this->client->request->get('products/' . urlencode($variant->productNumber));

		if ($testResponse->httpStatus() == 200) {
			$response = $this->client->request->put('products/' . urlencode($variant->productNumber), $variant->asArray());
		} else {
			$response = $this->client->request->post('products', $variant->asArray());
		}

		$status = $response->httpStatus();
		if ($status == 201 || $status == 200) {
			return $response;
		}
	}
}
