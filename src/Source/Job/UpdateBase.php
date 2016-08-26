<?php

namespace BS\ExtendedSearch\Source\Job;

abstract class UpdateBase extends \Job {

	protected $sIndexKey = 'local';
	protected $sSourceKey = '';

	/**
	 *
	 * @return \BS\ExtendedSearch\Index
	 */
	protected function getIndex() {
		return \BS\ExtendedSearch\Indices::factory( $this->sIndexKey );
	}

	protected function getSource() {
		return $this->getIndex()->getSource( $this->sSourceKey );
	}
}
