<?php

namespace QD\commerce\economic\migrations;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use QD\commerce\economic\db\Table;

/**
 * m220802_090000_ean_payment migration.
 */
class m220802_090000_ean_payment extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{

		if (!$this->db->columnExists(Table::CREDITNOTES, 'isEan')) {
			$this->addColumn(
				Table::CREDITNOTES,
				'isEan',
				$this->boolean()
			);
		}

		if (!$this->db->columnExists(Table::CREDITNOTES, 'regNr')) {
			$this->addColumn(
				Table::CREDITNOTES,
				'regNr',
				$this->string()->null()
			);
		}

		if (!$this->db->columnExists(Table::CREDITNOTES, 'accountNumber')) {
			$this->addColumn(
				Table::CREDITNOTES,
				'accountNumber',
				$this->string()->null()
			);
		}

		if (!$this->db->columnExists(Table::CREDITNOTES, 'refunded')) {
			$this->addColumn(
				Table::CREDITNOTES,
				'isRefunded',
				$this->boolean()
			);
		}

		if (!$this->db->columnExists(Table::CREDITNOTES, 'dateRefunded')) {
			$this->addColumn(
				Table::CREDITNOTES,
				'dateRefunded',
				$this->dateTime()
			);
		}

		if (!$this->db->columnExists(Table::SETTINGS, 'creditnoteNotificationEmail')) {
			$this->addColumn(
				Table::SETTINGS,
				'creditnoteNotificationEmail',
				$this->string()->null()
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m220802_090000_ean_payment cannot be reverted.\n";
		return false;
	}
}
