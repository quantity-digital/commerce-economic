<?php

namespace QD\commerce\economic\migrations;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use QD\commerce\economic\db\Table;

/**
 * m210630_072437_discountProductNumber migration.
 */
class m211001_072437_creditnotes extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{

		if (!$this->db->columnExists(Table::SETTINGS, 'autoBookCreditnote')) {
			$this->addColumn(
				Table::SETTINGS,
				'autoBookCreditnote',
				$this->boolean()
			);
		}

		if (!$this->db->columnExists(Table::SETTINGS, 'creditnoteLayoutNumber')) {
			$this->addColumn(
				Table::SETTINGS,
				'creditnoteLayoutNumber',
				$this->string()
			);
		}

		if (!$this->db->columnExists(Table::SETTINGS, 'creditnoteEmailTemplate')) {
			$this->addColumn(
				Table::SETTINGS,
				'creditnoteEmailTemplate',
				$this->string()
			);
		}

		if (!$this->db->columnExists(Table::SETTINGS, 'creditnoteEmailSubject')) {
			$this->addColumn(
				Table::SETTINGS,
				'creditnoteEmailSubject',
				$this->string()
			);
		}

		$this->createTable(
			Table::CREDITNOTES,
			[
				'id' => $this->primaryKey(),
				'orderId' => $this->integer(), //Commerce order
				'draftInvoiceNumber' => $this->string(), //E-conomic ID
				'invoiceNumber' => $this->string(), //Crednote number assigned in e-conomic
				'sent' => $this->boolean()->defaultValue(false), //Is it sent to the customer
				'restock' => $this->boolean()->defaultValue(false),
				'isCompleted' => $this->boolean(),
				'dateSent' => $this->dateTime(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]
		);

		$this->createTable(
			Table::CREDITNOTES_ROWS,
			[
				'id' => $this->primaryKey(),
				'creditnoteId' => $this->integer(),
				'lineItemId' => $this->integer(),
				'qty' => $this->integer(),
				'available' => $this->integer(),
				'description' => $this->string(),
				'sku' => $this->string(),
				'price' => $this->decimal(14, 4)->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]
		);

		$this->addForeignKey(null, Table::CREDITNOTES, 'id', '{{%elements}}', 'id', 'CASCADE', null);
		$this->addForeignKey(null, Table::CREDITNOTES, 'orderId', CommerceTable::ORDERS, 'id', null, null);
		$this->addForeignKey(null, Table::CREDITNOTES_ROWS, 'creditnoteId', Table::CREDITNOTES, 'id', null, null);
		$this->addForeignKey(null, Table::CREDITNOTES_ROWS, 'lineItemId', CommerceTable::LINEITEMS, 'id', null, null);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m210630_072437_discountProductnumber cannot be reverted.\n";
		return false;
	}
}
