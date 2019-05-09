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
  
  public function __construct( $object_id = null , $link = false ) {

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
  
  public function set_grandparent_as_parent( $parent_type , $grand_parent_type , $target ) {
    
    $parent_id = toolset_get_related_post( $this->ID , $parent_type );
    $grandparent_id = toolset_get_related_post( $parent_id , $grand_parent_type );
    
    toolset_connect_posts( $target , $grandparent_id , $this->ID );
    
    return true;
    
  }
  
  
  /**
  *
  * Get bespoke fields
  * @todo load all post types as a bespoke field
  *
  **/
  
  public function get_bespoke_fields() {
    
    return apply_filters( 'bdtos_get_bespoke_fields' , array() );
    
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
        
        do_action( 'after_' . $field . '_save' , $this->ID , $field , $_POST[ $field ] , $this );
        
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
    
    $relationships = $this->get_linked_object_types( 'parent' );
    
    if( count( $relationships ) === 0 ) {
      return false;
    }
    
    if( ! isset( $relationships[ $parent_type ] ) ) {
      return false;
    }
    
    $relationship_slugs = $relationships[ $parent_type ];
    $relationship_slug = $relationship_slugs[0];
    
    return toolset_get_related_post( $this->ID , $relationship_slug );
    
  }
  
  
  /**
  *
  * Get linked objects
  *
  **/
  
  public function get_linked_objects( $type = null , $parent_type = null , $output_object = 'BDTOS_Object' , $args = array() , $link = false ) {
    
    $cache_args = array(
      'type' => $type,
      'parent_type' => $parent_type,
      'output_object' => $output_object,
      'args' => $args,
      'link' => $link,
    );
    
    $cache_check = $this->get_cached_request( $cache_args );
    
    if( $cache_check !== false ) {
      return $cache_check;
    }
    
    if( ! $type ) {
      $type = 'any';
    } 
    
    if( ! $parent_type ) {
      $parent_type = get_post_type( $this->ID );
    }
    
    $args['posts_per_page'] = -1;
    $args['post_type'] = $type;
    
    /**
    *
    * Updated WP_Query args for Toolset Object relationships
    *
    **/
    
    $relationships = $this->get_linked_object_types();
    
    if( count( $relationships ) !== 0 ) {
    
      foreach( $relationships[ $type ] as $relationship_slug ) {

        $args['toolset_relationships'][] = array(
          'role' => 'child',
          'related_to' => $this->ID,
          'relationship' => $relationship_slug,
        );

      }
      
    }
        
    $linked_object_query = new WP_Query( $args );
    
    $objects = $linked_object_query->get_posts();
    
    $objects_array = array();
    
    foreach( $objects as $object ) {
      
      $objects_array[] = new $output_object( $object->ID , $link );
      
    }
    
    $this->cache_request( $cache_args , $objects_array );
    
    return $objects_array;
    
  }
  
  
  /**
  *
  * Get Toolset relationship query array
  *
  **/
  
  public function get_toolset_replationship_query_array( $type = null , $parent_type = null ) {
    
    if( ! $parent_type ) {
      $parent_type = get_post_type( $this->ID );
    }
    
    $relationships = $this->get_linked_object_types();
    
    if( count( $relationships ) === 0 ) {
      return array();
    }
    
    $relationships_query = array();
    
    foreach( $relationships[ $type ] as $relationship_slug ) {

      $relationships_query[] = array(
        'role' => 'child',
        'related_to' => $this->ID,
        'relationship' => $relationship_slug,
      );

    }
    
    return $relationships_query;
    
  }
  
  
  /**
  *
  * Link objects
  *
  **/
  
  public function link_with_object( $id , $type , $this_is_child = true ) {
    
    if( $this_is_child ) {
      $link_types = 'parent';
    }
    
    if( ! $this_is_child ) {
      $link_types = 'child';
    }
    
    $relationships = $this->get_linked_object_types( $link_types );
    
    if( count( $relationships ) === 0 ) {
      return false;
    }
    
    $relationship_slugs = $relationships[ $type ];
    
    foreach( $relationship_slugs as $relationship_slug ) {
    
      if( ! $this_is_child ) {
        return toolset_connect_posts( $relationship_slug , $this->ID , $id );
      }

      return toolset_connect_posts( $relationship_slug , $id , $this->ID );
      
    }
    
  }
  
  
  /**
  *
  * Remove the link with this post and another
  *
  **/
  
  public function unlink_from_post( $id , $type , $this_is_child = true ) {
    
    if( $this_is_child ) {
      $link_types = 'parent';
    }
    
    if( ! $this_is_child ) {
      $link_types = 'child';
    }
    
    $relationships = $this->get_linked_object_types( $link_types );
    
    if( count( $relationships ) === 0 ) {
      return false;
    }
    
    $relationship_slugs = $relationships[ $type ];
    
    foreach( $relationship_slugs as $relationship_slug ) {
    
      if( ! $this_is_child ) {
        return toolset_disconnect_posts( $relationship_slug , $this->ID , $id );
      }

      return toolset_disconnect_posts( $relationship_slug , $id , $this->ID );
      
    }
    
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
  
  
  /**
  *
  * Get the linked object types
  *
  **/
  
  public function get_linked_object_types( $type = 'child' ) {
    
    return $all_relations = toolset_get_related_post_types( $type , get_post_type( $this->ID ) );
    
  }
  
  
  /**
  *
  * Get the parent object of a type
  *
  **/
  
  public function get_parent_object_id( $type ) {
    
    return toolset_get_related_post( $this->ID , $type . '-' . get_post_type( $this->ID ) );
    
  }
  
  
  /**
  *
  * Get a cached request for this object
  *
  **/
  
  public function get_cached_request( $args = array() ) {
    
    $key = 'bdtos-cache-' . hash_hmac( 'md5' , json_encode( $args ) , TOOLSET_CASH_SALT );
    
    $current_cache = get_post_meta( $this->ID , $key , true );
    
    if( $current_cache === '' ) {
      return false;
    }
    
    return $current_cache;
    
  }
  
  
  /**
  *
  * Cache a request
  *
  **/
  
  public function cache_request( $args = array() , $value ) {
    
    $key = 'bdtos-cache-' . hash_hmac( 'md5' , json_encode( $args ) , TOOLSET_CASH_SALT );
    
    return update_post_meta( $this->ID , $key , $value );
    
  }
 
  
}
