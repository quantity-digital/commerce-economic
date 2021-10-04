<?php

namespace QD\commerce\economic\records;

use craft\commerce\models\LineItem as ModelsLineItem;
use craft\commerce\records\LineItem;
use craft\db\ActiveRecord;
use QD\commerce\economic\db\Table;

class CreditnoteRowRecord extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return Table::CREDITNOTES_ROWS;
	}

	public function subTotal()
	{
		return $this->qty * $this->price;
	}

	public function total()
	{
		return $this->qty * $this->price;
	}

	public function getLineItem()
	{
		if (!$this->lineItemId) {
			return false;
		}

		return LineItem::findOne($this->lineItemId);
	}
}
