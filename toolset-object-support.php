<?php

/**
*
* Plugin Name: Toolset Object Support
* Plugin URI: https://www.bedevious.co.uk/
* Description: NB - current version developed for The Agility Club
* Version: 0.2.4
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