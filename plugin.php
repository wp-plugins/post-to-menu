<?php 
/*
Plugin Name: Post To Menu
Author: Spencer Guier
Version: 1.0
Tags: Menus, Pages
Author URI: http://wp.spencer.vegas
Description: Add posts, pages, and other post types to the top level of WordPress menus directly from the Post Editor Page.
*/

	// Define File
	
	define( 'POST_TO_MENU_PLUGIN_FILE', __FILE__ );


// register activation hook.
 
register_activation_hook( POST_TO_MENU_PLUGIN_FILE, 'post_to_menu_install');

function post_to_menu_install(){
	
  global $wp_roles;
  
  $wp_roles->add_cap( 'administrator', 'post_to_menu' );
  
  $wp_roles->add_cap( 'administrator', 'post_to_menu_settings' );
  
}


// ------------
// Options page stuff


//Register options admin page hook
	
	function post_to_menu_menu() {
		
	  add_options_page( __('Post To Menu Settings'), __('Post To Menu'), 'post_to_menu_settings', 'post-to-menu', 'post_to_menu_options' );
  
    }

	add_action( 'admin_menu', 'post_to_menu_menu' );




//admin_init to register settings hook


	function post_to_menu_register_settings(){
		
	  register_setting( 'post-to-menu', 'post_to_menu_post_types_to_handle' );
	  
	}
	
	add_action( 'admin_init', 'post_to_menu_register_settings' );




//the options page

	function post_to_menu_options(){
		
	  ?>
	  
	  <div class="wrap">
	  
		<h2><?php _e('Post To Menu Settings'); ?></h2>
		
		<form method="post" action="options.php">
		
		  <?php settings_fields( 'post-to-menu' ); ?>
		  
		  <table cellspacing="0" class="widefat page fixed">
		  
		  <thead>
		  
			<tr>
			
			  <th style="width: 5em;">Enable</th>
			  
			  <th>Post Type</th>
			  
			  <th>Post Type Description</th>
			  
			</tr>
			
		  </thead>
		  
		  <tbody>
		  
		  <?php 
		  
		  $checked = get_option('post_to_menu_post_types_to_handle'); 
		  
		  $post_types = get_post_types( array('public' => true,), 'objects');
		  
		  $alternate = true;
		  
		  foreach ($post_types as $post_type ): 
		  
		  ?>
		  
			<tr <?php echo $alternate? 'class="alternate"': ''; $alternate = !$alternate; ?>>
			
			  <td style="width: 5em;">
			  
			  <input name="post_to_menu_post_types_to_handle[]" id="post_to_menu_post_types_to_handle--<?php echo $post_type->name; ?>" type="checkbox" value="<?php echo $post_type->name; ?>" <?php if($checked !== '') { echo in_array( $post_type->name, $checked )? 'checked="checked"' : ''; }?> />
			  
			  </td>
			  
			  <td>
			  
			  <label for="post_to_menu_post_types_to_handle--<?php echo $post_type->name; ?>"><?php 
			  
				if(!empty($post_type->menu_icon)){
					
				  echo '<img src="'.$post_type->menu_icon.'" alt="{icon}" style="vertical-align: top;" />';
				  
				} else {
				
				}
				
				echo $post_type->labels->name; ?></label></td>
			  
			  <td><?php echo $post_type->description; ?>&nbsp;</td>
			  
			</tr>
			
		  <?php endforeach; ?>
		  
		  </tbody></table>
		  
		  <p class="submit">
		  
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			
		  </p>
	
		</form>
		
	  </div>
	  
	  <?php
	}



// ----------
// the post edit page 'Post To Menu' form elements and processing


// add_meta_box. Used to register meta boxes (on the post edit page) hook
 
	add_action('add_meta_boxes', 'post_to_menu_add_meta_boxes');
	
	function post_to_menu_add_meta_boxes() {
		
	  // Check user permissions
	  
	  if( current_user_can('edit_theme_options', 'post_to_menu') ){
		  
		$post_types = get_post_types('','names'); 
		
		$display_on = get_option('post_to_menu_post_types_to_handle'); 
		
		$post_types = array_intersect( $post_types, $display_on );
		
		foreach ($post_types as $post_type ) {
			
		  add_meta_box('post-to-menu', 
		  
					 __('Add To Menu'), 
					 
					 'post_to_menu_box', 
					 
					 $post_type,
					 
					 'side',
					 
					 'low');
					 
		}
	  }
	}



// the contents of the 'Post To Menu' meta box.
 
	function post_to_menu_box(){
		
	  global $post;
	  
	  if(isset($edit_post->ancestors)) {
		  
		$active_post = get_post( $edit_post->ancestors[0] );
		
	  } else {
		  
		$active_post = $post;
		
	  }
	  
	  
	  $get_args = array('meta_key' => '_menu_item_object_id', 'meta_value' => $active_post->ID, 'post_status' => 'any' );
	  
	  $menus = wp_get_nav_menus( array('orderby' => 'name') );
	  
	  foreach( $menus as $id => $menu ){
		  
		$has_menu_item = wp_get_nav_menu_items($menu->term_id, $get_args);
		
		$has_menu_item = count($has_menu_item) > 0;
		
		echo '<p><label><input type="checkbox" '.($has_menu_item? 'checked="checked" disabled="disabled"' : '').' name="post_to_menu['.$menu->term_id.']" /> '.$menu->name.'</label></p>';
		
	  }
	}


// save_post. Processes the 'Post To Menu' meta box hook

	add_action('save_post', 'post_to_menu_save_post', 20, 2);
	
	function post_to_menu_save_post( $post_id, $edit_post ) {
		
	  // check if this is a nav_menu_item
	  if($post->post_type == 'nav_menu_item' || !isset($_POST['post_to_menu'])) {
		  
		return;
		
	  }
	  
	  // Check for revision post
	  if(isset($edit_post->ancestors)) {
		  
		$post_id = $edit_post->ancestors[0];
		
	  }
	  
	  $post = get_post($post_id);
	  
	  
	  // get the menus to add the menu item
	  $menus = $_POST['post_to_menu'];
	  
	  unset($_POST['post_to_menu']); // to prevent it getting handled again
	  
	  foreach($menus as $menu_id => $v ){
		
		// Find if the post has a parent post, if so try to put it underneath the parent in the menu
		$parent_id = 0;
		
		$walk_post = clone $post;
		
		// If this is the first time the post is being saved (hence being inserted into the DB), 
		// grab post parent from $_POST['parent_id']
		if( empty($walk_post->post_parent) && !empty($_POST['parent_id']) ){
			
		  $walk_post->post_parent = $_POST['parent_id'];
		  
		}
		
		while( $parent_id == 0 && $walk_post->post_parent != 0 ){
			
		  $get_args = array('meta_key' => '_menu_item_object_id', 'meta_value' => $walk_post->post_parent, 'post_status' => 'any' );
		  
		  $parent_menu_items = wp_get_nav_menu_items($menu_id, $get_args);
		  
		  if( count($parent_menu_items) > 0 ){
			  
			$parent_id = $parent_menu_items[0]->ID;
			
		  }
		  
		  $walk_post = get_post( $walk_post->post_parent );
		  
		}
		
		$menu_item_data = array(
		
		  'menu-item-object' => $post->post_type,
		  
		  'menu-item-object-id' => $post_id,
		  
		  'menu-item-parent-id' => $parent_id,
		  
		  'menu-item-type' => 'custom',
		  
		  'menu-item-title' => $post->post_title,
		  
		  'menu-item-status' => 'publish',
		  
		  'menu-item-url' => get_permalink( $post_id ),
		  
		);
		
		wp_update_nav_menu_item( $menu_id, 0, $menu_item_data);
	  }
	}

