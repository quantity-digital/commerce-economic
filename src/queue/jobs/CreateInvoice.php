<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\economic\Economic;
use yii\queue\RetryableJobInterface;

class CreateInvoice extends BaseJob
{
	/**
	 * @var int Order ID
	 */
	public $orderId;

	public function canRetry($attempt, $error)
	{
		$attempts = 5;
		return $attempt < $attempts;
	}

	public function getTtr()
	{
		return 300;
	}

	public function execute($queue)
	{
		try {
			$order = Order::find()->id($this->orderId)->one();
			$this->setProgress($queue, 0.05);

			if (!$order) {
				Craft::error('Unable to fetch order with id' . $this->orderId, __METHOD__);
				$this->setProgress($queue, 1);
				return;
			}

			if ($order->invoiceNumber) {
				Craft::error('Order has alreade an invoice number, on order with id ' . $this->orderId, __METHOD__);
				$this->setProgress($queue, 1);
				return;
			}

			$this->setProgress($queue, 0.1);
			$response = Economic::getInstance()->getInvoices()->createFromOrder($order);
			$this->setProgress($queue, 0.5);

			if (!$response) {
				$this->reAddToQueue();
				$this->setProgress($queue, 1);
				return;
			}

			$this->setProgress($queue, 0.90);

			if ($response) {
				$invoice = $response->asObject();
				$this->setProgress($queue, 0.91);
				$order->draftInvoiceNumber = (int) $invoice->draftInvoiceNumber;
				$this->setProgress($queue, 0.92);
				$this->setProgress($queue, 0.93);
				Craft::$app->getElements()->saveElement($order);
				$order->setStatus(Economic::getInstance()->getSettings()->statusIdAfterInvoice);
				$this->setProgress($queue, 0.94);
			}

			$this->setProgress($queue, 0.95);

			if (Economic::getInstance()->getSettings()->autoBookInvoice) {
				Craft::$app->getQueue()->delay(10)->push(new BookInvoice(
					[
						'orderId' => $order->id,
					]
				));
			}

			$this->setProgress($queue, 1);
		} catch (\Throwable $th) {
			$this->reAddToQueue();
			$this->setProgress($queue, 1);
		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Create invoice in e-conomic';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(300)->push(new CreateInvoice(
			[
				'orderId' => $this->orderId,
			]
		));
	}
}
