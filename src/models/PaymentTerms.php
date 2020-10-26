<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;

class PaymentTerms extends Model
{

	public $paymentTermsNumber;

	public function __construct()
	{
		//Default value is from the plugin settings
		$this->setPaymentTermsNumber((int) Economic::getInstance()->getSettings()->defaultpaymentTermsNumber);
	}

	public static function transformFromOrder($order){
		$vatZone = new self();

		//Key 0 = gateway id
		//Key 1 = paymentterm number
		$gatewayId = $order->gatewayId;

		$gatewayRelations = Economic::getInstance()->getSettings()->gatewayPaymentTerms;
			foreach($gatewayRelations as $gatewayRelation){
				if($gatewayRelations[0] == $gatewayId){
					$vatZone->setPaymentTermsNumber($gatewayRelation[1]);
				}
			}

		return $vatZone;
	}

	public function setPaymentTermsNumber(int $value)
	{
		$this->paymentTermsNumber = $value;
		return $this;
	}
}
