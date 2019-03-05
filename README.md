# roopal-php

Parses Deliveroo PDF invoices.

## Install
Via Composer:
```composer require guym4c/roopal-php```

## Usage

Parse using the ```invoice``` constructor
```php
$invoice = new Guym4c\Roopal\Invoice(string $pathToYourPDF [, bool $anonymise = false]);
```

If ```$anonymise``` is passed to the constructor, any output using the ```toArray()``` methods or ```toCSV()``` will only include md5 hashes of rider names. Bear in mind that some payment adjustments can contact personally identifying information, such as the name of the referring rider in referral payments.

Export invoices to CSV using the static method in ```Invoice```
```php
$csv = Guym4c\Roopal\Invoice::toCsv([$invoice]);
```

## Issues
Compatibility could be broken at any time - if invoices are not parsing correctly, feel free to open an issue or PR. 

This repo is maintained by the IWGB. If you have an invoice which breaks the parser, you can also email it to me, @guym4c, at [guymac@iwgb.co.uk](mailto:guymac@iwgb.co.uk) - invoices mailed to this address will be handled in strictest confidence under the IWGB's [Data Protection Policy](https://iwgb.org.uk/page/about/data-protection).

# Hosted?
IWGB Couriers Branch members can use this over the web on the IWGB website - contact a branch official to find out more. We're working on making this public once it's stable.

*Putting Workers First*

