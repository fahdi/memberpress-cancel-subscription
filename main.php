<?php
/*
Plugin Name: MemberPress Cancel Sub From Email
Plugin URI: https://www.fahdmurtaza.com/contact/
Description: Cancel Subscriptions via email
Version: 2020.11.1
Requires at least: 5.0
Author: Fahad Murtaza
Author URI: https://www.fahdmurtaza.com/contact/
Text Domain: memberpress-cancel-subscription
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether MemberPress is loaded. Evaluated at call time, not at plugin load time:
 * is_plugin_active() is only defined in wp-admin and MemberPress may load after us.
 */
function gravixar_mepr_available() {
	return class_exists( 'MeprTransaction' );
}

/**
 * Signed token that authorizes cancelling exactly one transaction.
 *
 * Use this when building the link in the cancellation e-mail:
 *   gravixar_mepr_cancel_link( $txn->trans_num )
 */
function gravixar_mepr_cancel_token( $trans_num ) {
	return hash_hmac( 'sha256', 'gravixar_mepr_cancel|' . (string) $trans_num, wp_salt( 'auth' ) );
}

/**
 * Cancellation URL for a transaction, carrying its signed token.
 */
function gravixar_mepr_cancel_link( $trans_num ) {
	$page = get_page_by_path( 'mepr-cancel-transaction' );
	$base = $page ? get_permalink( $page ) : home_url( '/mepr-cancel-transaction/' );

	return add_query_arg( array(
		'transaction_cancel' => 'true',
		'transaction_id'     => rawurlencode( (string) $trans_num ),
		'token'              => gravixar_mepr_cancel_token( $trans_num ),
	), $base );
}

/**
 * Whether the current request is allowed to cancel this transaction:
 * either it carries the signed token for it, or the logged-in user owns it.
 *
 * @param MeprTransaction $txn
 * @param string          $token
 */
function gravixar_mepr_can_cancel( $txn, $token ) {
	if ( empty( $txn ) || empty( $txn->id ) ) {
		return false;
	}

	if ( is_string( $token ) && '' !== $token && hash_equals( gravixar_mepr_cancel_token( $txn->trans_num ), $token ) ) {
		return true;
	}

	if ( is_user_logged_in() && (int) $txn->user_id === get_current_user_id() ) {
		return true;
	}

	return current_user_can( 'manage_options' );
}

/**
 * Look up a transaction by its gateway transaction number.
 *
 * @return MeprTransaction|null
 */
function gravixar_mepr_get_transaction( $trans_num ) {
	if ( ! gravixar_mepr_available() || '' === trim( (string) $trans_num ) ) {
		return null;
	}

	$row = MeprTransaction::get_one_by_trans_num( $trans_num );

	if ( empty( $row ) || empty( $row->id ) ) {
		return null;
	}

	return new MeprTransaction( $row->id );
}

/**
 * Cancel the given transaction (and its recurring subscription, when there is one).
 *
 * NOTE: this performs no authorization; callers must check gravixar_mepr_can_cancel() first.
 *
 * @param string $sub_id Gateway transaction number
 *
 * @return bool
 */
function gravixar_mepr_cancel_sub( $sub_id ) {
	$txn = gravixar_mepr_get_transaction( $sub_id );

	if ( null === $txn ) {
		return false;
	}

	$cancelled = false;

	// Recurring: stop the gateway from billing again.
	if ( ! empty( $txn->subscription_id ) ) {
		$sub = $txn->subscription();

		if ( $sub && ! empty( $sub->id ) && MeprSubscription::$cancelled_str !== $sub->status ) {
			try {
				$sub->cancel();
				$cancelled = true;
			} catch ( Exception $e ) {
				error_log( 'gravixar_mepr_cancel_sub: ' . $e->getMessage() );
			}
		} elseif ( $sub && MeprSubscription::$cancelled_str === $sub->status ) {
			$cancelled = true;
		}
	}

	$txn->status = MeprTransaction::$failed_str;
	$txn->store();

	return $cancelled || MeprTransaction::$failed_str === $txn->status;
}

register_activation_hook( __FILE__, 'gravixar_mepr_cancel_sub_activate' );

function gravixar_mepr_cancel_sub_activate() {
	gravixar_create_custom_cancellation_page( 'mepr-cancel-transaction' );
}

function gravixar_create_custom_cancellation_page( $page_name ) {
	if ( get_page_by_path( $page_name ) ) {
		return;
	}

	wp_insert_post( array(
		'post_type'   => 'page',
		'post_name'   => $page_name,
		'post_title'  => __( 'Cancel subscription', 'memberpress-cancel-subscription' ),
		'post_status' => 'publish',
	) );
}

add_filter( 'page_template', 'gravixar_mepr_cancel_page_template' );

function gravixar_mepr_cancel_page_template( $page_template ) {
	if ( is_page( 'mepr-cancel-transaction' ) ) {
		$page_template = __DIR__ . '/mepr-cancel-transaction.php';
	}

	return $page_template;
}
