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
    
    //take action when a post is saved by the WP All Import plugin
    add_action( 'pmxi_saved_post' , array( $this , 'pmxi_saved_post' ) , 999 , 1 );
    
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
    
    //after a child post is saved
    add_action( 'wpcf_relationship_save_child' , array( $this , 'after_child_post_saved' ) , 999 , 2 );
    
    //register rest fields
    add_action( 'rest_api_init' , array( $this , 'add_child_post_type_counts' ));
    add_action( 'rest_api_init' , array( $this , 'add_child_posts' ));
    add_action( 'rest_api_init' , array( $this , 'rest_add_parent_post_ids' ));
    add_action( 'rest_api_init' , array( $this , 'add_registered_custom_fields' ));
    
    add_action( 'init' , array( $this , 'register_rest_field_filters' ) , 15 );
    
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
    $object->clear_cache();
    
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
    $object->clear_cache();
    
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
    $object->clear_cache();
    
  }
  
  
  /**
  *
  * Take action when a post is updated by WP All Import
  *
  **/
  
  public function pmxi_saved_post( $post_id ) {
    
    $object_type = $this->get_post_type_class( get_post_type( $post_id ) );
    $object = new $object_type( $post_id );
    
    if( ! method_exists( $object , 'pmxi_saved_post' ) ) {
      return;
    }
    
    $object->pmxi_saved_post( $post_id );
    $object->clear_cache();
    
  }
  
  
  /**
  *
  * Take action after a post is deleted
  *
  **/
  
  public function after_delete_post( $post_id ) {
    
    if( ! apply_filters( 'bdtos_auto_delete_children' , false )) {
      return;
    }
    
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
    
    remove_action( 'rest_pre_echo_response' , array( $this , 'rest_pre_echo_response' ) , 10 , 3 );
    
    $object_type = $this->get_post_type_class( get_post_type( $post->ID ) );
    $object = new $object_type( $post->ID );
    return $object->rest_post_created_updated( $post , $request , $new );
    
  }
  
  
  /**
  *
  * Add child post type counts
  *
  **/
  
  public function add_child_post_type_counts() {
    
    $relationships = array();
    $post_types = get_post_types();
    
    foreach( $post_types as $post_type ) {
      //toolset_get_related_post_types() returns an array in format [ $child_post_type => $relationship_slug[] ]
      $relationships[ $post_type ] = toolset_get_related_post_types( 'child' , $post_type );
    }
    
    foreach( $relationships as $post_type => $relationship_array ) {
      foreach( $relationship_array as $child_post_type => $relationship_slugs ) {
        foreach( $relationship_slugs as $relationship_slug ) {
        
          $field_name = str_replace( '-' , '_' , $child_post_type ) . '_count';

          register_rest_field( $post_type , $field_name , array(

            'get_callback' => function( $object , $fieldname , $request , $type ) {
              $object = bdtos_get_bdtos( $object['id'] );
              return count( $object->get_linked_objects( str_replace( '_' , '-' , str_replace( '_count' , '' , $fieldname ))));
            },

            'update_callback' => function( $value , $post , $fieldname ) {
              return new WP_Error( 'relationship_error' , 'You cannot update an object child count' , array( 'status' => 404 ));
            },

            'schema' => array(
              'description' => __( 'The number of ' . $child_post_type . 's attached to the ' . $post_type . ' via the ' . $relationship_slug . ' relationship.' ),
              'type' => $post_type,
            ),

          ));
        
        }
      }      
    }
    
  }
  
  
  /**
  *
  * Add child posts
  *
  **/
  
  public function add_child_posts() {
    
    $relationships = array();
    $post_types = get_post_types();
    
    foreach( $post_types as $post_type ) {
      //toolset_get_related_post_types() returns an array in format [ $child_post_type => $relationship_slug[] ]
      $relationships[ $post_type ] = toolset_get_related_post_types( 'child' , $post_type );
    }
    
    foreach( $relationships as $post_type => $relationship_array ) {
      foreach( $relationship_array as $child_post_type => $relationship_slugs ) {
        foreach( $relationship_slugs as $relationship_slug ) {
        
          $field_name = str_replace( '-' , '_' , $child_post_type );

          register_rest_field( $post_type , $field_name , array(

            'get_callback' => function( $object , $fieldname , $request , $type ) {
              
              $object = bdtos_get_bdtos( $object['id'] );
              $linked_objects = $object->get_linked_objects( str_replace( '_' , '-' , str_replace( '_count' , '' , $fieldname )));
              
              $return_array = array();
              
              foreach( $linked_objects as $linked_object ) {
                
                $child_post_data = $linked_object->rest_get_child_post_data_value( $object->ID );
                
                if( ! empty( $child_post_data )) {
                  $return_array[] = $child_post_data;
                }
                
              }
              
              return $return_array;
              
            },

            'update_callback' => function( $value , $post , $fieldname ) {
              return new WP_Error( 'relationship_error' , 'You cannot link posts in this way.' , array( 'status' => 404 ));
            },

            'schema' => array(
              'description' => __( 'The list of IDs of the ' . $child_post_type . 's attached to the ' . $post_type . ' via the ' . $relationship_slug . ' relationship.' ),
              'type' => $post_type,
            ),

          ));
        
        }
      }      
    }
    
  }
  
  
  /**
  *
  * Add parent post IDs
  *
  **/
  
  public function rest_add_parent_post_ids() {
    
    $relationships = array();
    $post_types = get_post_types();
    
    foreach( $post_types as $post_type ) {
      //toolset_get_related_post_types() returns an array in format [ $parent_post_type => $relationship_slug[] ]
      $relationships[ $post_type ] = toolset_get_related_post_types( 'parent' , $post_type );
    }
    
    foreach( $relationships as $post_type => $relationship_array ) {
      foreach( $relationship_array as $parent_post_type => $relationship_slugs ) {
        foreach( $relationship_slugs as $relationship_slug ) {
          
          $field_name = str_replace( '-' , '_' , $parent_post_type ) . '_id';

          register_rest_field( $post_type , $field_name , array(

            'get_callback' => function( $object , $fieldname , $request , $type ) {
              $object = bdtos_get_bdtos( $object['id'] );
              return $object->get_parent_id( str_replace( '_' , '-' , str_replace( '_id' , '' , $fieldname )));
            },

            'update_callback' => function( $value , $post , $fieldname ) {
              return new WP_Error( 'relationship_error' , 'You cannot curently create relationships via Toolset Object Support REST API functionality' , array( 'status' => 404 ));
            },

            'schema' => array(
              'description' => __( 'The ' . $parent_post_type . 's attached to the ' . $post_type . ' via the ' . $relationship_slug . ' relationship.' ),
              'type' => $post_type,
            ),

          ));
          
        }
      }
    }
    
  }
  
  
  /**
  *
  * Add registered custom fields
  *
  **/
  
  public function add_registered_custom_fields() {
    
    foreach( get_post_types() as $post_type ) {
      foreach( $this->get_fields_by_post_type( $post_type ) as $field_data ) {

        $field_name = str_replace( '-' , '_' , $field_data['slug'] );

        register_rest_field( $post_type , $field_name , array(

          'get_callback' => function( $object , $fieldname , $request , $type ) {
            
            $object = bdtos_get_bdtos( $object['id'] );
            
            $custom_method_name = 'rest_get_' . $fieldname . '_value';
            $fieldname = str_replace( '_' , '-' , $fieldname );           
                        
            $value = ( method_exists( $object , $custom_method_name )) ? $object->$custom_method_name() : false;
            
            return ( $object->custom_fields[ $fieldname ] && ! $value ) ? $object->custom_fields[ $fieldname ] : $value;
            
          },

          'update_callback' => function( $value , $post , $fieldname ) {
            return new WP_Error( 'relationship_error' , 'You cannot update Toolset custom fields via Toolset Object Support REST API functionality' , array( 'status' => 404 ));
          },

          'schema' => array(
            'description' => __( 'The ' . $field_data['name'] . ' field: ' . $field_data['description'] ),
            'type' => $post_type,
          ),

        ));

      }
    }
           
  }
  
  
  /**
  *
  * Get fields by post type
  *
  **/
  
  public function get_fields_by_post_type( $post_type ) {
    
    $return_fields = array();
    
    foreach( wpcf_admin_get_groups_by_post_type( $post_type ) as $group_id => $group_data ) {
      $fields = wpcf_admin_fields_get_fields_by_group( $group_id );
      $return_fields = array_merge( $return_fields , $fields );      
    }
    
    return $return_fields;
    
  }
  
  
  /**
  *
  * Filter fields for the REST API
  *
  **/
  
  public function add_parent_filter_fields( $args, $request ) {
    
    $post_type = $args['post_type'];
    $relationships = toolset_get_related_post_types( 'parent' , $post_type );
    
    foreach( $relationships as $parent_post_type => $relationship_array ) {
      foreach( $relationship_array as $relationship_slug ) {
          
        $fieldname = $parent_post_type . '_parent';

        if( ! $request->get_param( $fieldname )) {
          continue;
        }

        $args['toolset_relationships'][] = array(
          'role' => 'child',
          'related_to' => $request->get_param( $fieldname ),
          'relationship' => $relationship_slug,
        );
       
      }
    }
    
    return $args;
    
  }
  
  
  /**
  *
  * Register REST field filters
  *
  **/
  
  public function register_rest_field_filters() {
    foreach( get_post_types() as $post_type ) {
      add_filter( 'rest_' . $post_type . '_query' , array( $this , 'add_parent_filter_fields' ) , 10 , 2 );  
    }
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
