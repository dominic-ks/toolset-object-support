<?php

/**
*
* The plugin facotry / bootstrapper class
*
**/

class BDTOS_Factory {
  
  
  /**
  *
  * The constructor
  *
  **/
  
  public function __construct() {
    
    include_once( 'class.object.php' );
    include_once( 'class.user.php' );
    
    $this->add_hooks();
  
  }
  
  
  /**
  *
  * Add hooks
  *
  **/
  
  private function add_hooks() {
    
    //take action after cred forms have been submitted
    add_action( 'cred_submit_complete' , array( $this , 'after_object_save' ) , 9999 , 2 );
    
    //take action when a post is saved, not by CRED
    add_action( 'save_post' , array( $this , 'after_post_save' ) , 999 , 1 );
    
    //register post types and classes
    add_action( 'init' , array( $this , 'register_class_names' ) , 10 );
    
    //after a child post is saved
    add_action( 'wpcf_relationship_save_child' , array( $this , 'after_child_post_saved' ) , 999 , 2 );
    
  }
  
  
  /**
  *
  * Take action after an object has been saved
  *
  **/
  
  public function after_object_save( $post_id , $form_data ) {
    
    $object_type = $this->get_post_type_class( get_post_type( $post_id ) );
    $object = new $object_type( $post_id );
    $object->save_linked_field_values();
    $object->cred_submit_complete( $post_id , $form_data );
    
  }
  
  
  /**
  *
  * Take action when a post is saved, generically
  *
  **/
  
  public function after_post_save( $post_id ) {
    
    $object_type = $this->get_post_type_class( get_post_type( $post_id ) );
    $object = new $object_type( $post_id );
    $object->after_post_save( $post_id );
    
  }
  
  
  /**
  *
  * Take action when a post is saved, generically
  *
  **/
  
  public function after_child_post_saved( $post_id ) {
    
    $object_type = $this->get_post_type_class( get_post_type( $post_id ) );
    $object = new $object_type( $post_id );
    $object->after_child_post_saved( $post_id );
    
  }
  
  
  /**
  *
  * Find a registered post type class
  *
  **/
  
  public function get_post_type_class( $post_type ) {
    
    if( ! isset( $this->object_types[ $post_type ] ) ) {
      return 'BDTOS_Object';
    } else {
      return $this->object_types[ $post_type ];
    }
    
  }
  
  
  /**
  *
  * Dump registered post type classes
  *
  **/
  
  public function dump_types() {
    
    var_dump( apply_filters( 'bdtos_object_types' , array() ) );
    
  }
  
  
  /**
  *
  * Register classes by post type
  *
  **/
  
  public function register_class_names() {
    
    $this->object_types = apply_filters( 'bdtos_object_types' , array() );
    
  }
  
  
}

$bdtos_factory = new BDTOS_Factory;
$auto_cred = new Auto_Cred;