<?php

/**
*
* Class of the toolset object support generic object
*
**/

class BDTOS_Object {
  
  
  /**
  *
  * The constructor
  *
  **/
  
  public function __construct( $object_id = null ) {

    if( $object_id === null ) {
      return false;
    }
    
    $this->ID = $object_id;
    $this->post = $this->load_post();
    
    $this->refresh_custom_fields();
    
    $this->init();
  
  }
  
  
  /**
  *
  * Initialise the object
  *
  **/
  
  private function init() {
    
    return false;
    
  }
  
  
  /**
  *
  * Load the post object
  *
  **/
  
  private function load_post() {
    
    return get_post( $this->ID );
    
  }
  
  
  /**
  *
  * Refresh custom field values
  *
  **/
  
  public function refresh_custom_fields() {
    
    $this->custom_fields = $this->load_custom_fields();
    
  }
  
  
  /**
  *
  * Load all custom fields
  *
  **/
  
  private function load_custom_fields() {
    
    global $auto_cred;
    
    $fields = $auto_cred->get_all_fields( get_post_type( $this->ID ) );
    $custom_fields = array();
    
    if( empty( $fields ) ) {
      return $custom_fields;
    }
    
    foreach( $fields as $field_group ) {
    
      foreach( explode( ',' , $field_group ) as $field ) {

        if( $field === '' ) {
          continue;
        }

        $custom_fields[ $field ] = get_post_meta( $this->ID , 'wpcf-' . $field , true );

      }
      
    }
      
    return $custom_fields;
    
  }
  
  
  /**
  *
  * Set the title of the post
  *
  **/
  
  public function set_post_title( $title ) {
    
    $post = array(
      'ID' => $this->ID,
      'post_title' => $title,
    );
    
    return wp_update_post( $post );
    
  }
  
  
  /**
  * 
  * Whether or not an object should have it's title auto-set
  *
  **/
  
  private function auto_set_post_title() {
    
    if( in_array( get_post_type( $this->ID ) , array(
      '',
    ))) {
      return true;
    }
    
    return false;
    
  }
  
  
  /**
  *
  * Set grandparent post as parent post
  * @todo make meta keys generic
  *
  **/
  
  public function set_grandparent_as_parent( $parent_type , $grand_parent_type ) {
    
    $parent_id = wpcf_pr_post_get_belongs( $this->ID , $parent_type );
    $grandparent_id = wpcf_pr_post_get_belongs( $parent_id , $grand_parent_type );
    
    update_post_meta( $this->ID , '_wpcf_belongs_' . $parent_type . '_id' , $grandparent_id );
    
    return true;
    
  }
  
  
  /**
  *
  * Get bespoke fields
  * @todo load all post types as a bespoke field
  *
  **/
  
  private function get_bespoke_fields() {
    
    $fields = array(
      '_wpcf_belongs_game-week_id',
      '_wpcf_belongs_game_id',
      '_wpcf_belongs_prediction-set_id',
      '_wpcf_belongs_team_id',
      'wpcf-status',
    );
    
    return $fields;
    
  }
  
  
  /**
  *
  * Save linked field values
  *
  **/
  
  public function save_linked_field_values() {
    
    $bespoke_fields = $this->get_bespoke_fields();
    
    foreach( $bespoke_fields as $field ) {
      
      if( isset( $_POST[ $field ] ) ) {
        
        /**
        *
        * We need to sanitize this field dynamically somehow
        *
        * @todo
        *
        **/
        
        update_post_meta( $this->ID , $field , $_POST[ $field ] );
        
        /**
        *
        * Do an acton after saving a bespoke field, specific to that field
        *
        * @param int the post ID the meta is being saved for
        * @param str the meta key for the field
        * @param mixed the value being saved
        *
        **/
        
        do_action( 'after_' . $field . '_save' , $this->ID , $field , $_POST[ $field ] );
        
      }
      
    }
    
    return true;
    
  }
  
  
  /**
  *
  * Has object assigned
  *
  */
  
  public function has_object_assigned( $type , $parent_type ) {
    
    if( count( $this->get_linked_objects( $type , $parent_type ) ) === 0 ) {
      return 'false';
    }
    
    return 'true';
    
  }
  
  
  /**
  *
  * Get parent object
  *
  **/
  
  public function get_parent_id( $parent_type ) {
    
    return wpcf_pr_post_get_belongs( $this->ID , $parent_type );
    
  }
  
  
  /**
  *
  * Get linked objects
  *
  **/
  
  public function get_linked_objects( $type , $parent_type , $output_object = false , $args = array() ) {
    
    if( ! $output_object ) {
      $output_object = 'BDTOS_Object';
    }
    
    $args['posts_per_page'] = -1;
    $args['post_type'] = $type;
    $args['meta_query'][] = array(
      array(
        'key' => '_wpcf_belongs_' . $parent_type . '_id',
        'value' => $this->ID,
        'compare' => '=',
      ),
    );
    
    $objects = get_posts( $args );
    
    $objects_array = array();
    
    foreach( $objects as $object ) {
      
      $objects_array[] = new $output_object( $object->ID );
      
    }
    
    return $objects_array;
    
  }
  
  
  /**
  *
  * run code on CRED submit
  *
  **/
  
  public function cred_submit_complete( $post_id , $form_data ) {
    
    return false;
    
  }
  
  
  /**
  *
  * Run on general post save
  *
  **/
  
  public function after_post_save( $post_id ) {
    
    return false;
    
  }

 
  
}