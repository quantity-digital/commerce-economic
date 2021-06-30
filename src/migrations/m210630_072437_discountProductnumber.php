<?php

namespace QD\commerce\economic\migrations;

use Craft;
use craft\db\Migration;
use QD\commerce\economic\db\Table;

/**
 * m210630_072437_discountProductNumber migration.
 */
class m210630_072437_discountProductnumber extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		if (!$this->db->columnExists(Table::SETTINGS, 'discountProductnumber')) {
			$this->addColumn(
				Table::SETTINGS,
				'discountProductnumber',
				$this->string()
			);
		}
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
