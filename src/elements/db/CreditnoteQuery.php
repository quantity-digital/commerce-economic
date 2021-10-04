<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace QD\commerce\economic\elements\db;

use craft\elements\db\ElementQuery;
use QD\commerce\economic\db\Table;

class CreditnoteQuery extends ElementQuery
{
	public $isCompleted = [true, false];
	public $orderId;

	public function isCompleted($value)
	{
		$this->isCompleted = $value;
		return $this->owner;
	}

	public function orderId($value)
	{
		$this->orderId = $value;
		return $this->owner;
	}

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare(): bool
	{
		$this->joinElementTable('economic_creditnotes');

		$this->query->select([
			'economic_creditnotes.orderId',
			'economic_creditnotes.isCompleted',
			'economic_creditnotes.sent',
			'economic_creditnotes.invoiceNumber',
			'economic_creditnotes.draftInvoiceNumber',
		]);

		$this->subQuery->where(['isCompleted' => $this->isCompleted]);

		if ($this->orderId) {
			$this->subQuery->andWhere(['orderId' => $this->orderId]);
		}

		return parent::beforePrepare();
	}
}
