<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote;
use QD\commerce\economic\helpers\Log;
use yii\queue\RetryableJobInterface;

class CreateCreditnote extends BaseJob
{
	/**
	 * @var int Order ID
	 */
	public $creditnoteId;

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
			$creditnote = Creditnote::find()->id($this->creditnoteId)->one();
			$this->setProgress($queue, 0.05);

			if (!$creditnote) {
				Log::error('Unable to fetch creditnote with id' . $this->creditnoteId);
				$this->setProgress($queue, 1);
				return;
			}


			if ($creditnote->invoiceNumber) {
				Log::error('Creditnote has alreade an invoice number, on order with id ' . $this->creditnoteId);
				$this->setProgress($queue, 1);
				return;
			}


			$this->setProgress($queue, 0.1);
			$response = Economic::getInstance()->getCreditnotes()->createCreditnoteDraft($creditnote);
			$this->setProgress($queue, 0.5);

			if (!$response) {
				Log::error('Create request failed on creditnote with id ' . $this->creditnoteId);
				$lines = Economic::getInstance()->getCreditnoteRows()->getAllRowsByCreditnoteId($this->creditnoteId);
				foreach ($lines as $line) {
					Craft::$app->getQueue()->delay(10)->push(new SyncVariant(
						[
							'variantId' => $line->sku,
						]
					));
				}
				$this->reAddToQueue();
				$this->setProgress($queue, 1);
				return;
			}

			$this->setProgress($queue, 0.90);

			if ($response) {
				$invoice = $response->asObject();
				$this->setProgress($queue, 0.91);
				$creditnote->draftInvoiceNumber = (int) $invoice->draftInvoiceNumber;
				$this->setProgress($queue, 0.92);
				Craft::$app->getElements()->saveElement($creditnote);
			}

			$this->setProgress($queue, 0.95);

			if (Economic::getInstance()->getEconomicSettings()->autoBookCreditnote) {
				Craft::$app->getQueue()->delay(10)->push(new BookCreditnote(
					[
						'creditnoteId' => $creditnote->id,
					]
				));
			}

			$this->setProgress($queue, 1);
		} catch (\Throwable $th) {
			$this->reAddToQueue();
			Log::error('Queue failed with an error');
			Log::info(\print_r($th, true));
			$this->setProgress($queue, 1);
		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Create creditnote in e-conomic';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(3600)->push(new CreateCreditnote(
			[
				'creditnoteId' => $this->creditnoteId,
			]
		));
	}
}
