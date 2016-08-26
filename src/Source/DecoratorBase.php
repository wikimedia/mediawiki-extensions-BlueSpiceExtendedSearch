<?php

namespace BS\ExtendedSearch\Source;

class DecoratorBase extends Base {

	/**
	 *
	 * @var Base
	 */
	protected $oDecoratedSource = null;

	/**
	 *
	 * @param Base $oSource
	 */
	public function __construct( $oSource ) {
		$this->oDecoratedSource = $oSource;
	}

	public function makeMappingPropertyConfig() {
		return $this->oDecoratedSource->makeMappingPropertyConfig();
	}

	public function getTypeKey() {
		return $this->oDecoratedSource->getTypeKey();
	}
}