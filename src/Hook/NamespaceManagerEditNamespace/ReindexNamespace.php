<?php

namespace BS\ExtendedSearch\Hook\NamespaceManagerEditNamespace;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerEditNamespace;
use BS\ExtendedSearch\Source\Job\UpdateRepoFile;
use BS\ExtendedSearch\Source\Job\UpdateWikiPage;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Title;

class ReindexNamespace extends NamespaceManagerEditNamespace {
	protected function skipProcessing() {
		if ( !isset( $this->namespaceDefinition[$this->nsId]['name' ] ) ) {
			return true;
		}
		$canonical = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getCanonicalName( $this->nsId );
		$name = $this->namespaceDefinition[$this->nsId]['name'];

		return $canonical === $name;
	}

	protected function doProcess() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			[ 'page_namespace' => $this->nsId ],
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::newFromID( $row->page_id );
			if ( $title === null ) {
				continue;
			}
			$oldTitle = Title::newFromText(
				MediaWikiServices::getInstance()
					->getNamespaceInfo()
					->getCanonicalName( $this->nsId ) . ':' . $title->getText()
			);
			// Delete old
			JobQueueGroup::singleton()->push(
				new UpdateWikiPage(
					$title,
					[
						'action' => UpdateRepoFile::ACTION_DELETE,
						'forceDelete' => true,
						// We have to get the URL here, by the time job runs, URL is changed
						'canonicalUrl' => $oldTitle->getCanonicalURL()
					]
				)
			);
			// Add new
			JobQueueGroup::singleton()->push(
				new UpdateWikiPage( $title )
			);
		}
	}
}
