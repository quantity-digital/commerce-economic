<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\helpers\Json;
use QD\commerce\economic\Economic;

class CustomerGroup extends Model
{

	public $customerGroupNumber;

	public function __construct($countryId = null)
	{
		$customerGroupNumber = null;
		if ($countryId) {
			//Get customer group relations
			$relations = Json::decode(Economic::getInstance()->getEconomicSettings()->customerGroups);

			//Key 0 = countryId, Key 1 = customergroup number
			foreach ($relations as $country) {
				if ($country[0] == $countryId) {
					$customerGroupNumber = $country[1];
					break;
				}
			}
		}

		if (!$customerGroupNumber) {
			//Default value is from the plugin settings
			$customerGroupNumber = Economic::getInstance()->getEconomicSettings()->defaultCustomerGroup;
		}

		$this->setCustomerGroupNumber((int) $customerGroupNumber);
	}

	public static function transform($object)
	{
		$customerGroup = new self();
		$customerGroup->setCustomerGroupNumber($object->customerGroupNumber);
		return $customerGroup;
	}

	public function setCustomerGroupNumber(int $value)
	{
		$this->customerGroupNumber = $value;
		return $this;
	}
	public function getCustomerGroupNumber()
	{
		return $this->customerGroupNumber;
	}
}
