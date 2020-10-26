<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use QD\commerce\economic\Economic;
use QD\commerce\economic\queue\jobs\CreateInvoice;

class Orders extends Component
{

	public function getTaxRatesForOrder($order)
	{
		$adjustments = $order->getAdjustments();
		$taxRates = [];
		foreach ($adjustments as $adjustment) {
			if ($adjustment->type === 'tax') {
				return $adjustment->sourceSnapshot;
			}
		}
	}

	/**
	 * Return all order lineitems as e-conomic array
	 *
	 * @param \craft\commerce\elements\Order $order
	 *
	 * @return array;
	 */
	public function getOrderLines(Order $order)
	{
		$orderLines = $order->getLineItems();
		$lines = [];

		//Add order line items
		foreach ($orderLines as $orderLine) {
			$price = $orderLine->price;
			$discountAmount = 0;

			$adjustments = $orderLine->getAdjustments();

			foreach ($adjustments as $adjustment) {
				if ($adjustment->included && $adjustment->type !== 'tax') {
					continue;
				}

				if (!$adjustment->included && $adjustment->type === 'tax') {
					continue;
				}

				if ($adjustment->included && $adjustment->type === 'tax') {
					$price -= $adjustment->amount;
					continue;
				}

				if ($adjustment->amount < 0) {
					$discountAmount -= $adjustment->amount;
					continue;
				}

				if ($adjustment->amount > 0) {
					$price += $adjustment->amount;
					continue;
				}
			}

			$lines[] = [
				"product" => [
					"productNumber" => $orderLine->purchasable->sku
				],
				"description" => $orderLine->description,
				"quantity" => $orderLine->qty,
				"discountAmount" => $discountAmount,
				"unitNetPrice" => $price,
			];
		}

		//Add shipping line items
		$adjustments = $order->getAdjustments();
		foreach ($adjustments as $adjustment) {
			if ($adjustment->type === 'shipping') {
				$lines[] = [
					"product" => [
						"productNumber" => 'FREIGHT'
					],
					"quantity" => 1,
					"unitNetPrice" => $adjustment->amount,
				];
			}
		}

		return $lines;
	}
}
