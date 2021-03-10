# Release Notes for QuickPay for Craft Commerce

All notable changes to this project will be documented in this file.

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
