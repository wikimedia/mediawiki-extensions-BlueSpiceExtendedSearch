<?php

namespace BS\ExtendedSearch\Param\Definition;

use BS\ExtendedSearch\Param\Parser\SearchResultTypeParser;
use BS\ExtendedSearch\Param\Validator\SearchResultTypeValidator;

class SearchResultTypeListParam extends \ParamProcessor\ParamDefinition {
	/** @var string */
	protected $delimiter = '|';
	/** @var SearchResultTypeValidator|null */
	protected $validator = null;

	/**
	 * SearchResultTypeListParam constructor.
	 * @param string $name
	 * @param string|null $message
	 */
	public function __construct( $name, $message = null ) {
		parent::__construct( 'searchresulttypelist', $name, [], $message, true );
	}

	protected function postConstruct() {
		$this->validator = new SearchResultTypeValidator();
		$this->parser = new SearchResultTypeParser();
	}
}
