<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use Exception;
use Lenius\Economic\RestClient;
use QD\commerce\economic\Economic;
use QD\commerce\economic\models\Product;

class Api extends Component
{

	public $client;

	// Public Methods
	// =========================================================================

	public function __construct()
	{
		$settings = Economic::getInstance()->getPlugin()->getSettings();
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
		// Exists
		$variantExists = $this->client->request->get('products/' . urlencode($variant->productNumber));

		// If returned status is 200, then the variant exists, therefore we can return true
		if($variantExists->httpStatus() == 200) {
			// Update variant in e-conomic
			$response = $this->client->request->put('products/' . urlencode($variant->productNumber), $variant->asArray());
		}
		else{
			// Create variant in e-conomic
			$response = $this->client->request->post('products', $variant->asArray());
		}

		
		// If returned status is 201, then the variant was created successfully
		$status = $response->httpStatus();
		if ($status == 201 || $status == 200) {
			return true;
		}

		if($status == 400) {
			$object = $response->asObject();
			throw new Exception(json_encode($object->errors, JSON_UNESCAPED_UNICODE), 1);
		}

		return false;
	}
}
