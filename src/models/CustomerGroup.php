<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;

class CustomerGroup extends Model
{

	public $customerGroupNumber;

	public function __construct()
	{
		//Default value is from the plugin settings
		$this->setCustomerGroupNumber((int) Economic::getInstance()->getSettings()->defaultCustomerGroup);
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
