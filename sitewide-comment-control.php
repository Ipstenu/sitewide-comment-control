<?php
/*
Plugin Name: Sitewide Comment Control
Plugin URI: http://halfelf.org/plugins/sitewide-comment-control/
Description: Block specific users from commenting network wide by user ID or email.
Version: 3.0
Author: Mika Epstein (Ipstenu)
Author URI: http://halfelf.org/
Network: true

Copyright 2012-18 Mika Epstein (email: ipstenu@halfelf.org)

	This file is part of Sitewide Comment Control, a plugin for WordPress.

	Sitewide Comment Control is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	(at your option) any later version.

	Sitewide Comment Control is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with WordPress.  If not, see <http://www.gnu.org/licenses/>.
*/

// First we check to make sure you meet the requirements
global $wp_version;
$exit_msg_ms  = 'Sorry, but this plugin is not supported (and will not work) on WordPress single installs.';
$exit_msg_ver = 'Sorry, but this plugin is not supported on pre-3.3 WordPress installs.';
if ( !is_multisite() ) { exit( $exit_msg_ms ); }
if ( version_compare( $wp_version, '3.2', '<' ) ) { exit( $exit_msg_ver ); }

class Sitewide_Comment_Control() {
	
	/*
	 * Starter defines and vars for use later
	 *
	 * @since 3.0
	 */

	// Holds option data.
	var $option_name = 'sitewide_comment_control_options';
	var $option_defaults;

	// DB version, for schema upgrades.
	var $db_version = 1;

	/**
	 * Construct
	 *
	 * @since 3.0
	 * @access public
	 */
	public function __construct() {
		add_filter( 'preprocess_comment', array( &$this, 'preprocess_comments' ) );
		add_action( 'network_admin_menu', array( &$this, 'network_admin_menu' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );

		// Setting plugin defaults here:
		$this->option_defaults = array( 
			'db_version' => $this->db_version,
			'keys'       => 'spammer@example.com', 
			'type'       => 'moderate',
			'group'      => false,
			'logged_in'  => false,
		);

		// Check for upgrades
		$this->check_upgrade();
	}

	/**
	 * Plugin row meta content
	 *
	 * @since 1.0
	 * @access public
	 */
	function plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$donate_link = '<a href="https://ko-fi.com/A236CEN/">Donate</a>';
			$links[]     = $donate_link;
		}
		return $links;
	}

	/**
	 * Check upgrade
	 * Make sure to only moderate legit content
	 *
	 * @since 3.0
	 * @access public
	 */
	function check_upgrade ( $data ) {

		// Fetch and set up options.
		$this->options = wp_parse_args( get_site_option( $this->option_name ), $this->option_defaults, false );

		// Version 1 MIGHT be an upgrade from the old version...
		if ( $this->options['db_version'] == 1 ) {
			// Migrate the old shit
			if ( get_site_option( 'scc_keys' ) !== false ) {
				$this->options['keys'] = get_site_option( 'scc_keys' );
				delete_site_option( 'scc_keys' );
			}
	
			// Migrate the old shit
			if ( get_site_option( 'scc_type' ) !== false ) {
				$this->options['type'] = get_site_option( 'scc_type' );
				delete_site_option( 'scc_type' );
			}
		}
		
		// Update:
		update_site_option( $this->option_name, $this->options );
	}

	/**
	 * Preprocess Comments
	 * Make sure to only moderate legit content
	 *
	 * @since 1.0
	 * @access public
	 */
	function preprocess_comments ( $data ) {
		extract ( $data );
		
		// it's a pingback or trackback so let it through.
		if ( '' != $comment_type ) { return $data; }
		
		// It's a logged in user and the check for logged in users is false, we'll let it go
		get_currentuserinfo();
		if ( is_user_logged_in() && $this->options['logged_in'] == false ) { return $data; }
	
		// Get blacklist
		$scc_string = $this->options['keys'];

		// If group is allowed, we'll check _all_ sites...
		if ( $this->options['group'] == true ) {
			// aggregate all blacklists on ALL sites
			// Add them to $scc_string
		}

		// Format Blacklist
		$scc_array  = explode( '\n', $scc_string );
		$scc_size   = sizeof( $scc_array );
	
		// Go through blacklist
		for( $i = 0; $i < $scc_size; $i++ ) {
			$scc_current = trim( $scc_array[$i] );
			$scc_type_now = $this->options['type'];
			if ( $scc_type_now == 'moderate' ) $scc_type_now = 0;
				if( stripos( $comment_author_email, $scc_current ) !== false ) {
					if ( $this->options['type'] == 'blackhole' ) {
						wp_redirect( get_permalink() ); die;
					}
					if ( $this->options['type'] == 'spam' || 'moderate' ) {
						$time = current_time('mysql'); // Get the date
						$result = array(
							'comment_post_ID'      => $comment_post_ID,
							'comment_author'       => $comment_author,
							'comment_author_email' => $comment_author_email,
							'comment_author_url'   => $comment_author_url,
							'comment_content'      => $comment_content,
							'comment_type'         => $comment_type,
							'comment_parent'       => $comment_parent,
							'user_id'              => $user_ID,
							'comment_author_IP'    => $_SERVER['REMOTE_ADDR'],
							'comment_agent'        => $_SERVER['HTTP_USER_AGENT'],
							'comment_date'         => $time,
							'comment_approved'     => $scc_type_now,
						);
						wp_insert_comment( $result );
					}
				wp_safe_redirect( $_SERVER['HTTP_REFERER'] ); die;
				}
			}
		return $data;
	}

	/**
	 * Network Admin Page
	 *
	 * Since 1.0
	 * 
	 * @access public
	 * @return void
	 */
	function network_admin_menu() {
		global $scc_options_page;
		$scc_options_page = add_submenu_page('settings.php', __( 'Sitewide Comment Control', 'sitewide-comment-control' ), __( 'Comment Control', 'sitewide-comment-control' ), 'manage_networks', 'scc_', array( &$this, 'network_setting_page' )  );
	}

	/**
	 * Options
	 *
	 * The options page. Since this has to run on Multisite, it can't use the settings API.
	 *
	 * @since 1.0
	 * @access public
	 */
	function options() { 
		?>
		<div class="wrap">
	
		<h1><?php _e( 'Sitewide Comment Control', 'sitewide-comment-control' ); ?></h1>

		<?php settings_errors();

			if ( is_network_admin() ) {
				?><form method="post" width='1'><?php
					wp_nonce_field( 'scc_networksave' ); 
					do_settings_sections( 'sitewide-comment-control-settings' );
					submit_button( '', 'primary', 'update');
				?></form>
		</div>
		<?php
	}

	/**
	 * Register Admin Settings
	 *
	 * @since 3.0
	 */
	function register_settings() {
		register_setting( 'sitewide-comment-control', 'scc_options', array( &$this, 'scc_sanitize' ) );

		// The main section
		add_settings_section( 'scc-settings', '', array( &$this, 'scc_settings_callback'), 'sitewide-comment-control-settings' );

		// The Fields
		add_settings_field( 'message', __( 'Options', 'sitewide-comment-control' ), array( &$this, 'options_callback'), 'sitewide-comment-control-settings', 'scc-settings' );
		add_settings_field( 'blacklist', __( 'The Blacklist', 'sitewide-comment-control' ), array( &$this, 'blacklist_callback'), 'sitewide-comment-control-settings', 'scc-settings' );
	}

	/**
	 * Settings Callback
	 *
	 * @since 3.0
	 */
	function scc_settings_callback() {
		?><p><?php _e( 'Customize Sitewide Comment Control via the settings below.', 'sitewide-comment-control' ); ?></p><?php
	}

	/**
	 * The Blacklist Callback
	 *
	 * @since 3.0
	 */
	function blacklist_callback() {
		?>
		<p><?php _e( 'The email addresses added below will not be allowed to leave comments on any site on your network. Partial email addresses are accepted, but use this wisely. If you put \'a\' in the form, all email addresses with the letter \'a\' will be flagged.', 'sitewide-comment-control' ); ?></p>
		
		<p><textarea name="scc_options[keys]" id="scc_options[keys]" cols="40" rows="15"><?php
			echo esc_textarea( $this->options['keys'] );
		?></textarea>
		<?php
	}

	/**
	 * Options Callback
	 *
	 * @since 3.0
	 */
	function options_callback() {
		?>
		<p><?php _e( 'Customize your options as to how Sitewide Comment Control should run.', 'sitewide-comment-control' ); ?></p>
		<p><input type="checkbox" id="scc_options[logged_in]" name="scc_options[logged_in]" value="yes" <?php checked( $this->options['logged_in'], 'yes', true ); ?> <?php checked( $this->options['logged_in'], '1', true ); ?> >
		<label for="scc_options[logged_in]"><?php _e( 'Prevent logged in users from commenting if they\'re on the ban list.', 'sitewide-comment-control' ); ?></label></p>

		<p><input type="checkbox" id="scc_options[group]" name="scc_options[group]" value="yes" <?php checked( $this->options['group'], 'yes', true ); ?> <?php checked( $this->options['group'], '1', true ); ?> >
		<label for="scc_options[group]"><?php _e( 'Share ban lists from all sites on the network.', 'sitewide-comment-control' ); ?></label></p>
		<?php
	}

	/**
	 * Options sanitization and validation
	 *
	 * @param $input the input to be sanitized
	 * @since 3.0
	 */
	function scc_sanitize( $input ) {

		$options = $this->options;
		$input['db_version'] = $this->db_version;

		// Blacklist
		if( empty( $input['keys'] ) ) {
			$this->options['keys'] = '';
		} elseif( $input['keys'] != $this->options['keys'] ) {
			$new_blacklist = explode( '\n', $input['keys'] );
			$new_blacklist = array_filter( array_map( 'trim', $new_blacklist ) );
			$new_blacklist = array_unique( $new_blacklist );
			foreach( $new_blacklist as &$keyname ) {
				$keyname = sanitize_text_field( $keyname );
			}
			$new_blacklist = implode( "\n", $new_blacklist );
			$input['keys'] = $new_blacklist;
		}

		// Moderation
		$valid_types        = array ( 'blackhole', 'spam', 'moderate' );
		$input['logged_in'] = sanitize_text_field( $input['logged_in'] );
		if ( !isset($input['type']) || is_null( $input['type'] ) || !in_array( $input['type'], $valid_types ) ) {
			$input['logged_in'] = 'moderate';
		}

		// Logged In
		if ( !isset($input['logged_in']) || is_null( $input['logged_in'] ) || $input['logged_in'] == '0' ) {
			$input['logged_in'] = 'no';
		} else {
			$input['logged_in'] = 'yes';
		}

		// Group List
		if ( !isset($input['group']) || is_null( $input['group'] ) || $input['group'] == '0' ) {
			$input['group'] = 'no';
		} else {
			$input['group'] = 'yes';
		}

		return $input;
	}

}

new Sitewide_Comment_Control();