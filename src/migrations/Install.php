<?php

namespace QD\commerce\economic\migrations;

use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use craft\helpers\MigrationHelper;
use QD\commerce\economic\db\Table;

class Install extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp(): bool
	{

		$this->createTables();
		$this->createIndexes();
		$this->addForeignKeys();

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown(): bool
	{
		$this->dropForeignKeys();
		$this->dropTables();
		return true;
	}

	// Protected Methods
	// =========================================================================

	protected function createTables()
	{
		$this->createTable(Table::ORDERINFO, [
			'id' => $this->integer()->notNull(),
			'invoiceNumber' => $this->integer()->null(),
			'draftInvoiceNumber' => $this->integer()->null(),
			'eanNumber' => $this->string()->null(),
			'eanReference' => $this->string()->null(),
			'eanContact' => $this->string()->null(),
			'PRIMARY KEY([[id]])',
		]);

		$this->createTable(Table::SETTINGS, [
			'id' => $this->integer()->notNull(),
			'secretToken' => $this->string()->null(),
			'grantToken' => $this->string()->null(),

			//Defaults
			'defaultpaymentTermsNumber' => $this->integer()->null(),
			'defaultLayoutNumber' => $this->string()->null(),
			'defaultCustomerGroup' => $this->integer()->null(),
			'defaultVatZoneNumber' => $this->integer()->null(),
			'defaultProductgroup' => $this->integer()->null(),

			//Invoice settings
			'invoiceEnabled' => $this->boolean()->null(),
			'onlyB2b' => $this->boolean()->null(),
			'statusIdAfterInvoice' => $this->string()->null(),
			'invoiceOnStatusId' => $this->string()->null(),
			'autoBookInvoice' => $this->boolean(),
			'invoiceLayoutNumber' => $this->string()->null(),


			'autoBookCreditnote' => $this->boolean(),
			'creditnoteLayoutNumber' => $this->string()->null(),
			'creditnoteEmailTemplate' => $this->string()->null(),
			'creditnoteEmailSubject' => $this->string()->null(),

			//Quotations settings
			'quotationsEnabled' => $this->boolean(),
			'quoteOnStatusId' => $this->string(),
			'statusIdAfterQuotation' => $this->string(),

			//Orders settings
			'ordersEnabled' => $this->boolean(),
			'orderOnStatusId' => $this->string(),
			'statusIdAfterOrder' => $this->string(),

			//Relations
			'gatewayPaymentTerms' => $this->json()->null(),
			'shippingProductnumbers' => $this->json()->null(),
			'discountProductnumber' => $this->string()->null(),
			'vatZones' => $this->json()->null(),
			'syncVariants' => $this->boolean()->null(),
			'convertAmount' => $this->boolean()->null(),

			'uid' => $this->uid(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'PRIMARY KEY([[id]])',
		]);

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
	}

	protected function createIndexes()
	{
		$this->createIndex(null, Table::ORDERINFO, 'invoiceNumber');
	}

	protected function addForeignKeys()
	{
		$this->addForeignKey(null, Table::ORDERINFO, ['id'], CommerceTable::ORDERS, ['id'], 'CASCADE', 'CASCADE');

		$this->addForeignKey(null, Table::CREDITNOTES, 'id', '{{%elements}}', 'id');
		$this->addForeignKey(null, Table::CREDITNOTES, 'orderId', CommerceTable::ORDERS, 'id',);
		$this->addForeignKey(null, Table::CREDITNOTES_ROWS, 'creditnoteId', Table::CREDITNOTES, 'id');
		$this->addForeignKey(null, Table::CREDITNOTES_ROWS, 'lineItemId', CommerceTable::LINEITEMS, ['id'], null, null);
	}

	protected function dropForeignKeys()
	{
		if ($this->db->tableExists(Table::ORDERINFO)) {
			MigrationHelper::dropAllForeignKeysOnTable(Table::ORDERINFO, $this);
			MigrationHelper::dropAllForeignKeysOnTable(Table::CREDITNOTES, $this);
			MigrationHelper::dropAllForeignKeysOnTable(Table::CREDITNOTES_ROWS, $this);
		}
	}

	protected function dropTables()
	{
		$this->dropTableIfExists(Table::ORDERINFO);
		$this->dropTableIfExists(Table::SETTINGS);
		$this->dropTableIfExists(Table::CREDITNOTES);
		$this->dropTableIfExists(Table::CREDITNOTES_ROWS);
	}
}
