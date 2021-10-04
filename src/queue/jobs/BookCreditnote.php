<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\queue\BaseJob;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\helpers\Log;
use yii\queue\RetryableJobInterface;

class BookCreditnote extends BaseJob implements RetryableJobInterface
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
		return 3600;
	}

	public function execute($queue)
	{
		try {
			$creditnote = Creditnote::find()->id($this->creditnoteId)->one();
			$this->setProgress($queue, 0.05);

			if ($creditnote) {
				$sendViaEan = false;

				if (Ean::class === get_class($creditnote->order()->getGateway())) {
					$sendViaEan = true;
				}

				$response = Economic::getInstance()->getCreditnotes()->bookCreditnoteDraft($creditnote->draftInvoiceNumber, $sendViaEan);
				$this->setProgress($queue, 0.5);

				if (!$response) {
					$this->reAddToQueue();
				}

				$this->setProgress($queue, 0.90);

				if ($response) {
					$invoice = $response->asObject();
					$creditnote->invoiceNumber = $invoice->bookedInvoiceNumber;
					Craft::$app->getElements()->saveElement($creditnote);


					if (!$sendViaEan) {
						$settings = Economic::getInstance()->getEconomicSettings();

						if ($settings->creditnoteEmailTemplate) {
							Craft::$app->getQueue()->push(new SendCreditnote([
								'creditnoteId' => $this->creditnoteId
							]));
						}
					}
				}

				$this->setProgress($queue, 1);
			} else {
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
		return 'Booking creditnote in e-conomic';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(3600)->push(new BookCreditnote(
			[
				'creditnoteId' => $this->creditnoteId,
			]
		));
	}
}
