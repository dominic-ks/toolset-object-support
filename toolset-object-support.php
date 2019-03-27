<?php

/**
*
* Plugin Name: Toolset Object Support
* Plugin URI: https://github.com/dominic-ks/toolset-object-support
* Description: This plugin houses provides a structure for common functions required when working with objects for post types created with Toolset Types
* Version: 0.2.0
* Author: Be Devious Web Development
* Author URI: Plugin URI: https://www.bedevious.co.uk/
* License: GNU GENERAL PUBLIC LICENSE V3
*
**/

/**
*
* Include all required files for the plugin
*
* @category Administration
*
**/

include_once( 'inc/class/class.factory.php' );


/**
*
* Enqueue styles and scripts
*
* @category Administration
*
**/

function bdtos_enqueue_styles_and_scripts() {

}

add_action( 'wp_enqueue_scripts', 'bdtos_enqueue_styles_and_scripts' );