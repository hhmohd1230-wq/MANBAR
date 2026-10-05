<?php
/**
 * Shared-hosting entry point.
 *
 * MANBAR normally uses public/ as its document root. Some managed hosts keep
 * public_html fixed, so this small front controller lets the repository be
 * deployed there while the root .htaccess keeps private application files
 * inaccessible.
 */
require __DIR__ . '/public/index.php';
