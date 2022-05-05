<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;

class CustomerContact extends Model
{

    public $name;
    public $customerNumber;

    public static function transformFromOrder($order)
    {
        //Check for business customer
        $response = Economic::getInstance()->getCustomers()->getCustomerByVatNumber(str_replace(' ', '', $billingAddress->businessTaxId));
);

        if ($response && isset($response->asObject()->collection[0])) {
            $customerData = $response->asObject()->collection[0];
        }

        //Customer not created, do it now

        if (!$customerData) {
            $response = Economic::getInstance()->getCustomers()->createCustomerFromOrder($order);
            $customerData = $response->asObject();
        }

        $customer = self::transform($customerData);
        return $customer;
    }

    public static function transform($object)
    {
        $customerContact = new self();
        $customerContact->customerNumber = $object->customerNumber;
        $customerContact->name = $object->name;

        return $customerContact;
    }

    /** Helpers */
    public function asArray()
    {
        return (array) $this;
    }
}
