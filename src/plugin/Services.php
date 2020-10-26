<?php

namespace QD\commerce\economic\plugin;

use QD\commerce\economic\services\Api;
use QD\commerce\economic\services\Customers;
use QD\commerce\economic\services\Email;
use QD\commerce\economic\services\Invoices;
use QD\commerce\economic\services\Orders;

trait Services
{
	private function initComponents()
	{
		$this->setComponents([
			'api' => Api::class,
			'customers' => Customers::class,
			'email' => Email::class,
			'orders' => Orders::class,
			'invoices' => Invoices::class
		]);
	}

	public function getApi(){
		return $this->get('api');
	}

	public function getCustomers(){
		return $this->get('customers');
	}

	public function getEmail(){
		return $this->get('email');
	}

	public function getInvoices(){
		return $this->get('invoices');
	}

	public function getOrders(){
		return $this->get('orders');
	}

}
