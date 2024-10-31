<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'ABSPATH is not defined! "Script didn\' run on WordPress."' );
}
if ( ! is_admin() ) {
	die( 'Not enough privileges' );
}

?>
<form method="post" action="options.php">
	<?php
		settings_fields( 'wcotc_settings' );
	?>
	<table>
		<tr><th colspan="2"><?php _e( 'Custom e-mail message', 'order-tracking-codes-for-woocommerce' ); ?></th></tr>
		<tr>
			<td colspan="2">
				<!--
				<textarea type="text" name="<?php echo 'wcotc_email_message'; ?>" placeholder="<?php _e( 'Add your custom message. Use {order_tracking_code} in the message to display the tracking code.', 'order-tracking-codes-for-woocommerce' ); ?>"><?php echo esc_attr( get_option( 'wcotc_email_message' ) ); ?></textarea>
				-->
				<?php
					echo '<p>' . __( 'Add your custom message. Use {order_tracking_code} in the message to display the tracking code.', 'order-tracking-codes-for-woocommerce' ) . '</p>';
					echo '<p><em>' . __( '*The default message is used if the custom message is empty or does not contain the tracking code', 'order-tracking-codes-for-woocommerce' ) . '</em></p>';
					wp_editor(
						get_option( 'wcotc_email_message' ),
						'wcotc_email_message',
						array(
							'wcotc_email_message',
							array(
								'textarea_name' => 'wcotc_email_message',
							),
						)
					);
					?>
			</td>
		</tr>
		<tr>
			<td colspan="2"><?php submit_button(); ?></td>
		</tr>
	</table>
</form>
