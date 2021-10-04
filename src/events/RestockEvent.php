<?php

namespace QD\commerce\economic\events;

use craft\events\CancelableEvent;

class RestockEvent extends CancelableEvent
{
	public $creditnote;
}
