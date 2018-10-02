<?php

class WP_Monolog_Wrapper {

	public $logger;

	public $level;

	public function __construct( Monolog\Logger $logger, $level = 100 ) {
		$this->logger = $logger;
		$this->level  = $level;
	}

	public function addDebug( $message, array $context = array() ) {
		if ( 100 >= $this->level ) {
			do_action( 'wp_monolog_log_debug', $message, $context );
			return $this->logger->addDebug( $message, $context );
		}
		return false;
	}

	public function addInfo( $message, array $context = array() ) {
		if ( 200 >= $this->level ) {
			do_action( 'wp_monolog_log_info', $message, $context );
			return $this->logger->addInfo( $message, $context );
		}
		return false;
	}

	public function addNotice( $message, array $context = array() ) {
		if ( 250 >= $this->level ) {
			do_action( 'wp_monolog_log_notice', $message, $context );
			return $this->logger->addNotice( $message, $context );
		}
		return false;
	}

	public function addWarning( $message, array $context = array() ) {
		if ( 300 >= $this->level ) {
			do_action( 'wp_monolog_log_warning', $message, $context );
			return $this->logger->addWarning( $message, $context );
		}
		return false;
	}

	public function addError( $message, array $context = array() ) {
		if ( 400 >= $this->level ) {
			do_action( 'wp_monolog_log_error', $message, $context );
			return $this->logger->addError( $message, $context );
		}
		return false;
	}

	public function addCritical( $message, array $context = array() ) {
		if ( 500 >= $this->level ) {
			do_action( 'wp_monolog_log_critical', $message, $context );
			return $this->logger->addCritical( $message, $context );
		}
		return false;
	}

	public function addAlert( $message, array $context = array() ) {
		if ( 550 >= $this->level ) {
			do_action( 'wp_monolog_log_alert', $message, $context );
			return $this->logger->addAlert( $message, $context );
		}
		return false;
	}

	public function addEmergency( $message, array $context = array() ) {
		if ( 600 >= $this->level ) {
			do_action( 'wp_monolog_log_emergency', $message, $context );
			return $this->logger->addEmergency( $message, $context );
		}
		return false;
	}

}