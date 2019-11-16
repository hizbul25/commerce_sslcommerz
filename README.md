# Commerce SslCommerz

CONTENTS
---------------------
* [Introduction](#introduction)
* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [How It Works](#how-it-works)
* [Credits](#credits)

## Introduction
Accept Credit card, Debit card, Mobile Banking and Online Banking payment directly on your store with the SslCommerz payment gateway for Drupal Commerce 2.x.
Take Credit card & Mobile Banking payments easily and directly on your store.

## Requirements
This module requires the following:
* Submodules of [Drupal Commerce Module](https://drupal.org/project/commerce). 
  - Commerce core
  - Commerce Payment (and its dependencies)

## Installation
* Download or clone this module from https://github.com/hizbul25/commerce_sslcommerz
* Place module inside drupal8 directory.

## Configuration
* Create a new SslCommerz payment gateway.  
  *Administration > Commerce > Configuration > Payment gateways > Add payment gateway*  
  SslCommerz-specific settings available:
  - Store ID
  - Store Password
  
  Use the Store credentials provided by your SslCommerz merchant account. It is
  recommended to enter test credentials and then override these with live
  credentials when you are on production.

## How It Works
* General considerations:
  - The store owner must have a SslCommerz merchant account.
    Sign up here:
    [https://signup.sslcommerz.com/register](https://signup.sslcommerz.com/register)
  - Customers should have a valid credit card/bank account.
    - SslCommerz provides some test credit card numbers for testing.
    - SslCommerz also provides some demo bkash/rocket accounts for testing.
* Checkout workflow:
  - It follows the Drupal Commerce Credit Card workflow.
  The customer should enter his/her credit card data or bank account info.
  - The system redirect to SslCommerz site with payment information automatically with:
    - Title
    - Description
    - Site Logo
* Payment Terminal:
  - The store owner can view the SslCommerz payments.


## License

GNU GENERAL PUBLIC LICENSE V2

## Credits
 - [Github](https://github.com/sslcommerz/Integration-in-RAW-PHP)
