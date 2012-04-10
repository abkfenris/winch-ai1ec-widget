<?php
/*
Plugin Name: Winchendon Ai1ec Agenda Widget
Plugin URI: 
Description: A customized widget to display a modified version of the Seed Networks All in 1 Event Calendar Data on the homepage of the website. Built off of version 1.5 of AI1EC.
Version:
Author: Alex Kerney
Author URI: http://alexkerney.com
*/
/**
 * Ai1ec_Agenda_Widget_Winch class
 * 
 * A customized widget for The Seed Network's All In 1 Event Calendar Wordpress Plugin
 */
 
class Ai1ec_Agenda_Widget_Winch extends WP_Widget {
	/**
	 * __construct function
	 * Constructor function
	 * Registers the widget with WordPress
	 */
	function __construct() {
		parent::__construct(
			'ai1ec_agenda_widget_winch', // Base ID
			__( 'Upcoming Events Winch', AI1EC_PLUGIN_NAME), // Name
			array(
				'description' => __('AI1EC Winchendon Widget: Lists Upcoming Events on the Homepage', AI1EC_PLUGIN_NAME ), 'class' => 'ai1ec-agenda-widget-winch',
			) // Args
		);
	}
	
	/**
	 * form function
	 * Renders the widget's configuration form for the Manage Widgets page.
	 * @param array $instance The data array for the widget instance being configured
	 *
	 * Outputs the options form on admin.
	 */
	function form( $instance ){
		global $ai1ec_view_helper;
		
		$default = array(
			'title'					=> __( 'Upcoming Events Winchendon' , AI1EC_PLUGIN_NAME ),
			'events_per_page'		=> 10,
			'show_subscibe_buttons' => true,
			'show_calendar_button'	=> true,
			'hide_on_calendar_page'	=> true,
			'limit_by_cat'			=> false,
			'limit_by_tag'			=> false,
			'limit_by_post'			=> false,
			'event_cat_ids'			=> array(),
			'event_tag_ids'			=> array(),
			'event_post_ids'		=> array(),
		);
		$instance = wp_parse_args( (array) $instance, $default );
		
		// Get available cats, tags, events to allow user to limit widget to certain categories
		$events_categories	= get_terms( 'events_categories', array( 'orderby' => 'name', "hide_empty" => false ) );
		$events_tags		= get_terms( 'events_tags', array( 'orderby' => 'name', "hide_empty" => false) );
		$get_events			= new WP_Query( array ( 'post_type' => AI1EC_POST_TYPE, 'posts_per_page' => -1 ) );
		$events_options		= $get_events->posts;
		
		// Generate unique IDs and NAMEs of all needed form fields
		$fields = array(
			'title'					=> array('value'	=> $instance['title']),
			'events_per_page'		=> array('value'	=> $instance['events_per_page']),
			'show_subscibe_buttons' => array('value'	=> $instance['show_subscribe_buttons']),
			'show_calendar_button'	=> array('value'	=> $instance['show_calendar_button']),
			'hide_on_calendar_page'	=> array('value'	=> $instance['hide_on_calendar_page']),
			'limit_by_cat'			=> array('value'	=> $instance['limit_by_cat']),
			'limit_by_tag'			=> array('value'	=> $instance['limit_by_tag']),
			'limit_by_post'			=> array('value'	=> $instance['limit_by_post']),
			'event_cat_ids'			=> array(
											 'value'	=> $instance['event_cat_ids'],
											 'options'	=> $events_categories
											 ),
			'event_tag_ids'			=> array(
											 'value'	=> $instance['event_tag_ids'],
											 'options'	=> $events_tags
											 ),
			'event_post_ids'		=> array(
											 'value'	=> $instance['title'],
											 'options'	=> $events_options
											 ),
		);
		foreach( $fields as $field => $data ) {
			$fields[$field]['id']		= $this->get_field_id( $field );
			$fields[$field]['name']		= $this->get_field_name( $field);
			$fields[$field]['value']	= $data['vaule'];
			if( isset($data['options'] ) ) {
				$fields[$field]['options'] = $data['options'];
			} 
		}
		
		// Show the form on the widget configuation page
		$ai1ec_view_helper->display( 'agenda-widget-form.php', $fields );
	}
	
	/**
	 * update function
	 *
	 * called when a user submits the widget configuration form. The data should be validated and returned.
	 *
	 * @param array $new_instance The new data that was submitted.
	 * @param array $old_instance The widget's old data.
	 * @return array The new data to save for this widget instance.
	 *
	 * Process the widget options to be saved
	 */
	function update( $new_instance, $old_instance ) {
		// save existing data as a base to modify with new data
		$instance = $old_instance;
		$instance['title']					= strip_tags( $new_instance['title'] );
		$instance['events_per_page']		= intval( $new_instance['events_per_page'] );
		if( $instance['events_per_page'] < 1 ) $instance['events_per_page'] = 1;
		$instance['show_subscribe_button']	= $new_instance['show_subscribe_buttons'] ? true : false;
		$instance['show_calendar_button']	= $new_instance['show_calendar_button'] ? true : false;
		$instance['hide_on_calendar_page']	= $new_instance['hide_on_calendar_page'] ? true : false;
		
		// For limits, set the limit to False if no IDs were selected, or set the respective IDs to empty if "limit by" was unchecked
		// If limit by catagories and categories given set the categories
		$instance['limit_by_cat'] = false;
		$instance['event_cat_ids'] = array ();
		if( isset( $new_instance['event_cat_ids'] ) && $new_instance['event_cat_ids'] != false ) {
			$instance['limit_by_cat'] = true;
		}
		if( isset( $new_instance['limit_by_cat'] ) && $new_instance['limit_by_cat'] != false ) {
			$instance['limit_by_cat'] = true;
		}
		if( isset( $new_instance['event_cat_ids'] ) && $instance['limit_by_cat'] === true ) {
			$instance['event_cat_ids'] = $new_instance['event_cat_ids'];
		}
		
		// If limit by tags and tags given set the tags
		$instance['limit_by_tag'] = false;
		$instance['event_tag_ids'] = array();
		if( isset( $new_instance['event_tag_ids'] ) && $new_instance['event_tag_ids'] != false ) {
			$instance['limit_by_tag'] = true;
		}
		if( isset( $new_instance['limit_by_tag'] ) && $new_instance['limit_by_tag'] != false ) {
			$instance['limit_by_tag'] = true;
		}
		if( isset( $new_instance['event_tag_ids'] ) && $instance['limit_by_tag'] === true ) {
			$instance['event_tag_ids'] = $new_instance['event_tag_ids'];
		}
		
		// If limit by posts and post given set the posts
		$instance['limit_by_post'] = false;
		$instance['event_post_ids'] = array();
		if( isset( $new_instance['event_post_ids'] ) && $instance['event_post_ids'] != false ) {
			$instance['limit_by_post'] = true;
		}
		if( isset( $new_instance['limit_by_post'] ) && $new_instance['limit_by_post'] != false ) {
			$instance['limit_by_post'] = true;
		}
		if( isset( $new_instance['event_post_ids'] ) && $instance['limit_by_post'] === true ) {
			$instance['event_post_ids'] = $new_instance['event_post_ids'];
		}
		
		return $instance;
	}
	
	/**
	 * widget function
	 * 
	 * Outputs the given instance of the widget to the front-end.
	 *
	 * @param array $args Display arguments passed to the widget
	 * @param array $instance The settings for this widget instance
	 *
	 * outputs the contents of the widget
	 */
	function widget( $args, $instance ){
		global $ai1ec_veiw_helper,
			   $ai1ec_events_helper,
			   $ai1ec_calendar_helper,
			   $ai1ec_settings;
		
		$defaults = array(
			'hide_on_calendar_page'		=> true,
			'event_cat_ids'				=> array(),
			'event_tag_ids'				=> array(),
			'event_post_ids'			=> array(),
			'events_per_page'			=> 10,
		);
		$instance = wp_parse_args( $instance, $defaults );
		
		if( $instance['hide_on_calendar_page'] && 
			is_page( $ai1ec_settings->calendar_page_id ) )
			return;
		
		// Add params to the subscribe_url for fildering by Limits (category, tag)
		$subscribe_filter  = '';
		$subscribe_filter .= $instance['event_cat_ids'] ? '&ai1ec_cat_ids=' . join( ',', $instance['event_cat_ids'] ) : '';
		$subscribe_filter .= $instance['event_tag_ids'] ? '&ai1ec_tag_ids=' . join( ',', $instance['event_tag_ids'] ) : '';
		$subscribe_filter .= $instance['event_post_ids'] ? '&ai1ec_post_ids=' . join( ',', $instance['event_post_ids'] ) : '';
		
		// Get localized time
		$timestamp = $ai1ec_events_helper->gmt_to_local( time() );
		
		// Set $limit ot the specified category/tag
		$limit = array(
			'cat_ids'	=> $instance['event_cat_ids'],
			'tag_ids'	=> $instance['event_tag_ids'],
			'post_ids'	=> $instance['event_post_ids'],
		);
		
		// Get events, then classify into date array
		$event_results = $ai1ec_calendar_helper->get_events_relative_to( $timestamp, $instance['events_per_page'], 0, $limit );
		$dates = $ai1ec_calendar_helper->get_agenda_date_array( $event_results['events'] );
		
		$args['title']					= $instance['title'];
		$args['show_subscribe_buttons']	= $instance['show_subscribe_buttons'];
		$args['show_calendar_buttons']	= $instance['show_calendar_button'];
		$args['dates']					= $dates;
		$args['calendar_url']			= $ai1ec_calendar_helper->get_calendar_url( null, $limit );
		$args['subscribe_url']			= AI1EC_EXPORT_URL . $subscribe_filter;
		
		// Show it!
		$ai1ec_view_helper->display( 'agenda-widget.php', $args );
	}
	
} // Final Bracket to Close the Widget

add_action( 'widgets_init', create_function( '', 'register_widget( "Ai1ec_Agenda_Widget_Winch");') );
?>
