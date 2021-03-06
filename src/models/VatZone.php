<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\helpers\Json;
use QD\commerce\economic\Economic;

class VatZone extends Model
{

    public $vatZoneNumber;

    public function __construct()
    {
        //Default value is from the plugin settings
        $this->setVatZoneNumber((int) Economic::getInstance()->getEconomicSettings()->defaultVatZoneNumber);
    }

    public static function transformFromOrder($order)
    {
        $vatZone = new self();

        //Key 0 = tax rate id
        //Key 1 = vat zone number
        $orderTaxRate = Economic::getInstance()->getOrders()->getTaxRatesForOrder($order);

        if ($orderTaxRate) {
            $vatRelations = Json::decode(Economic::getInstance()->getEconomicSettings()->vatZones);
            foreach ($vatRelations as $vatRelation) {
                if ($vatRelation[0] == $orderTaxRate['id']) {
                    $vatZone->setVatZoneNumber($vatRelation[1]);
                }
            }
        }

        return $vatZone;
    }

    public static function transform($object)
    {
        $vatZone = new self();
        $vatZone->setVatZoneNumber($object->vatZoneNumber);
        return $vatZone;
    }

    public function setVatZoneNumber(int $value)
    {
        $this->vatZoneNumber = $value;
        return $this;
    }

    public function getVatZoneNumber()
    {
        return $this->vatZoneNumber;
    }
}
