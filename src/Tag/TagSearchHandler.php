<?php

namespace BS\ExtendedSearch\Tag;

use BlueSpice\Tag\Handler;
use BS\ExtendedSearch\Lookup;
use Config;
use ConfigException;
use MWException;
use Parser;
use PPFrame;

class TagSearchHandler extends Handler {
	public const OPERATOR_OR = 'OR';
	public const OPERATOR_AND = 'AND';

	/** @var Config */
	protected $config;
	/** @var int */
	protected $tagIdNumber;

	/**
	 * TagSearchHandler constructor.
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Config $config
	 * @param int $tagIdNumber
	 */
	public function __construct(
		$processedInput,
		array $processedArgs,
		$parser,
		PPFrame $frame,
		Config $config,
		$tagIdNumber
	) {
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );

		$this->config = $config;
		$this->tagIdNumber = $tagIdNumber;
	}

	/**
	 * @return string
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function handle() {
		$this->parser->getOutput()->addModuleStyles( [ 'ext.blueSpiceExtendedSearch.TagSearch.styles' ] );
		$this->parser->getOutput()->addModules( [ 'ext.blueSpiceExtendedSearch.TagSearch' ] );

		$templateParser = new \TemplateParser( $this->config->get( 'TagSearchSearchFieldTemplatePath' ) );

		$lookup = new Lookup();

		$namespaceNames = [];
		$this->addFilterNamespaceNames( TagSearch::PARAM_NAMESPACE, $namespaceNames );
		$this->addFilterNamespaceNames( TagSearch::PARAM_NAMESPACE_FULLNAME, $namespaceNames );
		if ( !empty( $namespaceNames ) ) {
			$lookup->addTermsFilter( 'namespace_text', array_unique( $namespaceNames ) );
		}

		$this->handleCategories( $lookup, TagSearch::PARAM_CATEGORY );
		$this->handleCategories( $lookup, TagSearch::PARAM_CATEGORY_FULLNAME );

		if ( count( $this->processedArgs[TagSearch::PARAM_TYPE] ) > 0 ) {
			$lookup->addTermsFilter( '_type', $this->processedArgs[TagSearch::PARAM_TYPE] );
		}

		$lookup = \FormatJson::encode( $lookup );

		$params = [
			"placeholder" => $this->processedArgs[TagSearch::PARAM_PLACEHOLDER],
			"action" => \SpecialPage::getTitleFor( 'SearchCenter' )->getLocalURL(),
			"lookup_object" => $lookup,
			"id_number" => $this->tagIdNumber,
			"returnto" => ""
		];

		$title = \RequestContext::getMain()->getTitle();
		if ( $title instanceof \Title ) {
			$params['returnto'] = $title->getPrefixedDBkey();
		}

		return $templateParser->processTemplate(
			'TagSearchField',
			$params
		);
	}

	/**
	 * @param Lookup $lookup
	 * @param string $argName
	 */
	protected function handleCategories( $lookup, $argName ) {
		// Category supports "AND" operator
		if ( count( $this->processedArgs[$argName] ) > 0 ) {
			// Should we only consider categories that actually have members?
			$categories = [];
			foreach ( $this->processedArgs[$argName] as $cat ) {
				$categories[] = $cat->getDBkey();
				if ( $this->processedArgs[TagSearch::PARAM_OPERATOR] == static::OPERATOR_AND ) {
					$lookup->addTermFilter( 'categories', $cat->getDBkey() );
				}
			}

			if ( $this->processedArgs[TagSearch::PARAM_OPERATOR] == static::OPERATOR_OR ) {
				$lookup->addTermsFilter( 'categories', $categories );
			}
		}
	}

	/**
	 * Converts NS IDs to names, so that namespace filter
	 * can be shown on SearchCenter
	 *
	 * @param array $namespaceIds
	 * @return array
	 */
	protected function getNamespaceNamesFromIds( $namespaceIds ) {
		$namespaceNames = [];
		foreach ( $namespaceIds as $nsId ) {
			$nsName = \BsNamespaceHelper::getNamespaceName( $nsId, true );
			if ( $nsName == false ) {
				// This cannot happen because all NSs at this point
				// must exist, but just in case
				continue;
			}
			$namespaceNames[] = $nsName;
		}

		return $namespaceNames;
	}

	/**
	 * Read in namespace param(s) and add namespace names
	 *
	 * @param string $param
	 * @param array &$namespaceNames
	 */
	private function addFilterNamespaceNames( $param, &$namespaceNames ) {
		if (
			!isset( $this->processedArgs[$param] ) || !is_array( $this->processedArgs[$param] )
		) {
			return;
		}
		if ( count( $this->processedArgs[$param] ) > 0 ) {
			$namespaceNames = array_merge(
				$namespaceNames, $this->getNamespaceNamesFromIds(
					$this->processedArgs[$param]
				)
			);
		}
	}

}
