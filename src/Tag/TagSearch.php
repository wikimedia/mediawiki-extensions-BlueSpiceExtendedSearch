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
use MediaWiki\Config\ConfigException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class TagSearch extends Tag {
	public const PARAM_NAMESPACE = 'ns';
	public const PARAM_NAMESPACE_FULLNAME = 'namespace';
	public const PARAM_CATEGORY = 'cat';
	public const PARAM_CATEGORY_FULLNAME = 'category';
	public const PARAM_PLACEHOLDER = 'placeholder';
	public const PARAM_OPERATOR = 'operator';
	public const PARAM_TYPE = 'type';

	protected static int $tagCounter = 0;

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

		return new TagSearchHandler(
			$processedInput,
			$processedArgs,
			$parser,
			$frame,
			$config,
			$this->nextTagId()
		);
	}

	protected function nextTagId(): int {
		return self::$tagCounter++;
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
