<?php
if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for Wordpress
 *
 * @package		Event Espresso
 * @author		Seth Shoultes
 * @copyright	(c)2009-2012 Event Espresso All Rights Reserved.
 * @license		http://eventespresso.com/support/terms-conditions/  ** see Plugin Licensing **
 * @link		http://www.eventespresso.com
 * @version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * Venues_Admin_Page
 *
 * This contains the logic for setting up the Event Venue related admin pages.  Any methods without phpdoc comments have inline docs with parent class. 
 *
 *
 * @package		Venues_Admin_Page
 * @subpackage	caffeinated/admin/new/Venues_Admin_Page.core.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class Venues_Admin_Page extends EE_Admin_Page_CPT {


	/**
	 * _venue
	 * This will hold the venue object for venue_details screen.
	 *
	 * @access protected
	 * @var object
	 */
	protected $_venue;




	/**
	 * This will hold the category object for category_details screen.
	 * @var object
	 */
	protected $_category;




	/**
	 * This property will hold the venue model instance
	 * @var object
	 */
	protected $_venue_model;





	protected function _init_page_props() {
		require_once( EE_MODELS . 'EEM_Venue.model.php' );
		$this->page_slug = EE_VENUES_PG_SLUG;
		$this->page_label = __('Event Venues', 'event_espresso');
		$this->_cpt_model_name = 'EEM_Venue';
		$this->_venue_model = EEM_Venue::instance();
	}




	protected function _ajax_hooks() {
		//todo: all hooks for ee_venues ajax goes in here.
	}






	protected function _define_page_props() {
		$this->_admin_base_url = EE_VENUES_ADMIN_URL;
		$this->_admin_page_title = $this->page_label;
		$this->_labels = array(
			'buttons' => array(
				'add' => __('Add New Venue', 'event_espresso'),
				'edit' => __('Edit Venue', 'event_espresso'),
				'delete' => __('Delete Venue', 'event_espresso'),
				'add_category' => __('Add New Category', 'event_espresso'),
				'edit_category' => __('Edit Category', 'event_espresso'),
				'delete_category' => __('Delete Category', 'event_espresso')
			),
			'editor_title' => __('Enter Venue name here'),
			'publishbox' => array( 
				'create_new' => __('Save New Venue', 'event_espresso'),
				'edit' => __('Update Venue', 'event_espresso'),
				'add_category' => __('Save New Category', 'event_espresso'),
				'edit_category' => __('Update Category', 'event_espresso')
				)
		);
	}





	protected function _set_page_routes() {

		//load formatter helper
		require_once EE_HELPERS . 'EE_Formatter.helper.php';

		//load field generator helper
		require_once EE_HELPERS . 'EE_Form_Fields.helper.php';

		$this->_page_routes = array(
			'default' => '_overview_list_table',
			'trash_venue' => array(
				'func' => '_trash_or_restore_venue',
				'args' => array( 'venue_status' => 'trash' ),
				'noheader' => TRUE
				),
			'trash_venues' => array(
				'func' => '_trash_or_restore_venues',
				'args' => array( 'venue_status' => 'trash' ),
				'noheader' => TRUE
				),
			'restore_venue' => array(
				'func' => '_trash_or_restore_venue',
				'args' => array( 'venue_status' => 'draft' ),
				'noheader' => TRUE
				),
			'restore_venues' => array(
				'func' => '_trash_or_restore_venues',
				'args' => array( 'venue_status' => 'draft' ),
				'noheader' => TRUE
				),
			'delete_venues' => array(
				'func' => '_delete_venues', 
				'noheader' => TRUE 
				),
			'delete_venue' => array(
				'func' => '_delete_venue', 
				'noheader' => TRUE
				),
			//venue category tab related
			'add_category' => array(
				'func' => '_category_details',
				'args' => array('add'),
				),
			'edit_category' => array(
				'func' => '_category_details',
				'args' => array('edit')
				),
			'delete_categories' => array(
				'func' => '_delete_categories', 
				'noheader' => TRUE 
				),

			'delete_category' => array(
				'func' => '_delete_categories', 
				'noheader' => TRUE
				),

			'insert_category' => array(
				'func' => '_insert_or_update_category',
				'args' => array('new_category' => TRUE),
				'noheader' => TRUE
				),

			'update_category' => array(
				'func' => '_insert_or_update_category',
				'args' => array('new_category' => FALSE),
				'noheader' => TRUE
				),
			'export_categories' => array(
				'func' => '_categories_export',
				'noheader' => TRUE
				),
			'import_categories' => '_import_categories',
			'category_list' => array(
				'func' => '_category_list_table'
				)
		);
	}




	protected function _set_page_config() {
		$this->_page_config = array(
			'default' => array(
				'nav' => array(
					'label' => __('Overview', 'event_espresso'),
					'order' => 10
				),
				'list_table' => 'Venues_Admin_List_Table',
				'metaboxes' => array('_espresso_news_post_box', '_espresso_links_post_box'),
			),
			'create_new' => array(
				'nav' => array(
					'label' => __('Add Venue', 'event_espresso'),
					'order' => 5,
					'persistent' => FALSE
				),
				'metaboxes' => array('_venue_editor_metaboxes')
				),
			'edit' => array(
				'nav' => array(
					'label' => __('Edit Venue', 'event_espresso'),
					'order' => 5,
					'persistent' => FALSE,
					'url' => isset($this->_req_data['id']) ? add_query_arg(array('id' => $this->_req_data['id'] ), $this->_current_page_view_url )  : $this->_admin_base_url
				),
				'metaboxes' => array('_venue_editor_metaboxes')
			),
			//event category stuff
			'add_category' => array(
				'nav' => array(
					'label' => __('Add Category', 'event_espresso'),
					'order' => 15,
					'persistent' => false),
				'metaboxes' => array('_publish_post_box'),
				'help_tabs' => array(
					'unique_id_help_tab' => array(
						'title' => __('Unique ID', 'event_espresso'),
						'callback' => 'unique_id_help_tab'
						)
					)
				),
			'edit_category' => array(
				'nav' => array(
					'label' => __('Edit Category', 'event_espresso'),
					'order' => 15,
					'persistent' => FALSE,
					'url' => isset($this->_req_data['EVT_CAT_ID']) ? add_query_arg(array('EVT_CAT_ID' => $this->_req_data['EVT_CAT_ID'] ), $this->_current_page_view_url )  : $this->_admin_base_url
					),
				'metaboxes' => array('_publish_post_box'),
				'help_tabs' => array(
					'unique_id_help_tab' => array(
						'title' => __('Unique ID', 'event_espresso'),
						'callback' => 'unique_id_help_tab'
						)
					)
				),
			'category_list' => array(
				'nav' => array(
					'label' => __('Categories', 'event_espresso'),
					'order' => 20
					),
				'list_table' => 'Venue_Categories_Admin_List_Table',
				'metaboxes' => array('_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box'),
				)
		);
	}





	protected function _add_screen_options() {
		//todo
	}





	protected function _add_screen_options_default() {
		$this->_per_page_screen_option();
	}






	//none of the below group are currently used for Event Venues
	protected function _add_help_tabs() {}
	protected function _add_feature_pointers() {}
	public function admin_init() {}
	public function admin_notices() {}
	public function admin_footer_scripts() {}






	public function load_scripts_styles_create_new() {
		$this->load_scripts_styles_edit();
	}





	public function load_scripts_styles() {
		wp_register_style('ee-cat-admin', EVENTS_ASSETS_URL . 'ee-cat-admin.css', array(), EVENT_ESPRESSO_VERSION );
		wp_enqueue_style('ee-cat-admin');
	}



	public function load_scripts_styles_add_category() {
		$this->load_scripts_styles_edit_category();
	}





	public function load_scripts_styles_edit_category() {
		//styles
		//wp_enqueue_style('jquery-ui-style');

		//scripts
		wp_enqueue_script( 'ee_cat_admin_js', EVENTS_ASSETS_URL . 'ee-cat-admin.js', array('jquery-validate'), EVENT_ESPRESSO_VERSION, TRUE );
		
		global $eei18n_js_strings;
		$eei18n_js_strings['add_cat_name'] = __('Category Name is a required field. Please enter a value in order to continue.', 'event_espresso');
		wp_localize_script( 'ee_cat_admin_js', 'eei18n', $eei18n_js_strings );

	}





	public function load_scripts_styles_edit() {
		//styles
		wp_enqueue_style('jquery-ui-style');
		wp_register_style( 'espresso_venues', EE_VENUES_ASSETS_URL . 'ee-venues-admin.css', array(), EVENT_ESPRESSO_VERSION );
		wp_enqueue_style('espresso_venues');

		//scripts
		wp_register_script('espresso_venue_admin', EE_VENUES_ASSETS_URL . 'ee-venues-admin.js', array('jquery-validate'), EVENT_ESPRESSO_VERSION, TRUE );
		wp_enqueue_script('espresso_venue_admin');

		global $eei18n_js_strings;
		$eei18n_js_strings['required'] = __( 'This is a required field. Please add a value in order to continue.', 'event_espresso' );
		wp_localize_script( 'espresso_venue_admin', 'eei18n', $eei18n_js_strings );

	}






	protected function _set_list_table_views_default() {
		$this->_views = array(
			'all' => array(
				'slug' => 'all',
				'label' => __('All', 'event_espresso'),
				'count' => 0,
				'bulk_action' => array(
					'restore_venues' => __('Restore_from Trash', 'event_espresso'),
					'trash_venues' => __('Move to Trash', 'event_espresso'),
					'delete_venues' => __('Delete Permanently', 'event_espresso')
					)
				)
		);
	}





	protected function _set_list_table_views_category_list() {
		$this->_views = array(
			'all' => array(
				'slug' => 'all',
				'label' => __('All', 'event_espresso'),
				'count' => 0,
				'bulk_action' => array(
					'delete_categories' => __('Delete Permanently', 'event_espresso'),
					'export_categories' => __('Export Categories', 'event_espresso'),
					)
				)
		);
	}





	protected function _overview_list_table() {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$this->_admin_page_title .= $this->_get_action_link_or_button('create_new', 'add', array(), 'button add-new-h2');
		$this->display_admin_list_table_page_with_sidebar();
	}



	public function extra_misc_actions_publish_box() {
		$extra_rows = array(
			'vnu_capacity' => $this->_cpt_model_obj->capacity(),
			'vnu_url' => $this->_cpt_model_obj->venue_url(),
			'vnu_phone' => $this->_cpt_model_obj->phone()
			);
		$template = EE_VENUES_TEMPLATE_PATH . 'venue_publish_box_extras.template.php';
		espresso_display_template( $template, $extra_rows );
	}



	protected function _venue_editor_metaboxes() {

		add_meta_box( 'espresso_venue_address_options', __('Physical Location', 'event_espresso'), array( $this, 'venue_address_metabox'), $this->page_slug, 'side', 'default' );
		add_meta_box( 'espresso_venue_gmap_options', __('Google Map', 'event_espresso'), array( $this, 'venue_gmap_metabox'), $this->page_slug, 'side', 'default' );
		add_meta_box( 'espresso_venue_virtual_loc_options', __('Virtual Location', 'event_espresso'), array( $this, 'venue_virtual_loc_metabox'), $this->page_slug, 'side', 'default' );

	}



	public function venue_gmap_metabox() {
		global $org_options;
		$template_args = array(
			'vnu_enable_for_gmap' => EE_Form_Fields::select_input('vnu_enable_for_gmap', $this->get_yes_no_values(), $this->_cpt_model_obj->enable_for_gmap() ),
			'vnu_google_map_link' => $this->_cpt_model_obj->google_map_link(),
			'org_options' => $org_options
			);
		$template = EE_VENUES_TEMPLATE_PATH . 'venue_gmap_metabox_content.template.php';
		espresso_display_template( $template, $template_args );
	}



	public function venue_address_metabox() {

		//states and countries model
		require_once( EE_MODELS . 'EEM_State.model.php' );
		require_once( EE_MODELS . 'EEM_Country.model.php');

		$states = EEM_State::instance()->get_all_active_states();
		$countries = EEM_Country::instance()->get_all_active_countries();

		//prepare state/country arrays
		foreach ( $states as $id => $obj ) {
			$st_ary[$id] = $obj->name();
		}

		foreach ( $countries as $id => $obj ) {
			$ctry_ary[$id] = $obj->name();
		}


		$template_args = array(
			'_venue' => $this->_cpt_model_obj,
			'states_dropdown' => EE_Form_Fields::select_input('sta_id', $st_ary, $this->_cpt_model_obj->state_ID(), 'id="sat_id"'),
			'countries_dropdown' => EE_Form_Fields::select_input('cnt_iso', $ctry_ary, $this->_cpt_model_obj->country_ID(), 'id="cnt_iso"')
			);

		$template = EE_VENUES_TEMPLATE_PATH . 'venue_address_metabox_content.template.php';
		espresso_display_template( $template, $template_args );
	}






	public function venue_virtual_loc_metabox() {
		$template_args = array(
			'_venue' => $this->_cpt_model_obj
			);
		$template = EE_VENUES_TEMPLATE_PATH . 'venue_virtual_location_metabox_content.template.php';
		espresso_display_template( $template, $template_args );
	}






	/**
	 * Handles updates for venue cpts
	 * @param  int    $post_id ID of Venue CPT
	 * @param  object $post    Post object (with "blessed" WP properties)
	 * @return void
	 */
	protected function _insert_update_cpt_item( $post_id, $post ) {
		$wheres = array( $this->_venue_model->primary_key_name() => $post_id );

		$venue_values = array(
			'VNU_address' => !empty( $this->_req_data['vnu_address'] ) ? $this->_req_data['vnu_address'] : NULL,
			'VNU_address2' => !empty( $this->_req_data['vnu_address2'] ) ? $this->_req_data['vnu_address2'] : NULL,
			'VNU_city' => !empty( $this->_req_data['vnu_city'] ) ? $this->_req_data['vnu_city'] : NULL,
			'STA_ID' => !empty( $this->_req_data['sta_id'] ) ? $this->_req_data['sta_id'] : NULL,
			'CNT_ISO' => !empty( $this->_req_data['cnt_iso'] ) ? $this->_req_data['cnt_iso'] : NULL,
			'VNU_zip' => !empty( $this->_req_data['vnu_zip'] ) ? $this->_req_data['vnu_zip'] : NULL,
			'VNU_phone' => !empty( $this->_req_data['vnu_phone'] ) ? $this->_req_data['vnu_phone'] : NULL,
			'VNU_capacity' => !empty( $this->_req_data['vnu_capacity'] ) ? $this->_req_data['vnu_capacity'] : NULL,
			'VNU_url' => !empty( $this->_req_data['vnu_url'] ) ? $this->_req_data['vnu_url'] : NULL,
			'VNU_virtual_phone' => !empty( $this->_req_data['vnu_virtual_phone'] ) ? $this->_req_data['vnu_virtual_phone'] : NULL,
			'VNU_virtual_url' => !empty( $this->_req_data['vnu_virtual_url'] ) ? $this->_req_data['vnu_virtual_url'] : NULL,
			'VNU_enable_for_gmap' => isset( $this->_req_data['vnu_enable_for_gmap'] ) ? TRUE : FALSE,
			'VNU_google_map_link' => !empty( $this->_req_data['vnu_google_map_link'] ) ? $this->_req_data['vnu_google_map_link'] : NULL
			);
		
		//update venue
		$success = $this->_venue_model->update( $venue_values, array( $wheres ) );

		//get venue_object for other metaboxes that might be added via the filter... though it would seem to make sense to just use $this->_venue_model->get_one_by_ID( $post_id ).. i have to setup where conditions to override the filters in the model that filter out autodraft and inherit statuses so we GET the inherit id!
		$get_one_where = array( $this->_venue_model->primary_key_name() => $post_id, 'status' => $post->post_status  );
		$venue = $this->_venue_model->get_one( array( $get_one_where ) );

		//notice we've applied a filter for venue metabox callbacks but we don't actually have any default venue metaboxes in use.  So this is just here for addons to more easily hook into venue saves.
		$venue_update_callbacks = apply_filters( 'FHEE_venue_editor_update', array() );

		$att_success = TRUE;

		foreach ( $venue_update_callbacks as $v_callback ) {
			$_succ = call_user_func_array( $v_callback, array( $venue,  $this->_req_data ) );
			$att_success = !$att_success ? $att_success : $_succ; //if ANY of these updates fail then we want the appropriate global error message
		}

		//any errors?
		if ( $success && !$att_success ) {
			EE_Error::add_error( __('Venue Details saved successfully but something went wrong with saving attachments.', 'event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
		} else if ( $success === FALSE ) {
			EE_Error::add_error( __('Venue Details did not save successfully.', 'event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
		}	
	}





	public function trash_cpt_item( $post_id ) {
		$this->_req_data['VNU_ID'] = $post_id;
		$this->_trash_or_restore_venue( 'trash', FALSE );
	}






	public function restore_cpt_item( $post_id ) {
		$this->_req_data['VNU_ID'] = $post_id;
		$this->_trash_or_restore_venue( 'draft', FALSE );
	}





	public function delete_cpt_item( $post_id ) {
		$this->_req_data['VNU_ID'] = $post_id;
		$this->_delete_venue( FALSE );
	}






	public function get_venue_object() {
		return $this->_cpt_model_obj;
	}




	protected function _trash_or_restore_venue( $venue_stats = 'trash', $redirect_after = TRUE ) {
		$VNU_ID = isset( $this->_req_data['VNU_ID'] ) ? absint( $this->_req_data['VNU_ID'] ) : FALSE;

		//loop thru venues
		if ( $VNU_ID ) {
			//clean status
			$venue_status = strtoupper( sanitize_key( $venue_status ) );
			// grab status
			if (!empty($venue_status)) {
				$success = $this->_change_venue_status($VNU_ID, $venue_status);
			} else {
				$success = FALSE;
				$msg = __('An error occured. The venue could not be moved to the trash because a valid venue status was not not supplied.', 'event_espresso');
				EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			}
		} else {
			$success = FALSE;
			$msg = __('An error occured. The venue could not be moved to the trash because a valid venue ID was not not supplied.', 'event_espresso');
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
		}
		$action = $venue_status == 'trash' ? 'moved to the trash' : 'restored from the trash';

		if ( $redirect_after )
			$this->_redirect_after_action($success, 'Venue', $action, array('action' => 'default'));

	}





	protected function _trash_or_restore_venues( $venue_status = 'trash' ) {
		// clean status
		$venue_status = strtoupper(sanitize_key($venue_status));
		// grab status
		if (!empty($venue_status)) {
			$success = TRUE;
			//determine the event id and set to array.
			$VNU_IDs = isset($this->_req_data['VNU_IDs']) ? (array) $this->_req_data['VNU_IDs'] : array();
			// loop thru events
			foreach ($VNU_IDs as $VNU_ID) {
				if ($VNU_ID = absint($VNU_ID)) {
					$results = $this->_change_venue_status($VNU_ID, $venue_status);
					$success = $results !== FALSE ? $success : FALSE;
				} else {
					$msg = sprintf(__('An error occured. Venue #%d could not be moved to the trash because a valid event ID was not not supplied.', 'event_espresso'), $VNU_ID);
					EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
					$success = FALSE;
				}
			}
		} else {
			$success = FALSE;
			$msg = __('An error occured. The venue could not be moved to the trash because a valid venue status was not not supplied.', 'event_espresso');
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
		}
		// in order to force a pluralized result message we need to send back a success status greater than 1
		$success = $success ? 2 : FALSE;
		$action = $venue_status == 'trash' ? 'moved to the trash' : 'restored from the trash';
		$this->_redirect_after_action($success, 'Venues', $action, array('action' => 'default'));
	}





	/**
	 * _trash_or_restore_venues
	 *
	 * //todo this is pretty much the same as the corresponding change_event_status method in Events_Admin_Page.  We should probably abstract this up to the EE_Admin_Page_CPT (or even EE_Admin_Page) and make this a common method accepting a certain number of params.
	 *
	 * @access  private
	 * @param  int $event_id 
	 * @param  string $event_status 
	 * @return void
	 */
	private function _change_venue_status( $VNU_ID = FALSE, $venue_status = FALSE ) {
		// grab venue id
		if (!$VNU_ID) {
			$msg = __('An error occured. No Venue ID or an invalid Venue ID was received.', 'event_espresso');
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			return FALSE;
		}

		$this->_set_model_object( $VNU_ID );

		// clean status
		$venue_status = strtoupper(sanitize_key($venue_status));
		// grab status
		if (empty($venue_status)) {
			$msg = __('An error occured. No Venue Status or an invalid Venue Status was received.', 'event_espresso');
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			return FALSE;
		}
		
		// was event trashed or restored ?
		switch ($venue_status) {
			case 'draft' :
				$action = 'restored from the trash';
				$hook = 'AHEE_venue_restored_from_trash';
				break;
			case 'trash' :
				$action = 'moved to the trash';
				$hook = 'AHEE_venue_moved_to_trash';
				break;
			default :
				$action = 'updated';
				$hook = FALSE;
		}
		//use class to change status
		$this->_cpt_model_obj->set_status( $venue_status );
		$success = $this->_cpt_model_obj->save();
		
		if ($success === FALSE) {
			$msg = sprintf(__('An error occured. The venue could not be %s.', 'event_espresso'), $action);
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			return FALSE;
		}
		if ($hook) {
			do_action($hook);
		}
		return TRUE;
	}


	/**
	 * todo ... move this to parent (pretty much the same logic as in Events Admin) (same with delete_venues and permanently delete venue)
	 * @param  boolean $redirect_after [description]
	 * @return [type]                  [description]
	 */
	protected function _delete_venue( $redirect_after = TRUE ) {
		//determine the venue id and set to array.
		$VNU_ID = isset($this->_req_data['VNU_ID']) ? absint($this->_req_data['VNU_ID']) : NULL;
		$VNU_ID = isset( $this->_req_data['id'] ) ? absint( $this->_req_data['id'] ) : NULL;


		// loop thru venues
		if ($VNU_ID) {
			$success = $this->_permanently_delete_venue( $VNU_ID );
		} else {
			$success = FALSE;
			$msg = __('An error occured. An venue could not be deleted because a valid venue ID was not not supplied.', 'event_espresso');
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
		}
		if ( $redirect_after )
			$this->_redirect_after_action($success, 'Venue', 'deleted', array('action' => 'default'));
	}



	protected function _delete_venues() {
		$success = TRUE;
		//determine the event id and set to array.
		$VNU_IDs = isset($this->_req_data['VNU_IDs']) ? (array) $this->_req_data['VNU_IDs'] : array();
		// loop thru events
		foreach ($VNU_IDs as $VNU_ID) {
			if ($VNU_ID = absint($VNU_ID)) {
				$results = $this->_permanently_delete_venue($VNU_ID);
				$success = $results !== FALSE ? $success : FALSE;
			} else {
				$success = FALSE;
				$msg = __('An error occured. An venue could not be deleted because a valid venue ID was not not supplied.', 'event_espresso');
				EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			}
		}
		// in order to force a pluralized result message we need to send back a success status greater than 1
		$success = $success ? 2 : FALSE;
		$this->_redirect_after_action($success, 'Events', 'deleted', array('action' => 'default'));
	}




	//todo: put in parent
	private function _permanently_delete_venue($VNU_ID = FALSE) {
		// grab event id
		if (!$VNU_ID = absint($VNU_ID)) {
			$msg = __('An error occured. No Venue ID or an invalid Venue ID was received.', 'event_espresso');
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			return FALSE;
		}
		
		
		$this->_set_model_object( $VNU_ID );
		$success = $this->_cpt_model_obj->delete();
		// did it all go as planned ?
		if ($success) {
			$msg = sprintf(__('Venue ID # %d has been deleted.', 'event_espresso'), $VNU_ID);
			EE_Error::add_success($msg);
		} else {
			$msg = sprintf(__('An error occured. Venue ID # %d could not be deleted.', 'event_espresso'), $VNU_ID);
			EE_Error::add_error($msg, __FILE__, __FUNCTION__, __LINE__);
			return FALSE;
		}
		do_action('AHEE_venue_permanently_deleted');
		return TRUE;
	}

	


	/***********/
	/* QUERIES */


	public function get_venues( $per_page = 10, $count = FALSE ) {
		global $wpdb;

		$_orderby = !empty( $this->_req_data['orderby'] ) ? $this->_req_data['orderby'] : '';

		switch ( $_orderby ) {
			case 'id':
				$orderby = 'VNU_id';
				break;

			case 'capacity':
				$orderby = 'VNU_capacity';
				break;

			case 'city':
				$orderby = 'VNU_city';
				break;

			default:
				$orderby = 'VNU_name';
		}


		$sort = ( isset( $this->_req_data['order'] ) && ! empty( $this->_req_data['order'] )) ? $this->_req_data['order'] : 'ASC';

		$current_page = isset( $this->_req_data['paged'] ) && !empty( $this->_req_data['paged'] ) ? $this->_req_data['paged'] : 1;
		$per_page = isset( $per_page ) && !empty( $per_page ) ? $per_page : 10;
		$per_page = isset( $this->_req_data['perpage'] ) && !empty( $this->_req_data['perpage'] ) ? $this->_req_data['perpage'] : $per_page;


		$offset = ($current_page-1)*$per_page;
		$limit = array($offset, $per_page);

		$where = array(
			'status' => isset( $this->_req_data['venue_status'] ) && $this->_req_data['venue_status'] != '' ? $this->_req_data['venue_status'] : 'publish'
			//todo add filter by category
			);

		$venues = $count ? $this->_venue_model->count( array($where), 'VNU_ID' ) : $this->_venue_model->get_all( array( $where, 'limit' => $limit, 'order_by' => $orderby, 'order' => $sort ) );

		return $venues;

	}




	/** Venue Category Stuff **/

	/**
	 * set the _category property with the category object for the loaded page.
	 *
	 * @access private
	 * @return void
	 */
	private function _set_category_object() {
		if ( isset( $this->_category->id ) && !empty( $this->_category->id ) )
			return; //already have the category object so get out.

		//set default category object
		$this->_set_empty_category_object();
		
		//only set if we've got an id
		if ( !isset($this->_req_data['VEN_CAT_ID'] ) ) {
			return;
		}

		$category_id = absint($this->_req_data['VEN_CAT_ID']);
		$term = get_term( $category_id, 'espresso_venue_categories' );


		if ( !empty( $term ) ) {
			$this->_category->category_name = $term->name;
			$this->_category->category_identifier = $term->slug;
			$this->_category->category_desc = $term->description;
			$this->_category->id = $term->term_id;
		}
	}




	private function _set_empty_category_object() {
		$this->_category = new stdClass();
		$this->_category->id = $this->_category->category_name = $this->_category->category_identifier = $this->_category->category_desc = '';
	}



	public function unique_id_help_tab() {
		?>		
			<h2><?php _e('Unique Category Identifier', 'event_espresso'); ?></h2>
			<p><?php _e('This should be a unique identifier for the category. Example: "category1" (without qoutes.)', 'event_espresso'); ?></p>
			<p><?php printf( __('The unique ID can also be used in individual pages using the %s shortcode', 'event_espresso'), '[EVENT_ESPRESSO_CATEGORY category_id="category_identifier"]' ); ?>.</p>		
		<?php
	}




	protected function _category_list_table() {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$this->_admin_page_title .= $this->_get_action_link_or_button('add_category', 'add_category', array(), 'button add-new-h2');
		$this->display_admin_list_table_page_with_sidebar();
	}


	protected function _category_details($view) {

		//load formatter helper
		require_once EVENT_ESPRESSO_PLUGINFULLPATH . '/helpers/EE_Formatter.helper.php';
		//load field generator helper
		require_once EVENT_ESPRESSO_PLUGINFULLPATH . '/helpers/EE_Form_Fields.helper.php';

		$route = $view == 'edit' ? 'update_category' : 'insert_category';
		$this->_set_add_edit_form_tags($route);

		$this->_set_category_object();
		$id = !empty($this->_category->id) ? $this->_category->id : '';

		$delete_action = $this->_category->category_identifier == 'uncategorized' ? FALSE : 'delete_category';

		$this->_set_publish_post_box_vars( 'category_id', $id, $delete_action );

		//take care of contents
		$this->_template_args['admin_page_content'] = $this->_category_details_content();
		$this->display_admin_page_with_sidebar();
	}



	protected function _category_details_content() {
		$editor_args['category_desc'] = array(
			'type' => 'wp_editor',
			'value' => EE_Formatter::admin_format_content($this->_category->category_desc),
			'class' => 'my_editor_custom'
		);
		$_wp_editor = $this->_generate_admin_form_fields( $editor_args, 'array' );
		$template_args = array(
			'category' => $this->_category,
			'unique_id_info_help_link' => $this->_get_help_tab_link('unique_id_info'),
			'category_desc_editor' =>  $_wp_editor['category_desc']['field'],
			'disable' => $this->_category->category_identifier == 'uncategorized' ? ' disabled' : '',
			'disabled_message' => $this->_category->category_identifier == 'uncategorized' ? TRUE : FALSE
			);
		$template = EVENTS_TEMPLATE_PATH . 'event_category_details.template.php';
		return espresso_display_template($template, $template_args, TRUE );
	}


	protected function _delete_categories() {
		$cat_ids = isset( $this->_req_data['VEN_CAT_ID'] ) ? (array) $this->_req_data['VEN_CAT_ID'] : (array) $this->_req_data['category_id'];

		foreach ( $cat_ids as $cat_id ) {
			$this->_delete_category($cat_id);
		}

		//doesn't matter what page we're coming from... we're going to the same place after delete.
		$query_args = array(
			'action' => 'category_list'
			);
		$this->_redirect_after_action(0,'','',$query_args);

	}





	protected function _delete_category($cat_id) {
		global $wpdb;
		$cat_id = absint( $cat_id );
		wp_delete_term( $cat_id, 'espresso_venue_categories' );
	}



	protected function _insert_or_update_category($new_category) {

		$cat_id = $new_category ? $this->_insert_category() : $this->_insert_category( TRUE );
		$success = 0; //we already have a success message so lets not send another.
		$query_args = array(
			'action' => 'edit_category', 
			'VEN_CAT_ID' => $cat_id
		);
		$this->_redirect_after_action( $success, '','', $query_args );

	}



	private function _insert_category( $update = FALSE ) {
		global $wpdb;
		$cat_id = '';
		$category_name= $this->_req_data['category_name'];
		$category_identifier = $this->_req_data['category_identifier'];
		$category_desc= $this->_req_data['category_desc']; 


	
		$term_args=array(
			'category_name'=>$category_name, 
			'slug'=>$category_identifier, 
			'description'=>$category_desc,
			//'parent'=>$espresso_wp_user //eventually this will be added.
		);
		
		$insert_ids = $update ? wp_update_term( $category_name, 'espresso_venue_categories', $term_args ) :wp_insert_term( $category_name, 'espresso_venue_categories', $term_args );

		if ( !is_array( $insert_ids ) ) {
			$msg = __( 'An error occured and the category has not been saved to the database.', 'event_espresso', 'event_espresso' );
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
		} else {
			$cat_id = $insert_ids['term_id'];
			$msg = sprintf ( __('The category %s was successfuly created', 'event_espresso'), $category_name );
			EE_Error::add_success( $msg );
		}
		
		return $cat_id;
	}


	/**
	 * TODO handle category exports()
	 * @return file export
	 */
	protected function _categories_export() {

		//todo: I don't like doing this but it'll do until we modify EE_Export Class.
		$new_request_args = array(
			'export' => 'report',
			'action' => 'categories',
			'category_ids' => $this->_req_data['VEN_CAT_ID']
			);

		$this->_req_data = array_merge( $this->_req_data, $new_request_args );

		if ( file_exists( EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Export.class.php') ) {
			require_once( EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Export.class.php');
			$EE_Export = EE_Export::instance( $this->_req_data );
			$EE_Export->export();
		}

	}





	protected function _import_categories() {

		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Import.class.php');
		EE_Import::instance()->import();

	}




	public function get_categories( $per_page = 10, $current_page = 1, $count = FALSE ) {
		global $wpdb;

		//testing term stuff
		$orderby = isset( $this->_req_data['orderby'] ) ? $this->_req_data['orderby'] : 'Term.term_id';
		$order = isset( $this->_req_data['order'] ) ? $this->_req_data['order'] : 'DESC';
		$limit = ($current_page-1)*$per_page;


		$query_params = array(
			0 => array( 'taxonomy' => 'espresso_venue_categories' ),
			'order_by' => array( $orderby => $order ),
			'limit' => $limit . ',' . $per_page,
			'force_join' => array('Term')
			);

		$categories = $count ? EEM_Term_Taxonomy::instance()->count( $query_params, 'term_id' ) :EEM_Term_Taxonomy::instance()->get_all( $query_params );

		return $categories;
	}


	/* end category stuff */
	/**************/


	
} //end Venues_Admin_Page class