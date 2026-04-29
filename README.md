# Luma Meta Viewer

Luma Meta Viewer is a lightweight WordPress admin debugging plugin for inspecting meta data on demand.

It adds a simple "Show Meta" interface in admin areas and loads results via AJAX, so there is no frontend UI noise and minimal overhead.

## Features

- View post/product meta in the editor
- View core order fields (status, totals, customer, payment, dates), plus order and item meta (WooCommerce)
- View user meta on profile pages
- Admin-only access with nonce-protected AJAX requests
- On-demand loading to keep performance impact low

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce (optional, only needed for order/product-specific data)

## Installation

1. Copy this plugin folder into `wp-content/plugins/`.
2. Activate **Luma Meta Viewer** in WordPress Plugins.
3. Open a product, order, or user profile in wp-admin.
4. Click the meta viewer button to load data.

## Notes

- Intended for development, debugging, and support workflows.
- Access is restricted to administrators (`manage_options`).

## License

GPL-2.0-or-later
