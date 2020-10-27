<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\commerce\elements\Order;
use QD\commerce\economic\Economic;

class Recipient extends Model
{
	/** @var string $name */
    public $name;

	/** @var string $address */
    public $address;

	/** @var string $zip */
    public $zip;

	/** @var string $city */
    public $city;

    /** @var VatZone $vatZone */
    public $vatZone;

	public static function transformFromOrder(Order $order){
		$address = $order->getShippingAddress();
		$recipent = new self();
		$recipent->setName(($address->businessName) ? $address->businessName : $address->firstName . ' ' . $address->lastName);
		$recipent->setAddress($address->address1 . ' ' . $address->address2 . ' ' . $address->address3);
		$recipent->setZip($address->zipCode);
		$recipent->setCity($address->city);
		$recipent->setVatZone(VatZone::transformFromOrder($order));

		return $recipent;
	}

	public function setName($value){
		$this->name = $value;
		return $this;
	}

	public function getName(){
		return $this->name;
	}

	public function setAddress($value){
		$this->address = $value;
		return $this;
	}

	public function getAddress(){
		return $this->address;
	}

	public function setZip($value){
		$this->zip = $value;
		return $this;
	}

	public function getZip($value){
		return $this->zip;
	}

	public function setCity($value){
		$this->city = $value;
		return $this;
	}

	public function getCity($value){
		return $this->city;
	}

	public function setVatZone(VatZone $value){
		$this->vatZone = $value;
		return $this;
	}

	public function getVatZone(){
		return $this->vatZone;
	}
}
