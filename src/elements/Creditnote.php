<?php

namespace QD\commerce\economic\elements;

use Craft;
use craft\base\Element;
use craft\commerce\elements\Order;
use craft\commerce\helpers\Currency;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use QD\commerce\economic\Economic;
use QD\commerce\economic\elements\db\CreditnoteQuery;
use QD\commerce\economic\records\CreditnoteRecord;
use yii\base\Exception;

class Creditnote extends Element
{
	/**
	 * Credit note
	 *
	 * @property-read string $isCompletedHtml
	 */

	/**
	 * Order ID in Craft Commerce
	 *
	 * @var int
	 */
	public $orderId;

	/**
	 * The sequential invoice ID
	 *
	 * @var int
	 */
	public $draftInvoiceNumber;

	/**
	 * @var int Invoice Number
	 */
	public $invoiceNumber;

	/**
	 * Was the invoice sent
	 * @var bool
	 */
	public $sent;

	/**
	 * Decides if the stock should be reset
	 *
	 * @var bool
	 */
	public $restock;

	public $isCompleted;

	/**
	 * @var array
	 */
	private $_rows;
	private $_total;




	/**
	 * @inheritdoc
	 */
	public static function displayName(): string
	{
		return 'Credinote';
	}

	/**
	 * @inheritdoc
	 */
	public static function pluralDisplayName(): string
	{
		return 'Creditnotes';
	}

	public static function hasStatuses(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public static function refHandle()
	{
		return 'creditnote';
	}

	/**
	 * @inheritdoc
	 */
	public static function trackChanges(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getIsEditable(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function __toString()
	{
		return strval($this->id);
	}

	public static function find(): ElementQueryInterface
	{
		return new CreditnoteQuery(static::class);
	}

	/**
	 * @param bool $isNew
	 * @throws Exception
	 */
	public function afterSave(bool $isNew)
	{
		if (!$isNew) {
			$record = CreditnoteRecord::findOne($this->id);

			if (!$record) {
				$record = new CreditnoteRecord();
				$record->id = $this->id;
			}
		} else {
			$record = new CreditnoteRecord();
			$record->id = $this->id;
		}

		$record->orderId = $this->orderId;
		$record->draftInvoiceNumber = $this->draftInvoiceNumber;
		$record->invoiceNumber = $this->invoiceNumber;
		$record->sent = $this->sent;
		$record->isCompleted = $this->isCompleted;
		$record->restock = $this->restock;

		$record->save(false);

		return parent::afterSave($isNew);
	}

	/**
	 * @inheritdoc
	 */
	protected static function defineActions(string $source = null): array
	{
		// Now figure out what we can do with it
		$actions = [];
		$elementsService = Craft::$app->getElements();

		// Delete
		$actions[] = Delete::class;

		return $actions;
	}
	/**
	 * @inheritdoc
	 */
	protected static function defineSources(string $context = null): array
	{
		$sources = [
			'*' => [
				'key' => '*',
				'label' => Craft::t('commerce', 'All creditnotes'),
				'defaultSort' => ['dateCrated', 'desc'],
			],
			[
				'key' => 'editable',
				'label' => Craft::t('app', 'Editable'),
				'criteria' => ['isCompleted' => false],
				'defaultSort' => ['dateCrated', 'desc'],
			],
			[
				'key' => 'completed',
				'label' => Craft::t('app', 'Completed	'),
				'criteria' => ['isCompleted' => true],
				'defaultSort' => ['dateCrated', 'desc'],
			],
		];

		return $sources;
	}


	/**
	 * Protected methods
	 */

	/**
	 * Generate edit url for the element index
	 *
	 * @return string|null
	 */
	protected function cpEditUrl(): ?string
	{
		$path = "commerce/creditnotes/edit/" . $this->id;

		return UrlHelper::cpUrl($path);
	}

	/**
	 * Define all available attributes for the element index
	 *
	 * @return array
	 */
	protected static function defineTableAttributes(): array
	{
		return [
			'id' => ['label' => Craft::t('site', 'Credit note')],
			'orderId' => ['label' => Craft::t('site', 'Order ID')],
			'invoiceNumber' => ['label' => Craft::t('site', 'E-conomic invoice')],
			'sent' => ['label' => Craft::t('site', 'Is sent')],
			'total' => 'Total',
			'dateCreated' => 'Credit date',
			'isCompletedHtml' => 'Is completed'
		];
	}


	/**
	 * Which attributes should be available by default in the view
	 *
	 * @param string $source
	 *
	 * @return array
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return [
			'id',
			'orderId',
			'invoiceNumber',
			'dateCreated',
			'isCompletedHtml',
			'total'
		];
	}

	/**
	 * Returns options which can be sorted by
	 *
	 * @return array
	 */
	protected static function defineSortOptions(): array
	{
		return [
			'id' => \Craft::t('site', 'Creditnote ID'),
			'orderId' => \Craft::t('site', 'Order ID'),
			'dateCreated' => 'Credit date'
		];
	}

	protected static function defineSearchableAttributes(): array
	{
		return ['orderId', 'invoiceNumber'];
	}



	public function getPdfUrl()
	{
		return UrlHelper::siteUrl('/commerce-invoices/download/' . $this->uid);
	}

	/**
	 * @return LineItem[]
	 */
	public function getRows(): array
	{
		if ($this->_rows === null) {
			$rows = $this->id ? Economic::getInstance()->getCreditnoteRows()->getAllRowsByCreditnoteId($this->id) : [];

			$this->_rows = $rows;
		}

		return $this->_rows;
	}

	public function getTotal()
	{
		foreach ($this->getRows() as $row) {
			$this->_total += $row->total();
		}
		return Currency::formatAsCurrency($this->_total, $this->order()->paymentCurrency);
	}

	public function order(): Order
	{
		return Order::findOne($this->orderId);
	}

	/**
	 * @return string
	 */
	public function getIsCompletedHtml(): string
	{
		$status = 'No';

		if ($this->isCompleted) {
			$status = 'Yes';
		}

		// return '<span class="commerceStatusLabel"><span class="status ' . $status . '"></span></span>';
		return $status;
	}
}
