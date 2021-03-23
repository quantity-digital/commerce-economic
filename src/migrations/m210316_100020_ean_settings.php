<?php

namespace QD\commerce\economic\migrations;

use Craft;
use craft\db\Migration;
use QD\commerce\economic\db\Table;

/**
 * m210316_100020_ean_settings migration.
 */
class m210316_100020_ean_settings extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		// $this->addColumn(Table::ORDERINFO, 'eanNumber', $this->string());
		// $this->addColumn(Table::ORDERINFO, 'eanReference', $this->string());
		// $this->addColumn(Table::ORDERINFO, 'eanContact', $this->string());

		// $this->createTable(Table::SETTINGS, [
		// 	'id' => $this->integer()->notNull(),
		// 	'secretToken' => $this->string()->null(),
		// 	'grantToken' => $this->string()->null(),
		// 	'defaultpaymentTermsNumber' => $this->integer()->null(),
		// 	'defaultLayoutNumber' => $this->string()->null(),
		// 	'defaultCustomerGroup' => $this->integer()->null(),
		// 	'defaultVatZoneNumber' => $this->integer()->null(),
		// 	'defaultProductgroup' => $this->integer()->null(),
		// 	'invoiceEnabled' => $this->boolean()->null(),
		// 	'onlyB2b' => $this->boolean()->null(),
		// 	'statusIdAfterInvoice' => $this->string()->null(),
		// 	'invoiceOnStatusId' => $this->string()->null(),
		// 	'autoBookInvoice' => $this->boolean(),
		// 	'invoiceLayoutNumber' => $this->string()->null(),
		// 	'gatewayPaymentTerms' => $this->json()->null(),
		// 	'shippingProductnumbers' => $this->json()->null(),
		// 	'vatZones' => $this->json()->null(),
		// 	'syncVariants' => $this->boolean()->null(),
		// 	'convertAmount' => $this->boolean()->null(),
		// 	'uid' => $this->uid(),
		// 	'dateCreated' => $this->dateTime()->notNull(),
		// 	'dateUpdated' => $this->dateTime()->notNull(),
		// 	'PRIMARY KEY([[id]])',
		// ]);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m210316_100020_ean_settings cannot be reverted.\n";
		return false;
	}
}
