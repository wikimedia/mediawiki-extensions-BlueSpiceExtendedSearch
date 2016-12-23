<?php

namespace BS\ExtendedSearch\Source\Job;

abstract class UpdateBase extends \Job {

	protected $sBackendKey = 'local';
	protected $sSourceKey = '';

	/**
	 *
	 * @return \BS\ExtendedSearch\Backend
	 */
	protected function getBackend() {
		return \BS\ExtendedSearch\Backend::instance( $this->getBackendKey() );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source
	 * @throws \Exception
	 */
	protected function getSource() {
		return $this->getBackend()->getSource( $this->getSourceKey() );
	}

	/**
	 *
	 * @return string
	 */
	protected function getBackendKey() {
		if( isset( $this->params['backend'] ) ) {
			return $this->params['backend'];
		}
		return $this->sBackendKey;
	}

	/**
	 *
	 * @return string
	 */
	protected function getSourceKey() {
		if( isset( $this->params['source'] ) ) {
			return $this->params['source'];
		}
		return $this->sSourceKey;
	}
}
