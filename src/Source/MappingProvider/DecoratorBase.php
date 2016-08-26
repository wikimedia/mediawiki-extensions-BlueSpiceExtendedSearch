<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class DecoratorBase extends Base {
	/**
	 *
	 * @var Base
	 */
	protected $oDecoratedMP = null;

	/**
	 *
	 * @param Base $oDecoratedMP
	 */
	public function __construct( $oDecoratedMP ) {
		$this->oDecoratedMP = $oDecoratedMP;
	}

	public function getPropertyConfig() {
		return $this->oDecoratedMP->getPropertyConfig();
	}
}