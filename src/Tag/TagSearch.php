<?php

namespace BS\ExtendedSearch\Tag;

use BlueSpice\ParamProcessor\IParamDefinition;
use BlueSpice\ParamProcessor\ParamDefinition;
use BlueSpice\ParamProcessor\ParamType;
use BlueSpice\Tag\IHandler;
use BlueSpice\Tag\Tag;
use BS\ExtendedSearch\Param\Definition\SearchResultTypeListParam;
use BSCategoryListParam;
use BSNamespaceListParam;
use ConfigException;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;

class TagSearch extends Tag {
	const PARAM_NAMESPACE = 'ns';
	const PARAM_CATEGORY = 'cat';
	const PARAM_CATEGORY_FULLNAME = 'category';
	const PARAM_PLACEHOLDER = 'placeholder';
	const PARAM_OPERATOR = 'operator';
	const PARAM_TYPE = 'type';

	protected $tagCounter = 0;

	/**
	 * @param mixed $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return IHandler
	 * @throws ConfigException
	 */
	public function getHandler(
		$processedInput,
		array $processedArgs,
		Parser $parser,
		PPFrame $frame
	) {
		$config = MediaWikiServices::getInstance()
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
	 * @return IParamDefinition[]
	 */
	public function getArgsDefinitions() {
		$namespaceListParam = new BSNamespaceListParam(
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
			new BSCategoryListParam(
				ParamType::CATEGORY_LIST,
				static::PARAM_CATEGORY,
				[]
			),
			new BSCategoryListParam(
				ParamType::CATEGORY_LIST,
				static::PARAM_CATEGORY_FULLNAME,
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
