<?php
/**
 * Plugin Name: Luma Meta Viewer
 * Plugin URI: https://www.luma-retail.com/
 * Description: Lightweight on-demand meta viewer for products, variations, users, and orders.
 * Version: 0.9.1
 * Author: Luma Solutions
 * Author URI: https://www.luma-retail.com/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.8
 * License: GPL-2.0-or-later
 * Text Domain: luma-meta-viewer
 */

namespace Luma\Metaviewer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/meta-renderer.php';
require_once __DIR__ . '/includes/ajax.php';
require_once __DIR__ . '/includes/admin-ui.php';
