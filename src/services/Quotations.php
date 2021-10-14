<?php

namespace QD\commerce\economic\services;

use craft\base\Component;
use craft\commerce\elements\Order;
use QD\commerce\economic\Economic;
use QD\commerce\economic\models\Customer;
use tkj\Economics\Quotation\Quotation;

class Quotations extends Component
{
	public function createQuotationFromOrder(Order $order)
	{
		$soapApi = Economic::getInstance()->getSoapApi();
		$quotation = new Quotation($soapApi->client);

		$customer = Customer::transformFromOrder($order);

		$new_quotation = $quotation->create($customer->customerNumber, function ($order, $line) {

			$lineItems = Economic::getInstance()->getOrders()->getOrderLines($order);

			foreach ($lineItems as $lineItem) {
				$data = array(
					"product"     => $lineItem['product']['productNumber'],
					"description" => $lineItem['description'],
					"price"       => $lineItem['unitNetPrice'],
					"qty"         => $lineItem['quantity'],
				);
				$line->add($data);
			}
		});

		return $new_quotation;
	}
}
