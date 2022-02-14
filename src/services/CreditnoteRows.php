<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\helpers\Json;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote;
use QD\commerce\economic\records\CreditnoteRowRecord;

class CreditnoteRows extends Component
{
	public function getAllRowsByCreditnoteId(int $creditnoteId)
	{
		return CreditnoteRowRecord::find()->where(['creditnoteId' => $creditnoteId])->all();
	}

	public function getOrderLines($creditnote)
	{
		$creditRows = $this->getAllRowsByCreditnoteId($creditnote->id);
		$lines = [];


		$adjustments = $creditnote->order()->getAdjustments();

		$vatDecimal = 0;
		foreach ($creditRows as $row) {

			//If no quantity has been set, skip line for the creditnote
			if (!$row->qty || $row->qty < 1) {
				continue;
			}

			if ($row->lineItemId) {

				$lineItem = $row->getLineItem();

				$lineItemAdjustments = [];
				foreach ($adjustments as $adjustment) {
					if ($adjustment->lineItemId == $row->lineItemId) {
						$lineItemAdjustments[] = $adjustment;
					}
				}

				foreach ($lineItemAdjustments as $adjustment) {

					if (!$adjustment->included && $adjustment->type === 'tax') {
						continue;
					}

					if ($adjustment->included && $adjustment->type === 'tax') {
						$vatDecimal = round($adjustment->amount / ($lineItem->total - $adjustment->amount), 2);
						continue;
					}
				}
			}

			$lines[] = [
				"product" => [
					"productNumber" => $row->sku
				],
				"description" => $row->description,
				"quantity" => -$row->qty,
				"unitNetPrice" => round((float)($row->price / ($vatDecimal + 1)), 2),
			];
		}

		return $lines;
	}

	public function createFromOrder(Order $order, Creditnote $creditnote): bool
	{
		CreditnoteRowRecord::deleteAll("creditnoteId = {$creditnote->id}");

		foreach ($order->lineItems as $lineItem) {
			$this->createFromLineItem($lineItem, $creditnote);
		}

		// Process shipping
		$this->createFromShipping($order, $creditnote);

		return true;
	}

	public function createFromLineItem(LineItem $lineItem, Creditnote $creditnote)
	{
		$returnedQty = 0;
		$completedCreditNotes = Creditnote::find()->orderId($creditnote->orderId)->ids();
		$returnedQty = CreditnoteRowRecord::find()->where(['lineItemId' => $lineItem->id, 'creditnoteId' => $completedCreditNotes])->sum('qty') ?: 0;

		$row = new CreditnoteRowRecord();
		$row->lineItemId = $lineItem->id;
		$row->creditnoteId = $creditnote->id;
		$row->description = $lineItem->description;
		$row->available = $lineItem->qty - $returnedQty;
		$row->qty = 0;
		$row->sku = $lineItem->snapshot['sku'];

		$row->price = $lineItem->getTotal() / $lineItem->qty;

		return $row->save();
	}

	public function createFromShipping(Order $order, Creditnote $creditnote)
	{
		if (($shipping = $order->getTotalShippingCost()) == 0) {
			return false;
		}

		$row = new CreditnoteRowRecord();
		$row->creditnoteId = $creditnote->id;
		$row->qty = 0;
		$row->available = 1;
		$row->description = sprintf('Shipping costs');
		$row->price = $shipping;

		$shippingRelations = Json::decode(Economic::getInstance()->getEconomicSettings()->shippingProductnumbers);
		$shippingProductNumber = 0;

		foreach ($shippingRelations as $shippingRelation) {
			if ($shippingRelation[0] == $order->getShippingMethod()->id) {
				$shippingProductNumber = $shippingRelation[1];
			}
		}

		$row->sku = $shippingProductNumber;

		return $row->save();
	}
}
