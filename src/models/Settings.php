<?php

namespace QD\commerce\economic\models;

use craft\base\Model;

class Settings extends Model
{

	//E-conomic secret token: kjjFnQQszYsSLMqvbtT1lCXXihz6i00OBvrS1S6prcA1
	//Installation url: https://secure.e-conomic.com/secure/api1/requestaccess.aspx?appPublicToken=wh2wePDFtRPuj3wzKVD91CazhA6mBMGHc0B8x0MfFG01

	//Authorization
	public $secretToken;
	public $grantToken;

	//Defaults
	public $defaultpaymentTermsNumber;
	public $defaultLayoutNumber;
	public $defaultCustomerGroup;
	public $defaultVatZoneNumber;
	public $defaultEInvoiceEnabled = false;

	//Invoicing
	public $invoiceEnabled = false;
	public $statusIdAfterInvoice;
	public $invoiceOnStatusId;
	public $autoBookInvoice = false;
	public $invoiceLayoutNumber;

	//Relations
	public $gatewayPaymentTerms;
	public $vatZones;

	public function rules()
	{
		if (!$this->secretToken || !$this->grantToken) {
			return [
				[['grantToken', 'secretToken'], 'required'],
			];
		}

		return [
			[['grantToken', 'secretToken'], 'required'],
		];
	}
}
