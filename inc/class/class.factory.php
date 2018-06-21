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
    
    $this->add_hooks();
  
  }
  
  
  /**
  *
  * Add hooks
  *
  **/
  
  private function add_hooks() {
    
    //take action after cred forms have been submitted
    add_action( 'cred_submit_complete' , array( $this , 'after_object_save' ) , 10 , 2 );
    
  }
  
  
  /**
  *
  * Take action after an object has been saved
  *
  **/
  
  public function after_object_save( $post_id , $form_data ) {
    
    $object = new BDTOS_Object( $post_id );
    $object->save_linked_field_values();
    
  }
  
  
}

$bdtos_factory = new BDTOS_Factory;
$auto_cred = new Auto_Cred;