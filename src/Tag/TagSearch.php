<?php

namespace BS\ExtendedSearch\Tag;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ISearchSource;
use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\NamespaceInfo;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\InputProcessor\Processor\KeywordListValue;
use MWStake\MediaWiki\Component\InputProcessor\Processor\KeywordValue;
use MWStake\MediaWiki\Component\InputProcessor\Processor\StringValue;

class TagSearch extends GenericTag {

	/** @var int */
	private int $idCounter = 0;

	/**
	 * @param Backend $backend
	 * @param NamespaceInfo $namespaceInfo
	 * @param Language $language
	 */
	public function __construct(
		private readonly Backend $backend,
		private readonly NamespaceInfo $namespaceInfo,
		private readonly Language $language
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [ 'bs:tagsearch', 'tagsearch' ];
	}

	/**
	 * @inheritDoc
	 */
	public function hasContent(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getHandler( MediaWikiServices $services ): ITagHandler {
		return new TagSearchHandler(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$this->getTagId()
		);
	}

	public function getTagId(): int {
		return $this->idCounter++;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamDefinition(): ?array {
		$types = array_map( static function ( ISearchSource $source ) {
			return $source->getTypeKey();
		}, $this->backend->getSources() );
		return [
			'namespace' => [
				'type' => 'namespace-list',
				'separator' => ',',
			],
			// B/C
			'ns' => [
				'type' => 'namespace-list',
				'separator' => ',',
			],
			'category' => [
				'type' => 'category-list',
				'separator' => ',',
			],
			// B/C
			'cat' => [
				'type' => 'category-list',
				'separator' => ',',
			],
			'placeholder' => ( new StringValue() )
				->setDefaultValue(
					Message::newFromKey( 'bs-extendedsearch-tagsearch-searchfield-placeholder' )->text()
				),
			'operator' => ( new KeywordValue() )
				->setKeywords( [
					'AND' => 'AND',
					'OR' => 'OR',
				] )
				->setDefaultValue( 'AND' ),
			'type' => ( new KeywordListValue() )
				->setKeywords( $types )
				->setListSeparator( ',' ),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		$formSpec = new StandaloneFormSpecification();
		$formSpec->setItems( [
			[
				'type' => 'menutag_multiselect',
				'name' => 'type',
				'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-type-label' )->text(),
				'help' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-type-help' )->text(),
				'options' => array_values( array_map( static function ( ISearchSource $source ) {
					return [
						'data' => $source->getTypeKey(),
						'label' => Message::newFromKey(
							'bs-extendedsearch-source-type-' . $source->getTypeKey() . '-label'
						)->text(),
					];
				}, $this->backend->getSources() ) ),
				'widget_allowArbitrary' => false,
				'widget_$overlay' => true,
			],
			[
				'type' => 'menutag_multiselect',
				'name' => 'namespace',
				'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-ns-label' )->text(),
				'help' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-ns-help' )->text(),
				'options' => array_values( array_map( function ( int $ns ) {
					return [
						'data' => $ns,
						'label' => $ns === NS_MAIN ?
							Message::newFromKey( 'blanknamespace' )->text() :
							$this->language->getNsText( $ns ),
					];
				}, $this->namespaceInfo->getValidNamespaces() ) ),
				'widget_allowArbitrary' => false,
				'widget_$overlay' => true,
			],
			[
				'type' => 'category_multiselect',
				'name' => 'category',
				'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-cat-label' )->text(),
				'help' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-cat-help' )->text(),
				'widget_$overlay' => true
			],
			[
				'type' => 'dropdown',
				'name' => 'operator',
				'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-operator-label' )->text(),
				'help' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-operator-help' )->text(),
				'options' => [
					[
						'data' => 'AND',
						'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-operator-and-label' )->text()
					],
					[
						'data' => 'OR',
						'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-operator-or-label' )->text()
					]
				],
				'widget_$overlay' => true,
			],
			[
				'type' => 'text',
				'name' => 'placeholder',
				'label' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-placeholder-label' )->text(),
				'help' => Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder-help' )->text(),
				'widget_placeholder' => Message::newFromKey(
					'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder-placeholder'
				)->text(),
			]
		] );

		return new ClientTagSpecification(
			'TagSearch',
			Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-desc' ),
			$formSpec,
			Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-title' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getResourceLoaderModules(): ?array {
		return [
			'ext.blueSpiceExtendedSearch.TagSearch',
			'ext.blueSpiceExtendedSearch.TagSearch.styles',
		];
	}
}
