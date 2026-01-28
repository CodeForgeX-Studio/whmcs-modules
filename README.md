# CodeForgeX Studio – WHMCS Modules

Welcome to the WHMCS Modules repository of CodeForgeX Studio.  
This repository contains all proprietary extensions, integrations, and automation solutions developed for WHMCS.  
Each module is built with a focus on security, scalability, and maintainability, adhering to the official WHMCS development standards.

## Overview

WHMCS supports multiple categories of modules, each serving a specific role within the platform.  
This repository follows the default WHMCS directory structure to maintain organization, clarity, and ease of development.  
Below is an overview of each module category, its path, and primary function.

## Provisioning Modules (Server / Product Modules)

**Path:** `/modules/servers/`  
Provisioning modules handle automation tasks related to product delivery and server management.  
They enable services such as account creation, suspension, and termination through direct API communication.

**Typical use cases:**  
- Shared hosting platforms (e.g., cPanel, Plesk).  
- VPS, cloud, or dedicated server provisioning (e.g., SolusVM, Virtualizor).  
- Automated product lifecycle management.

## Payment Gateways (Billing Modules)

**Path:** `/modules/gateways/`  
Payment gateway modules process transactions, manage payment callbacks, and support recurring billing automation.

**Typical use cases:**  
- Payment integrations such as iDEAL, [PayPal](https://www.paypal.com/), and [Stripe](https://stripe.com/).  
- Handling one-time and subscription payments.  
- Callback scripts within `/modules/gateways/callback/`.

These modules must follow WHMCS's official gateway interface to ensure secure and reliable payment handling.

## Addon Modules (System Extensions)

**Path:** `/modules/addons/`  
Addon modules extend WHMCS beyond its core functionality.  
They can introduce new tools, automate workflows, or provide integrations with external systems.

**Typical use cases:**  
- Admin area utilities or management tools.  
- Client area enhancements and custom interfaces.  
- API connections to third‑party platforms or internal systems.

Each addon is self‑contained and may include templates, configuration, and hook implementations.

## Registrar Modules (Domain Registrars)

**Path:** `/modules/registrars/`  
Registrar modules automate domain operations by connecting WHMCS to registrar APIs for real‑time management.

**Typical use cases:**  
- Domain registration, renewal, and transfer automation.  
- Synchronizing domain details, expiry dates, and nameservers.  
- Integration with providers such as OpenSRS and TransIP.

Each registrar module follows the WHMCS module template to ensure reliable API communication.

## Fraud Modules (Fraud Detection)

**Path:** `/modules/fraud/`  
Fraud modules integrate with external fraud detection systems to validate and score incoming orders.  
They help prevent fraudulent transactions before service activation occurs.

**Typical use cases:**  
- Integrations with services such as MaxMind.  
- Automated order blocking or flagging based on risk results.  
- Logging and reporting for fraud monitoring.

## Development Guidelines

- Follow official WHMCS development documentation and naming conventions.  
- Use strict namespacing to prevent conflicts with other modules.  
- Keep each module isolated, version-controlled, and consistently structured.  
- Test thoroughly in a staging environment before deployment.  
- Provide detailed documentation and changelogs per module.

## License and Credits

All WHMCS modules within this repository are developed and maintained by **CodeForgeX Studio**.  
Each module is licensed under its respective terms as stated in its folder.

For development services, technical support, or integration inquiries, visit:  
[https://codeforgex.studio](https://codeforgex.studio)