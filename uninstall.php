<?php

// This file is part of Sitewide Comment Control, a plugin for WordPress.
// This is the uninstall script.

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();

	delete_site_option( 'ippy_scc_keys' );
	delete_site_option( 'ippy_scc_type' );
	delete_site_option( 'sitewide_comment_control_options' );