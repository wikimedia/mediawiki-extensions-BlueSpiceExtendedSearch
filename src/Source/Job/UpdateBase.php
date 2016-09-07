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
		return \BS\ExtendedSearch\Backend::instance( $this->sBackendKey );
	}

	protected function getSource() {
		return $this->getBackend()->getSource( $this->sSourceKey );
	}
}
