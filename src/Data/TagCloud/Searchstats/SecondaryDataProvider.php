<?php

namespace BS\ExtendedSearch\Data\TagCloud\Searchstats;

use BlueSpice\TagCloud\Data\TagCloud\Record;

class SecondaryDataProvider extends \BlueSpice\Data\SecondaryDataProvider {

	/**
	 *
	 * @var \MediaWiki\Linker\LinkRenderer
	 */
	protected $linkrenderer = null;

	/**
	 * @var \IContextSource
	 */
	protected $context;

	/**
	 *
	 * @param \MediaWiki\Linker\LinkRenderer $linkrenderer
	 * @param \IContextSource $context
	 */
	public function __construct( $linkrenderer, $context ) {
		$this->linkrenderer = $linkrenderer;
		$this->context = $context;
	}

	/**
	 *
	 * @param Record $dataSet
	 */
	protected function doExtend( &$dataSet ){
		$rawData = $dataSet->getData();

		$title = \SpecialPage::getTitleFor( 'BSSearchCenter' );
		$rawData->{Record::RENDEREDLINK} = '';
		$rawData->{Record::LINK} = $title->getLocalURL( [
			'q' => $rawData->{Record::NAME}
		]);

		$dataSet = new Record( $rawData );
	}
}
