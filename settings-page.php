<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\WPMailHandler;
use Monolog\Formatter\HtmlFormatter;

add_action( 'admin_menu', 'wp_monolog_add_admin_menu' );
add_action( 'admin_init', 'wp_monolog_settings_init' );

function wp_monolog_add_admin_menu(  ) { 
	add_management_page( 'WP Monolog', 'WP Monolog', 'manage_options', 'wp_monolog', 'wp_monolog_options_page' );
}

function wp_monolog_settings_init(  ) {

	if ( isset( $_REQUEST['wp_monolog_do'] ) && wp_verify_nonce( $_REQUEST['wp_monolog_do'], 'wp_monolog_setting' ) ) {
		$inputs = $_POST['wp_monolog_settings'];
		$setting = get_option( 'wp_monolog_settings', array() );
		foreach ( $inputs as $key => $value ) {
			$setting[ $key ] = $value;
		}
		update_option( 'wp_monolog_settings', $setting );
	}

	// register_setting( 'pluginPage', 'wp_monolog_settings' );

	// add_settings_section(
	// 	'wp_monolog_error_email_settings', 
	// 	__( 'Error Notification Settings', 'wp_monolog' ), 
	// 	'wp_monolog_settings_section_callback', 
	// 	'pluginPage'
	// );

	// add_settings_field( 
	// 	'wp_monolog_error_from', 
	// 	__( 'From e-mail:', 'wp_monolog' ), 
	// 	'wp_monolog_error_from_render', 
	// 	'pluginPage', 
	// 	'wp_monolog_error_email_settings'
	// );

	// add_settings_field( 
	// 	'wp_monolog_error_to', 
	// 	__( 'To e-mail:', 'wp_monolog' ), 
	// 	'wp_monolog_error_to_render', 
	// 	'pluginPage', 
	// 	'wp_monolog_error_email_settings'
	// );

	// add_settings_field( 
	// 	'wp_monolog_error_subject', 
	// 	__( 'Subject', 'wp_monolog' ), 
	// 	'wp_monolog_error_subject_render', 
	// 	'pluginPage', 
	// 	'wp_monolog_error_email_settings'
	// );

}

function wp_monolog_error_from_render(  ) { 

	$options = get_option( 'wp_monolog_settings' );
	$from = !empty($options['WPMailHandler']['from']) ? $options['WPMailHandler']['from'] : get_option('admin_email');
	?>
	<input type='text' name='wp_monolog_settings[WPMailHandler][from]' value='<?php echo $from; ?>'>
	<?php

}

function wp_monolog_error_to_render(  ) { 

	$options = get_option( 'wp_monolog_settings' );
	$to = !empty($options['WPMailHandler']['to']) ? $options['WPMailHandler']['to'] : get_option('admin_email');
	?>
	<input type='text' name='wp_monolog_settings[WPMailHandler][to]' value='<?php echo $to; ?>'>
	<?php

}

function wp_monolog_error_subject_render(  ) { 

	$options = get_option( 'wp_monolog_settings' );
	$subject = !empty($options['WPMailHandler']['subject']) ? $options['WPMailHandler']['subject'] : 'An Error on the site "'.get_option('blogname').'" has been detected.';
	?>
	<input type='text' name='wp_monolog_settings[WPMailHandler][subject]' value='<?php echo $subject; ?>'>
	<?php

}

function wp_monolog_settings_section_callback(  ) { 

	echo __( 'These settings will override default values when configuring Monolog', 'wp_monolog' );

}

function wp_monolog_options_page(  ) { 
	$tabs = apply_filters(
		'monolog_setting_tabs', array(
			'setting'   => array(
				'label'     => __( 'Setting', 'pok' ),
				'callback'  => 'wp_monolog_page_setting',
			),
			'viewer'    => array(
				'label'     => __( 'Log Viewer', 'pok' ),
				'callback'  => 'wp_monolog_page_viewer',
			),
		)
	);
	if ( isset( $_GET['tab'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['tab'] ) ), array_keys( $tabs ), true ) ) { // WPCS: Input var okay, CSRF ok.
		$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) ); // WPCS: Input var okay, CSRF ok.
	} else {
		$tab = current( array_keys( $tabs ) );
	}
	?>
	<style>
		.monolog-wrapper .form-table {
			width: 100%;
		}
		.monolog-wrapper .form-table td.value {
			width: 300px;
		}
		.monolog-wrapper .form-table td.value [name] {
			width: 100%;
		}
	</style>
	<div class="wrap monolog-wrapper">
		<h1 class="wp-heading-inline">WP Monolog</h1>
		<hr class="wp-header-end">
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $value ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp_monolog&tab=' . $key ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $value['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="monolog-setting-content">
			<?php
			if ( isset( $tabs[ $tab ]['callback'] ) ) {
				call_user_func( $tabs[ $tab ]['callback'] );
			}
			?>
		</div>
	</div>
	<?php

}

function wp_monolog_page_setting() {
	$settings = wp_monolog_settings();
	?>
	<form action="" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Logging level:</th>
					<td class="value">
						<select name="wp_monolog_settings[level]" <?php echo defined( 'WP_MONOLOG_LOG_LEVEL' ) ? 'disabled' : '' ?>>
							<?php
								$level = wp_monolog_get_level();
							?>
							<?php foreach ( Logger::getLevels() as $key => $value) : ?>
								<option <?php echo ( $value == $level ) ? 'selected' : '' ?> value="<?php echo esc_attr( $value ) ?>"><?php echo esc_html( $key ) ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td><?php esc_html_e( 'Can be overridden by defining WP_MONOLOG_LOG_LEVEL', 'wp_monolog' ) ?></td>
				</tr>
				<!-- <tr>
					<th scope="row">From e-mail:</th>
					<td class="value">
						<input name="wp_monolog_settings[WPMailHandler][from]" value="<?php echo esc_attr( $settings['WPMailHandler']['from'] ) ?>" type="email">
					</td>
					<td></td>
				</tr>
				<tr>
					<th scope="row">To e-mail:</th>
					<td class="value">
						<input name="wp_monolog_settings[WPMailHandler][to]" value="<?php echo esc_attr( $settings['WPMailHandler']['to'] ) ?>" type="email">
					</td>
					<td></td>
				</tr>
				<tr>
					<th scope="row">Subject:</th>
					<td class="value">
						<textarea name="wp_monolog_settings[WPMailHandler][subject]"><?php echo esc_html( $settings['WPMailHandler']['subject'] ) ?></textarea>
					</td>
					<td></td>
				</tr> -->
			</tbody>
		</table>
		<p class="submit">
			<?php wp_nonce_field( 'wp_monolog_setting', 'wp_monolog_do' ) ?>
			<input name="submit" id="submit" class="button button-primary" value="Save Changes" type="submit">
		</p>
	</form>
	<?php
}

function wp_monolog_page_viewer() {
	echo 'asdas';
}