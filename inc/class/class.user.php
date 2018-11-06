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
    
    $this->user = get_user_by( 'id' , $this->user_id );
    
  }
  
  
  /**
  *
  * Get a user's custom field value
  *
  **/
  
  public function get_custom_field( $key , $single = true ) {
    
    return get_user_meta( $this->user_id , 'wpcf-' . $key , $single );
    
  }
  
  
  /**
  *
  * Get user objects
  *
  **/
  
  public function get_user_objects( $args ) {
    
    //default args
    $args = wp_parse_args( $args , array(
      'type' => null,
      'parent_id' => null,
      'relationship_slug' => null,
      'output_object' => 'BDTOS_Object', 
      'args' => array(), 
      'link' => false,
    ));
    
    //standard query args
    $query_args['posts_per_page'] = -1;
    $query_args['post_type'] = $args['type'];
    $query_args['author'] = $this->user_id;
    
    //if we're looking for child objects then we'll add this
    if( $args['parent_id'] !== null && $args['relationship_slug'] !== null ) {
      $query_args['toolset_relationships'][] = array(
        'role' => 'parent',
        'related_to' => $args['parent_id'],
        'relationship' => $args['relationship_slug'],
      );
    }
    
    //if we have args, merge them
    if( ! empty( $args['args'] ) ) {
      $query_args = array_merge( $query_args , $args['args'] );
    }
    
    //run the query
    $linked_object_query = new WP_Query( $query_args );
    
    $objects = $linked_object_query->get_posts();
    
    $objects_array = array();
    
    foreach( $objects as $object ) {
      
      $objects_array[] = new $args['output_object']( $object->ID );
      
    }
    
    return $objects_array;
    
  }
  
  
}