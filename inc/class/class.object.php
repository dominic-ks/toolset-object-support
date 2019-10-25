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
  
  public function init() {
    
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
  
  public function load_custom_fields() {
    
    $fields = $this->get_all_fields();
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
  
  private function get_bespoke_fields() {
    $fields = array();
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
        
        update_post_meta( $this->ID , $field , $_REQUEST[ $field ] );
        
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
    
    if( $parent_type === '' || ! $parent_type ) {
      return false;
    }
    
    $relationship_slugs = $relationships[ $parent_type ];
    $relationship_slug = $relationship_slugs[0];
    
    return toolset_get_related_post( $this->ID , $relationship_slug , 'parent' , array( 'post_status' => array_merge(
      get_post_statuses(), array( 'private' ))));
    
  }
  
  
  /**
  *
  * Get linked objects
  *
  * @todo re-write this function because it's args are terrible
  *
  **/
  
  public function get_linked_objects( $type = null , $parent_type = null , $output_object = 'BDTOS_Object' , $args = array() , $link = false ) {
    
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
    
    return $objects_array;
    
  }
  
  
  /**
  *
  * Link objects
  *
  **/
  
  public function link_with_object( $id , $type = false , $this_is_child = true ) {
    
    if( $this_is_child ) {
      $link_types = 'parent';
    }
    
    if( ! $this_is_child ) {
      $link_types = 'child';
    }
    
    if( ! $type ) {
      $type = get_post_type( $id );
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
  * Take action on REST GET
  *
  **/
  
  public function rest_pre_echo_response( $response , $object , $request ) {
    return $response;
  }
  
  
  /**
  *
  * Take action on REST create / update
  *
  **/
  
  public function rest_post_created_updated( $post , $request , $new ) {
    return true;
  }
  
  
  /**
  *
  * Take action after a post is deleted
  *
  **/
  
  public function after_delete_post( $post_id ) {
    
    $relationships = $this->get_linked_object_types();
    
    if( count( $relationships ) !== 0 ) {
      foreach( $relationships as $type => $relationship_info ) {
        
        $children_objects = $this->get_linked_objects( $type );
        
        foreach( $children_objects as $child_object ) {
          wp_delete_post( $child_object->ID );
        }
        
      }
    }
    
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
	* Get all the field groups
	*
	**/
	
	public function get_all_field_groups() {
		
		$args = array(
			'posts_per_page' => -1,
			'post_type' => 'wp-types-group',
			'meta_query' => array(
				'relation' => 'OR',
        array(
          'key' => '_wp_types_group_post_types',
          'value' => get_post_type( $this->ID ),
          'compare' => 'LIKE',
        ),
			),
		);
		
		foreach( $this->get_object_terms() as $term ) {
			
			$args['meta_query'][] = array(
				'key' => '_wp_types_group_terms',
				'value' => ',' . $term->term_id . ',',
				'compare' => 'LIKE',
			);
			
		}
		
		return get_posts( $args );
		
	}
	
	
	/**
	*
	* Get all the fields for this post
	*
	**/
	
	public function get_all_fields() {
		
		$field_groups = $this->get_all_field_groups();
    
    $fields = array();
		
		foreach( $field_groups as $field_group ) {
			$fields[ $field_group->ID ] = get_post_meta( $field_group->ID , '_wp_types_group_fields' , true );
		}
		
		return $fields;
		
	}
	
	
	/**
	*
	* Get the current object's taxonomies
	*
	**/
	
	private function get_object_taxonomies() {
		global $post;
		return get_object_taxonomies( get_post_type( $post ) );
	}
	
	
	/**
	*
	* Get the current object's terms
	*
	**/
	
	private function get_object_terms() {
		$taxonomies = $this->get_object_taxonomies();
		return wp_get_object_terms( get_the_ID() , $taxonomies );
	}
 
  
}
