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
	global $wp_filesystem;

	if ( empty( $wp_filesystem ) ) {
		require_once  ABSPATH . '/wp-admin/includes/file.php' ;
		WP_Filesystem();
	}
	$logfiles = $wp_filesystem->dirlist( wp_monolog_settings( 'log_path' ) );

	if ( false === $logfiles ) {
		wp_monolog_not_writable_alert();
	}
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
					<td>
						<p><?php esc_html_e( 'Can be overridden by defining WP_MONOLOG_LOG_LEVEL', 'wp_monolog' ) ?></p>
						<p>
							<strong>DEBUG</strong>: Detailed debug information.<br>
							<strong>INFO</strong>: Interesting events. Examples: User logs in, SQL logs.<br>
							<strong>NOTICE</strong>: Normal but significant events.<br>
							<strong>WARNING</strong>: Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.<br>
							<strong>ERROR</strong>: Runtime errors that do not require immediate action but should typically be logged and monitored.<br>
							<strong>CRITICAL</strong>: Critical conditions. Example: Application component unavailable, unexpected exception.<br>
							<strong>ALERT</strong>: Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.<br>
							<strong>EMERGENCY</strong>: Emergency: system is unusable.<br>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Log files:</th>
					<td class="value">
						<select name="wp_monolog_settings[log_interval]">
							<option value="daily" <?php echo ( 'daily' == $settings['log_interval'] ) ? 'selected' : '' ?>>Daily</option>
							<option value="weekly" <?php echo ( 'weekly' == $settings['log_interval'] ) ? 'selected' : '' ?>>Weekly</option>
							<option value="monthly" <?php echo ( 'monthly' == $settings['log_interval'] ) ? 'selected' : '' ?>>Monthly</option>
						</select>
					</td>
					<td>
						<p><?php esc_html_e( 'The interval of log file creation.', 'wp_monolog' ) ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">Viewer chunksize per page:</th>
					<td class="value">
						<input type="number" name="wp_monolog_settings[chunksize]" value="<?php echo esc_attr( $settings['chunksize'] ) ?>">
					</td>
					<td>
						<p>bytes</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Log file location:</th>
					<td class="value">
						<textarea style="width: 100%;" readonly><?php echo esc_attr( $settings['log_path'] ) ?></textarea>
					</td>
					<td>
						<p><?php esc_html_e( 'Log path. Can be set by defining WP_MONOLOG_LOG_PATH', 'wp_monolog' ) ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<?php wp_nonce_field( 'wp_monolog_setting', 'wp_monolog_do' ) ?>
			<input name="submit" id="submit" class="button button-primary" value="Save Changes" type="submit">
		</p>
	</form>
	<?php
}

function wp_monolog_not_writable_alert() {
    ?>
    <div class="notice notice-error">
        <p><?php _e( 'We unable to access <strong>' . wp_monolog_settings( 'log_path' ) . '</strong>, please make sure that path si writeable.', 'wp_monolog' ); ?></p>
    </div>
    <?php
}

function wp_monolog_page_viewer() {
	global $logger;
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once  ABSPATH . '/wp-admin/includes/file.php' ;
		WP_Filesystem();
	}

	$path = wp_monolog_settings( 'log_path' );

	// get directory file lists.
	$logfiles = scandir( $path, 1 );
	if ( false === $logfiles || ! is_array( $logfiles ) ) {
		return wp_monolog_not_writable_alert();
	}
	// remove unecessary dir results.
	$logfiles = array_diff( $logfiles, array('.', '..') );
	if ( ! empty( $logfiles ) ) {
		// get first 20 files.
		$logfiles = array_slice($logfiles, 0, 20);
	}
	// reset array indexes.
	$logfiles = array_values($logfiles);

	if ( isset( $_GET['file'] ) && in_array( $_GET['file'], $logfiles ) ) {
		$file = sanitize_text_field( wp_unslash( $_GET['file'] ) );
	} else {
		$file = $logfiles[0];
	}
	$page = isset( $_GET['pagenum'] ) && 0 < intval( $_GET['pagenum'] ) ? sanitize_text_field( wp_unslash( $_GET['pagenum'] ) ) : 1;

	$logs = wp_monolog_readfile( $file, $page );
	if ( false === $logs ) {
		return wp_monolog_not_writable_alert();
	}

	$page_links = paginate_links( array(
		'base' => add_query_arg( 'pagenum', '%#%' ),
		'format' => '',
		'prev_text' => __( '&laquo;', 'text-domain' ),
		'next_text' => __( '&raquo;', 'text-domain' ),
		'total' => $logs['total_page'],
		'current' => $page
	) );

	?>
	<div class="wp-monolog-wrapper">
		<div class="tablenav">
			<form class="filter" action="">
				<input type="hidden" name="page" value="<?php echo isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : '' ?>">
				<input type="hidden" name="tab" value="<?php echo isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : '' ?>">
				<select name="file">
					<?php foreach ( $logfiles as $logfile ) : ?>
						<option <?php echo $file === $logfile ? 'selected' : ''; ?> value="<?php echo esc_attr( $logfile ) ?>"><?php echo esc_html( $logfile . formatBytes( filesize( $path . $logfile ) ) ) ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button button-primary">Get Logs</button>
			</form>
			<?php
				if ( $page_links ) {
					echo '<div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div>';
				}
			?>
		</div>
		<table class="log-table widefat striped">
			<thead>
				<tr>
					<th>Level</th>
					<th>Date</th>
					<th>Log</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs['logs'] as $log ) : ?>
					<tr>
						<td class="col-level"><div class="<?php echo esc_attr( $log['level_class'] ) ?>"><?php echo esc_html( $log['level'] ) ?></div></td>
						<td class="col-date"><?php echo esc_html( date( 'Y-m-d H:i:s', strtotime( $log['date'] ) + intval( 3600 * get_option( 'gmt_offset' ) ) ) ) ?></td>
						<td class="col-log"><div class="log-container"><?php echo rtrim( $log['text'], ' [] []' ); ?><span class="truncate-toggle"></span></div></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="tablenav">
			<?php
				if ( $page_links ) {
					echo '<div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div>';
				}
			?>
		</div>
	</div>
	<?php
}

function formatBytes($bytes, $precision = 2) {
	if ( false === $bytes ) {
		return;
	}
	$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

	$bytes = max($bytes, 0); 
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	$pow = min($pow, count($units) - 1); 

	// Uncomment one of the following alternatives
	$bytes /= pow(1024, $pow);
	// $bytes /= (1 << (10 * $pow)); 

	return ' (' . round($bytes, $precision) . $units[$pow] . ')'; 
}

function wp_monolog_readfile($file, $page = 1) {
	$handle = fopen( wp_monolog_settings('log_path') .'/'. $file, "r");
	
	if ( false === $handle ) {
		return false;
	}

	$chunkSize = intval( wp_monolog_settings('chunksize') );
	$iterations = 0;

	$data = [];

	$logs = [];
	if ($handle) {
		while (! feof($handle)) {
			$iterations++;
			$chunk = fread($handle, $chunkSize);
			$logs[] = wp_monolog_pretty($chunk);
			// if($iterations == $page){
			// 	$data['logs'] = wp_monolog_pretty($chunk);
			// }
		}

		fclose($handle);
	}

	$data['logs']		= $logs[ $iterations - $page ];
	$data['total_page'] = $iterations;

	return $data;
}

function wp_monolog_pretty($file) {
	$logLevels = array(
		'emergency',
		'alert',
		'critical',
		'error',
		'warning',
		'notice',
		'info',
		'debug',
		'processed',
		'failed'
	);

	$levelsClasses = array(
		'debug' => 'info',
		'info' => 'info',
		'notice' => 'info',
		'warning' => 'warning',
		'error' => 'danger',
		'critical' => 'danger',
		'alert' => 'danger',
		'emergency' => 'danger',
		'processed' => 'info',
		'failed' => 'warning',
	);

	$levelsImgs = array(
		'debug' => 'info-circle',
		'info' => 'info-circle',
		'notice' => 'info-circle',
		'warning' => 'exclamation-triangle',
		'error' => 'exclamation-triangle',
		'critical' => 'exclamation-triangle',
		'alert' => 'exclamation-triangle',
		'emergency' => 'exclamation-triangle',
		'processed' => 'info-circle',
		'failed' => 'exclamation-triangle'
	);

	$pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?\].*/';

	$log = [];

	preg_match_all($pattern, $file, $headings);

	if (!is_array($headings)) return $log;

	$logData = preg_split($pattern, $file);

	if ($logData[0] < 1) {
		array_shift($logData);
	}

	foreach ($headings as $h) {
		for ($i=0, $j = count($h); $i < $j; $i++) {
			foreach ($logLevels as $level) {
				if (strpos(strtolower($h[$i]), '.' . $level) || strpos(strtolower($h[$i]), $level . ':')) {

					preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?)\](?:.*?(\w+)\.|.*?)' . $level . ': (.*?)( in .*?:[0-9]+)?$/i', $h[$i], $current);
					if (!isset($current[4])) continue;

					$log[] = [
						'context' => $current[3],
						'level' => $level,
						'level_class' => $levelsClasses[$level],
						'level_img' => $levelsImgs[$level],
						'date' => $current[1],
						'text' => $current[4],
						'in_file' => isset($current[5]) ? $current[5] : null,
						'stack' => preg_replace("/^\n*/", '', $logData[$i])
					];
				}
			}
		}
	}

	return array_reverse($log);
}

function wp_monolog_enqueue_scripts() {
	$screen = get_current_screen();
	if ( 'tools_page_wp_monolog' === $screen->id ) {
		wp_enqueue_style( 'wp_monolog', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/assets/style.css' );
		wp_enqueue_script( 'wp_monolog', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/assets/script.js' );
	}
}
add_action( 'admin_enqueue_scripts', 'wp_monolog_enqueue_scripts' );
