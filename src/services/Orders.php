<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\helpers\Json;
use QD\commerce\economic\Economic;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\queue\jobs\CapturePayment;
use craft\commerce\records\Transaction as TransactionRecord;

class Orders extends Component
{
	public function addAutoCaptureJob($event)
	{
		$order = $event->order;
		$orderstatus = $order->getOrderStatus();
		$gateway = $order->getGateway();

		if ($gateway instanceof Ean && $gateway->autoCapture && $gateway->autoCaptureStatus === $orderstatus->handle) {
			$transaction = $this->getSuccessfulTransactionForOrder($order);

			if ($transaction && $transaction->canCapture()) {
				Craft::$app->getQueue()->delay(10)->push(new CapturePayment(
					[
						'transaction' => $transaction,
					]
				));
			}
		}
	}

	public function getSuccessfulTransactionForOrder(Order $order)
	{
		$transactions = $order->getTransactions();
		usort($transactions, array($this, 'dateCompare'));

		foreach ($transactions as $transaction) {

			if (
				$transaction->status === TransactionRecord::STATUS_SUCCESS
				&& $transaction->type === TransactionRecord::TYPE_AUTHORIZE
			) {
				return $transaction;
			}
		}

		return false;
	}

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
			$vatDecimal = 1;

			$adjustments = $orderLine->getAdjustments();

			foreach ($adjustments as $adjustment) {
				if ($adjustment->included && $adjustment->type !== 'tax') {
					continue;
				}

				if (!$adjustment->included && $adjustment->type === 'tax') {
					continue;
				}

				if ($adjustment->included && $adjustment->type === 'tax') {
					$vatDecimal = $adjustment->amount / (($orderLine->price * $orderLine->qty) - $adjustment->amount);
					$price -= ($adjustment->amount / $orderLine->qty);
					continue;
				}

				if ($adjustment->amount < 0) {
					$price -= ($adjustment->amount / $orderLine->qty);
					continue;
				}

				if ($adjustment->amount > 0) {
					$price += ($adjustment->amount / $orderLine->qty);
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
		$shippingRelations = Json::decode(Economic::getInstance()->getSettings()->shippingProductnumbers);
		$shippingProductNumber = null;
		foreach ($shippingRelations as $shippingRelation) {
			if ($shippingRelation[0] == $order->getShippingMethod()->id) {
				$shippingProductNumber = $shippingRelation[1];
			}
		}

		if ($shippingProductNumber) {
			foreach ($adjustments as $adjustment) {
				if ($adjustment->type === 'shipping') {
					$lines[] = [
						"product" => [
							"productNumber" => $shippingProductNumber
						],
						"description" => 'Shipping',
						"quantity" => 1,
						"unitNetPrice" => $adjustment->amount / ($vatDecimal + 1),
					];
				}
			}
		}

		return $lines;
	}

	private static function dateCompare($element1, $element2)
	{
		$datetime1 = date_timestamp_get($element1['dateCreated']);
		$datetime2 = date_timestamp_get($element2['dateCreated']);
		return $datetime2 - $datetime1;
	}
}
