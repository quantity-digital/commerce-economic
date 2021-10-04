<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace QD\commerce\economic\elements\db;

use craft\elements\db\ElementQuery;
use QD\commerce\economic\db\Table;

class SettingQuery extends ElementQuery
{
	/**
	 * @inheritdoc
	 */
	protected function beforePrepare(): bool
	{
		$this->joinElementTable('economic_settings');

		$this->query->select([
			'economic_settings.id',
			'economic_settings.secretToken',
			'economic_settings.grantToken',
			'economic_settings.defaultLayoutNumber',
			'economic_settings.defaultpaymentTermsNumber',
			'economic_settings.defaultCustomerGroup',
			'economic_settings.defaultVatZoneNumber',
			'economic_settings.defaultProductgroup',
			'economic_settings.invoiceEnabled',
			'economic_settings.statusIdAfterInvoice',
			'economic_settings.invoiceOnStatusId',
			'economic_settings.autoBookInvoice',
			'economic_settings.invoiceLayoutNumber',
			'economic_settings.autoBookCreditnote',
			'economic_settings.creditnoteLayoutNumber',
			'economic_settings.creditnoteEmailTemplate',
			'economic_settings.creditnoteEmailSubject',
			'economic_settings.gatewayPaymentTerms',
			'economic_settings.vatZones',
			'economic_settings.syncVariants',
			'economic_settings.onlyB2b',
			'economic_settings.convertAmount',
			'economic_settings.shippingProductnumbers',
			'economic_settings.discountProductnumber',
		]);

		return parent::beforePrepare();
	}
}
