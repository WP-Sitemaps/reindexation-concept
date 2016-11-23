<?php
/*
Plugin Name: WP Sitemap Indexer POC
Description: Re-index the sitemaps
Author: jipmoors
Version: 1.0
Author URI: https://yoast.com/about-us/jip-moors/
*/

require_once 'classes/class-wp-sitemap-indexer.php';
require_once 'classes/class-wp-sitemap-indexer-custom.php';
require_once 'classes/class-wp-sitemap-indexer-post-type.php';

add_action( 'admin_init', function() {
	$sitemap_indexer = new WP_Sitemap_Indexer();
	$sitemap_indexer->run();
});
