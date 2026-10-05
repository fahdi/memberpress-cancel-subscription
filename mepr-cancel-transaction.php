<?php
/**
 * Template for the /mepr-cancel-transaction/ page.
 *
 * Flow: the e-mail link (GET, with a signed token) shows a confirmation form;
 * the cancellation itself only happens on the POST from that form.
 */
get_header();

$trans_num = isset( $_REQUEST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['transaction_id'] ) ) : '';
$token     = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : '';
$wants     = isset( $_REQUEST['transaction_cancel'] ) && 'true' === sanitize_text_field( wp_unslash( $_REQUEST['transaction_cancel'] ) );
$message   = '';
$show_form = false;

if ( $wants && '' !== $trans_num ) {
	$txn = function_exists( 'gravixar_mepr_get_transaction' ) ? gravixar_mepr_get_transaction( $trans_num ) : null;

	if ( null === $txn || ! gravixar_mepr_can_cancel( $txn, $token ) ) {
		// Same message whether the transaction is missing or not ours: do not leak which IDs exist.
		$message = __( 'This cancellation link is invalid or has expired. Please contact us to cancel your subscription.', 'memberpress-cancel-subscription' );
	} elseif ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['gravixar_confirm'] ) ) {
		$nonce_ok = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'gravixar_mepr_cancel_' . $trans_num );

		if ( ! $nonce_ok && ! ( '' !== $token && hash_equals( gravixar_mepr_cancel_token( $trans_num ), $token ) ) ) {
			$message = __( 'Your session has expired. Please open the cancellation link again.', 'memberpress-cancel-subscription' );
		} elseif ( gravixar_mepr_cancel_sub( $trans_num ) ) {
			$message = __( 'Your subscription has been cancelled. You will not be billed again.', 'memberpress-cancel-subscription' );
		} else {
			$message = __( 'Your subscription could not be cancelled. Please contact us to cancel your subscription.', 'memberpress-cancel-subscription' );
		}
	} else {
		$show_form = true;
	}
}
?>

	<main style="width: 80%; margin: 100px auto; text-align: center">
		<?php if ( '' !== $message ) : ?>
			<p><?php echo esc_html( $message ); ?></p>
		<?php elseif ( $show_form ) : ?>
			<p><?php esc_html_e( 'Are you sure you want to cancel your subscription?', 'memberpress-cancel-subscription' ); ?></p>
			<form method="post" action="">
				<input type="hidden" name="transaction_cancel" value="true">
				<input type="hidden" name="transaction_id" value="<?php echo esc_attr( $trans_num ); ?>">
				<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
				<?php wp_nonce_field( 'gravixar_mepr_cancel_' . $trans_num ); ?>
				<button type="submit" name="gravixar_confirm" value="1"><?php esc_html_e( 'Yes, cancel my subscription', 'memberpress-cancel-subscription' ); ?></button>
			</form>
		<?php endif; ?>
	</main>
<?php
get_footer();
