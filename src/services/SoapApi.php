<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use QD\commerce\economic\Economic;
use Tkj\Economics\TokenClient;

class SoapApi extends Component
{
	public $client;

	public function __construct()
	{
		$settings = Economic::getInstance()->getPlugin()->getSettings();
		$grantToken = Craft::parseEnv($settings->grantToken);
		$secretToken = Craft::parseEnv($settings->secretToken);

		$this->client = new TokenClient($secretToken, $grantToken, 'Quanity Digital Craft E-conomic integration', $options = []);
	}
}
