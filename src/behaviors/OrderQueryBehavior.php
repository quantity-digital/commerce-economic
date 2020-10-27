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
			ElementQuery::EVENT_BEFORE_PREPARE => [$this, 'beforePrepare'],
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
	public function beforePrepare()
	{
		if ($this->owner->select === ['COUNT(*)']) {
			return;
		}

		// Join our `orderextras` table:
		$this->owner->query->leftJoin('economic_orders economic', '`economic`.`id` = `commerce_orders`.`id`');
		$this->owner->subQuery->leftJoin('economic_orders economic', '`economic`.`id` = `commerce_orders`.`id`');

		// Select custom columns:
		$this->owner->query->addSelect([
			'economic.invoiceNumber',
			'economic.draftInvoiceNumber',
		]);

		if (!is_null($this->invoiceNumber)) {
			$this->owner->subQuery->andWhere(Db::parseParam('economic.invoiceNumber', $this->invoiceNumber));
		}

		if (!is_null($this->draftInvoiceNumber)) {
			$this->owner->subQuery->andWhere(Db::parseParam('economic.draftInvoiceNumber', $this->draftInvoiceNumber));
		}

	}
}
