<?php

/*
  Plugin Name: BP Simple Front End Post
  Plugin URI: http://buddydev.com/plugins/bp-simple-front-end-post/
  Description: Provides the ability to create unlimited post forms and allow users to save the post from front end.It is much powerful than it looks.
  Version: 1.2.5
  Author: Brajesh Singh
  Author URI: http://buddydev.com/members/sbrajesh/
  License: GPL
 */
/**
 * How to Use this plugin
 * 
 * If you want to  create a form and show it on Front end, You will need to create and Register a form as follows
 * 
 * Register a from on/before bp_init action using 
 * $form= bp_new_simple_blog_post_form('form_name',$settings);// please see @ bp_new_simple_blog_post_form for the settings options
 * 
 * now, you can retrieve this form anywhere and render it as below
 * 
 * $form = bp_get_simple_blog_post_form( 'form_name' );
 * if( $form ) {
 *  $form->show();//show this post form
 * }
 */

/**
 * This is a helper class, adds support for localization
 */
class BPSimpleBlogPostComponent {

    private static $instance;

	private $path = '';
	private $url ='';
    private function __construct() {
		
		$this->path = plugin_dir_path( __FILE__ );
        $this->url = plugin_dir_url( __FILE__ );
		$this->setup();
    }

    /**
     * Factory method for singleton object
     * 
     */
    public static function get_instance() {
		
        if ( ! isset( self::$instance ) )
            self::$instance = new self();
		
        return self::$instance;
    }
	
	public function setup() {
		
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 2 );
		add_action( 'plugins_loaded', array( $this, 'load' ) );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'load_js' ) );
		add_filter( 'user_has_cap', array( $this, 'add_upload_cap_filter' ), 10, 3 );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_ajax_attachment_args' ) );
		
		add_action( 'set-post-thumbnail', array( $this, 'set_post_thumbnail' ), 0 );//high priority
		
	}

	public function load() {
		
		$path = $this->path;
		
		$files = array(
			'core/classes/class-terms-checklist-walker.php',
			'core/classes/class-edit-form.php',
			'core/classes/class-editor.php',
			'core/functions.php',
		);
		
		if( is_admin() ) {
		//	return ;//we don't need these in admin
		}
		foreach( $files as $file ) {
			
			require_once $path . $file ;
		}
		
	}
    //localization
    public function load_textdomain() {


        $locale = apply_filters( 'bsfep_load_textdomain_get_locale', get_locale() );

        // if load .mo file
        if ( ! empty( $locale ) ) {
            $mofile_default = sprintf( '%slanguages/%s.mo', plugin_dir_path(__FILE__), $locale );

            $mofile = apply_filters( 'bsfep_load_mofile', $mofile_default );
            // make sure file exists, and load it
            if ( file_exists( $mofile ) ) {
                load_textdomain( 'bsfep', $mofile );
            }
        }
    }

	/**
	 * 
	 * @return boolean
	 */
	public function enable_upload_filters() {
	
		$apply = function_exists('is_buddypress') && is_buddypress();

		$apply = apply_filters( 'bsfep_enable_upload_filters', $apply );
		
		return $apply;
	}
	
	public function add_upload_cap_filter( $allcaps, $cap, $args ) {

		if ( $args[0] !== 'upload_files' ) {
			return $allcaps;
		}
		
		if ( ! $this->enable_upload_filters() ) {
			return $allcaps;
		}
		
		$allcaps[ $cap[0] ] = true;
		

		return $allcaps;

	}
	
	/**
	 * Filter attachment for current user

	 * @param type $args
	 * @return type
	 */
	public function filter_ajax_attachment_args( $args ) {

		if( ! $this->enable_upload_filters() ) {
			return $args;
		}
		
		
		if ( is_user_logged_in() ) {
			$args['author'] = get_current_user_id();
		}

		return $args;
	}

	
    public function load_js() {
        
		wp_register_script( 'bsfep-js', $this->url .'assets/bsfep.js', array( 'jquery'), false,  true );
    }

    public function load_css() {
        
    }
	/**
	 * Get file system path of this plugin directory
	 * 
	 * @return type
	 */
	public function get_path() {
		
		return $this->path;
	}

	public function set_post_thumbnail() {

		$json = ! empty( $_REQUEST['json'] ); // New-style request
		
		if( ! $json ) {
			return ;//let wp handle it
		}
		
		$post_ID = intval( $_POST['post_id'] );

		$thumbnail_id = intval( $_POST['thumbnail_id'] );

		if ( $json )
			check_ajax_referer( "update-post_$post_ID" );

		if ( $thumbnail_id == '-1' ) {
			if ( delete_post_thumbnail( $post_ID ) ) {
				$return = _wp_post_thumbnail_html( null, $post_ID );
				$json ? wp_send_json_success( $return ) : wp_die( $return );
			} else {
				wp_die( 0 );
			}
		}

		if ( set_post_thumbnail( $post_ID, $thumbnail_id ) ) {
			$return = _wp_post_thumbnail_html( $thumbnail_id, $post_ID );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		}

		wp_die( 0 );


	}
}


/**
 * get singleton instance
 * 
 * @return BPSimpleBlogPostComponent
 */
function bp_simple_blog_post_helper() {
	return BPSimpleBlogPostComponent::get_instance();
}
BPSimpleBlogPostComponent::get_instance();

