<?php

namespace QD\commerce\economic\behaviors;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\errors\OrderStatusException;
use craft\commerce\models\OrderHistory;
use craft\commerce\Plugin as CommercePlugin;
use QD\commerce\economic\db\Table;
use yii\base\Behavior;

class OrderBehavior extends Behavior
{
	public $invoiceNumber;
	public $draftInvoiceNumber;


	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			Order::EVENT_BEFORE_SAVE => [$this, 'setOrderInfo'],
			Order::EVENT_AFTER_SAVE => [$this, 'saveOrderInfo'],
		];
	}

	public function setOrderInfo()
	{
		// If droppointId is set, store it on the order
		$request = Craft::$app->getRequest();
		$invoiceNumber = $request->getParam('invoiceNumber');
		$draftInvoiceNumber = $request->getParam('draftInvoiceNumber');

		if ($invoiceNumber !== NULL) {
			$this->invoiceNumber = $invoiceNumber;
		}

		if($draftInvoiceNumber){
			$this->draftInvoiceNumber = $draftInvoiceNumber;
		}
	}

	/**
	 * Saves extra attributes that the Behavior injects.
	 *
	 * @return void
	 */
	public function saveOrderInfo()
	{
		$data = [];

		if ($this->invoiceNumber !== null) {
			$data['invoiceNumber'] = $this->invoiceNumber;
		}

		if ($this->draftInvoiceNumber !== null) {
			$data['draftInvoiceNumber'] = $this->draftInvoiceNumber;
		}

		if ($data) {
			Craft::$app->getDb()->createCommand()
				->upsert(Table::ORDERINFO, [
					'id' => $this->owner->id,
				], $data, [], false)
				->execute();
		}
	}

	public function setStatus(int $statusId, string $message = null)
	{
		$order = $this->owner;
		$status = CommercePlugin::getInstance()->orderStatuses->getOrderStatusById($statusId);
		$oldStatusId = $order->orderStatusId;

		// Validate status
		if ($status === null) {
			throw new OrderStatusException('Invalid order status id');
		}

		// Update order status
		Craft::$app->getDb()->createCommand()->update(
			'{{%commerce_orders}}',
			['orderStatusId' => $statusId],
			['id' => $order->getId()]
		)->execute();

		// Create order history
		$orderHistoryModel = new OrderHistory();
		$orderHistoryModel->orderId = $order->id;
		$orderHistoryModel->prevStatusId = $oldStatusId;
		$orderHistoryModel->newStatusId = $statusId;
		$orderHistoryModel->customerId = $order->customerId;
		$orderHistoryModel->message = $message;

		CommercePlugin::getInstance()->getOrderHistories()->saveOrderHistory($orderHistoryModel);
	}
}
