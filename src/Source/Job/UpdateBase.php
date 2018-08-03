<?php

namespace BS\ExtendedSearch\Source\Job;

use BS\ExtendedSearch\Source\Base;

abstract class UpdateBase extends \Job {
	const ACTION_DELETE = 'delete';
	const ACTION_UPDATE = 'update';

	protected $action = self::ACTION_UPDATE;

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
	 * @return Base
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
