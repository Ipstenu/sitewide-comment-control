<?php
/*
Plugin Name: Sitewide Comment Control
Plugin URI: http://github.com/ipstenu/sitewide-comment-control
Description: Block specific users from commenting network wide by user ID or email.
Version: 3.0
Author: Mika Epstein (Ipstenu)
Author URI: http://halfelf.org/
Network: true

Copyright 2012-19 Mika Epstein (email: ipstenu@halfelf.org)

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

$exit_msg = array(
	'multisite' => __( 'Sorry, but this plugin is not supported (and will not work) on WordPress single installs.', 'sitewide-comment-control' ),
	'version'   => __( 'Sorry, but this plugin is not supported on pre-3.3 WordPress installs.', 'sitewide-comment-control' ),
);

if ( ! is_multisite() ) {
	exit( wp_kses_post( $exit_msg['multisite'] ) );
}
if ( version_compare( $wp_version, '3.2', '<' ) ) {
	exit( wp_kses_post( $exit_msg['version'] ) );
}

/**
 * Main class
 * @since 3.0
 */
class Sitewide_Comment_Control {

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
		add_filter( 'preprocess_comment', array( $this, 'preprocess' ) );
		add_action( 'comment_post', array( $this, 'postprocess' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'donate_link' ), 10, 2 );

		// Rename option and clean up
		if ( get_site_option( 'ippy_scc_keys' ) ) {
			$sitewide_comment_control = array(
				'blacklist' => array( 'trash@example.com' ),
				'modlist'   => explode( "\n", get_site_option( 'ippy_scc_keys' ) ),
				'spamlist'  => array( 'spammer@example.com' ),
				'wildcards' => false,
			);
			update_site_option( 'sitewide_comment_control', $sitewide_comment_control );
			delete_site_option( 'ippy_scc_keys' );
			delete_site_option( 'ippy_scc_type' );
		}
	}

	public function activate() {
		$sitewide_comment_control = array(
			'blacklist' => array( 'trash@example.com' ),
			'modlist'   => array( 'moderate@example.com' ),
			'spamlist'  => array( 'spammer@example.com' ),
			'wildcards' => false,
		);
		update_site_option( 'sitewide_comment_control', $sitewide_comment_control );
	}

	public function donate_link( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$links[] = '<a href="https://ko-fi.com/A236CEN/">Donate</a>';
		}
		return $links;
	}

	public function preprocess( $data ) {

		// Get the data
		$scc_data = get_site_option( 'sitewide_comment_control' );

		// Assume they're not jerks
		$is_bad = false;

		// Bail early if it's not a comment or the user is logged in OR if the blacklist is empty.
		if ( '' !== $data['comment_type'] || '' === $data['user_ID'] || empty( $scc_data['blacklist'] ) ) {
			return $data;
		}

		// If this person is already on a blacklist for a site on the network, we trust it.
		if ( wp_blacklist_check( $data['comment_author'], $data['comment_author_email'], $data['comment_author_url'], $data['comment_content'], $data['user_ip'], $data['user_agent'] ) ) {
			return $data;
		}

		// Run the checks
		if ( $this->blacklist_check( 'trash', $data['comment_author'], $data['comment_author_email'], $data['user_ip'] ) ) {
			$data['comment_approved'] = 0;
			$data['comment_karma']    = 999;
			$add_to_comment           = __( '-- FLAGGED AS TRASH BY NETWORK ADMIN', 'sitewide-comment-control' );
			$data['comment_content'] .= "\n\n" . $add_to_comment;
		} elseif ( $this->blacklist_check( 'spam', $data['comment_author'], $data['comment_author_email'], $data['user_ip'] ) ) {
			$data['comment_approved'] = 0;
			$data['comment_karma']    = 666;
			$add_to_comment           = __( '-- FLAGGED AS SPAM BY NETWORK ADMIN', 'sitewide-comment-control' );
			$data['comment_content'] .= "\n\n" . $add_to_comment;
		} elseif ( $this->blacklist_check( 'moderate', $data['comment_author'], $data['comment_author_email'], $data['user_ip'] ) ) {
			$data['comment_approved'] = 0;
		}

		return $data;
	}

	/**
	 * Blacklist Check
	 * @param  [string] $comment_author_email
	 * @param  [string] $comment_author
	 * @param  [string] $user_ip
	 * @return [bool]   true is the email or IP is on the blacklist.
	 */
	public function blacklist_check( $type, $comment_author, $comment_author_email, $user_ip ) {
		$scc_data = get_site_option( 'sitewide_comment_control' );

		switch ( $type ) {
			case 'trash':
				$words = $scc_data['blacklist'];
				break;
			case 'spam':
				$words = $scc_data['spamlist'];
				break;
			default:
				$words = $scc_data['modlist'];
				break;
		}

		// Get blacklist
		foreach ( $words as $word ) {
			$word = trim( $word );

			if ( empty( $word ) ) {
				continue;
			}

			/*
			 * Do some escaping magic so that '#' (number of) characters in the spam
			 * words don't break things:
			 */
			$preg_word = preg_quote( $word, '#' );

			/*
			 * Check the comment fields for moderation keywords. If any are found,
			 * mark is_bad as true.
			 * Else, check if for wildcards from the same domain.
			 */
			$pattern = "#$preg_word#i";
			if ( preg_match( $pattern, $comment_author ) || preg_match( $pattern, $comment_author_email ) || preg_match( $pattern, $user_ip ) ) {
				return true;
			} elseif ( $scc_data['wildcards'] && is_email( $word ) ) {
				$scc_parts = explode( '@', $word );
				$com_parts = explode( '@', $data['comment_author_email'] );
				// If the username from the blocked list is contained in the username
				// of the commenter AND they have the some domain, then we're assuming
				// the worst.
				$pattern2 = "#$scc_parts[0]#i";
				if ( preg_match( $pattern2, $com_parts[0] ) && $scc_parts[1] === $com_parts[1] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Post process any comments
	 * @param  [int] $comment_ID
	 * @param  [int] $comment_approved
	 * @param  [array] $commentdata
	 * @return N/A
	 *
	 * If the comment is karma'd the way we think is bad, treat it appropriately
	 */
	public function postprocess( $comment_ID, $comment_approved, $commentdata ) {
		switch ( $commentdata['comment_karma'] ) {
			case '999':
				wp_trash_comment( $comment_ID );
				break;
			case '666':
				wp_spam_comment( $comment_ID );
				break;
		}
	}

	public function network_admin_menu() {
		global $scc_options_page;
		$scc_options_page = add_submenu_page( 'settings.php', __( 'Sitewide Comment Control', 'sitewide-comment-control' ), __( 'Comment Control', 'sitewide-comment-control' ), 'manage_networks', 'sitewide_comment_control', array( $this, 'options_page' ) );
	}

	public function options_page() {
		$sitewide_comment_control = get_site_option( 'sitewide_comment_control' );
		$blacklist                = ( is_array( $sitewide_comment_control['blacklist'] ) ) ? implode( "\n", $sitewide_comment_control['blacklist'] ) : '';
		$spamlist                 = ( is_array( $sitewide_comment_control['spamlist'] ) ) ? implode( "\n", $sitewide_comment_control['spamlist'] ) : '';
		$modlist                  = ( is_array( $sitewide_comment_control['modlist'] ) ) ? implode( "\n", $sitewide_comment_control['modlist'] ) : '';
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Sitewide Comment Control', 'sitewide-comment-control' ); ?></h2>

			<?php
			if ( isset( $_POST['update'] ) && check_admin_referer( 'scc_saveit' ) ) {

				$new_blacklist = $_POST['helf_scc_blacklist'];
				$new_spamlist  = $_POST['helf_scc_spamlist'];
				$new_modlist   = $_POST['helf_scc_modlist'];

				// Check Blacklist
				if ( empty( $_POST['helf_scc_blacklist'] ) ) {
					$new_blacklist = '';
				} else {
					$new_blacklist = explode( "\n", $new_blacklist );
					$new_blacklist = array_filter( array_map( 'trim', $new_blacklist ) );
					$new_blacklist = array_filter( array_map( 'sanitize_text_field', $new_blacklist ) );
					$new_blacklist = array_unique( $new_blacklist );
					$blacklist     = implode( "\n", $new_blacklist );
				}

				// Check Spamlist
				if ( empty( $_POST['helf_scc_spamlist'] ) ) {
					$new_spamlist = '';
				} else {
					$new_spamlist = explode( "\n", $new_spamlist );
					$new_spamlist = array_filter( array_map( 'trim', $new_spamlist ) );
					$new_spamlist = array_filter( array_map( 'sanitize_text_field', $new_spamlist ) );
					$new_spamlist = array_unique( $new_spamlist );
					$spamlist     = implode( "\n", $new_spamlist );
				}

				// Check Spamlist
				if ( empty( $_POST['helf_scc_modlist'] ) ) {
					$new_modlist = '';
				} else {
					$new_modlist = explode( "\n", $new_modlist );
					$new_modlist = array_filter( array_map( 'trim', $new_modlist ) );
					$new_modlist = array_filter( array_map( 'sanitize_text_field', $new_modlist ) );
					$new_modlist = array_unique( $new_modlist );
					$modlist     = implode( "\n", $new_modlist );
				}

				// Update Wildcard if needed
				$new_wildcard = ( isset( $_POST['helf_scc_wildcard'] ) ) ? 1 : 0;

				// Rebuild and save
				$new_scc = array(
					'blacklist' => $new_blacklist,
					'modlist'   => $new_modlist,
					'spamlist'  => $new_spamlist,
					'wildcards' => $new_wildcard,
				);

				update_site_option( 'sitewide_comment_control', $new_scc );

				?>
				<div id="message" class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Options Updated!', 'sitewide-comment-control' ); ?></strong></p></div>
				<?php
			}
			?>

			<form method="post" width='1'>
				<?php wp_nonce_field( 'scc_saveit' ); ?>

				<table class="form-table"><tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Check for Wildcards', 'sitewide-comment-control' ); ?></th>
						<td><fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Check for Wildcards', 'sitewide-comment-control' ); ?></span></legend>
							<label for="helf_scc_wildcard"><input name="helf_scc_wildcard" type="checkbox" id="helf_scc_wildcard" value="1" <?php checked( $sitewide_comment_control['wildcards'], 1 ); ?>/> <?php wp_kses_post( _e( 'Attempt to check for email addresses with the same username on the same domain. Please use with extreme caution.', 'sitewide-comment-control' ) ); ?></label></fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Comment Moderation', 'sitewide-comment-control' ); ?></th>
						<td>
							<fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Comment Moderation', 'sitewide-comment-control' ); ?></span></legend>
								<p><label for="helf_scc_modlist"><?php esc_html_e( 'When a comment contains any of these words in its author name, email, or IP address, it will be held in the moderation queue on all sites on the network. One word or IP address per line. It will match inside words, so "press" will match "matt@wordpress.org" as well as "press@example.com".', 'sitewide-comment-control' ); ?></label></p>
								<p><textarea name="helf_scc_modlist" rows="10" cols="50" id="helf_scc_modlist" class="large-text code"><?php echo $modlist; ?></textarea></p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Comment Blacklist', 'sitewide-comment-control' ); ?></th>
						<td>
							<fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Comment Blacklist', 'sitewide-comment-control' ); ?></span></legend>
								<p><label for="helf_scc_blacklist"><?php esc_html_e( 'When a comment contains any of these words in its author name, email, or IP address, it will be sent to trash on all sites on the network. One word or IP address per line. It will match inside words, so "press" will match "matt@wordpress.org" as well as "press@example.com".', 'sitewide-comment-control' ); ?></label></p>
								<p><textarea name="helf_scc_blacklist" rows="10" cols="50" id="helf_scc_blacklist" class="large-text code"><?php echo $blacklist; ?></textarea></p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Comment Spam List', 'sitewide-comment-control' ); ?></th>
						<td>
							<fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Comment Spam List', 'sitewide-comment-control' ); ?></span></legend>
								<p><label for="helf_scc_spamlist"><?php esc_html_e( 'When a comment contains any of these words in its author name, email, or IP address, it will be sent to spam on all sites on the network. One word or IP address per line. It will match inside words, so "press" will match "matt@wordpress.org" as well as "press@example.com".', 'sitewide-comment-control' ); ?></label></p>
								<p><textarea name="helf_scc_spamlist" rows="10" cols="50" id="helf_scc_spamlist" class="large-text code"><?php echo $spamlist ?></textarea></p>
							</fieldset>
						</td>
					</tr>
				</tbody></table>

				<p><input class='button-primary' type='submit' name='update' value='<?php esc_html_e( 'Update Options', 'sitewide-comment-control' ); ?>' id='submitbutton' /></p>
			</form>
		</div>
		<?php
	}

}

new Sitewide_Comment_Control();
