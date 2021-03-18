<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Variant;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use QD\commerce\economic\Economic;
use QD\commerce\economic\fields\ProductGroup;
use QD\commerce\economic\helpers\Log;
use QD\commerce\economic\models\Product;
use QD\commerce\economic\queue\jobs\SyncVariant;

class Variants extends Component
{
    public function addSyncVariantJob(ModelEvent $event)
    {
        $variant = $event->sender;

        if (ElementHelper::isDraftOrRevision($variant)) {
            return;
        }

        $queuJobs = Craft::$app->getQueue()->getJobInfo();
        foreach ($queuJobs as $job) {
            $details = Craft::$app->getQueue()->getJobDetails($job['id']);
            if (isset($details['job']->variantId) && $details['job']->variantId == $variant->id) {
                return;
            }
        }

        Craft::$app->getQueue()->delay(10)->push(new SyncVariant(
            [
                'variantId' => $variant->id,
            ]
        ));
    }

    public function syncToEconomic(Variant $variant)
    {
        $variant = Product::transformFromVariant($variant);

        $response = Economic::getInstance()->getApi()->syncVariant($variant);

        return $response;
    }
}
