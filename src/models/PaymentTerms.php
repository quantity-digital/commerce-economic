<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\helpers\Json;
use QD\commerce\economic\Economic;

class PaymentTerms extends Model
{

	public $paymentTermsNumber;

	public function __construct()
	{
		//Default value is from the plugin settings
		$this->setPaymentTermsNumber((int) Economic::getInstance()->getEconomicSettings()->defaultpaymentTermsNumber);
	}

	public static function transformFromOrder($order)
	{
		$paymentTerms = new self();

		//Key 0 = gateway id
		//Key 1 = paymentterm number
		//Key 2 = layout id
		$gatewayId = $order->gatewayId;

		$gatewayRelations = Json::decode(Economic::getInstance()->getEconomicSettings()->gatewayPaymentTerms);

		foreach ($gatewayRelations as $gatewayRelation) {
			if ($gatewayRelation[0] == $gatewayId) {
				$paymentTerms->setPaymentTermsNumber($gatewayRelation[1]);
			}
		}

		return $paymentTerms;
	}

	public static function transform($object)
	{
		$paymentTerms = new self();
		$paymentTerms->setPaymentTermsNumber($object->paymentTermsNumber);
		return $paymentTerms;
	}

	public function setPaymentTermsNumber(int $value)
	{
		$this->paymentTermsNumber = $value;
		return $this;
	}

	public function getPaymentTermsNumber(int $value)
	{
		return $this->paymentTermsNumber;
	}
}
