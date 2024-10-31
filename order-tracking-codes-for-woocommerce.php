<?php
/**
 * Plugin Name: Order Tracking Codes for WooCommerce
 * Description: Add order tracking codes to WooCommerce orders and include them in "Order Completed" e-mails.
 * Version: 1.2.3
 * Author: nevma
 * Requires at least: 4.7
 * Author URI: https://nevma.gr
 * Text Domain: order-tracking-codes-for-woocommerce
 * Domain Path: /languages/
 * WC tested up to: 6.3
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCOTC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

if ( ! class_exists( 'Wecom_Order_Tracking_Codes' ) ) {

	class Wecom_Order_Tracking_Codes {

		// Instance of this class.
		protected static $instance = null;

		public function __construct() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			// Load translation files
			add_action( 'init', array( $this, 'add_translation_files' ) );

			// Show tracking code field in view order page
			add_action( 'woocommerce_view_order', array( $this, 'display_tracking_code_in_order_data' ), 20 );

			// Add tracking code field in admin order edit page
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box_for_tracking_code' ) );
			// Save tracking code field in admin order edit page
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_tracking_code' ), 10, 2 );

			// Add tracking code in order emai
			add_action( 'woocommerce_email_order_details', array( $this, 'add_tracking_code_to_email_order_meta' ), 1, 2 );

			// Show Voucher number on woocommerce order listing
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'order_items_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'order_items_column_cnt' ) );

			// Admin page
			add_action( 'admin_menu', array( $this, 'setup_menu' ) );

			// Add settings link to plugins page
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );

			// Register plugin settings fields
			register_setting( 'wcotc_settings', 'wcotc_email_message', array( 'sanitize_callback' => array( 'Wecom_Order_Tracking_Codes', 'wcotc_sanitize_code' ) ) );

		}

		public function add_translation_files() {
			load_plugin_textdomain( 'order-tracking-codes-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		public function order_items_column( $order_columns ) {
			$order_columns['wecom_tracking_code'] = __( 'Order Tracking Code', 'order-tracking-codes-for-woocommerce' );
			return $order_columns;
		}

		public function order_items_column_cnt( $colname ) {
			global $the_order; // the global order object
			if ( $colname == 'wecom_tracking_code' ) {
				// get voucher from the order object
				echo get_post_meta( $the_order->get_id(), '_wecom_tracking_code', true );
			}
		}

		public function display_tracking_code_in_order_data( $order_id ) {
			$tracking_code = get_post_meta( $order_id, '_wecom_tracking_code', true );
			if ( ! empty( $tracking_code ) ) {
				?>
					<h2><?php _e( 'Extra Information', 'order-tracking-codes-for-woocommerce' ); ?></h2>
					<table class="shop_table shop_table_responsive additional_info">
						<tbody>
							<tr>
								<th><?php _e( 'Order Tracking Code:', 'order-tracking-codes-for-woocommerce' ); ?></th>
								<td><?php echo $tracking_code; ?></td>
							</tr>
						</tbody>
					</table>
				<?php
			}
		}

		public function add_meta_box_for_tracking_code() {
			add_meta_box( 'wecom_tracking_code', __( 'Order Tracking Code', 'order-tracking-codes-for-woocommerce' ), array( $this, 'display_tracking_code_field_in_admin' ), 'shop_order', 'side', 'core' );
		}


		public function display_tracking_code_field_in_admin() {
			global $post;
			?>
				<div class="form-field form-field-wide">
					<div class="edit_address">
						<?php
						woocommerce_wp_text_input(
							array(
								'id'    => '_wecom_tracking_code',
								'label' => __(
									'',
									'order-tracking-codes-for-woocommerce'
								),
							)
						);
						?>
					</div>
				</div>
			<?php
		}

		public function save_tracking_code( $post_id, $post ) {
			update_post_meta( $post_id, '_wecom_tracking_code', wc_clean( $_POST['_wecom_tracking_code'] ) );
		}

		public function add_tracking_code_to_email_order_meta( $order, $sent_to_admin ) {

			$tracking_code     = get_post_meta( $order->get_id(), '_wecom_tracking_code', true );
			$is_order_complete = $order->has_status( 'completed' );
			if ( ! empty( $tracking_code ) && ! $sent_to_admin && $is_order_complete ) {
				$label_txt            = __( 'Order Tracking Code', 'order-tracking-codes-for-woocommerce' );
				$email_custom_message = get_option( 'wcotc_email_message' );
				$email_custom_message = str_replace( '{order_tracking_code}', $tracking_code, $email_custom_message );

				// Show default message if the custom message is empty or does not contain the tracking code
				if ( empty( $email_custom_message ) || strpos( $email_custom_message, $tracking_code ) === false ) {
					?>
						<p style="margin: 0 0 16px"><strong><?php echo $label_txt; ?>:</strong> <?php echo $tracking_code; ?></p>
					<?php
				} else {
					echo '<p style="margin: 0 0 16px">' . $email_custom_message . '</p>';
				}
			}
		}

		public static function wcotc_sanitize_code( $input ) {
			$sanitized = wp_kses_post( $input );
			if ( isset( $sanitized ) ) {
				return $sanitized;
			} else {
				return '';
			}
		}

		public function setup_menu() {
			add_management_page(
				__( 'Order Tracking Codes', 'order-tracking-codes-for-woocommerce' ),
				__( 'Order Tracking Codes', 'order-tracking-codes-for-woocommerce' ),
				'manage_options',
				'wcotc_settings_page',
				array( $this, 'admin_panel_page' )
			);
		}

		public function admin_panel_page() {
			require_once WCOTC_PLUGIN_DIR . 'order-tracking-codes-for-woocommerce.admin.php';
		}

		public function add_settings_link( $links ) {
			$links[] = '<a href="' . admin_url( 'tools.php?page=wcotc_settings_page' ) . '">' . __( 'Settings' ) . '</a>';
			return $links;
		}

		// Return an instance of this class.
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( self::$instance == null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	add_action( 'plugins_loaded', array( 'Wecom_Order_Tracking_Codes', 'get_instance' ), 0 );
}
