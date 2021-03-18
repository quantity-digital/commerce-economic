<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\commerce\elements\Order;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;

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

    public $ean;

    public $nemHandelType = 'ean';

    public static function transformFromOrder(Order $order)
    {
        $address = $order->getShippingAddress();
        $recipent = new self();
        $recipent->name = ($address->businessName) ? $address->businessName : $address->firstName . ' ' . $address->lastName;
        $recipent->address = $address->address1 . ' ' . $address->address2 . ' ' . $address->address3;
        $recipent->zip = $address->zipCode;
        $recipent->city = $address->city;
        $recipent->vatZone = VatZone::transformFromOrder($order);

        if ($order->eanNumber && $order->eanNumber !== 'null') {
            $recipent->ean = $order->eanNumber;
        } else {
            unset($recipent->ean);
        }

        return $recipent;
    }
}
