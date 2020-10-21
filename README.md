# HiPay Professional Gateway extension for Magento 2

## API credentials

HiPay API production or sandbox account credentials for each currency:
   - merchant login
   - merchant password
   - website id
   - website category id: to find the category id, open one of the following urls in your browser and replace WEBSITEID for the real website id. For a production account use https://payment.hipay.com/order/list-categories/id/WEBSITEID and for a test account use https://test-payment.hipay.com/order/list-categories/id/WEBSITEID. Use one of the returned values.

## Setup
    
  - Enabled: enable or disable extension
  - Sandbox: enable or disable sandbox account
  - Iframe: enable iframe payment page
  - Account credentials: for each currency enabled, set the API login, password, website id and category id
  - Debug: log payment info
  - Technical Email: this email will receive the notification results
  - Website Logo: full url for the logo that will appear on the HiPay payment window
  - Website Rating: choose the website rating
  
## Requirements
  - SOAP extension
  - SimpleXML

Version 1.0.3
