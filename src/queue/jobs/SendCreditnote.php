<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\Creditnote;
use QD\extensions\Extensions;

class SendCreditnote extends BaseJob
{
	/**
	 * @var int Order ID
	 */
	public $creditnoteId;

	public function getTtr()
	{
		return 300;
	}

	public function execute($queue)
	{
		$creditnote = Creditnote::find()->id($this->creditnoteId)->one();
		$order = $creditnote->order();
		$response = Economic::getInstance()->getInvoices()->getInvoicePdf($creditnote->invoiceNumber);
		$settings = Economic::getInstance()->getEconomicSettings();

		$this->setProgress($queue, 0.25);

		if ($response) {
			$responseData = $response->asRaw();
			$pdf = $responseData[2];

			$data = [
				'order' => $order,
				'invoiceNumber' => $creditnote->invoiceNumber
			];

			$this->setProgress($queue, 0.50);

			$email = Economic::getInstance()->getEmails();
			$this->setProgress($queue, 0.60);

			$email->attatchPdfData($pdf, Craft::t('commerce-economic', 'creditnote') . '-' . $creditnote->invoiceNumber);
			$this->setProgress($queue, 0.65);
			$email->setupMail($settings->creditnoteEmailSubject, $order->email, $data, $settings->creditnoteEmailTemplate);
			$this->setProgress($queue, 0.70);
			$email->send();

			$this->setProgress($queue, 0.75);

			if (!$email) {
				$this->reAddToQue();
			}


			$this->setProgress($queue, 1);
		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Send creditnote to customer';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(300)->push(new SendCreditnote(
			[
				'creditnoteId' => $this->creditnoteId,
			]
		));
	}
}
