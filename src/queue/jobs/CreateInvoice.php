<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use Exception;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;

class CreateInvoice extends BaseJob
{
    /**
     * @var int Order ID
     */
    public $orderId;
    public $retried = false;

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
                Log::error('Unable to fetch order with id' . $this->orderId);
                return;
            }


            if ($order->invoiceNumber) {
                Log::error('Order has already an invoice number, on order with id ' . $this->orderId);
                return;
            }


            $this->setProgress($queue, 0.1);
            $response = Economic::getInstance()->getInvoices()->createFromOrder($order);
            $this->setProgress($queue, 0.5);

            // If we get no response, we just retry the queue
            if (!$response) {
                $this->reAddToQueue(false, 300);
                return;
            }

            $this->setProgress($queue, 0.90);

            if ($response) {
                $invoice = $response->asObject();
                $this->setProgress($queue, 0.91);
                $order->draftInvoiceNumber = (int) $invoice->draftInvoiceNumber;
                $this->setProgress($queue, 0.92);
                Craft::$app->getElements()->saveElement($order);
                $order->setStatus(Economic::getInstance()->getEconomicSettings()->statusIdAfterInvoice);
                $this->setProgress($queue, 0.94);
            }

            $this->setProgress($queue, 0.95);

            if (Economic::getInstance()->getEconomicSettings()->autoBookInvoice) {
                Craft::$app->getQueue()->delay(10)->push(new BookInvoice(
                    [
                        'orderId' => $order->id,
                    ]
                ));
            }

            $this->setProgress($queue, 1);
        } catch (\Throwable $th) {

            // If we get thrown an error, try syncing variant and retrying
            if(!$this->retried)
            {
                $lines = $order->getLineItems();
                foreach ($lines as $line) {
                    Craft::$app->getQueue()->push(new SyncVariant(
                        [
                            'variantId' => $line->purchasableId,
                        ]
                    ));
                }

                // Readd the queue, if we haved retried with the variants added
                $this->reAddToQueue(true, 3600);
                return;
            }

            throw new Exception($th->getMessage() ?? 'Failed to sync invoice', 1);
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return 'Create invoice in e-conomic';
    }

    protected function reAddToQueue($retried = false, $delay = 0)
    {
        Craft::$app->getQueue()->delay($delay)->push(new CreateInvoice(
            [
                'orderId' => $this->orderId,
                'retried' => $retried,
            ]
        ));
    }
}
