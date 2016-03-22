<?php
/*
Plugin Name: Sitewide Comment Control
Plugin URI: http://halfelf.org/plugins/sitewide-comment-control/
Description: Block specific users from commenting network wide by user ID or email.
Version: 2.1
Author: Mika Epstein (Ipstenu)
Author URI: http://halfelf.org/
Network: true

Copyright 2012-16 Mika Epstein (email: ipstenu@halfelf.org)

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
if( !is_multisite() ) { exit($exit_msg_ms); }
if (version_compare($wp_version,"3.2","<")) { exit($exit_msg_ver); }

// The Hook
add_filter ('preprocess_comment', 'ippy_scc_preprocess');

function ippy_scc_preprocess ($data) {

	extract ($data);
	
	// it's a pingback or trackback so let it through.
	if ('' != $comment_type) { return $data; }
	
	// It's a logged in user so it's good.
	get_currentuserinfo();
	if ( is_user_logged_in() ) { return $data; }

    // Get blacklist
    $ippy_scc_string = get_site_option('ippy_scc_keys');
    $ippy_scc_array = explode("\n", $ippy_scc_string);
    $ippy_scc_size = sizeof($ippy_scc_array);

    // Go through blacklist
    for($i = 0; $i < $ippy_scc_size; $i++) {
            $ippy_scc_current = trim($ippy_scc_array[$i]);
			$ippy_scc_type_now = get_site_option('ippy_scc_type');
			if ($ippy_scc_type_now == 'moderate') $ippy_scc_type_now = 0;
            if(stripos($comment_author_email, $ippy_scc_current) !== false) {
				if ( get_site_option('ippy_scc_type') == 'blackhole' ) {
					wp_redirect( get_permalink() ); die;
				}
				if ( get_site_option('ippy_scc_type') == 'spam' || 'moderate' ) {
					$time = current_time('mysql'); // Get the date
                    $result = array(
						'comment_post_ID' => $comment_post_ID,
						'comment_author' => $comment_author,
						'comment_author_email' => $comment_author_email,
						'comment_author_url' => $comment_author_url,
						'comment_content' => $comment_content,
						'comment_type' => $comment_type,
						'comment_parent' => $comment_parent,
						'user_id' => $user_ID,
						'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
						'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
						'comment_date' => $time,
						'comment_approved' => $ippy_scc_type_now,
					);
					wp_insert_comment($result);
				}
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] ); die;
			}
        }
		
	return $data;
}

// Create the options for the message and spam assassin and set some defaults.
function ippy_scc_activate() {
	add_site_option('ippy_scc_keys', 'spammer@example.com' );
	add_site_option('ippy_scc_type', 'blackhole' );
}
register_activation_hook( __FILE__, 'ippy_scc_activate' );

// Options Pages

// add the admin options page
add_action('network_admin_menu', 'ippy_scc_admin_add_page');
function ippy_scc_admin_add_page() {
	global $ippy_scc_options_page;
	$ippy_scc_options_page = add_submenu_page('settings.php', __('Sitewide Comment Control', 'sitewide-comment-control'), __('Comment Control', 'sitewide-comment-control'), 'manage_networks', 'ippy_scc', 'ippy_scc_options');
}

// donate link on manage plugin page

add_filter('plugin_row_meta', 'ippy_scc_donate_link', 10, 2);
function ippy_scc_donate_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
                $donate_link = '<a href="https://store.halfelf.org/donate/">Donate</a>';
                $links[] = $donate_link;
        }
        return $links;
}

// Settings Page
function ippy_scc_options() {

global $wpdb;
?>
<div class="wrap">
<h2><?php _e('Sitewide Comment Control', 'sitewide-comment-control'); ?></h2>

<?php
    if ( isset($_POST['update'] ) && check_admin_referer( 'scc_saveit') ) {

		if ( $new_scc_keys = $_POST['ippy_scc_keys'] )	{
			$new_scc_keys = explode( "\n", $new_scc_keys );
			$new_scc_keys = array_filter( array_map( 'trim', $new_scc_keys ) );
			$new_scc_keys = array_unique( $new_scc_keys );

			// Sanitize emails!
			foreach ($new_scc_keys as &$keyname) {
			    $keyname = sanitize_text_field($keyname);
			}

			$new_scc_keys = implode( "\n", $new_scc_keys );
			update_site_option('ippy_scc_keys', $new_scc_keys);
			
		} elseif ( empty( $_POST['ippy_scc_keys'] ) ) {
            	update_site_option('ippy_scc_keys', '');
		}

		// Update the Key (spam or blackhole)
        if ( isset( $_POST['ippy_scc_type'] ) ) {
	        
	        $scc_type_array = array ('blackhole', 'spam', 'moderate');
	        $new_scc_type = sanitize_text_field( $_POST['ippy_scc_type'] );
	        
	        // If it's invalid, fall back to Blackhole
	        if ( !in_array( $new_scc_type, $scc_type_array ) ) {
		        $new_scc_type = 'blackhole';
	        }
	        
	        // SANITIZE AND VALIDATE THIS
			update_site_option( 'ippy_scc_type', $new_scc_type );
        }
	?><div id="message" class="updated dismissable"><p><strong><?php _e('Options Updated!', 'sitewide-comment-control'); ?></strong></p></div><?php   
	} 
?>

<form method="post" width='1'>
	<?php wp_nonce_field( 'scc_saveit' ); ?>

<legend><h3><?php _e('Mark the comment as...', 'sitewide-comment-control'); ?></h3></legend>
<p><?php _e('Select which option should be used to process comments. Using \'blackhole\' with delete the comment without any notification to the commenter or the admin, \'Mark as Spam\' will put the comment in the site\'s spam list, and using \'Moderate\' will put the comment in that site\'s moderation queue.', 'sitewide-comment-control'); ?></p>

<?php $ippy_scc_type = get_site_option('ippy_scc_type'); ?>

<select name="ippy_scc_type" id="ippy_scc_type">
<option value="blackhole" <?php if ( $ippy_scc_type == 'blackhole') echo 'selected="selected"'; ?> ><?php _e('Blackhole', 'sitewide-comment-control'); ?></option>
<option value="spam" <?php if ( $ippy_scc_type == 'spam') echo 'selected="selected"'; ?> ><?php _e('Mark as Spam', 'sitewide-comment-control'); ?></option>
<option value="moderate" <?php if ( $ippy_scc_type == 'moderate') echo 'selected="selected"'; ?> ><?php _e('Moderate', 'sitewide-comment-control'); ?></option>
</select>


<legend><h3><?php _e('Email List', 'sitewide-comment-control'); ?></h3></legend>
<p><?php _e('The email addresses added below will not be allowed to leave comments on any site on your network. Partial email addresses are accepted, but use this wisely. If you put \'a\' in the form, all email addresses with the letter \'a\' will be flagged.', 'sitewide-comment-control'); ?></p>

<textarea name="ippy_scc_keys" cols="40" rows="15"><?php
        $ippy_scc_keys = get_site_option('ippy_scc_keys');
        echo $ippy_scc_keys;
?></textarea>

<p><input class='button-primary' type='submit' name='update' value='<?php _e('Update Options', 'sitewide-comment-control'); ?>' id='submitbutton' /></p>
</form>

</div> <?php
}