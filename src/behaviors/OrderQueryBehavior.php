<?php

namespace QD\commerce\economic\behaviors;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\base\Behavior;

class OrderQueryBehavior extends Behavior
{
	/**
	 * @var mixed Value
	 */
	public $invoiceNumber;

	/**
	 * @var mixed Value
	 */
	public $draftInvoiceNumber;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ElementQuery::EVENT_AFTER_PREPARE => [$this, 'afterPrepare'],
		];
	}

	/**
	 * Applies the `economicInvoiceId param to the query. Accepts anything that can eventually be passed to `Db::parseParam(…)`.
	 *
	 * @param mixed $value
	 */
	public function invoiceNumber($value)
	{
		$this->invoiceNumber = $value;
		return $this->owner;
	}

	/**
	 * Applies the `economicInvoiceId param to the query. Accepts anything that can eventually be passed to `Db::parseParam(…)`.
	 *
	 * @param mixed $value
	 */
	public function draftInvoiceNumber($value)
	{
		$this->draftInvoiceNumber = $value;
		return $this->owner;
	}

	/**
	 * Prepares the user query.
	 */
	public function afterPrepare()
	{
		if ($this->owner->select === ['COUNT(*)']) {
			return;
		}

		// Join our `orderextras` table:
		$this->owner->query->leftJoin('economic_orders economic', '`economic`.`id` = `elements`.`id`');

		// Select custom columns:
		$this->owner->query->addSelect([
			'economic.invoiceNumber',
			'economic.draftInvoiceNumber',
		]);

		if ($this->invoiceNumber) {
			$this->owner->query->andWhere(Db::parseParam('economic.invoiceNumber', $this->invoiceNumber));
		}

		if ($this->draftInvoiceNumber) {
			$this->owner->query->andWhere(Db::parseParam('economic.draftInvoiceNumber', $this->draftInvoiceNumber));
		}

	}
}
