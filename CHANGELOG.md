# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

### 1.1.20 - 2024-09-04

- Handled queue error throws better

### 1.1.19 - 2024-09-04

- Updated reAddToQueue logic

### 1.1.18 - 2024-09-04

### Bug Fixes

- Fixed a bug in lineitem fetching

### 1.1.17 - 2024-09-04

### Bug Fixes

- Added variant sync functionallity, on invoice creation

### 1.1.16 - 2023-12-06

### Bug Fixes

- Handled deprecated methods of getting shipping method id

### 1.1.15 - 2023-12-06

### Bug Fixes

- adjusted fallback for productGroups ([d63cb95](https://github.com/quantity-digital/commerce-economic/commit/d63cb953b9b3341ebac5e9b0bcce28a0609d5c0d))

### 1.1.14 - 2023-11-10

### Bug Fixes

- Fallback for ProductGroup input on product pages failed when E-conomic call failed ([5aaeecd](https://github.com/quantity-digital/commerce-economic/commit/5aaeecd3b1c22cd10597688d93ce12db3e81f15a))

## 1.1.13 - 2023-02-21

### Added

- Failed invoices and creditnotes, not start product sync to ensure missing products is not the issue

## 1.1.12 - 2022-08-02

### Added

- Creditnotes for orders, paid using the EAN gateway, now requires account number and registration number for bank account.

## 1.1.11 - 2022-05-05

### Fixed

- Fixed bug where invoices would fail because customer entered business tax id with spaces. Plugin now strips all spaces away.
- Fixed a bug in `QD\commerce\economic\models\Product` where `setSalePrice` returned a int instead of float, resulting in decimal prices beeing stripped

## 1.1.10 - 2022-05-05

### Fixed

- Fixed a bug where shipping orderline could be calculated wrong, of order only consist of shipping
- Fixed a bug where discount on orderlines could be missed

## 1.1.9 - 2022-02-14

### Fixed

- Fixed creditnotes not skipping line with 0 qty

## 1.1.8 - 2021-12-20

### Fixed

Undo v. 1.1.5 changes, because if we remove the country code, we can't verify customers in multi country B2B integration because country field in e-conomic is a text field. E-conomic has been been notified about the consequences of the automated country code to the REST-api - [https://forum.e-conomic.dk/37762/kunders-landefelt-skal-ikke-vaere-fri-tekst](https://forum.e-conomic.dk/37762/kunders-landefelt-skal-ikke-vaere-fri-tekst)

## 1.1.7 - 2021-12-02

### Fixed

- Fixed bug in `QD\commerce\economic\models\CustomerGroup` where it would fail if a customer group wasn't a match

## 1.1.6 - 2021-11-15

### Added

- Its now possible to map checkout countries to specific customer groups

## 1.1.5 - 2021-11-15

### Changed

- Plugin now validates the VAT-number to filter out any country codes that is prepended it. This is because E-conomic will prepend the VAT-number automaticly with the country code of the selected customer.

## 1.1.4 - 2021-11-12

### Fixed

- Fixed error in creditnote calculation

## 1.1.3 - 2021-10-26

### Adjusted

- Adjusted `QD\commerce\economic\services\Orders::getOrderLines()` to calculate rate based on applied taxrates.

## 1.1.2 - 2021-10-14

### Added

- Plugin can now create orders and quotes in e-conomic. This isn't automatic, and you have to manually trigger the two Queue jobs. Integration is made via their SOAP-api beucase orders and quotes won't work with REST-api if inventory module is enabled in e-conomic.

## 1.1.1 - 2021-10-07

### Added

- Added support for [verbb/giftvoucher](https://github.com/verbb/gift-voucherhttps://) in adjustments

## 1.1.0 - 2021-10-04

### New feature

Added the possiblity to create credit notes via an order. Credit notes is stored in Craft CMS, and synced to e-conomic when it gets marked as completed. It's also possible to restock the qty set in the creditnote.

### Added

- New `CreditNoteEvent` which fires after an Creditnote is marked as completed.
- New `RestockEvent` which fires before the restock function is run. It's an cancellable event.

## 1.0.27 - 2021-08-09

### Added

- Added `Order` to `EVENT_BEFORE_CREATE_INVOICE_DRAFT`

## 1.0.25

### Fixed

- Fixed error where discount on lineitems wasn't calculated correctly

### Changed

- Line items now used the stores vat-decimal when removing VAT from unit price.

## 1.0.24 - 2021-07-13

### Fixed

- Fixed error in API Service, calling wrong method

## 1.0.23 - 2021-06-30

### Fixed

- Fixed settings issue that prevented migration to run

## 1.0.20 - 2021-06-30

### Added

- Added error logs for invoice creation and booking
- Added new database column for discount productnumber setting
- Added settings option to set productnumber for order discounts/vouchers

### Fixed

- Fixed invoices missing discounts/vouchers not applied to a specific product

## 1.0.19 - 2021-05-28

### Fixed

- Fixed error in invoice creation, when the salesprice was zero
- Fixed error in invoice creation when no ean contact had been set

## 1.0.14 - 2021-03-29

### Fixed

- Fixed error in PaymentTerms, which wouldnt match gatewayrelations to order gateway

## 1.0.11 - 2021-03-18

### Added

- Added an EAN gateway

### Changed

Settings is now stored in database instead of the project config

## 1.0.10 - 2021-03-11

### Fixed

- Fixed error in OrderBehaviour that prevent queue jobs executed by console to run

## 1.0.9 - 2020-10-28

### Changed

- Removed unnessesary log

### Fixed

- Fixed OrderQuery returning null qhen query on custom attributes
- Fixed EVENT_AFTER_INVOICE_BOOKING beeing trigger twice

## 1.0.8 - 2020-10-27

### Fixed

- Fixed issues with customer creation

## 1.0.5 - 2020-10-27

### Fixed

- Fixed error where a null value in customerNumber would make the customer creation fail

## 1.0.4 - 2020-10-27

### Fixed

- Fixed typo

## 1.0.3 - 2020-10-27

### Added

- Added `QD\commerce\economic\models\CustomerGroup` model
- Added getter functions to all models
- Added `QD\commerce\economic\services\Invoices\createCustomerFromOrder` function, which returns the customer object from E-conomic

### Fixed

- Fixed missing function to create a customer, if none exists in E-conomic
- Fixed error in `QD\commerce\economic\services\Invoices\bookInvoiceDraft` where it was trying to fetch collection data instead of the response object

## 1.0.2 - 2020-10-27

### Added

- Added getters to invoice model

### Fixed

- Fixed error in API request when booking drafted invoice

## 1.0.1 - 2020-10-27

### Fixed

- Fixed error where api service didn't parse Env variable befor setting granttoken and secrettoken.

## 1.0.0 - 2020-10-27

Initial release of the Visma E-conomic integration to the Craft Store
