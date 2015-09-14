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
 * @ link				http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * Event Details
 *
 * @package		Event Espresso
 * @subpackage	/modules/event_details/
 * @author		Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Event_Single  extends EED_Module {


	/**
	 * @type EE_Template_Part_Manager $template_parts
	 */
	protected $template_parts;



	/**
	 * @return EED_Event_Single
	 */
	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}



	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		add_filter( 'FHEE_run_EE_wp', '__return_true' );
		add_action( 'wp_loaded', array( 'EED_Event_Single', 'set_definitions' ), 2 );
		EE_Config::register_route( __( 'event', 'event_espresso' ), 'Event_Single', 'run' );
	}

	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
		add_action( 'wp_loaded', array( 'EED_Event_Single', 'set_definitions' ), 2 );
	}




	/**
	 * set_definitions
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function set_definitions() {
		define( 'EVENT_SINGLE_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets' . DS );
		define( 'EVENT_SINGLE_TEMPLATES_PATH', plugin_dir_path( __FILE__ ) . 'templates' . DS );
	}



	/**
	 *    set_config
	 *
	 * @void
	 */
	protected function set_config(){
		$this->set_config_section( 'template_settings' );
		$this->set_config_class( 'EE_Event_Single_Config' );
		$this->set_config_name( 'EED_Event_Single' );
	}




	/**
	 *    initialize_template_parts
	 *
	 * @access    public
	 * @return    void
	 */
	public function initialize_template_parts() {
		/** @type EE_Event_Single_Config $config */
		$config = $this->config();
		EEH_Autoloader::instance()->register_template_part_autoloaders();
		$this->template_parts = new EE_Template_Part_Manager();
		$this->template_parts->add_template_part( 'tickets', 'content-espresso_events-tickets.php', $config->display_order_tickets );
		$this->template_parts->add_template_part( 'datetimes', 'content-espresso_events-datetimes.php', $config->display_order_datetimes );
		$this->template_parts->add_template_part( 'event', 'content-espresso_events-event.php', $config->display_order_event );
		$this->template_parts->add_template_part( 'venue', 'content-espresso_events-venues.php', $config->display_order_venue );
		$this->template_parts = apply_filters(
			'FHEE__EED_Event_Single__set_config__template_parts',
			$this->template_parts
		);
	}




	/**
	 *    run - initial module setup
	 *
	 * @access    public
	 * @param WP $WP
	 * @return    void
	 */
	public function run( $WP ) {
		// ensure valid EE_Events_Single_Config() object exists
		$this->set_config();
		// check what template is loaded
		add_filter( 'template_include',  array( $this, 'template_include' ), 999, 1 );
		add_filter( 'FHEE__EED_Ticket_Selector__load_tckt_slctr_assets', '__return_true' );
		// load css
		add_action('wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 10 );
		EE_Registry::instance()->load_helper( 'Venue_View' );




	}



	/**
	 *    template_include
	 *
	 * @access 	public
	 * @param 	string $template
	 * @return 	string
	 */
	public function template_include( $template ) {
		global $post;
		/** @type EE_Event_Single_Config $config */
		$config = $this->config();
		if ( $config->display_status_banner_single ) {
			add_filter( 'the_title', array( 'EED_Event_Single', 'the_title' ), 100, 2 );
		}
		// not a custom template?
		if ( EE_Front_Controller::instance()->get_selected_template() != 'single-espresso_events.php' && ! post_password_required( $post ) ) {
			EEH_Template::load_espresso_theme_functions();
			// then add extra event data via hooks
			add_action( 'loop_start', array( 'EED_Event_Single', 'loop_start' ));
			add_filter( 'the_content', array( 'EED_Event_Single', 'event_details' ), 100 );
			add_action( 'loop_end', array( 'EED_Event_Single', 'loop_end' ));
			// don't display entry meta because the existing theme will take car of that
			add_filter( 'FHEE__content_espresso_events_details_template__display_entry_meta', '__return_false' );
		}
		return $template;
	}



	/**
	 * 	loop_start
	 *
	 *  	@access 	public
	 * 	@param		array 	$wp_query_array an array containing the WP_Query object
	 *  	@return 		void
	 */
	public static function loop_start( $wp_query_array ) {
		global $post;
		do_action( 'AHEE_event_details_before_post', $post, $wp_query_array );
	}



	/**
	 *    the_title
	 *
	 * @access 	public
	 * @param 	string $title
	 * @param 	int 	$id
	 * @return 	string
	 */
	public static function the_title( $title = '', $id = 0 ) {
		global $post;
		return in_the_loop() && $post->ID == $id ? espresso_event_status_banner( $post->ID ) . $title :  $title;
	}


	/**
	 * 	event_details
	 *
	 * 	@access 	public
	 * 	@param 	string 	$content
	 * 	@return 	string
	 */
	public static function event_details( $content ) {
		global $post;
		if ( $post->post_type == 'espresso_events' && ! post_password_required() ) {
			if ( EE_Registry::instance()->CFG->template_settings->EED_Event_Single->use_sortable_display_order /*&& false*/ ) {
				EED_Event_Single::instance()->initialize_template_parts();
				$content = EED_Event_Single::instance()->template_parts->apply_template_part_filters( $content );
			} else {
				$content = \EED_Event_Single::use_filterable_display_order( $content );
			}
		}
 		return $content;
	}



	/**
	 *    use_filterable_display_order
	 *
	 * @access    protected
	 * @param        string $content
	 * @return string
	 */
	protected static function use_filterable_display_order( $content ) {
		static $applied = false;
		if ( $applied ) {
			return $content;
		}
		$applied = true;
		// since the 'content-espresso_events-details.php' template might be used directly from within a theme,
		// it uses the_content() for displaying the $post->post_content
		// so in order to load a template that uses the_content() from within a callback being used to filter the_content(),
		// we need to first remove this callback from being applied to the_content() (otherwise it will recurse and blow up the interweb)
		remove_filter( 'the_content', array( 'EED_Event_Single', 'event_details' ), 100 );
		//now add additional content
		add_filter( 'the_content', array( 'EED_Event_Single', 'event_datetimes' ), 110, 1 );
		add_filter( 'the_content', array( 'EED_Event_Single', 'event_tickets' ), 120, 1 );
		add_filter( 'the_content', array( 'EED_Event_Single', 'event_venues' ), 130, 1 );
		do_action( 'AHEE__EED_Event_Single__use_filterable_display_order__after_add_filters' );
		// now load our template
		$template = EEH_Template::locate_template( 'content-espresso_events-details.php' );
		//now add our filter back in, plus some others
		add_filter( 'the_content', array( 'EED_Event_Single', 'event_details' ), 100 );
		remove_filter( 'the_content', array( 'EED_Event_Single', 'event_datetimes' ), 110 );
		remove_filter( 'the_content', array( 'EED_Event_Single', 'event_tickets' ), 120 );
		remove_filter( 'the_content', array( 'EED_Event_Single', 'event_venues' ), 130 );
		// we're not returning the $content directly because the template we are loading uses the_content (or the_excerpt)
		return ! empty( $template ) ? $template : $content;
	}



	/**
	 *    event_datetimes - adds datetimes ABOVE content
	 *
	 * @access    public
	 * @param        string $content
	 * @return        string
	 */
	public static function event_datetimes( $content ) {
		return EEH_Template::locate_template( 'content-espresso_events-datetimes.php' ) . $content;
	}



	/**
	 *    event_tickets - adds tickets ABOVE content (which includes datetimes)
	 *
	 * @access    public
	 * @param        string $content
	 * @return        string
	 */
	public static function event_tickets( $content ) {
		return EEH_Template::locate_template( 'content-espresso_events-tickets.php' ) . $content;
	}



	/**
	 *    event_venues
	 *
	 * @access 	public
	 * @param 	string $content
	 * @return 	string
	 */
	public static function event_venue( $content ) {
		return EED_Event_Single::event_venues( $content );
	}



	/**
	 *    event_venues - adds venues BELOW content
	 *
	 * @access    public
	 * @param        string $content
	 * @return        string
	 */
	public static function event_venues( $content ) {
		return $content . EEH_Template::locate_template( 'content-espresso_events-venues.php' );
	}



	/**
	 * 	loop_end
	 *
	 *  	@access 	public
	 * 	@param		array 	$wp_query_array an array containing the WP_Query object
	 *  	@return 		void
	 */
	public static function loop_end( $wp_query_array ) {
		global $post;
		do_action( 'AHEE_event_details_after_post', $post, $wp_query_array );
	}



	/**
	 * 	wp_enqueue_scripts
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function wp_enqueue_scripts() {
		// get some style
		if ( apply_filters( 'FHEE_enable_default_espresso_css', TRUE ) && apply_filters( 'FHEE__EED_Event_Single__wp_enqueue_scripts__enable_css', TRUE )) {
			EE_Registry::instance()->load_helper( 'File' );
			// first check uploads folder
			if ( is_readable( get_stylesheet_directory() . $this->theme . DS . 'style.css' )) {
				wp_register_style( $this->theme, get_stylesheet_directory_uri() . $this->theme . DS . 'style.css', array( 'dashicons', 'espresso_default' ));
			} else {
				wp_register_style( $this->theme, EE_TEMPLATES_URL . $this->theme . DS . 'style.css', array( 'dashicons', 'espresso_default' ));
			}
			wp_enqueue_script( $this->theme );
			if ( EE_Registry::instance()->CFG->map_settings->use_google_maps ) {
				EE_Registry::instance()->load_helper( 'Maps' );
				add_action('wp_enqueue_scripts', array( 'EEH_Maps', 'espresso_google_map_js' ), 11 );
			}
		}
	}








	/**
	 * 	display_venue
	 *
	 *  @access 	public
	 *  @return 	bool
	 */
	public static function display_venue() {
		EE_Registry::instance()->load_helper( 'Venue_View' );
		/** @type EE_Event_Single_Config $config */
		$config = EED_Event_Single::instance()->config();
		$display_venue= isset( $config->display_venue ) ? $config->display_venue : TRUE;
		$venue_name = EEH_Venue_View::venue_name();
		return $display_venue && ! empty( $venue_name ) ? TRUE : FALSE;
	}



}





/**
 * espresso_display_venue_in_event_details
 *
 * @see EED_Event_Single::display_venue()
 * @return bool
 */
function espresso_display_venue_in_event_details() {
	return EED_Event_Single::display_venue();
}



// End of file EED_Event_Single.module.php
// Location: /modules/event_details/EED_Event_Single.module.php