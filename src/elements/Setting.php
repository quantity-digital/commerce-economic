<?php

namespace QD\commerce\economic\elements;

use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use QD\commerce\economic\elements\db\SettingQuery;
use QD\commerce\economic\records\SettingsRecord;
use yii\base\Exception;

class Setting extends Element
{
	// Authorization
	public $secretToken;
	public $grantToken;

	// Defaults
	public $defaultpaymentTermsNumber;
	public $defaultLayoutNumber;
	public $defaultCustomerGroup;
	public $defaultVatZoneNumber;
	public $defaultProductgroup;

	// Invoicing
	public $invoiceEnabled = false;
	public $statusIdAfterInvoice;
	public $invoiceOnStatusId;
	public $autoBookInvoice = false;
	public $invoiceLayoutNumber;
	public $onlyB2b = false;
	public $convertAmount = false;

	//Creditnotes
	public $autoBookCreditnote = false;
	public $creditnoteLayoutNumber;
	public $creditnoteEmailTemplate;
	public $creditnoteEmailSubject;
	public $creditnoteNotificationEmail;

	// Relations
	public $gatewayPaymentTerms;
	public $vatZones;
	public $shippingProductnumbers;
	public $discountProductnumber;
	public $customerGroups;

	// Sync settings
	public $syncVariants = false;

	public static function find(): ElementQueryInterface
	{
		return new SettingQuery(static::class);
	}

	/**
	 * @param bool $isNew
	 * @throws Exception
	 */
	public function afterSave(bool $isNew)
	{
		if (!$isNew) {
			$record = SettingsRecord::findOne($this->id);

			if (!$record) {
				$record = new SettingsRecord();
				$record->id = $this->id;
			}
		} else {
			$record = new SettingsRecord();
			$record->id = $this->id;
		}

		// We want to always have the same date as the element table, based on the logic for updating these in the element service i.e resaving
		$record->secretToken = $this->secretToken;
		$record->grantToken = $this->grantToken;
		$record->defaultpaymentTermsNumber = $this->defaultpaymentTermsNumber;
		$record->defaultLayoutNumber = $this->defaultLayoutNumber;
		$record->defaultCustomerGroup = $this->defaultCustomerGroup;
		$record->defaultVatZoneNumber = $this->defaultVatZoneNumber;
		$record->defaultProductgroup = $this->defaultProductgroup;
		$record->invoiceEnabled = $this->invoiceEnabled;
		$record->statusIdAfterInvoice = $this->statusIdAfterInvoice;
		$record->invoiceOnStatusId = $this->invoiceOnStatusId;
		$record->autoBookInvoice = $this->autoBookInvoice;
		$record->invoiceLayoutNumber = $this->invoiceLayoutNumber;

		$record->autoBookCreditnote = $this->autoBookCreditnote;
		$record->creditnoteLayoutNumber = $this->creditnoteLayoutNumber;
		$record->creditnoteEmailTemplate = $this->creditnoteEmailTemplate;
		$record->creditnoteEmailSubject = $this->creditnoteEmailSubject;
		$record->creditnoteNotificationEmail = $this->creditnoteNotificationEmail;

		$record->gatewayPaymentTerms = $this->gatewayPaymentTerms;
		$record->shippingProductnumbers = $this->shippingProductnumbers;
		$record->discountProductnumber = $this->discountProductnumber;
		$record->vatZones = $this->vatZones;
		$record->customerGroups = $this->customerGroups;
		$record->onlyB2b = $this->onlyB2b;
		$record->syncVariants = $this->syncVariants;
		$record->convertAmount = $this->convertAmount;

		$record->save(false);

		return parent::afterSave($isNew);
	}
}
