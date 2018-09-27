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
			return $this->logger->addDebug( $message, $context );
		}
		return false;
	}

	public function addInfo( $message, array $context = array() ) {
		if ( 200 >= $this->level ) {
			return $this->logger->addInfo( $message, $context );
		}
		return false;
	}

	public function addNotice( $message, array $context = array() ) {
		if ( 250 >= $this->level ) {
			return $this->logger->addNotice( $message, $context );
		}
		return false;
	}

	public function addWarning( $message, array $context = array() ) {
		if ( 300 >= $this->level ) {
			return $this->logger->addWarning( $message, $context );
		}
		return false;
	}

	public function addError( $message, array $context = array() ) {
		if ( 400 >= $this->level ) {
			return $this->logger->addError( $message, $context );
		}
		return false;
	}

	public function addCritical( $message, array $context = array() ) {
		if ( 500 >= $this->level ) {
			return $this->logger->addCritical( $message, $context );
		}
		return false;
	}

	public function addAlert( $message, array $context = array() ) {
		if ( 550 >= $this->level ) {
			return $this->logger->addAlert( $message, $context );
		}
		return false;
	}

	public function addEmergency( $message, array $context = array() ) {
		if ( 600 >= $this->level ) {
			return $this->logger->addEmergency( $message, $context );
		}
		return false;
	}

}