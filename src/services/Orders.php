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
use QD\commerce\economic\helpers\Log;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\models\Settings;
use craft\commerce\records\TaxRate;
use verbb\giftvoucher\elements\Voucher;

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
		$taxrate = $this->getTaxRatesForOrder($order);
		$lineVat = 0;
		$shippingVat = null;

		if ($taxrate['taxable'] === TaxRate::TAXABLE_ORDER_TOTAL_SHIPPING && $taxrate['include']) {
			$shippingVat = $taxrate['rate'];
		}

		if ($taxrate['taxable'] === TaxRate::TAXABLE_ORDER_TOTAL_PRICE && $taxrate['include']) {
			$shippingVat = $taxrate['rate'];
			$lineVat = $taxrate['rate'];
		}

		if ($taxrate['taxable'] === TaxRate::TAXABLE_PRICE_SHIPPING && $taxrate['include']) {
			$shippingVat = $taxrate['rate'];
		}

		$orderLines = $order->getLineItems();
		$lines = [];
		$productValue = 0;

		//Add order line items
		foreach ($orderLines as $orderLine) {
			$subtotal = $orderLine->getSubtotal();
			$includedTax = $orderLine->getTaxIncluded();

			$linePrice = ($subtotal - $includedTax);
			$unitPrice = ($linePrice / $orderLine->qty) / ($lineVat + 1);

			//Fallback in case not VAT rule is defined for shipping, asumin same as product VAT
			if ($includedTax && $shippingVat === null) {
				$shippingVat = $includedTax / $linePrice;
			}

			$lines[] = [
				"product" => [
					"productNumber" => $orderLine->purchasable->sku
				],
				"description" => $orderLine->description,
				"quantity" => $orderLine->qty,
				"unitNetPrice" => round($unitPrice, 2),
			];

			//Save total product value
			$productValue += $linePrice;
		}

		//Handle order adjustments
		$adjustments = $order->getAdjustments();
		$shippingRelations = Json::decode(Economic::getInstance()->getEconomicSettings()->shippingProductnumbers);
		$shippingProductNumber = null;
		$discountProductnumber = Economic::getInstance()->getEconomicSettings()->discountProductnumber ?: false;
		$strategy = CommercePlugin::getInstance()->getSettings()->minimumTotalPriceStrategy;
		$totalDiscount = 0;
		$shippingPrice = 0;

		//Get shipping method product number
		foreach ($shippingRelations as $shippingRelation) {
			if ($shippingRelation[0] == $order->getShippingMethod()->id) {
				$shippingProductNumber = $shippingRelation[1];
			}
		}

		//Loop thru each order adjustments
		foreach ($adjustments as $adjustment) {

			if ($adjustment->type === 'voucher' && \class_exists('verbb\giftvoucher\elements\Voucher')) {
				$snapshot = $adjustment->sourceSnapshot;
				$voucher = Voucher::find()->id($snapshot['voucherId']);

				$lines[] = [
					"product" => [
						"productNumber" => $voucher->sku
					],
					"description" => $adjustment->name . ' - ' . $snapshot['codeKey'],
					"quantity" => 1,
					"unitNetPrice" => round($adjustment->amount, 2),
				];
			}

			if ($adjustment->type === 'shipping' && $shippingProductNumber) {
				$unitPrice = $adjustment->amount / ($shippingVat + 1);
				$lines[] = [
					"product" => [
						"productNumber" => $shippingProductNumber
					],
					"description" => 'Shipping - ' . $adjustment->name,
					"quantity" => 1,
					"unitNetPrice" => round($unitPrice, 2),
				];

				$shippingPrice += $unitPrice;
			}

			if ($adjustment->type === 'discount' && $discountProductnumber && !$adjustment->lineItemId) {
				$discount = $adjustment->amount / ($lineVat + 1);

				//If we dont allow negativ order totals, check if discount is greater than remaining total
				if ($strategy === Settings::MINIMUM_TOTAL_PRICE_STRATEGY_ZERO) {
					if ($shippingPrice + $productValue - $totalDiscount < $discount) {
						$discount = $shippingPrice + $productValue - $totalDiscount;
					}
				}

				//If we dont allow for discount on shipping, check if discount is grater than total product value
				if ($strategy === Settings::MINIMUM_TOTAL_PRICE_STRATEGY_SHIPPING) {
					if ($productValue - $totalDiscount < $discount) {
						$discount = $productValue - $totalDiscount;
					}
				}

				$lines[] = [
					"product" => [
						"productNumber" => $discountProductnumber
					],
					"description" => $adjustment->name,
					"quantity" => 1,
					"unitNetPrice" => round($discount, 2),
				];

				//Update total applied discount
				$totalDiscount = $discount;
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
