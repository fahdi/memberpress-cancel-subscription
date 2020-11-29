<?php
get_header();
?>

<main style="width: 80%; margin: 100px auto; text-align: center">
<?php
if ( gravixar_mepr_cancel_sub( 'mp-txn-5fbfc547d9ce9' ) ) :
	echo __( "Your subscription has been cancelled. We are not going to send you future reminders" );
else:
	echo __( "Your subscription could not been cancelled. Please contact uis to cancel your subscription" );
endif;
?>
</main>
<?php
get_footer();
