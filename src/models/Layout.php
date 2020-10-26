<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;

class Layout extends Model
{

	/** @var integer $layoutNumber */
    public $layoutNumber;

	public function __construct()
	{
		//Default value is from the plugin settings
		$this->setLayoutNumber((int) Economic::getInstance()->getSettings()->invoiceLayoutNumber);
	}

	public function setLayoutNumber(Int $value){
		$this->layoutNumber = $value;
		return $this;
	}

}
