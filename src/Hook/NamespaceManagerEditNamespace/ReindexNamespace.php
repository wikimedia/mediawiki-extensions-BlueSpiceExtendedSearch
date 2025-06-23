<?php

namespace BS\ExtendedSearch\Hook\NamespaceManagerEditNamespace;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerEditNamespace;
use BS\ExtendedSearch\Source\Job\UpdateRepoFile;
use BS\ExtendedSearch\Source\Job\UpdateWikiPage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

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
		$services = MediaWikiServices::getInstance();
		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			[ 'page_namespace' => $this->nsId ],
			__METHOD__
		);

		$jobs = [];
		$namespaceInfo = $services->getNamespaceInfo();
		foreach ( $res as $row ) {
			$title = Title::newFromID( $row->page_id );
			if ( $title === null ) {
				continue;
			}
			$oldTitle = Title::newFromText(
				$namespaceInfo->getCanonicalName( $this->nsId ) . ':' . $title->getText()
			);
			// Delete old
			$jobs[] = new UpdateWikiPage(
				$title,
				[
					'action' => UpdateRepoFile::ACTION_DELETE,
					'forceDelete' => true,
					'documentIdSource' => $this->nsId . '|' . $oldTitle->getDBkey(),
				]
			);
			// Add new
			$jobs[] = new UpdateWikiPage( $title );
		}
		$services->getJobQueueGroup()->push( $jobs );
	}
}
