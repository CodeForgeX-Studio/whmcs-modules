# WHMCS Spaceship Registrar Module

![WHMCS](https://img.shields.io/badge/WHMCS-Compatible-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/License-MIT-lightgrey)

A WHMCS registrar module that integrates **Spaceship** as a domain registrar, allowing domain registration and management directly from WHMCS.

## Features

* Domain registration
* Domain renewal
* Domain transfer
* Nameserver management
* Registrar automation via Spaceship API
* Native WHMCS registrar integration

## Requirements

* WHMCS 8.x or newer
* PHP 7.4 or higher
* Active Spaceship account
* Spaceship API credentials (API Key & API Secret)
* cURL enabled on the server

## Installation

1. Download or clone this repository.
2. Navigate to your WHMCS installation directory.
3. Create the following folder manually:
   ```
   /modules/registrars/spaceship
   ```
4. Upload all files from this repository into:
   ```
   /modules/registrars/spaceship
   ```

**Important:**
The `spaceship` directory does **not** exist by default and must be created manually.

## Module Activation

1. Log in to the WHMCS admin area.
2. Go to:
   ```
   https://yourwhmcsinstall.tld/admin/configregistrars.php
   ```
   or
   ```
   http://yourwhmcsinstall.tld/admin/configregistrars.php
   ```
   (depending on whether SSL is enabled)
3. Locate **Spaceship** in the registrar list.
4. Click **Activate**.
5. Enter the required API credentials.
6. Click **Save Changes**.

## API Credentials

You will need the following credentials from Spaceship:

* **API Key**
* **API Secret**

These can be generated via the Spaceship API Manager:

[https://www.spaceship.com/application/api-manager/](https://www.spaceship.com/application/api-manager/)

Make sure the API access is enabled and active.

## Domain Extensions & Pricing Setup

After activating the module:
1. Navigate to:
   ```
   https://yourwhmcsinstall.tld/admin/configdomains.php
   ```
   or
   ```
   http://yourwhmcsinstall.tld/admin/configdomains.php
   ```
2. Manually add the domain extensions (TLDs) you want to sell.
3. Configure pricing for:
   * Registration
   * Renewal
   * Transfer
4. Save the configuration.

## Client Area Verification

* Go to the **WHMCS Client Area**
* Check if domain extensions appear correctly
* Test a domain order

Normally, domains should appear automatically once pricing is configured.
If not, WHMCS may not display domains correctly due to misconfiguration.

## Troubleshooting

### Domains not visible in Client Area

* Verify TLD pricing is set
* Confirm the registrar is assigned to the TLD
* Clear WHMCS template cache

### API Errors

* Verify API Key and Secret
* Ensure API access is enabled in Spaceship
* Check server outbound connections (cURL)

### Module Not Showing

* Confirm folder path is correct:
  ```
  /modules/registrars/spaceship
  ```
* Ensure file permissions are correct
* Check WHMCS module logs

## Logging & Debugging

You can enable module debugging in WHMCS:
* Go to **Utilities → Logs → Module Log**
* Enable logging
* Retry the action to capture API responses

## Disclaimer

This is a third-party integration and is not officially affiliated with or endorsed by Spaceship.