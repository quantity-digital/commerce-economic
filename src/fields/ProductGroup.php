<?php

namespace QD\commerce\economic\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fields\BaseOptionsField;
use craft\fields\Dropdown;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\helpers\Html;
use QD\commerce\economic\Economic;

class ProductGroup extends Field
{
    public static function displayName(): string
    {
        return Craft::t('app', 'E-conomic product group');
    }

    protected function inputHtml($value, ElementInterface $element = null): string
    {
        /** @var SingleOptionFieldData $value */
        // if (!$value->valid) {
        //     Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        // }

        $productGroups = [];

        $response = Economic::getInstance()->getApi()->getAllProductGroups();

        if (!$response) {
            return "No response from E-conomic";
        }

        foreach ($response->asObject()->collection as $term) {
            $productGroups[] = [
                'value' => $term->productGroupNumber,
                'label' => $term->name
            ];
        }

        $id = Html::id($this->handle);
        return Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'id' => $id,
            'instructionsId' => "$id-instructions",
            'name' => $this->handle,
            'value' => $value,
            'options' => $productGroups,
        ]);
    }
}
