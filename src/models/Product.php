<?php

namespace QD\commerce\economic\models;

use craft\base\Model;
use craft\commerce\elements\Variant;
use QD\commerce\economic\Economic;
use QD\commerce\economic\fields\ProductGroup;

class Product extends Model
{
    /** @var string $productNumber */
    public $productNumber;

    /** @var string $name */
    public $name;

    /** @var int $salesPrice */
    public $salesPrice;

    /** @var string $name */
    public $productGroup;


    public static function transformFromVariant(Variant $variant)
    {
        $productGroupNumber = null;
        foreach ($variant->getFieldLayout()->getFields() as $field) {
            if (ProductGroup::class === get_class($field)) {
                $productGroupNumber = $variant->getFieldValue($field->handle);
            }
        }

        if (!$productGroupNumber) {
            foreach ($variant->product->getFieldLayout()->getFields() as $field) {
                if (ProductGroup::class === get_class($field)) {
                    $productGroupNumber = $variant->product->getFieldValue($field->handle);
                }
            }
        }

        if (!$productGroupNumber) {
            $productGroupNumber = Economic::getInstance()->getSettings()->defaultProductgroup;
        }

        $product = new self();
        $product->setName($variant->title);
        $product->setProductNumber($variant->SKU);
        $product->setSalesPrice($variant->price);
        $product->setProductGroup([
            'productGroupNumber' => (int) $productGroupNumber
        ]);

        return $product;
    }

    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setProductNumber($value)
    {
        $this->productNumber = $value;
        return $this;
    }

    public function getProductNumber()
    {
        return $this->productNumber;
    }

    public function setSalesPrice($value)
    {
        $this->salesPrice = (int) $value;
        return $this;
    }

    public function getSalesPrice()
    {
        return $this->salesPrice;
    }

    public function setProductGroup($value)
    {
        $this->productGroup = $value;
        return $this;
    }

    public function getProductGroup()
    {
        return $this->productGroup;
    }

    public function asArray()
    {
        return (array) $this;
    }
}
