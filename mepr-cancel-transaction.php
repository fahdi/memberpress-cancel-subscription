<?php
get_header();
?>

    <main style="width: 80%; margin: 100px auto; text-align: center">
		<?php
		if ( isset( $_GET["transaction_cancel"] ) && trim( esc_sql( $_GET["transaction_cancel"] ) ) == 'true' && isset( $_GET["transaction_id"] ) ):

			$transaction_id = esc_sql( $_GET["transaction_id"] );

			if ( gravixar_mepr_cancel_sub( $transaction_id ) ) :
				echo __( "Your subscription has been cancelled. We are not going to send you future reminders" );
			else:
				echo __( "Your subscription could not be cancelled. Please contact us to cancel your subscription" );
			endif;
		endif;
		?>
    </main>
<?php
get_footer();
