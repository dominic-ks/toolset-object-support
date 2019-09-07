<?php

/**
*
* Plugin Name: Toolset Object Support
* Plugin URI: https://github.com/dominic-ks/toolset-object-support
* Description: This plugin houses provides a structure for common functions required when working with objects for post types created with Toolset Types
* Version: 0.3.1
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


/**
*
* Get BDTOS Object
*
**/

function bdtos_get_bdtos( $id ) {
  
  $types = apply_filters( 'bdtos_object_types' , array() );
  
  $type = 'BDTOS_Object';
  
  if( isset( $types[ get_post_type( $id ) ] ) ) {
    $type = $types[ get_post_type( $id ) ];
  }
  
  return new $type( $id );
  
}
