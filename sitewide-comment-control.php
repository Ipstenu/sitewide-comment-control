<?php
/*
Plugin Name: Sitewide Comment Control
Plugin URI: http://halfelf.org/plugins/sitewide-comment-control/
Description: Block specific users from commenting network wide by user ID or email.
Version: 1.6
Author: Mika Epstein (Ipstenu)
Author URI: http://ipstenu.org/
Network: true

Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)

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

// Internationalization
add_action( 'init', 'ippy_scc_internationalization' );
function ippy_scc_internationalization() {
	load_plugin_textdomain('ippy_scc', false, 'sitewide-comment-control/languages' );
}

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
    for($i = 0; $i < $ippy_scc_size; $i++)
        {
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
		add_site_option('ippy_scc_keys','spammer@example.com');
		add_site_option('ippy_scc_type','blackhole');
		}

// Options Pages

// add the admin options page
add_action('network_admin_menu', 'ippy_scc_admin_add_page');
function ippy_scc_admin_add_page() {
	global $ippy_scc_options_page;
	$ippy_scc_options_page = add_submenu_page('settings.php', __('Sitewide Comment Control', 'ippy_scc'), __('Comment Control', 'ippy_scc'), 'manage_networks', 'ippy_scc', 'ippy_scc_options');
}

function ippy_scc_plugin_help() {
	global $ippy_scc_options_page;
	$screen = get_current_screen();
	if ($screen->id != 'settings_page_ippy_scc-network')
		return;
		
	$screen->add_help_tab( array(
		'id'      => 'ippy-ssc-base',
		'title'   => __('Readme', 'sfc'),
		'content' => 
		'<h3>' . __('Sitewide Comment Control', 'sfc') .'</h3>' .
		'<p>' . __( 'When you run a network, blacklisting commenters is handled per-site. That\'s normally okay, but sometimes people decide to be trolls and spam your whole network. This plugin allows you to ban, spam or moderate an unregistered commenter network wide. It does not replace the per-site blacklists or moderation lists, but simply adds on to it.', 'ippy_scc' ) . '</p>' .
		'<p>' . __( 'When a user posts a comment and they\'re on the list, they are redirected to the post they just tried to comment on, but their comment has been shunted to the mysterious black hole along with your socks. If you pick \'blackhole\', no one will ever see the comment. Pick \'spam\' and they go to spam. Pick \'moderate\' and the post is forced into moderation.', 'ippy_scc' ) . '</p>' . 
		'<p>' . __( 'A sample email of spammer@example.com is included in the plugin for you to play with.', 'ippy_scc' ) . '</p>' .
		'<p>' . __( 'Limited free support can be found in the WordPress forums.','sfc').'</p>'.
		'<ul>'.
			'<li><a href="http://wordpress.org/tags/sitewide-comment-control?forum_id=10#postform">'. __( 'Support Forums','sfc').'</a></li>'.
			'<li><a href="http://tech.ipstenu.org/my-plugins/sitewide-comment-control/">'. __( 'Plugin Site','sfc').'</a></li>'.
			'<li><a href="https://www.wepay.com/donations/halfelf-wp">'. __( 'Donate','sfc').'</a></li>'.
		'</ul>'
	));

}
add_action('contextual_help', 'ippy_scc_plugin_help', 10, 3);

register_activation_hook( __FILE__, 'ippy_scc_activate' );

// donate link on manage plugin page

add_filter('plugin_row_meta', 'ippy_scc_donate_link', 10, 2);
function ippy_scc_donate_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
                $donate_link = '<a href="https://www.wepay.com/donations/halfelf-wp">Donate</a>';
                $links[] = $donate_link;
        }
        return $links;
}

// Settings Page
function ippy_scc_options() {

global $wpdb;
?>
<div class="wrap">

<div id="icon-edit-comments" class="icon32"></div>
<h2><?php _e("Sitewide Comment Control", 'ippy_scc'); ?></h2>

<?php

        if (isset($_POST['update']))
        {
        // Update the Blacklist
            if ($ippy_scc_keys = $_POST['ippy_scc_keys'])
            {
                    $ippy_scc_array = explode("\n", $ippy_scc_keys);
                    sort ($ippy_scc_array);
                    $ippy_scc_string = implode("\n", $ippy_scc_array);
                    update_site_option('ippy_scc_keys', $ippy_scc_string);
            }

        // Update the Key (spam or blackhole)
            if ( isset( $_POST['ippy_scc_type'] ) )
            {
                   update_site_option( 'ippy_scc_type', $_POST['ippy_scc_type'] );
            }
?>
        <div id="message" class="updated fade"><p><strong><?php _e('Options Updated!', 'ippy_scc'); ?></strong></p></div>

<?php   } ?>

<form method="post" width='1'>

<legend><h3><?php _e("Mark the comment as...", 'ippy_scc'); ?></h3></legend>
<p><?php _e("Select which option should be used to process comments. Using 'blackhole' with delete the comment without any notification to the commenter or the admin, 'Mark as Spam' will put the comment in the site's spam list, and using 'Moderate' will put the comment in that site's moderation queue.", 'ippy_scc'); ?></p>

<?php $ippy_scc_type = get_site_option('ippy_scc_type'); ?>

<select name="ippy_scc_type" id="ippy_scc_type">
<option value="blackhole" <?php if ( $ippy_scc_type == 'blackhole') echo 'selected="selected"'; ?> ><?php _e("Blackhole", 'ippy_scc'); ?></option>
<option value="spam" <?php if ( $ippy_scc_type == 'spam') echo 'selected="selected"'; ?> ><?php _e("Mark as Spam", 'ippy_scc'); ?></option>
<option value="moderate" <?php if ( $ippy_scc_type == 'moderate') echo 'selected="selected"'; ?> ><?php _e("Moderate", 'ippy_scc'); ?></option>
</select>


<legend><h3><?php _e("Email List", 'ippy_scc'); ?></h3></legend>
<p><?php _e("The email addresses added below will not be allowed to leave comments on any site on your network. Partial email addresses are accepted, but use this wisely. If you put 'a' in the form, all email addresses with the letter 'a' will be flagged.", 'ippy_scc'); ?></p>

<textarea name="ippy_scc_keys" cols="40" rows="15"><?php
        $ippy_scc_keys = get_site_option('ippy_scc_keys');
        echo $ippy_scc_keys;
?></textarea>

<p><input class='button-primary' type='submit' name='update' value='<?php _e("Update Options", 'ippy_scc'); ?>' id='submitbutton' /></p>
</form>

</div> <?php
}