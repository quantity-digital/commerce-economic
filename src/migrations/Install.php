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
	}

	protected function createIndexes()
	{
		$this->createIndex(null, Table::ORDERINFO, 'invoiceNumber');
	}

	protected function addForeignKeys()
	{
		$this->addForeignKey(null, Table::ORDERINFO, ['id'], CommerceTable::ORDERS, ['id'], 'CASCADE', 'CASCADE');
	}

	protected function dropForeignKeys()
	{
		if ($this->db->tableExists(Table::ORDERINFO)) {
			MigrationHelper::dropAllForeignKeysOnTable(Table::ORDERINFO, $this);
		}
	}

	protected function dropTables()
	{
		$this->dropTableIfExists(Table::ORDERINFO);
		$this->dropTableIfExists(Table::SETTINGS);
	}
}
