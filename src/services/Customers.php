<?php

namespace QD\commerce\economic\services;

use craft\base\Component;
use craft\commerce\Plugin as CommercePlugin;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;
use QD\commerce\economic\models\Customer;
use QD\commerce\economic\models\CustomerGroup;
use QD\commerce\economic\models\PaymentTerms;
use QD\commerce\economic\models\VatZone;

class Customers extends Component
{

    public function getCustomerByVatNumber($vatNumber)
    {
        $response = Economic::getInstance()->getApi()->client->request->get('customers?filter=corporateIdentificationNumber$eq:' . $vatNumber);
        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }

    public function getCustomerByEmail($email)
    {
        $response = Economic::getInstance()->getApi()->client->request->get('customers?filter=email$eq:' . $email);

        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }

    public function getCustomerContactByName($customerNumber, $name)
    {
        $response = Economic::getInstance()->getApi()->client->request->get('customers/' . $customerNumber . '/contacts?filter=name$eq:' . \urlencode($name));

        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }

    public function createCustomerContact($customerNumber, $name)
    {
        $data = [
            'name' => $name
        ];

        $response = Economic::getInstance()->getApi()->client->request->post('customers/' . $customerNumber . '/contacts', $data);

        $status = $response->httpStatus();

        if ($status == 201) {
            return $response;
        }

        return false;
    }

    public function createCustomerFromOrder($order)
    {
        $billingAddress = $order->getBillingAddress();

        $customer = new Customer();
        $customer->currency = $order->paymentCurrency;
        $customer->customerGroup = new CustomerGroup();
        $customer->name = ($billingAddress->businessName) ? $billingAddress->businessName : $billingAddress->firstName . ' ' . $billingAddress->lastName;
        $customer->address = $billingAddress->address1;
        $customer->city = $billingAddress->city;
        $customer->zip = $billingAddress->zipCode;
        $customer->country = $billingAddress->country->name;
        $customer->telephoneAndFaxNumber = $billingAddress->phone;
        $customer->paymentTerms = PaymentTerms::transformFromOrder($order);
        $customer->vatZone = VatZone::transformFromOrder($order);
        $customer->corporateIdentificationNumber = ($order->getBillingAddress()->businessTaxId) ?: '';
        $customer->email = $order->email;

        //Removing empty custonerNumber
        unset($customer->customerNumber);

        $response = Economic::getInstance()->getApi()->client->request->post('customers', $customer->asArray());

        $status = $response->httpStatus();

        if ($status == 201) {
            return $response;
        }

        return false;
    }

    public function getAllCustomerGroups()
    {
        $response = Economic::getInstance()->getApi()->client->request->get('customer-groups');

        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }
}
