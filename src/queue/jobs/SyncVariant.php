<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Variant;
use craft\queue\BaseJob;
use QD\commerce\economic\Economic;
use QD\commerce\economic\helpers\Log;
use yii\queue\RetryableJobInterface;

class SyncVariant extends BaseJob implements RetryableJobInterface
{
    /**
     * @var int Variant ID
     */
    public $variantId;

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
            $this->setProgress($queue, 0.05, 'Finding variant');
            $variant = Variant::find()->id($this->variantId)->one();

            if (!$variant) {
                $this->setProgress($queue, 1);
                return;
            }

            $this->setProgress($queue, 0.1, 'Syncing to E-conomic');
            $response = Economic::getInstance()->getVariants()->syncToEconomic($variant);

            $this->setProgress($queue, 0.5);

            if (!$response) {
                $this->reAddToQueue();
                $this->setProgress($queue, 1);
                return;
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
        return 'Synchronizing variant to E-conomic';
    }

    protected function reAddToQueue()
    {
        Craft::$app->getQueue()->delay(300)->push(new SyncVariant(
            [
                'variantId' => $this->variantId,
            ]
        ));
    }
}
