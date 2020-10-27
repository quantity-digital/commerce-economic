<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\economic\Economic;
use yii\queue\RetryableJobInterface;

class BookInvoice extends BaseJob implements RetryableJobInterface
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
		return 3600;
	}

	public function execute($queue)
	{
		try {
			$order = Order::find()->id($this->orderId)->one();
			$this->setProgress($queue, 0.05);

			if ($order) {
				$response = Economic::getInstance()->getInvoices()->bookInvoiceDraft($order->draftInvoiceNumber);
				$this->setProgress($queue, 0.5);

				if (!$response) {
					$this->reAddToQueue();
				}

				$this->setProgress($queue, 0.90);

				if ($response) {
					$invoice = $response->asObject();
					$order->invoiceNumber = $invoice->bookedInvoiceNumber;
					Craft::$app->getElements()->saveElement($order);
				}

				$this->setProgress($queue, 1);
			}else{
				$this->reAddToQueue();
				$this->setProgress($queue, 1);
			}
		} catch (\Throwable $th) {
			$this->reAddToQueue();
		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Booking invoice in e-conomic';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(300)->push(new BookInvoice(
			[
				'orderId' => $this->orderId,
			]
		));
	}
}
