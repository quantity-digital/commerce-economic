<?php

namespace QD\commerce\economic\events;

use craft\events\CancelableEvent;

class CreditnoteEvent extends CancelableEvent
{
	public $creditnote;
}
