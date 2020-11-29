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

			return $txn->status;

		}

		return false;
	}

	echo gravixar_mepr_cancel_sub( 'mp-txn-5fbfc547d9ce9' );
}
