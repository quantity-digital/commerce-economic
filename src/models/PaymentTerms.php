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
		$paymentTerms = new self();

		//Key 0 = gateway id
		//Key 1 = paymentterm number
		$gatewayId = $order->gatewayId;

		$gatewayRelations = Economic::getInstance()->getSettings()->gatewayPaymentTerms;
			foreach($gatewayRelations as $gatewayRelation){
				if($gatewayRelations[0] == $gatewayId){
					$paymentTerms->setPaymentTermsNumber($gatewayRelation[1]);
				}
			}

		return $paymentTerms;
	}

	public static function transform($object){
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
