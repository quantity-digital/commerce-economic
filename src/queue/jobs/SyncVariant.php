<?php

namespace QD\commerce\economic\queue\jobs;

use Craft;
use craft\commerce\elements\Variant;
use craft\queue\BaseJob;
use Exception;
use QD\commerce\economic\Economic;

class SyncVariant extends BaseJob
{
    /**
     * @var int Variant ID
     */
    public $variantId;

    public function getTtr()
    {
        return 300;
    }

    public function execute($queue)
    {
        try {
            // Find the variant
            $this->setProgress($queue, 0.05, 'Finding variant');
            $variant = Variant::find()->id($this->variantId)->one();

            if (!$variant) {
                return;
            }

            // Sync variant to economic
            $this->setProgress($queue, 0.1, 'Syncing to E-conomic');
            $response = Economic::getInstance()->getVariants()->syncToEconomic($variant);

            // If variant add failed, throw error
            if (!$response) {
                throw new Exception("Failed to add variant", 1);
            }
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage() ?? '', 1);
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
        Craft::$app->getQueue()->push(new SyncVariant(
            [
                'variantId' => $this->variantId,
            ]
        ));
    }
}
