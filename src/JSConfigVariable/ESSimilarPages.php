<?php

namespace BS\ExtendedSearch\JSConfigVariable;

use BlueSpice\JSConfigVariable;
use BS\ExtendedSearch\Backend;
use Exception;
use FormatJson;
use MediaWiki\MediaWikiServices;

class ESSimilarPages extends JSConfigVariable {

	/**
	 * @inheritDoc
	 */
	public function getValue() {
		try {
			$title = $this->getContext()->getTitle();
			// Execution time for entire code here,
			// on ~1000 pages index => 102ms
			$similarPages = Backend::instance()->getSimilarPages(
				$title
			);

			$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

			$pageLinks = [];
			foreach ( $similarPages as $title ) {
				$pageLinks[] = [
					'page_anchor' => $linkRenderer->makeLink(
						$title,
						$title->getText()
					),
					'class' => 'pills'
				];
			}

			return FormatJson::encode( $pageLinks );
		}
		catch ( Exception $e ) {
			wfDebugLog( 'BSExtendedSearch', "AddSimilarPages: {$e->getMessage()}" );

			return null;
		}
	}
}
