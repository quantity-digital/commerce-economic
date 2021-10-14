<?php

namespace QD\commerce\economic\plugin;

use QD\commerce\economic\services\Api;
use QD\commerce\economic\services\CreditnoteRows;
use QD\commerce\economic\services\Creditnotes;
use QD\commerce\economic\services\Customers;
use QD\commerce\economic\services\economic\Orders as EconomicOrders;
use QD\commerce\economic\services\Emails;
use QD\commerce\economic\services\Invoices;
use QD\commerce\economic\services\Orders;
use QD\commerce\economic\services\Plugin;
use QD\commerce\economic\services\Quotations;
use QD\commerce\economic\services\SoapApi;
use QD\commerce\economic\services\Variants;

trait Services
{
	private function initComponents()
	{
		$this->setComponents([
			'api' => Api::class,
			'customers' => Customers::class,
			'emails' => Emails::class,
			'orders' => Orders::class,
			'invoices' => Invoices::class,
			'variants' => Variants::class,
			'plugin' => Plugin::class,
			'creditnotes' => Creditnotes::class,
			'creditnoteRows' => CreditnoteRows::class,
		]);
	}

	public function getApi()
	{
		return $this->get('api');
	}

	public function getCustomers()
	{
		return $this->get('customers');
	}

	public function getEmails()
	{
		return $this->get('emails');
	}

	public function getInvoices()
	{
		return $this->get('invoices');
	}

	public function getOrders()
	{
		return $this->get('orders');
	}

	public function getVariants()
	{
		return $this->get('variants');
	}

	public function getPlugin()
	{
		return $this->get('plugin');
	}

	public function getCreditnotes()
	{
		return $this->get('creditnotes');
	}

	public function getCreditnoteRows()
	{
		return $this->get('creditnoteRows');
	}
}
