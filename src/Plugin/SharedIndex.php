<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Backend;
use MediaWiki\Config\ConfigFactory;

class SharedIndex implements IIndexProvider {

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		private readonly ConfigFactory $configFactory
	) {
	}

	public function setIndices( Backend $backend, ?array $limitToSources, array &$indices ): void {
		$available = [ 'wikipage', 'repofile' ];
		if ( is_array( $limitToSources ) ) {
			$available = array_intersect( $available, $limitToSources );
		}
		$prefix = $this->getSharedUploadsIndexPrefix();
		if ( !$prefix ) {
			return;
		}
		foreach ( $available as $key ) {
			$indexName = $prefix . '_' . $key;
			$indices[] = $indexName;
		}
	}

	/**
	 * @param string $index
	 * @param Backend $backend
	 * @return string|null
	 */
	public function typeFromIndexName( string $index, Backend $backend ): ?string {
		$sharedPrefix = $this->getSharedUploadsIndexPrefix();
		if ( $sharedPrefix && str_starts_with( $index, $sharedPrefix ) ) {
			return substr( $index, strlen( $sharedPrefix ) + 1 );
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	private function getSharedUploadsIndexPrefix(): ?string {
		$config = $this->configFactory->makeConfig( 'bsg' );
		$useSharedUploads = $config->get( 'ESUseSharedUploads' );
		$indexPrefix = $config->get( 'ESSharedUploadsIndexPrefix' );
		if ( !$useSharedUploads || !$indexPrefix ) {
			return null;
		}
		return $indexPrefix;
	}

	/**
	 * @param string $index
	 * @return string|null
	 */
	public function getIndexLabel( string $index ): ?string {
		return null;
	}
}
