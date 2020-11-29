<?php
/*
Plugin Name: MemberPress Cancel Sub From Email
Plugin URI: https://www.fahdmurtaza.com/contact/
Description: Cancel Subscriptions via email
Version: 2020.11.0
Requires at least: 5.0
Author: Fahad Murtaza
Author URI: https://www.fahdmurtaza.com/contact/
*/

// Requires MemberPress
if ( ( defined( 'TESTS_RUNNING' ) && TESTS_RUNNING ) || is_plugin_active( 'memberpress/memberpress.php' ) ) {

	function gravixar_mepr_cancel_sub( $sub_id ) {
		require_once( __DIR__ . '/../memberpress/memberpress.php' );

		$txn_current = MeprTransaction::get_one_by_trans_num( $sub_id );
		$t_data      = MeprTransaction::get_one( $txn_current->id );

		if ( ! empty( $t_data ) ) {
			$txn = new MeprTransaction();
			$txn->load_data( $t_data );
			$txn->status = MeprTransaction::$failed_str;
			$txn->update( $txn );

			return $txn->status === "failed";

		}

		return false;
	}

}

register_activation_hook( __FILE__, 'gravixar_mepr_cancel_sub_activate' );

function gravixar_mepr_cancel_sub_activate() {
	gravixar_create_custom_cancellation_page( 'mepr-cancel-transaction' );
}

function gravixar_create_custom_cancellation_page( $page_name ) {
	$pageExists = false;
	$pages      = get_pages();
	foreach ( $pages as $page ) {
		if ( $page->post_name == $page_name ) {
			$pageExists = true;
			break;
		}
	}
	if ( ! $pageExists ) {
		wp_insert_post( [
			'post_type'   => 'page',
			'post_name'   => $page_name,
			'post_status' => 'publish',
		] );
	}
}

add_filter( 'page_template', 'catch_mypath' );

function catch_mypath( $page_template ) {
	if ( is_page( 'mepr-cancel-transaction' ) ) {
		$page_template = __DIR__ . '/mepr-cancel-transaction.php';
	}

	return $page_template;
}
