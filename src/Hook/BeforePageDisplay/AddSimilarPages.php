<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

use BlueSpice\Services;
use BS\ExtendedSearch\Backend;
use Exception;

class AddSimilarPages extends \BlueSpice\Hook\BeforePageDisplay {
	protected function skipProcessing() {
		$title = $this->out->getTitle();
		if ( $title->isSpecialPage() ) {
			return true;
		}

		if ( $this->getContext()->getRequest()->getVal( 'action', 'view' ) !== 'view' ) {
			return true;
		}

		return false;
	}

	protected function doProcess() {
		try {
			// Execution time for entire code here,
			// on ~1000 pages index => 102ms
			$similarPages = Backend::instance()->getSimilarPages(
				$this->out->getTitle()
			);

			$linkRenderer = Services::getInstance()->getLinkRenderer();

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

			$pageLinks = \FormatJson::encode( $pageLinks );

			$this->out->addJsConfigVars( 'bsgESSimilarPages', $pageLinks );
		}
		catch ( Exception $e ) {
			wfDebugLog( 'BSExtendedSearch', "AddSimilarPages: {$e->getMessage()}" );
		}
	}

}
