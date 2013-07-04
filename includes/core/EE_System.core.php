<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * EE_System
 *
 * @package			Event Espresso
 * @subpackage	core/
 * @author				Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
class EE_System {


	/**
	 * 	instance of the EE_System object
	 *	@var 	$_instance
	 * 	@access 	private
	 */
	private static $_instance = NULL;

	/**
	 * 	EE_Registry Object
	 *	@var 	object	
	 * 	@access 	protected
	 */
	protected $EE = NULL;





	/**
	 *		@singleton method used to instantiate class object
	 *		@access public
	 *		@return class instance
	 */
	public static function instance() {
		// check if class object is instantiated, and instantiated properly
		if ( self::$_instance === NULL  or ! is_object( self::$_instance ) or ! is_a( self::$_instance, __CLASS__ )) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}



	/**
	 * 	class constructor
	 *
	 *  @access 	private
	 *  @return 	void
	 */
	private function __construct() {
//		echo '<h3>'. __CLASS__ . '->' . __FUNCTION__ . ' <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h3>';
		// set autoloaders for core files, models, classes, and libraries
//		$this->_define_autoloaders();
		// set autoloaders for core files, models, classes, and libraries
		$this->_load_base_classes();
		// load files for managing exceptions and errors
		$this->_load_exception_handling();
		// load EE_Log class
		$this->_load_logging();
		// handy dandy object for holding shtuff
		$this->_load_registry();
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 5 );
		add_action( 'init', array( $this, 'init' ), 3 );
		add_filter('query_vars', array( $this, 'add_query_vars' ), 5 );
		add_action('wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 25 );
	}




	/**
	 * 		_load_base_classes
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _load_base_classes() {
		require_once( EE_CORE . 'EE_Base.core.php' );	
		require_once( EE_CLASSES . 'EE_Base_Class.class.php' );	
		require_once( EE_CLASSES . 'EE_CPT_Base.class.php' );	
		require_once( EE_MODELS . 'EEM_Base.model.php' );	
		require_once( EE_MODELS . 'EEM_CPT_Base.model.php' );	
	}




	/**
	 * 		Automagically load non-singleton class files - no need to include or require
	 * 		ONLY works with class objects created via  "new"  ie: $object = new SomeClassName();
	 *
	 * 		@access 	private
	* 		@param		$class		path and name of the class file to be loaded
	 * 		@return 		void
	 */
	private function _define_autoloaders() {
		spl_autoload_register( array( $this, '_autoload_core' ));
		spl_autoload_register( array( $this, '_autoload_models' ));
		spl_autoload_register( array( $this, '_autoload_classes' ));
		spl_autoload_register( array( $this, '_autoload_libraries' ));
	}

	/**
	 * 		_autoload_core
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _autoload_core( $className ) {
		if ( is_readable( EE_CORE . $className . '.core.php' )) {
			require_once( EE_CORE . $className . '.core.php' );
		} 
	}

	/**
	 * 		_autoload_models
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _autoload_models( $className ) {
		if ( is_readable( EE_MODELS . '' . $className . '.model.php' ) ) {
			require_once( EE_MODELS . '' . $className . '.model.php' );
		}
	}

	/**
	 * 		_autoload_classes
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _autoload_classes( $className ) {
		if ( is_readable( EE_CLASSES . $className . '.class.php' ) ) {
			require_once( EE_CLASSES . $className . '.class.php' );
		}
	}

	/**
	 * 		_autoload_libraries
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _autoload_libraries( $className ) {
		//let's setup an array of paths to check (for each subsystem)
		$root = EVENT_ESPRESSO_PLUGINFULLPATH . '/libraries/';		
		//todo:  more subsystems could be added in this array OR even better this array can be defined somewhere else!
		$dir_ref = array(
			'root' => array('core', 'lib'),
			'shortcodes/' => array('core', 'lib')
			);
		//assemble a list of filenames
		foreach ( $dir_ref as $dir => $types ) {
			if ( is_array($types) ) {
				foreach ( $types as $type) {
					$filenames[] = ( $dir == 'root' ) ? $root . $className . '.' . $type . '.php' : $root . $dir . $className . '.' . $type . '.php';
				}
			} else {
				$filenames[] = ( $dir == 'root' ) ? $root . $className . '.' . $types . '.php' : $root . $dir . $className . '.' . $types . '.php';
			}
		}
		//now loop through assembled filenames and require as available
		foreach ( $filenames as $filename ) {
			if ( is_readable($filename) )
				require_once( $filename );
		}
	}




	/**
	 * 		_load_exception_handling - loads files for managing exceptions and errors
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _load_exception_handling() {
		require_once( EE_CORE . 'EE_Exceptions.core.php');
	}



	/**
	 * 		_load_logging - loads system logging
	 *
	 * 		@access 	private
	 * 		@return 		void
	 */
	private function _load_logging() {
		if ( is_readable( EE_CORE . 'EE_Log.core.php' )) {
			require_once( EE_CORE . 'EE_Log.core.php' );
		} else {
			$msg = __( 'An error has occured. The EE_Log could not be loaded.', 'event_espresso' );
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
		}
	}



	/**
	 * 		_load_registry
	 *
	 * 		@access private
	 * 		@return void
	 */
	private function _load_registry() {
//		echo '<h3>'. __CLASS__ . '->' . __FUNCTION__ . ' <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h3>';
		if ( is_readable( EE_CORE . 'EE_Registry.core.php' )) {
			require_once( EE_CORE . 'EE_Registry.core.php' );
			$this->EE = EE_Registry::instance();
		} else {
			$msg = __( 'An error has occured. The EE_Registry could not be loaded.', 'event_espresso' );
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
		}
	}



	/**
	 * 		get_registry
	 *
	 * 		@access public
	 * 		@return object
	 */
	public function get_registry() {
		return $this->EE;
	}



	/**
	 * 		plugins_loaded
	 *
	 * 		@access 	public
	 * 		@return 		void
	 */
	public function plugins_loaded() {
//		echo '<h3>'. __CLASS__ . '->' . __FUNCTION__ . ' <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h3>';
		// set names for db tables
		$this->_define_database_tables();
	}



	/**
	 * 	_define_database_tables
	 *
	 *  @access 	private
	 *  @return 	void
	 */
	private function _define_database_tables() {
		// grab list of installed shortcodes
		$models = glob( EVENT_ESPRESSO_INCLUDES_DIR . 'models' . DS . '*.model.php' );
		$ignore = array( 'EEM_Base' );
//		echo '<h4>models : ' . EVENT_ESPRESSO_INCLUDES_DIR . 'models' . DS . '*.model.php' . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		printr( $models, '$models  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		foreach ( $models as $model ) {
			// create classname from model file name
			$model_class = str_replace( '.model.php', '', basename( $model ));
			if ( ! in_array( $model_class, $ignore )) {
				// load the model class file
				require_once( $model );
				// if classname was parsed correctly then call define_table_name() method
				if ( class_exists( $model_class ) && method_exists( $model_class, 'define_table_name' )) {
					$model_class::define_table_name();
				}			
			}
		}
	}



	/**
	 * 	init
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function init() {
//		echo '<h3>'. __CLASS__ . '->' . __FUNCTION__ . ' <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h3>';

		//Globals used throughout the site
//		global $is_UI_request, $espresso_content;
//		// is this request for UI or backend 
//		$is_UI_request = ( ! isset( $_REQUEST['noheader'] ) || $_REQUEST['noheader'] != 'true' ) ? TRUE : FALSE;
		// register Custom Post Types
		$this->EE->load_core( 'Register_CPTs' );
		require_once( EVENT_ESPRESSO_INCLUDES_DIR . 'functions/main.php' );

		// session loading is turned OFF by default, but prior to the init hook, can be turned back on again via: add_filter( 'FHEE_load_EE_Session', '__return_true' );
		if ( apply_filters( 'FHEE_load_EE_Session', FALSE )) {
			$this->load_EE_Session();
		}


		//$this->create_event_slug_rewrite_rule();

	}



	/**
	 * 		load and instantiate EE_Session class
	 *
	 * 		@access public
	 * 		@return void
	 */
	public function load_EE_Session() {
		global $EE_Session;
		// instantiate !!!
		$this->EE->load_class( 'Session' );
		if ( $this->EE->REQ->is_set( 'clear_session' )) {
			espresso_clear_session( __CLASS__, __FUNCTION__ );
		}
	}



	/**
	 * 	add_query_vars
	 *
	 *  @access 	public
	 *  @param 	boolean 		$to_flush_or_not_to_flush
	 *  @return 	void
	 */
	public function add_query_vars($query_vars) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
//		$query_vars[] = 'event_slug';
//		$query_vars[] = 'ee';
//		$query_vars[] = 'e_reg';
		//printr( $query_vars, '$query_vars  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		return $query_vars;
	}



	/**
	 * 	create_event_slug_rewrite_rule
	 *
	 *  @access 	public
	 *  @param 	boolean 	$to_flush_or_not_to_flush reset rules ?
	 *  @return 	void
	 */
	private function create_event_slug_rewrite_rule( $to_flush_or_not_to_flush = FALSE ) {
//		global $wpdb;		
//		// grab slug for event reg page
//		$SQL = 'SELECT post_name  FROM ' . $wpdb->prefix . 'posts WHERE ID = %d';
//		$reg_page_url_slug = $wpdb->get_var( $wpdb->prepare( $SQL, $this->EE->CFG->events_page ));
//		$this->_add_rewrite_rules( $reg_page_url_slug, 'event_slug', $to_flush_or_not_to_flush );
	}



	/**
	 * 	_add_rewrite_rules
	 *
	 *  @access 	private
	 *  @param 	string 		$url_slug 	the slug you want to appear in the URL
	 *  @param 	string 		$slug_key	the key you will use to obtain the slug
	 *  @param 	boolean 	$to_flush_or_not_to_flush reset rules ?
	 *  @return 	void
	 */
	private function _add_rewrite_rules( $url_slug = FALSE, $slug_key = FALSE, $to_flush_or_not_to_flush = FALSE ) {	
		// you don't get something for nothing !!!'
		if ( ! $url_slug || ! $slug_key ) {
			return FALSE;
		}
		$to_flush_or_not_to_flush = apply_filters( 'FHEE_flush_rewrite_rules', $to_flush_or_not_to_flush );
		// create pretty permalinks
		if ( get_option( 'permalink_structure' ) != '' ) {
			// rules for url slug pretty links
			add_rewrite_rule( $url_slug . '/([^/]+)/?$', 'index.php?pagename=' . $url_slug . '&'. $slug_key .'=$matches[1]', 'top');
			// whether tis nobler on the server to suffer the pings and errors of outrageous flushing
			if ( $to_flush_or_not_to_flush ) {
				flush_rewrite_rules();
			}
		}		
	}





	/*********************************************** 		WP_ENQUEUE_SCRIPTS HOOK		 ***********************************************/



	/**
	 * 	wp_enqueue_scripts
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function wp_enqueue_scripts() {
		// unlike other systems, EE_System_scripts loading is turned ON by default, but prior to the init hook, can be turned off via: add_filter( 'FHEE_load_EE_System_scripts', '__return_false' );
		if ( apply_filters( 'FHEE_load_EE_System_scripts', TRUE )) {

			// js for error handling
			wp_register_script('ee_error_js', EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/EE_Error.js', array('jquery'), EVENT_ESPRESSO_VERSION, false);
			wp_localize_script('ee_error_js','ee_settings',array('wp_debug'=>WP_DEBUG));
			wp_enqueue_script('ee_error_js');
			
			// jquery_validate loading is turned OFF by default, but prior to the wp_enqueue_scripts hook, can be turned back on again via:  add_filter( 'FHEE_load_jquery_validate', '__return_true' );
			if ( apply_filters( 'FHEE_load_jquery_validate', FALSE )) {
				// load jQuery Validate script from CDN with local fallback
				$jquery_validate_url = 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js'; 
				// is the URL accessible ?
				$test_url = @fopen( $jquery_validate_url, 'r' );
				// use CDN URL or local fallback ?
				$jquery_validate_url = $test_url !== FALSE ? $jquery_validate_url : EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/jquery.validate.min.js';
				// register jQuery Validate
				wp_register_script('jquery-validate', $jquery_validate_url, array('jquery'), '1.11.1', TRUE);			
			}
			
		}
	}






	


}
// End of file EE_System.core.php
// Location: /core/EE_System.core.php