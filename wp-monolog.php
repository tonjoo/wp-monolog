<?php
/*
Plugin Name: WP Monolog 
Plugin URI: 
Description: A lightweight WordPress integration of Monolog.
Version: 0.3
Author: RPP
Author URI: 
License: GPLv2 or later
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\WPMailHandler;
use Monolog\Formatter\HtmlFormatter;

require_once dirname(__FILE__) . '/class-wp-monolog-wrapper.php';

if( ! class_exists('WPMonolog') ) :

class WPMonolog {

	function __construct(){
		$this->logger   = $this->getLoggerInstance();
	}

	function initialize() {

		// add_action( 'init', array($this, 'initialize') );
		require_once dirname(__FILE__) . '/settings-page.php';

	}

	function getLoggerInstance( $log_name = 'WordPressLog', $overrides = null ){
		$s = wp_monolog_settings();
		if ( ! is_null( $overrides ) ) {
			$s = wp_parse_args( $s, $overrides );
		}

		if( !is_dir( $s["log_path"] ) ){
			mkdir( $s["log_path"] );
		}

		$log_file = untrailingslashit( $s["log_path"] );
		if ( 'daily' === $s['log_interval'] ) {
			$log_file .= '/' . date('Y-m-d') . '_wp_monolog.log';
		} elseif ( 'weekly' === $s['log_interval'] ) {
			$log_file .= '/' . date('Y-W') . '_wp_monolog.log';
		} elseif ( 'monthly' === $s['log_interval'] ) {
			$log_file .= '/' . date('Y-m') . '_wp_monolog.log';
		}

		if ( 'disabled' !== $s['level_cron'] && defined( 'DOING_CRON' ) && DOING_CRON ) {
			$log_file = str_replace( '.log', '_cron.log', $log_file );
		} elseif ( 'disabled' !== $s['level_cron'] && defined( 'DOING_BG_WORKER' ) && DOING_BG_WORKER ) {
			$log_file = str_replace( '.log', '_cron.log', $log_file );
		} elseif ( 'disabled' !== $s['level_cli'] && php_sapi_name() == "cli" ) {
			$log_file = str_replace( '.log', '_cli.log', $log_file );
		}

		if( !file_exists( $log_file ) ){
			file_put_contents($log_file, '');
			chmod( $log_file, 0774 );
		}
		
		if ( ! is_writable( $log_file ) ) {
			$log_file = str_replace( '.log', '-alt.log', $log_file );
			if ( ! file_exists( $log_file ) ) {
				file_put_contents( $log_file, '' );
				chmod( $log_file, 0774 );
			}
		}

		$logger = new Logger( $log_name );

		// create log channels               
		$logger->pushHandler( new StreamHandler( $log_file ) );

		// $mailStream = new WPMailHandler( $s['WPMailHandler']['to'], $s['WPMailHandler']['subject'], $s['WPMailHandler']['from'] );
		// $mailStream->setFormatter( new HTMLFormatter );

		// $logger->pushHandler( $mailStream );

		return $logger;
	}

}

function wp_monolog_settings( $index = '' ) {
	$settings = get_option( "wp_monolog_settings", array() );

	$defaults = array(
		'log_path'      => WP_CONTENT_DIR . '/monolog/',
		'log_interval'	=> 'daily',
		'chunksize'		=> 200000,
		'level'         => 100,
		'level_cli'		=> 'disabled',
		'level_cron'	=> 'disabled'
	);
	$settings = wp_parse_args( $settings, $defaults );
	$levels = Logger::getLevels();

	if ( defined( 'WP_MONOLOG_LOG_LEVEL' ) && in_array( WP_MONOLOG_LOG_LEVEL, array_keys( $levels ) ) ) {
		$settings['level'] = WP_MONOLOG_LOG_LEVEL;
	}
	if ( defined( 'WP_MONOLOG_LOG_LEVEL_CLI' ) && ( in_array( WP_MONOLOG_LOG_LEVEL_CLI, array_keys( $levels ) ) || 'same' === WP_MONOLOG_LOG_LEVEL_CLI || 'disabled' === WP_MONOLOG_LOG_LEVEL_CLI ) ) {
		$settings['level_cli'] = WP_MONOLOG_LOG_LEVEL_CLI;
	}
	if ( defined( 'WP_MONOLOG_LOG_LEVEL_CRON' ) && ( in_array( WP_MONOLOG_LOG_LEVEL_CRON, array_keys( $levels ) ) || 'same' === WP_MONOLOG_LOG_LEVEL_CRON || 'disabled' === WP_MONOLOG_LOG_LEVEL_CRON ) ) {
		$settings['level_cron'] = WP_MONOLOG_LOG_LEVEL_CRON;
	}
	if ( defined( 'WP_MONOLOG_LOG_PATH' ) ) {
		$settings['log_path'] = WP_MONOLOG_LOG_PATH;
	}
	$settings = apply_filters( 'wp_monolog_setting', wp_parse_args( $settings, $defaults ) );
	if ( ! empty( $index ) && isset( $settings[ $index ] ) ) {
		return $settings[ $index ];
	}
	return $settings;
}

function wp_monolog_get_level( $env = '' ) {
	$levels = Logger::getLevels();
	if ( 'cli' == $env ) {
		$level = wp_monolog_settings( 'level_cli' );
	} elseif ( 'cron' == $env ) {
		$level = wp_monolog_settings( 'level_cron' );
	} else {
		$level = wp_monolog_settings( 'level' );
	}
	if ( defined( 'WP_MONOLOG_LOG_LEVEL' ) && in_array( WP_MONOLOG_LOG_LEVEL, array_keys( $levels ) ) ) {
		$level = $levels[ WP_MONOLOG_LOG_LEVEL ];
	}
	return $level;
}

function wp_monolog() {

	global $wp_monolog;
	global $logger;

	if( !isset($wp_monolog) ) {
		$wp_monolog = new WPMonolog();
		$wp_monolog->initialize();

		if ( 'disabled' !== wp_monolog_settings('level_cron') && defined( 'DOING_CRON' ) && DOING_CRON ) {
			$logger = new WP_Monolog_Wrapper( $wp_monolog->logger, wp_monolog_get_level( 'cron' ) );
		} elseif ( 'disabled' !== wp_monolog_settings('level_cron') && defined( 'DOING_BG_WORKER' ) && DOING_BG_WORKER ) {
			$logger = new WP_Monolog_Wrapper( $wp_monolog->logger, wp_monolog_get_level( 'cron' ) );
		} elseif ( 'disabled' !== wp_monolog_settings('level_cli') && php_sapi_name() == "cli" ) {
			$logger = new WP_Monolog_Wrapper( $wp_monolog->logger, wp_monolog_get_level( 'cli' ) );
		} else {
			$logger = new WP_Monolog_Wrapper( $wp_monolog->logger, wp_monolog_get_level() );
		}
	}
	
	return $wp_monolog;
}

// initialize
wp_monolog();

endif; // class_exists check
