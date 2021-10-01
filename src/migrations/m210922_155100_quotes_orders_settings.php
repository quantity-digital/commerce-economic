<?php

namespace QD\commerce\economic\migrations;

use Craft;
use craft\db\Migration;
use QD\commerce\economic\db\Table;

/**
 * m210630_072437_discountProductNumber migration.
 */
class m210922_155100_quotes_orders_settings extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		if (!$this->db->columnExists(Table::SETTINGS, 'quotationsEnabled')) {

			$this->addColumn(
				Table::SETTINGS,
				'quotationsEnabled',
				$this->boolean()
			);

			$this->addColumn(
				Table::SETTINGS,
				'quoteOnStatusId',
				$this->string()
			);

			$this->addColumn(
				Table::SETTINGS,
				'statusIdAfterQuotation',
				$this->string()
			);

			$this->addColumn(
				Table::SETTINGS,
				'ordersEnabled',
				$this->boolean()
			);

			$this->addColumn(
				Table::SETTINGS,
				'orderOnStatusId',
				$this->string()
			);

			$this->addColumn(
				Table::SETTINGS,
				'statusIdAfterOrder',
				$this->string()
			);
		}
	}
	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m210922_155100_quotes_orders_settings cannot be reverted.\n";
		return false;
	}
}
