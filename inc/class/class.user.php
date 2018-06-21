<?php

/**
*
* Class of the Toolset User
*
**/

class BDTOS_User {
  
  
  /**
  *
  * The class constructor
  *
  * @param int $user_id the user's ID
  *
  * @return bool|null returns false if no user id is provided and the user is logged in, otherwise returns nothing
  *
  **/
  
  public function __construct( $user_id = null ) {
    
    if( $user_id === null && ! is_user_logged_in() ) {
      return false;
    }
    
    if( $user_id === null && is_user_logged_in() ) {
      $this->user_id = get_current_user_id();
    }
    
    if( $user_id !== null ) {
      $this->user_id = $user_id;
    }
    
  }
  
  
  /**
  *
  * Get a user's custom field value
  *
  **/
  
  public function get_custom_field( $key , $single = true ) {
    
    return get_user_meta( $this->user_id , 'wpcf-' . $key , $single );
    
  }
  
  
}