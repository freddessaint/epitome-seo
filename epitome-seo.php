<?php
/**
 * Epitome SEO
 *
 * Easily handle SEO metadata for every pages of a theme.
 * Designed to fit into WordPress as a core functionality.
 *
 * Plugin Name: Epitome SEO
 * Plugin URI:  http://www.freddessaint.com/
 * Description: Easily handle SEO metadata for every pages of a theme. Designed to fit into WordPress as a core functionality.
 * Version:     1.2
 * Author:      Fred Dessaint
 * Author URI:  http://www.freddessaint.com/
 * License:     GPLv2
 * License URI: http://choosealicense.com/licenses/gpl-2.0/
 */

/**
 * Load the MetaBox class
 *
 * @since 1.1
 */
require_once(plugin_dir_path(__FILE__).'includes/MetaBox.php');

/**
 * Instanciate a new plugin object
 *
 * @since 1.2
 */
if(!class_exists('Epitome_SEO')) {
	require_once(plugin_dir_path(__FILE__).'Epitome_SEO.class.php');
	Epitome_SEO::get_instance();
}
