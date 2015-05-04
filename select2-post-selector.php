<?php

/**
 *
 * @link              http://oikos.org.uk/
 * @since             1.0.1
 * @package           Select2_Post_Selector
 *
 * @wordpress-plugin
 * Plugin Name:       Select2 Post Selector
 * Plugin URI:        http://oikos.org.uk/select2-post-selector
 * Description:       Provides developers with a simple means of creating AJAX-powered Select 2 Post Select Meta Boxes
 * Version:           1.0.3
 * Author:            Ross Wintle/Oikos
 * Author URI:        http://oikos.org.uk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       select2-post-selector
 * Domain Path:       /languages
 */

/***
 * This is a library for making AJAX-powered selectboxes for choosing posts
 ***/

add_action('init', 'S2PS_Post_Select::init');
add_action('admin_enqueue_scripts', 'S2PS_Post_Select::enqueue_scripts_and_styles');

class S2PS_Post_Select_Instance {

	private $field_id = null;
	private $meta_key = '';
	private $form_field_name = '';
	private $form_field_label = '';
	private $post_post_type = 'post';
	private $item_post_type = 'post';
	private $additional_query_params = array();

	function __construct($field_id, $meta_key, $form_field_name, $form_field_label, $post_post_type='post', $item_post_type='post', $additional_query_params=array()) {
		$this->field_id = $field_id;
		$this->meta_key = $meta_key;
		$this->form_field_name = $form_field_name;
		$this->form_field_label = $form_field_label;
		$this->post_post_type = $post_post_type;
		$this->item_post_type = $item_post_type;
		$this->additional_query_params = $additional_query_params;
	}

	function get_addition_query_params() {
		return $this->additional_query_params;
	}

	/*
	 * Note that we're using Select2 which, for AJAX-powered selects uses a hidden field as starting point
	 * and that the value should be a comma-separated list
	 */
	function display() {
		global $post;
	    $current_item_ids = get_post_meta( $post->ID, $this->meta_key, false );

	    // Some entries may be arrays themselves!
	    $processed_item_ids = array();
	    foreach ($current_item_ids as $this_id) {
	        if (is_array($this_id)) {
	            $processed_item_ids = array_merge( $processed_item_ids, $this_id );
	        } else {
	            $processed_item_ids[] = $this_id;
	        }
	    }

	    if (is_array($processed_item_ids) && !empty($processed_item_ids)) {
	        $processed_item_ids = implode(',', $processed_item_ids);
	    } else {
	        $processed_item_ids = '';
	    }
	?>
	    <p>
	        <label for="<?php echo $this->form_field_name; ?>"><?php echo $this->form_field_label; ?></label>

	        <input style="width: 400px;" type="hidden" name="<?php echo $this->form_field_name; ?>" class="s2ps-post-selector" data-post-type="<?php echo $this->item_post_type ?>" data-s2ps-post-select-field-id="<?php echo $this->field_id; ?>" value="<?php echo $processed_item_ids; ?>" />
	    </p>
	<?php

	}

	function save() {
		global $post;
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	        return;
	    }

	    if ( isset( $_POST['post_type'] ) && $this->post_post_type == $_POST['post_type'] ) {

	        // Check the user's permissions.
	        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
	            return;
	        }

	        /* OK, its safe for us to save the data now. */
	        
	        // Make sure that it is set.
	        if ( ! isset( $_POST[$this->form_field_name] ) ) {
	            return;
	        }

	        // If it's set but empty, the lists may have been deleted, so we need to delete existing meta values
	        if ( empty( $_POST[$this->form_field_name] ) ) {
	        	delete_post_meta($post->ID, $this->meta_key);
				return;	        	
	        }

	        // The Select2 with multiple option submits a comma-separated list of vaules
	        // but we want to store each ID as a separate meta item (for compatibility with existing
	        // options and queries - note that this is compatible with how the meta-box
	        // plugin handles multiple selects)
	        if (strpos($_POST[$this->form_field_name], ',') === false) {
	            // No comma, must be single value - still needs to be in an array for now
	            $post_ids = array( $_POST[$this->form_field_name] );
	        } else {
	            // There is a comma so it's explodable
	            $post_ids = explode(',', $_POST[$this->form_field_name]);
	        }
	        // Delete all existing entries
	        delete_post_meta($post->ID, $this->meta_key);
	        // Add new entries
	        if (is_array($post_ids) && !empty($post_ids)) {
	        	foreach($post_ids as $this_id) {
	        		add_post_meta($post->ID, $this->meta_key, $this_id, false );
	        	}
	        }
	    }
	}
}

class S2PS_Post_Select {

	private static $instances = array();
	
	public static function init() {
		add_action('wp_ajax_s2ps_post_select_lookup', 'S2PS_Post_Select::post_lookup');
		add_action('wp_ajax_s2ps_get_post_titles', 'S2PS_Post_Select::get_post_titles');
		add_action('save_post', 'S2PS_Post_Select::do_saves');
	}

	public static function enqueue_scripts_and_styles() {
		wp_enqueue_script('select2', plugin_dir_url( __FILE__ ) . '/includes/select2-3.5.0/select2.min.js', array('jquery'), '', true);
		wp_enqueue_style('select2', plugin_dir_url( __FILE__ ) . '/includes/select2-3.5.0/select2.css', array(), '');
		wp_enqueue_script('ajax-select2-s2ps', plugin_dir_url( __FILE__ ) . '/includes/ajax-select2-s2ps.js', array('jquery', 'select2'), '', true);
	}

	/*
		This function is the AJAX call that does the search and echoes a JSON array of the results in format:
		array(
				array(
					'id' => <post_id>,
					'title' => <post_title>,
				)
			)

		Originally I did this as array( post_id => post_title ), but it turns out that browsers sort
		AJAX results like this by the numeric ID. So I've fixed the index of each item so that it gives
		items in the correct order in the select2 drop-down.
	 */
	public static function post_lookup() {
	    global $wpdb;

	    $result = array();

	    $search = like_escape($_REQUEST['q']);

	    $post_type = $_REQUEST['post_type'];

	    $field_id = $_REQUEST['s2ps_post_select_field_id'];

	    // Don't forget that the callback here is a closure that needs to use the $search from the current scope
	    add_filter('posts_where', function( $where ) use ($search) {
	    							$where .= (" AND post_title LIKE '%" . $search . "%'");
	    							return $where;
	    						});
	    $default_query = array(
	    					'posts_per_page' => -1,
	    					'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
	    					'post_type' => $post_type,
	    					'order' => 'ASC',
	    					'orderby' => 'title',
	    					'suppress_filters' => false,
	    				);

	    $custom_query = self::$instances[$field_id]->get_addition_query_params();

	    $merged_query = array_merge( $default_query, $custom_query );
	    $posts = get_posts( $merged_query );

	    // We'll return a JSON-encoded result. 
	    foreach ($posts as $this_post) {
	        $post_title = $this_post->post_title;
	        $id = $this_post->ID;

	        $result[] = array(
	        				'id' => $id,
	        				'title' => $post_title,
	        				);
	    }

	    echo json_encode($result);

	    die();
	}

	public static function get_post_titles() {
		$result = array();

		if (isset($_REQUEST['post_ids'])) {
			$post_ids = $_REQUEST['post_ids'];
			if (strpos($post_ids, ',') === false) {
				// There is no comma, so we can't explode, but we still want an array
				$post_ids = array( $post_ids );
			} else {
				// There is a comma, so it must be explodable
				$post_ids = explode(',', $post_ids);
			}
		} else {
			$post_ids = array();
		}

		if (is_array($post_ids) && ! empty($post_ids)) {

			$posts = get_posts(array(
									'posts_per_page' => -1,
									'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
									'post__in' => $post_ids,
									'post_type' => 'any'
									));
			foreach ($posts as $this_post) {
		        $result[] = array(
        				'id' => $this_post->ID,
        				'title' => $this_post->post_title,
    				);
			}
		}

		echo json_encode($result);

		die;
	}

	/*
	 * This creates a new instance, stores it, and prints the form field. It returns the instance ID.
	 *
	 * Parameters:
	 *   $field_id - this is the 'name' of the field - used to identify it for printing or saving - it must be unique!
	 *   $meta_key - the meta_key fo fetch/save data to/from
	 *   $form_field_name - the name attribute of the form field to be created
	 *   $form_field_label - the label text for the form field
	 *   $post_post_type - the post type of the post we're creating the field for
	 *   $item_post_type - the post type of the things to appear in the list
	 *   $additional_query_params - any additional query params for generating the list
	 *
	 * Returns the id of the created instance as passed in
	 */
	public static function create( $field_id, $meta_key, $form_field_name, $form_field_label, $post_post_type='post', $item_post_type='post', $additional_query_params=array() ) {
		$new_instance = new S2PS_Post_Select_Instance($field_id, $meta_key, $form_field_name, $form_field_label, $post_post_type, $item_post_type, $additional_query_params);
		self::$instances[$field_id] = $new_instance;

		return $field_id;
	}

	public static function display( $field_id ) {
		self::$instances[$field_id]->display();
	}

	public static function do_saves( ) {
		foreach (self::$instances as $this_instance) {
			$this_instance->save();
		}
	}
}

