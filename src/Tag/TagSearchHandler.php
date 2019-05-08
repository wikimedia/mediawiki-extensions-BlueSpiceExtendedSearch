<?php

namespace BS\ExtendedSearch\Tag;

use BlueSpice\Tag\Handler;

class TagSearchHandler extends Handler {
	const OPERATOR_OR = 'OR';
	const OPERATOR_AND = 'AND';

	protected $config;
	protected $tagIdNumber;

	/**
	 * TagSearchHandler constructor.
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @param \Config $config
	 * @param int $tagIdNumber
	 */
	public function __construct(
		$processedInput,
		array $processedArgs,
		$parser,
		\PPFrame $frame,
		\Config $config,
		$tagIdNumber
	) {
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );

		$this->config = $config;
		$this->tagIdNumber = $tagIdNumber;
	}

	/**
	 * @return string
	 * @throws \ConfigException
	 * @throws \MWException
	 */
	public function handle() {
		$this->parser->getOutput()->addModuleStyles( 'ext.blueSpiceExtendedSearch.TagSearch.styles' );
		$this->parser->getOutput()->addModules( 'ext.blueSpiceExtendedSearch.TagSearch' );

		$templateParser = new \TemplateParser( $this->config->get( 'TagSearchSearchFieldTemplatePath' ) );

		$lookup = new \BS\ExtendedSearch\Lookup();

		if ( count( $this->processedArgs[TagSearch::PARAM_NAMESPACE] ) > 0 ) {
			$namespaceNames = $this
				->getNamespaceNamesFromIds( $this->processedArgs[TagSearch::PARAM_NAMESPACE] );
			$lookup->addTermsFilter( 'namespace_text', $namespaceNames );
		}

		// Category supports "AND" operator
		if ( count( $this->processedArgs[TagSearch::PARAM_CATEGORY] ) > 0 ) {
			// Should we only consider categories that actually have members?
			$categories = [];
			foreach ( $this->processedArgs[TagSearch::PARAM_CATEGORY] as $cat ) {
				$categories[] = $cat->getDBKey();
				if ( $this->processedArgs[TagSearch::PARAM_OPERATOR] == static::OPERATOR_AND ) {
					$lookup->addTermFilter( 'categories', $cat->getDBKey() );
				}
			}

			if ( $this->processedArgs[TagSearch::PARAM_OPERATOR] == static::OPERATOR_OR ) {
				$lookup->addTermsFilter( 'categories', $categories );
			}
		}

		if ( count( $this->processedArgs[TagSearch::PARAM_TYPE] ) > 0 ) {
			$lookup->addTermsFilter( '_type', $this->processedArgs[TagSearch::PARAM_TYPE] );
		}

		$lookup = \FormatJson::encode( $lookup );

		$params = [
			"placeholder" => $this->processedArgs[TagSearch::PARAM_PLACEHOLDER],
			"action" => \SpecialPage::getTitleFor( 'SearchCenter' )->getLocalURL(),
			"lookup" => $lookup,
			"id_number" => $this->tagIdNumber
		];

		$title = \RequestContext::getMain()->getTitle();
		if ( $title instanceof \Title ) {
			$params['returnto'] = $title->getPrefixedDBkey();
		}

		$html = $templateParser->processTemplate(
			'SearchField',
			$params
		);

		return $html;
	}

	/**
	 * Converts NS IDs to names, so that namespace filter
	 * can be shown on SearchCenter
	 *
	 * @param type $namespaceIds
	 * @return type
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

}
