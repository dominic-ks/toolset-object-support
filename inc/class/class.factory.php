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
    
    //take action when a post is deleted
    add_action( 'after_trash_post' , array( $this , 'after_delete_post' ) , 10 , 1 );
    add_action( 'before_delete_post' , array( $this , 'after_delete_post' ) , 10 , 1 );
    
    //register post types and classes
    add_action( 'init' , array( $this , 'register_class_names' ) , 10 );
    
    //fire after REST GET
    add_action( 'rest_pre_echo_response' , array( $this , 'rest_pre_echo_response' ) , 10 , 3 );
    
    //fire after REST create / update
    add_action( 'init' , function() {
      foreach( apply_filters( 'bdtos_object_types' , array() ) as $post_type => $object_type ) {
       add_action( 'rest_after_insert_' . $post_type , array( $this , 'rest_post_created_updated' ) , 10 , 3 );
      }
    } , 99 );
    
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
  * Take action after a post is deleted
  *
  **/
  
  public function after_delete_post( $post_id ) {
    
    $object_type = $this->get_post_type_class( get_post_type( $post_id ) );
    $object = new $object_type( $post_id );
    $object->after_delete_post( $post_id );
    
  }
  
  
  /**
  *
  * Take action on REST GET
  *
  **/
  
  public function rest_pre_echo_response( $response , $object , $request ) {
    
    if( ! isset( $response['id'] ) ) {
      return $response;
    }
    
    $object_type = $this->get_post_type_class( get_post_type( $response['id'] ) );
    $object = new $object_type( $response['id'] );
    
    return $object->rest_pre_echo_response( $response , $object , $request );
    
  }
  
  
  /**
  *
  * Take action on REST create_update
  *
  **/
  
  public function rest_post_created_updated( $post , $request , $new ) {
    
    $object_type = $this->get_post_type_class( get_post_type( $post->ID ) );
    $object = new $object_type( $post->ID );
    
    return $object->rest_post_created_updated( $post , $request , $new );
    
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
  * Register classes by post type
  *
  **/
  
  public function register_class_names() {
    $this->object_types = apply_filters( 'bdtos_object_types' , array() );
  }
  
  
}

$bdtos_factory = new BDTOS_Factory;