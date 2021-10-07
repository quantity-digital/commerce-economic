# Release Notes for E-conomic for Craft Commerce

All notable changes to this project will be documented in this file.

## 1.1.1 - 2021-10-07

### Added

* Added support for [verbb/giftvoucher](https://github.com/verbb/gift-voucherhttps://) in adjustments

## 1.1.0 - 2021-10-04

### New feature

Added the possiblity to create credit notes via an order. Credit notes is stored in Craft CMS, and synced to e-conomic when it gets marked as completed. It's also possible to restock the qty set in the creditnote.

### Added

* New `CreditNoteEvent` which fires after an Creditnote is marked as completed.
* New `RestockEvent` which fires before the restock function is run. It's an cancellable event.

## 1.0.27 - 2021-08-09

### Added

* Added `Order` to `EVENT_BEFORE_CREATE_INVOICE_DRAFT`

## 1.0.25

### Fixed

* Fixed error where discount on lineitems wasn't calculated correctly

### Changed

* Line items now used the stores vat-decimal when removing VAT from unit price.

## 1.0.24 - 2021-07-13

### Fixed

* Fixed error in API Service, calling wrong method

## 1.0.23 - 2021-06-30

### Fixed

* Fixed settings issue that prevented migration to run

## 1.0.20 - 2021-06-30

### Added

* Added error logs for invoice creation and booking
* Added new database column for discount productnumber setting
* Added settings option to set productnumber for order discounts/vouchers

### Fixed

* Fixed invoices missing discounts/vouchers not applied to a specific product

## 1.0.19 - 2021-05-28

### Fixed

* Fixed error in invoice creation, when the salesprice was zero
* Fixed error in invoice creation when no ean contact had been set

## 1.0.14 - 2021-03-29

### Fixed

* Fixed error in PaymentTerms, which wouldnt match gatewayrelations to order gateway

## 1.0.11 - 2021-03-18

### Added

* Added an EAN gateway

### Changed

Settings is now stored in database instead of the project config

## 1.0.10 - 2021-03-11

### Fixed

* Fixed error in OrderBehaviour that prevent queue jobs executed by console to run

## 1.0.9 - 2020-10-28

### Changed

* Removed unnessesary log

### Fixed

* Fixed OrderQuery returning null qhen query on custom attributes
* Fixed EVENT_AFTER_INVOICE_BOOKING beeing trigger twice

## 1.0.8 - 2020-10-27

### Fixed

* Fixed issues with customer creation

## 1.0.5 - 2020-10-27

### Fixed

* Fixed error where a null value in customerNumber would make the customer creation fail

## 1.0.4 - 2020-10-27

### Fixed

* Fixed typo

## 1.0.3 - 2020-10-27

### Added

* Added `QD\commerce\economic\models\CustomerGroup` model
* Added getter functions to all models
* Added `QD\commerce\economic\services\Invoices\createCustomerFromOrder` function, which returns the customer object from E-conomic

### Fixed

* Fixed missing function to create a customer, if none exists in E-conomic
* Fixed error in `QD\commerce\economic\services\Invoices\bookInvoiceDraft` where it was trying to fetch collection data instead of the response object

## 1.0.2 - 2020-10-27

### Added

* Added getters to invoice model

### Fixed

* Fixed error in API request when booking drafted invoice

## 1.0.1 - 2020-10-27

### Fixed

* Fixed error where api service didn't parse Env variable befor setting granttoken and secrettoken.

## 1.0.0 - 2020-10-27

Initial release of the Visma E-conomic integration to the Craft Store
