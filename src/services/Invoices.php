<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use QD\commerce\economic\Economic;
use QD\commerce\economic\events\ApiResponseEvent;
use QD\commerce\economic\events\InvoiceEvent;
use QD\commerce\economic\gateways\Ean;
use QD\commerce\economic\helpers\Log;
use QD\commerce\economic\models\Customer;
use QD\commerce\economic\models\CustomerContact;
use QD\commerce\economic\models\Invoice;
use QD\commerce\economic\models\Layout;
use QD\commerce\economic\models\PaymentTerms;
use QD\commerce\economic\models\Recipient;
use QD\commerce\economic\queue\jobs\CreateInvoice;

class Invoices extends Component
{

    const EVENT_BEFORE_CREATE_INVOICE_DRAFT = 'beforeCrateInvoiceDraft';
    const EVENT_AFTER_INVOICE_BOOKING = 'afterInvoiceBooking';

    public function createFromOrder(Order $order)
    {
        //Get base invoice model
        $invoice = Invoice::transformFromOrder($order);
        return $this->createInvoiceDraft($invoice);
    }

    public function createInvoiceDraft(Invoice $invoice)
    {
        //Make it possible to modify Invoice model before creating invoice in e-conomic
        $event =  new InvoiceEvent([
            'invoice' => $invoice
        ]);
        $this->trigger(self::EVENT_BEFORE_CREATE_INVOICE_DRAFT, $event);
        if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_INVOICE_DRAFT)) {
            $this->trigger(self::EVENT_BEFORE_CREATE_INVOICE_DRAFT, $event);
        }

        $response = Economic::getInstance()->getApi()->client->request->post('/invoices/drafts', $event->invoice->asArray());

        $status = $response->httpStatus();

        if ($status == 201) {
            return $response;
        }

        return false;
    }

    public function getInvoiceDraft($draftNumber)
    {
        $response = Economic::getInstance()->getApi()->client->request->get('/invoices/drafts/' . $draftNumber);
        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }

    public function bookInvoiceDraft($draftNumber, $sendByEan = false)
    {
        $draftInvoice = $this->getInvoiceDraft($draftNumber);

        if (!$draftInvoice) {
            return false;
        }

        $params = [
            "draftInvoice" => $draftInvoice->asObject()
        ];

        if ($sendByEan) {
            $params['sendBy'] = 'ean';
        }

        $response = Economic::getInstance()->getApi()->client->request->post('/invoices/booked/', $params);

        $status = $response->httpStatus();

        if ($status == 201) {

            $event =  new ApiResponseEvent([
                'response' => $response
            ]);

            if ($this->hasEventHandlers(self::EVENT_AFTER_INVOICE_BOOKING)) {
                $this->trigger(self::EVENT_AFTER_INVOICE_BOOKING, $event);
            }

            return $event->response;
        }

        return false;
    }

    public function getInvoice($invoicenumber)
    {
        $response = Economic::getInstance()->getApi()->client->request->get('/invoices/booked/' . $invoicenumber);
        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }

    public function getInvoicePdf($invoicenumber)
    {
        $response = Economic::getInstance()->getApi()->client->request->get('/invoices/booked/' . $invoicenumber . '/pdf');
        $status = $response->httpStatus();

        if ($status == 200) {
            return $response;
        }

        return false;
    }

    /**
     * Jobs
     */
    public function addCreateInvoiceJob($event)
    {
        $order = $event->order;

        if ($order->draftInvoiceNumber) {
            return;
        }

        if ($order->invoiceNumber) {
            return;
        }


        $billingAddress = $order->getBillingAddress();
        $settings = Economic::getInstance()->getSettings();

        if ($settings->onlyB2b && !$billingAddress->businessTaxId) {
            return;
        }

        if ($event->orderHistory->newStatusId != Economic::getInstance()->getSettings()->invoiceOnStatusId) {
            return;
        }

        Craft::$app->getQueue()->delay(10)->push(new CreateInvoice(
            [
                'orderId' => $order->id,
            ]
        ));
    }
}
