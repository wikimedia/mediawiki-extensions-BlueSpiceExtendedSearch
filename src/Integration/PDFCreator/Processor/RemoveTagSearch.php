<?php

namespace BS\ExtendedSearch\Integration\PDFCreator\Processor;

use DOMXPath;
use MediaWiki\Extension\PDFCreator\IProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;

class RemoveTagSearch implements IProcessor {

	/**
	 * @inheritDoc
	 */
	public function execute(
		array &$pages, array &$images, array &$attachments, ExportContext $context, string $module = '', $params = []
	): void {
		/** @var ExportPage $page */
		foreach ( $pages as &$page ) {
			$dom = $page->getDOMDocument();
			$xpath = new DOMXPath( $dom );
			$forms = $xpath->query( "//*[contains(@class, 'bs-tagsearch-form')]" );
			foreach ( $forms as $form ) {
				$form->parentNode->removeChild( $form );
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getPosition(): int {
		return 50;
	}
}
