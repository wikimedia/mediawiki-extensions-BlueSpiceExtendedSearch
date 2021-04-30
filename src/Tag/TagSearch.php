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
	const PARAM_NAMESPACE_FULLNAME = 'namespace';
	const PARAM_CATEGORY = 'cat';
	const PARAM_CATEGORY_FULLNAME = 'category';
	const PARAM_PLACEHOLDER = 'placeholder';
	const PARAM_OPERATOR = 'operator';
	const PARAM_TYPE = 'type';

	/** @var int */
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
		$this->tagCounter++;

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
		$namespaceListParamFull = new BSNamespaceListParam(
			ParamType::NAMESPACE_LIST,
			static::PARAM_NAMESPACE_FULLNAME,
			[]
		);
		$namespaceListParam->setArrayValues( [ 'hastoexist' => true ] );

		$categoryParam  = new BSCategoryListParam(
			ParamType::CATEGORY_LIST, static::PARAM_CATEGORY_FULLNAME, []
		);
		$catParam  = new BSCategoryListParam(
			ParamType::CATEGORY_LIST, static::PARAM_CATEGORY, []
		);
		$categoryParam->setArrayValues( [ 'hastoexist' => false ] );
		$catParam->setArrayValues( [ 'hastoexist' => false ] );
		return [
			new SearchResultTypeListParam(
				static::PARAM_TYPE
			),
			$namespaceListParam,
			$namespaceListParamFull,
			$catParam,
			$categoryParam,
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
