# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.13] - 2026-10-04

### Fixed
- The "Delete This Order" button is placed in the order view action bar next to Back again. It was left outside the bar because the bar is built after the page loads.
- Closing the confirmation dialog with Escape clears the typed order number and disables the confirm button, the same as Cancel. Focus returns to the delete button.
- The confirmation dialog only lists invoices, shipments, credit memos and payment transactions when they are set to be deleted.
- Deletion Log grid: Invoices Deleted and Shipments Deleted show Yes/No instead of 1/0, Deleted At uses the configured date format, and Grand Total uses the currency of the deleted order.

### Added
- Credit Memos Deleted column and filter on the Deletion Log grid.
- The confirmation dialog is announced as a modal dialog to screen readers and keeps keyboard focus inside while open.
- "Require Confirmation for Mass Delete" and "Maximum Orders Per Mass Action" are hidden while the mass delete action is disabled.
