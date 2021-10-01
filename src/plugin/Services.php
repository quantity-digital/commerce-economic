<?php

namespace QD\commerce\economic\plugin;

use QD\commerce\economic\services\Api;
use QD\commerce\economic\services\Customers;
use QD\commerce\economic\services\Email;
use QD\commerce\economic\services\Invoices;
use QD\commerce\economic\services\Orders;
use QD\commerce\economic\services\Plugin;
use QD\commerce\economic\services\SoapApi;
use QD\commerce\economic\services\Variants;

trait Services
{
	private function initComponents()
	{
		$this->setComponents([
			'api' => Api::class,
			'customers' => Customers::class,
			'email' => Email::class,
			'orders' => Orders::class,
			'invoices' => Invoices::class,
			'variants' => Variants::class,
			'plugin' => Plugin::class,
			'soapapi' => SoapApi::class
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

	public function getEmail()
	{
		return $this->get('email');
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

	public function getSoapApi()
	{
		return $this->get('soapapi');
	}
}
