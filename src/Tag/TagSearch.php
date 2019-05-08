<?php

namespace BS\ExtendedSearch\Tag;

use BlueSpice\Tag\Tag;
use BlueSpice\ParamProcessor\ParamType;
use BlueSpice\ParamProcessor\ParamDefinition;
use BS\ExtendedSearch\Param\Definition\SearchResultTypeListParam;

class TagSearch extends Tag {
	const PARAM_NAMESPACE = 'ns';
	const PARAM_CATEGORY = 'cat';
	const PARAM_PLACEHOLDER = 'placeholder';
	const PARAM_OPERATOR = 'operator';
	const PARAM_TYPE = 'type';

	protected $tagCounter = 0;

	/**
	 * @param mixed $processedInput
	 * @param array $processedArgs
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @return \BlueSpice\Tag\IHandler|TagSearchHandler
	 * @throws \ConfigException
	 */
	public function getHandler(
		$processedInput,
		array $processedArgs,
		\Parser $parser,
		\PPFrame $frame
	) {
		$config = \BlueSpice\Services::getInstance()
			->getConfigFactory()
			->makeConfig( 'bsg' );
		$this->tagCounter ++;

		return new TagSearchHandler(
			$processedInput,
			$processedArgs,
			$parser,
			$frame,
			$config,
			$this->tagCounter
		);
	}

	/**
	 * @return array|string[]
	 */
	public function getTagNames() {
		return [ 'bs:tagsearch', 'tagsearch' ];
	}

	/**
	 * @return array|\BlueSpice\ParamProcessor\IParamDefinition[]
	 */
	public function getArgsDefinitions() {
		$namespaceListParam = new \BSNamespaceListParam(
			ParamType::NAMESPACE_LIST,
			static::PARAM_NAMESPACE,
			[]
		);
		$namespaceListParam->setArrayValues( [ 'hastoexist' => true ] );

		return [
			new SearchResultTypeListParam(
				static::PARAM_TYPE
			),
			$namespaceListParam,
			new \BSCategoryListParam(
				ParamType::CATEGORY_LIST,
				static::PARAM_CATEGORY,
				[]
			),
			new ParamDefinition(
				ParamType::STRING,
				static::PARAM_PLACEHOLDER,
				wfMessage( 'bs-extendedsearch-tagsearch-searchfield-placeholder' )->plain()
			),
			new ParamDefinition(
				ParamType::STRING,
				static::PARAM_OPERATOR,
				TagSearchHandler::OPERATOR_OR
			)
		];
	}
}
