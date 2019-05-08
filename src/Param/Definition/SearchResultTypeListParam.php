<?php

namespace BS\ExtendedSearch\Param\Definition;

use BS\ExtendedSearch\Param\Validator\SearchResultTypeValidator;

class SearchResultTypeListParam extends \ParamProcessor\ParamDefinition {
	protected $delimiter = '|';
	protected $validator = null;

	/**
	 * SearchResultTypeListParam constructor.
	 * @param $name
	 * @param null $message
	 */
	public function __construct( $name, $message = null ) {
		parent::__construct( 'searchresulttypelist', $name, [], $message, true );
	}

	protected function postConstruct() {
		$this->validator = new SearchResultTypeValidator();
	}
}
