<?php

namespace QD\commerce\economic\migrations;

use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
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
		$this->dropForeignKey('economic_orders_id_fk', Table::ORDERINFO);
	}

	protected function dropTables()
	{
		$this->dropTableIfExists(Table::ORDERINFO);
	}
}
