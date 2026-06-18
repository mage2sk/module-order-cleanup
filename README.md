<!-- SEO Meta -->
<!--
  Title: Magento 2 Order Cleanup Extension: Delete Orders, Invoices & Shipments with Audit Log | Panth Infotech
  Description: Panth Order Cleanup adds a delete button, mass delete action, double confirmation, configurable safety controls, and a permanent audit log to Magento 2. Remove test orders, junk data, and unwanted transactions safely. Works on Magento 2.4.4 to 2.4.8 and PHP 8.1 to 8.4. Built by Top Rated Plus Magento developer Kishan Savaliya.
  Keywords: magento 2 delete orders, magento 2 order cleanup, magento 2 remove test orders, magento mass delete orders, magento order deletion extension, magento 2 delete invoices shipments, magento 2 order audit log, magento 2 order management, panth order cleanup, magento 2 order cleanup extension
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://kishansavaliya.com/magento-2-order-cleanup.html
-->

# Magento 2 Order Cleanup: Delete Orders, Invoices and Shipments with Safety Controls and Audit Log

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Admin Only](https://img.shields.io/badge/Themes-Admin%20Only-14b8a6)](https://magento.com)
[![Live Demo & Details](https://img.shields.io/badge/Live%20Demo%20%26%20Details-magento--2--order--cleanup-0D9488?style=flat)](https://kishansavaliya.com/magento-2-order-cleanup.html)
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--order--cleanup-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-order-cleanup)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)

> **Delete test orders and junk data from Magento 2 safely.** Panth Order Cleanup adds a delete button to every order view page, a mass delete action in the order grid, double confirmation with order ID verification, configurable safety controls over which data gets removed, and a permanent audit log that records every deletion.

**Product page:** [kishansavaliya.com/magento-2-order-cleanup.html](https://kishansavaliya.com/magento-2-order-cleanup.html)

---

## Quick Answer

**What is Panth Order Cleanup?** It is a Magento 2 extension that adds safe order deletion to the admin. Magento does not ship with order deletion. This module adds it with seven layers of safety controls so you can clean up test orders and junk data without risking real transactions.

**What does it add to my store?**

- A **delete button on every order view page** with configurable text and color.
- A **mass delete action** in the order grid for bulk cleanup.
- A **double confirmation modal** that requires typing the exact order number before deletion proceeds.
- **Configurable safety controls** over which related data (invoices, shipments, credit memos, payment transactions) gets removed.
- A **permanent deletion audit log** that records who deleted what, when, from which IP address, and by which method.

**Which themes are supported?** This is an admin-only module. It works the same on any frontend theme, whether Hyva, Luma, or custom.

**What does it need?** Magento 2.4.4 to 2.4.8, PHP 8.1 to 8.4, and the free `mage2kishan/module-core` package.

---

## Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** for custom modules, Hyva themes, performance work, M1 to M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success - 10+ Years Magento Experience
Adobe Certified - Hyva Specialist

</td>
<td width="50%" align="center">

### Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules - Theme Design - Migrations
Performance - SEO - Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Who Is It For](#who-is-it-for)
- [Key Features](#key-features)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [How It Works](#how-it-works)
- [Deletion Audit Log](#deletion-audit-log)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Who Is It For

- **Stores that ran test orders** during development and want to clear them before going live, without leaving dummy data in reports.
- **Merchants who received spam or fraud orders** and need a clean audit trail showing those records were intentionally removed.
- **Developers and agencies** who set up staging stores and need a quick way to clear order history between test runs.
- **Store owners who want control** over which admin users can delete orders, mass delete, and view the deletion log, all through Magento's ACL.
- **Any Magento store** that has accumulated junk data over time and wants a safe, logged way to remove it.

---

## Key Features

### Delete Button on Order View

- **Configurable delete button** added to every order detail page, with custom text and color set from admin config.
- **Confirmation modal** shows all data that will be removed before anything is deleted.
- **Type-to-confirm** requires the admin to type the exact order increment ID before the delete button becomes active, the same pattern GitHub uses for repository deletion.

### Mass Delete from Order Grid

- **Mass delete action** added to the order grid so you can select multiple orders and remove them in one step.
- **Confirmation dialog** before mass deletion, so selections can be reviewed.
- **Configurable safety limit** caps how many orders can be deleted in a single mass action (default: 50).

### Configurable Data Controls

- **Choose what gets deleted** alongside the order: invoices, shipments, credit memos, and payment transactions are each individually toggleable.
- **Restrict by order status** so only orders with certain statuses (for example, Pending or Canceled) can be deleted. Processing or Complete orders stay protected.
- **Quote cleanup** removes the associated quote and its items, addresses, payment, and shipping rates.

### Deletion Audit Log

- **Every deletion is logged** to the `panth_order_deletion_log` table with order details, customer name and email, grand total, status at deletion, admin username, IP address, deletion method (single or mass), and timestamp.
- **Log persists after deletion**, so the record remains for accounting and compliance even after the order data is gone.
- **Accessible from the admin menu** under Panth Infotech > Order Cleanup > Deletion Log.

### Safety Layers

- **Seven layers of protection**: ACL permissions, confirmation modal, type-to-confirm, status restrictions, mass delete limit, database transactions with rollback, and the permanent audit log.
- **Database transactions** wrap every deletion. If any step fails, everything rolls back, so partial deletions cannot happen.
- **Three ACL resources** control access separately: single order deletion, mass deletion, and log viewing.

### Admin-Only Module

- **No storefront code** is added. This module has zero impact on your frontend performance or theme.
- **Works with any frontend** theme including Hyva and Luma.
- **Translation ready** using Magento's `__()` function.

---

## Preview

### Mass Delete from Order Grid

![Mass Delete Orders](docs/mass-delete-action.png)

*Select multiple orders and choose "Delete Orders (Permanent)" from the mass actions dropdown.*

### Delete Button on Order View

![Delete Button on Order View](docs/delete-button-order-view.png)

*A configurable "Delete This Order" button appears on every order detail page.*

### Double Confirmation Modal

![Double Confirmation Modal](docs/double-confirmation-modal.png)

*Type the order number to confirm - prevents accidental deletions.*

### Deletion Audit Log

![Deletion Log Grid](docs/deletion-log-grid.png)

*Every deletion is logged with order details, admin user, IP address, method, and timestamp.*

### Admin Configuration

![Admin Configuration](docs/admin-configuration.png)

*Full control over button appearance, safety requirements, allowed statuses, and mass delete limits.*

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 to 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| Frontend Theme | Any (admin-only module) |
| Required Dependency | `mage2kishan/module-core` (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-order-cleanup
bin/magento module:enable Panth_Core Panth_OrderCleanup
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release from [Packagist](https://packagist.org/packages/mage2kishan/module-order-cleanup) or from the [product page](https://kishansavaliya.com/magento-2-order-cleanup.html).
2. Extract it to `app/code/Panth/OrderCleanup/` in your Magento install.
3. Make sure `Panth_Core` is installed too (required dependency).
4. Run the commands above starting from `bin/magento module:enable`.

### Verify Installation

```bash
bin/magento module:status Panth_OrderCleanup
# Expected: Module is enabled
```

After install, open:
```
Admin -> Sales -> Orders  (mass delete action in the grid)
Admin -> any order view   (delete button on the page)
Admin -> Panth Infotech -> Order Cleanup -> Deletion Log
Admin -> Stores -> Configuration -> Panth Extensions -> Order Cleanup
```

---

## Configuration

Go to **Stores -> Configuration -> Panth Extensions -> Order Cleanup**.

### General

| Setting | Group | Default | Description |
|---|---|---|---|
| Enable Order Cleanup | general | Yes | Master toggle for all order deletion features. |

### Delete Button on Order View

| Setting | Group | Default | Description |
|---|---|---|---|
| Show Delete Button on Order View | button | Yes | Add a delete button to every order detail page. |
| Button Text | button | Delete This Order | Text shown on the delete button. |
| Button Color | button | #DC2626 | Hex color for the delete button background. |

### Safety and Data Controls

| Setting | Group | Default | Description |
|---|---|---|---|
| Require Confirmation Modal | safety | Yes | Show a confirmation popup before deleting any order. |
| Require Typing Order ID to Confirm | safety | Yes | Admin must type the exact order increment ID before deletion is allowed. |
| Delete Related Invoices | safety | Yes | Also delete all invoices tied to the order. |
| Delete Related Shipments | safety | Yes | Also delete all shipments tied to the order. |
| Delete Related Credit Memos | safety | Yes | Also delete all credit memos tied to the order. |
| Delete Related Payment Transactions | safety | Yes | Also delete all payment transactions tied to the order. |
| Keep Deletion Log | safety | Yes | Log every deletion with order details, admin user, IP address, and timestamp. |
| Allowed Order Statuses for Deletion | safety | canceled, closed, holded, pending, fraud | Only orders with these statuses can be deleted. Leave empty to allow all statuses. |

### Mass Delete

| Setting | Group | Default | Description |
|---|---|---|---|
| Enable Mass Delete Action | mass_action | Yes | Add a delete option to the mass actions dropdown in the order grid. |
| Require Confirmation for Mass Delete | mass_action | Yes | Show a confirmation dialog before mass deleting orders. |
| Maximum Orders Per Mass Action | mass_action | 50 | Cap on how many orders can be deleted in a single mass action. |

---

## How It Works

### Single Order Deletion

1. Admin opens an order detail page.
2. Clicks the **Delete This Order** button.
3. Confirmation modal appears showing all data that will be removed.
4. Admin types the order increment ID to confirm (if that setting is on).
5. Clicks **Yes, Permanently Delete**.
6. Module checks: module enabled, order status allowed, ACL permission present.
7. A database transaction begins.
8. Related data is deleted in this order: invoices, shipments, credit memos, payment transactions, order items, addresses, payments, status history, tax, and quote data.
9. The order and its order grid record are deleted.
10. Transaction is committed.
11. Deletion is written to the audit log.
12. Admin is redirected to the order grid with a success message.

### Mass Deletion

1. Admin selects orders in the order grid.
2. Chooses **Delete Orders (Permanent)** from the mass actions dropdown.
3. Confirmation dialog appears with a warning.
4. Module checks the safety limit (default: 50 orders maximum).
5. Each order is processed through the same single-deletion flow.
6. A summary message reports success and failure counts.

---

## Deletion Audit Log

Open **Panth Infotech -> Order Cleanup -> Deletion Log** to view all deletions.

The log is stored in the `panth_order_deletion_log` table and captures:

| Field | Description |
|---|---|
| Order # | The deleted order's increment ID. |
| Customer | Customer name at time of deletion. |
| Email | Customer email address. |
| Grand Total | Order total with currency. |
| Status at Deletion | Order status when it was deleted. |
| Items | Number of items in the order. |
| Invoices Deleted | Whether invoices were also deleted. |
| Shipments Deleted | Whether shipments were also deleted. |
| Deleted By | Admin username who performed the deletion. |
| Method | "single" from the order view, or "mass" from the grid. |
| IP Address | Admin user's IP address. |
| Deleted At | Exact timestamp of deletion. |

The log persists permanently. Even after order data is gone, the log record remains for accounting, compliance, and dispute resolution.

---

## FAQ

### Is this safe to use on a live production store?

Yes. The module includes seven layers of safety controls: ACL permissions, confirmation modal, type-to-confirm, status restrictions, mass delete limit, database transactions with automatic rollback, and a permanent audit log. It was designed with production use in mind.

### Can I recover a deleted order?

No. Deletion is permanent. The module keeps a snapshot in the deletion log (customer name, email, totals, item count, timestamps), which can serve as an audit record. There is no undo. Back up your database before using this on a store with real order history.

### Does it delete from third-party extension tables?

No. The module only removes from core Magento sales and quote tables. Records added by other extensions (ERP sync logs, custom shipping tables, etc.) may be left as orphaned rows. Check with your other extension vendors if this matters.

### Does deleting orders affect reports?

Yes. Deleted orders are no longer in Magento reports because the data no longer exists. The deletion log can serve as an alternative record for accounting purposes.

### Can I control which admin roles can delete?

Yes. The module registers three separate ACL resources. Assign them per role under System -> Permissions -> User Roles:
- `Panth_OrderCleanup::delete_order` for the single delete button.
- `Panth_OrderCleanup::mass_delete` for mass deletion from the grid.
- `Panth_OrderCleanup::view_log` for viewing the deletion log.

### Does it work with Hyva storefronts?

Yes. This is an admin-only module. It has no frontend code, so it works the same regardless of which storefront theme you use.

### What happens if a deletion fails halfway through?

The entire deletion rolls back. Every deletion is wrapped in a database transaction, so either all related data is removed cleanly, or nothing changes at all. An error message is shown and the system log has the details.

### Can I change the delete button text and color?

Yes. The Button Text and Button Color fields in the Delete Button group under Configuration let you set any text and any hex color for the button.

### Does the module add a scheduled job?

Yes. There is a cron job that sends a periodic heartbeat check. It does not auto-delete any orders. All deletions are always manual admin actions.

---

## Support

| Channel | Contact |
|---|---|
| Product Page | [kishansavaliya.com/magento-2-order-cleanup.html](https://kishansavaliya.com/magento-2-order-cleanup.html) |
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-order-cleanup/issues](https://github.com/mage2sk/module-order-cleanup/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme work**, **store migrations**, or **performance tuning**? Get a free quote in 24 hours:

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com/magento-2-order-cleanup.html">
    <img src="https://img.shields.io/badge/View%20Product%20Page-magento--2--order--cleanup-0D9488?style=for-the-badge" alt="View Product Page" />
  </a>
</p>

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** ([kishansavaliya.com](https://kishansavaliya.com)), a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency that builds high quality, security focused extensions and themes for both Hyva and Luma storefronts. The extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management, with each module built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on our [Magento extensions page](https://kishansavaliya.com/magento-extensions.html) or on [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

| Resource | Link |
|---|---|
| **Product Page** | [magento-2-order-cleanup.html](https://kishansavaliya.com/magento-2-order-cleanup.html) |
| **Packagist** | [mage2kishan/module-order-cleanup](https://packagist.org/packages/mage2kishan/module-order-cleanup) |
| **GitHub** | [mage2sk/module-order-cleanup](https://github.com/mage2sk/module-order-cleanup) |
| **Website** | [kishansavaliya.com](https://kishansavaliya.com) |
| **Free Quote** | [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote) |
| **Upwork (Top Rated Plus)** | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| **Upwork Agency** | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |
| **Email** | kishansavaliyakb@gmail.com |
| **WhatsApp** | +91 84012 70422 |

---

<p align="center">
  <strong>Need to clean up test orders from your Magento store?</strong><br/>
  <a href="https://kishansavaliya.com/magento-2-order-cleanup.html">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20See%20Order%20Cleanup%20%E2%86%92-Product%20Page%20%26%20Details-DC2626?style=for-the-badge" alt="See Order Cleanup" />
  </a>
</p>

---

**SEO Keywords:** magento 2 delete orders, magento 2 order cleanup, magento 2 order cleanup extension, magento 2 remove test orders, magento 2 mass delete orders, magento 2 order deletion extension, magento 2 delete invoices shipments, magento 2 order audit log, magento 2 order management extension, magento 2 delete order safely, magento order cleanup module, magento 2 double confirmation delete, magento 2 order status restriction, magento 2 acl order delete, magento delete order admin, magento 2 order deletion log, panth order cleanup, panth infotech, mage2kishan order cleanup, hire magento developer, top rated plus upwork, kishan savaliya magento, custom magento development, magento 2.4.8 delete orders, php 8.4 magento order cleanup
