<?php

namespace QD\commerce\economic\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use QD\commerce\economic\db\Table;
use yii\db\ActiveQueryInterface;

class CreditnoteRecord extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return Table::CREDITNOTES;
	}

	/**
	 * @return ActiveQueryInterface
	 */
	public function getElement(): ActiveQueryInterface
	{
		return $this->hasOne(Element::class, ['id', 'id']);
	}
}
