<?php

namespace QD\commerce\economic\migrations;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use QD\commerce\economic\db\Table;

/**
 * m211116_072437_customergroup-relations migration.
 */
class m211116_072437_customergroupRelations extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{

		if (!$this->db->columnExists(Table::SETTINGS, 'customerGroups')) {
			$this->addColumn(
				Table::SETTINGS,
				'customerGroups',
				$this->json()->null()
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m211116_072437_customergroup-relations cannot be reverted.\n";
		return false;
	}
}
