<?php

namespace QD\commerce\economic\events;

use yii\base\Event;

class ApiResponseEvent extends Event
{
	/**
	 * @var Invoice Subscription
	 */
	public $response;

	public $sendByEan;
}
