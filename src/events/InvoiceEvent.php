<?php

namespace QD\commerce\economic\events;

use craft\events\CancelableEvent;

class InvoiceEvent extends CancelableEvent
{
    /**
     * @var Invoice Subscription
     */
    public $invoice;
}
