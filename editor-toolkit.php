<?php
if ( !defined('ABSPATH') ) die();  // Prevent direct access


/**
 * Editor Toolkit: TinyMCE helper class for WordPress
 *
 * @package Editor_Toolkit
 * @version 1.0
 * @author John B. Fakorede
 * @link http://studioanino.com
 * @license GPL
 * @copyright Copyright (c) 2012-2013, John B. Fakorede
 */
class Editor_Toolkit {

	/**
	 * Set version constant
	 */
	const VERSION = '1.0';

	
	/**
	 * Editor Toolkit settings
	 */
	var $config;

	
	/**
	 * Initialize Editor Toolkit
	 */
	function __construct( $config = array() ) {
		
		// Configuration
		$this->config = wp_parse_args( $config, array( 'directory' => 'editor-toolkit', 'post_types' => array('page') ) );

		if ( is_admin() ) {
			
			// Apply post types filter
			add_filter( 'edtoolkit_post_types', array($this, 'post_types_filter') );
			
			// Customize block element dropdown on rich text editor
			add_filter( 'tiny_mce_before_init', array($this, 'tiny_mce_before_init') );
			
			// Set default editor content for new pages
			add_filter( 'default_content', array($this, 'default_content') );
			
			// Add buttons and plugin to editor
			add_action( 'current_screen', array($this, 'extend_editor') );
			
			// Process editor content on save
			add_filter( 'wp_insert_post_data', array($this, 'process_content'), 10, 2 );
			
			// Disable HTML editor and set visual editor as default
			add_action( 'admin_head', array($this, 'no_html_editor_css') );
			add_action( 'admin_footer', array($this, 'no_html_editor_js') );
			add_filter( 'wp_default_editor', array($this, 'no_html_editor') );
			
			// Add styles to content editor
			add_editor_style($this->config['directory'] . '/editor-toolkit.css');

		} else {

			// Unserializes content blocks and adds to current $post object
			add_action( 'the_post', array($this, 'add_blocks') );

		}

	 }


	/**
	 * Setup post types
	 *
	 * @return array
	 */
	function post_types_filter() {
		return $this->config['post_types'];
	}


	/**
	 * Check if edit screen is in allowed list of post types
	 *
	 * @global array $current_screen
	 * @return bool
	 */
	function post_types_verify() {
		global $current_screen;
		
		if ( in_array( $current_screen->post_type, apply_filters( 'edtoolkit_post_types', $this->config['post_types'] ) ) )
			return true;
		else
			return false;
	}
	
	
	/**
	 * Customize block element dropdown on second row of rich text editor
	 *
	 * @global array $current_screen
	 * @return $settings
	 */
	function tiny_mce_before_init($settings) {
		if ( $this->post_types_verify() ) {
			$valid_elements = $settings['extended_valid_elements'];
			$settings['extended_valid_elements'] = $valid_elements . ( $valid_elements && strlen($valid_elements) > 0 ? ',' : NULL ) . 'div[*]';
		}
		return $settings;
	}
	
	
	/**
	 * Set default editor content for new pages
	 *
	 * @return $content
	 */
	function default_content($content) {
		if ( $this->post_types_verify() )
			$content = "<div>\r\t<p>\r\t</p>\r</div>";

		return $content;
	}
	
	
	/**
	 * Add buttons and plugin to editor
	 */
	function extend_editor($current_screen) {
		if ( !$this->post_types_verify() )
			return;
	
		if ( get_user_option('rich_editing') == 'true' ) {
			add_filter( 'mce_external_plugins', create_function('$plugin_array', 'return $plugin_array + array("editor_toolkit" => get_stylesheet_directory_uri() . "/editor-toolkit/editor-toolkit.js");') );
			add_filter( 'tiny_mce_version', create_function('$ver', 'return $ver += 3;') );
	   }
	}
	
	
	/**
	 * Process editor content on save
	 *
	 * @return $data
	 */
	function process_content($data, $postarr) {
		if ( $this->post_types_verify() ) {	
			// Disable shortcodes
			global $shortcode_tags;
			$shortcode_tags_stash = $shortcode_tags;
			$shortcode_tags = array();
		
			// Prep content for XML conversion
			$content = force_balance_tags( apply_filters( 'the_content', stripslashes( $data['post_content'] ) ) );
		
			// Bypass if post_content is empty, e.g. on new post (auto-draft post is created in DB)
			$content_check = str_replace( array('&nbsp;', ' '), '', strip_tags($content) );
			if (trim($content_check) == '')
				return $data;
		
			// Re-enable shortcodes
			$shortcode_tags = $shortcode_tags_stash;
			
			// Start processing
			libxml_use_internal_errors(true);
			try {
				$raw = new SimpleXmlIterator('<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>' . PHP_EOL . '<content>' . PHP_EOL . $content . '</content>');
				$content_xml = $this->prep_xml($raw);
				if ($content_xml)
					$data['post_content_filtered'] = serialize($content_xml);
			} catch (Exception $e) {  // Error handling
				new WP_Error('Something went wrong');
			}
		}
	
		return $data;
	}
	
	
	/**
	 * Prepare XML as array with content blocks as objects
	 *
	 * @return $xml_prep
	 */
	function prep_xml($xml) {
		$xml_new = array();
		for ( $xml->rewind(); $xml->valid(); $xml->next() ) {
			if ( $xml->key() === 'div' ) {
				if ( $xml->hasChildren() ) {
					$children = '';
					$non_div_found = false;
	
					foreach($xml->current()->children() as $key => $value) {
						if ($key !== 'div')
							$non_div_found = true;
	
						$children .= $value->asXML() . PHP_EOL;
					}
	
					if ($non_div_found)
						$xml_new['block'][] = $children;
					else
						$xml_new['block'][] = (object)$this->prep_xml( $xml->current() );
	
				}					
			}
		}
		return (object)$xml_new;
	}


	/**
	 * Disable HTML editor
	 */
	function no_html_editor_css() {  // CSS
		if ( $this->post_types_verify() ) { ?>
		<style type="text/css">
			#content-tmce, #content-html, #ed_toolbar { display: none !important; }  /* Disable HTML editor; remove 'Visual' tab  */
		</style><?php
		}
	}
	function no_html_editor_js() {  // JavaScript
		if ( $this->post_types_verify() ) { ?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {  /* Disable HTML editor; remove 'Visual' tab */
			$("#content-tmce, #content-html, #ed_toolbar").remove();
		});
		</script><?php
		}
	}
	function no_html_editor($r) {  // Set visual editor as default
		if ( $this->post_types_verify() )
			return 'tinymce';
		else
			return $r;	
	}

	
	/** 
	 * Unserializes content blocks and adds to current $post object
	 *
	 * @global array $post Current post.
	 */
	function add_blocks($post = NULL) {
		if ($post == NULL) {
			global $post;
			$in_loop = 'yes';
		}
	
		$content = maybe_unserialize($post->post_content_filtered);
		if ( !$content || !is_object($content) )
			return NULL;
	
		$post->post_blocks = $content;
		
		if ( !isset($in_loop) )
			return $post;
	}
	
	
	/** 
	 * Returns a content block or multiple blocks from current post
	 * Shortcodes are left intact
	 *
	 * @param string $request Pointer(s) to an array key.
	 * @param array $post Post array, if used outside the loop.
	 * @global array $post Current post, if used in the loop.
	 * @return string|array|NULL String of single block, array of multiple blocks, or NULL.
	 */
	public function get_block($request, $post = NULL) {
		if ($post == NULL)
			global $post;
	
		if (isset($request) && $post) {
			if ( !array_key_exists('post_blocks', $post) )  // In case function is called outside the loop
				$this->add_blocks($post);
	
			$content = $post->post_blocks;
			if ( !$content || !is_object($content) )
				return NULL;
		
			$request = str_replace(' ', '', $request);
			$request = trim($request, ',');
			$keys = explode(',', $request);
			
			$block = $content;  // $block starts as entire $content object
			for ($i = 0; $i < count($keys); $i++) {
				try {
					$block = $block->block[(int)($keys[$i]) - 1];
				} catch (Exception $e) {  // Error message if current_user_can('edit_posts')
					return NULL;
				}
			}
			return is_object($block) ? $block->block : $block;							
		} else {
			return NULL;
		}
	}
	
	
	/** 
	 * Displays a content block or multiple blocks from current post
	 * Shortcodes are executed
	 *
	 * @param string $request Pointer(s) to an array key.
	 * @param array $post Post array, if used outside the loop.
	 *
	 * @global array $post Current post, if used in the loop.
	 *
	 * @uses get_block() Retrieves a block/blocks for output.
	 * @uses shortcode_unautop() Ensures that stand-alone shortcodes are not wrapped in <p> tags.
	 * @uses do_shortcode() Searches block for shortcodes and filters them through their hooks.
	 * @uses implode_array() Converts sub-blocks into a single block.
	 * @uses apply_filters() Calls 'the_block' filter.
	 *
	 * @return string String of single block.
	 */
	public function the_block($request, $post = NULL) {
		if ($post == NULL)
			global $post;
			
		$the_block = $this->get_block($request, $post);
		if ( is_array($the_block) )
			$the_block = $this->implode_array($the_block);
	
		$the_block = do_shortcode( shortcode_unautop($the_block) );
		$the_block = apply_filters('the_block', $the_block);
		echo $the_block;
	}
	
	
	/** 
	 * Converts a multi-dimensional array into a string
	 *
	 * @return string String version of array elements
	 */
	function implode_array($array, $sep = '') {
		if ( is_array($array) ) {
			reset($array);
			while ( list($key, $value) = each($array) ) {
				$string .= $sep . $this->implode_array($value);
			}
		} else {
			return $array;
		}
		return trim($string, $sep);
	}
}
