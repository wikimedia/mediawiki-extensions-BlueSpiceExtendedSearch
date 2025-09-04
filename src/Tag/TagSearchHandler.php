<?php

namespace BS\ExtendedSearch\Tag;

use BS\ExtendedSearch\Lookup;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Json\FormatJson;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

class TagSearchHandler implements ITagHandler {

	/**
	 * @param Config $config
	 * @param int $tagId
	 */
	public function __construct(
		private readonly Config $config,
		private readonly int $tagId
	) {
	}

	public function getRenderedContent( string $input, array $params, Parser $parser, PPFrame $frame ): string {
		$templateParser = new TemplateParser( $this->config->get( 'TagSearchSearchFieldTemplatePath' ) );

		$lookup = new Lookup();

		$namespaces = array_unique( array_merge( $params['namespace'] ?? [], $params['ns'] ?? [] ) );
		if ( !empty( $namespaces ) ) {
			$lookup->addTermsFilter( 'namespace', $namespaces );
		}
		$categories = array_unique( array_merge( $params['category'] ?? [], $params['cat'] ?? [] ) );
		if ( !empty( $categories ) ) {
			if ( $params['operator'] === 'AND' ) {
				foreach ( $categories as $cat ) {
					$categories[] = $cat->getText();
					$lookup->addTermFilter( 'categories', $cat->getText() );
				}
			} else {
				$lookup->addTermsFilter( 'categories', array_map(
					static function ( $cat ) {
						return $cat->getText();
					},
					$categories
				) );
			}
		}

		$types = $params['type'] ?? [];
		if ( !empty( $types ) ) {
			$lookup->addSearchInTypes( $types );
		}
		$this->modifyLookup( $lookup );

		$lookup = FormatJson::encode( $lookup );

		$params = [
			"placeholder" => $params['placeholder'],
			"action" => SpecialPage::getTitleFor( 'SearchCenter' )->getLocalURL(),
			"lookup_object" => $lookup,
			"id_number" => $this->tagId,
			"returnto" => "",
			"input-aria-label" => Message::newFromKey( 'bs-extendedsearch-tagsearch-input-aria-label' )->text(),
			"button-aria-label" => Message::newFromKey( 'bs-extendedsearch-tagsearch-btn-aria-label' )->text()
		];

		$title = RequestContext::getMain()->getTitle();
		if ( $title instanceof Title ) {
			$params['returnto'] = $title->getPrefixedDBkey();
		}

		return $templateParser->processTemplate(
			'TagSearchField',
			$params
		);
	}

	/**
	 * @param Lookup $lookup
	 * @return void
	 */
	protected function modifyLookup( Lookup $lookup ) {
		// NOOP
	}
}
