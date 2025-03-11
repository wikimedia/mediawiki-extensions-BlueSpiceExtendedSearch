<?php

namespace BS\ExtendedSearch;

use Exception;
use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

class PostProcessor {

	/**
	 * @var string
	 */
	protected $searchType;

	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 * @var array
	 */
	protected $processors = [];

	/**
	 * @var bool
	 */
	protected $reSort = false;

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @param string $type
	 * @param Backend $backend
	 *
	 * @return PostProcessor
	 * @throws Exception
	 */
	public static function factory( $type, $backend ) {
		$postProcessor = new static(
			$type,
			$backend,
			MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' )
		);
		$processors = [];
		foreach ( $backend->getSources() as $source ) {
			$processors = array_merge( $processors, $source->getPostProcessors( $postProcessor ) );
		}
		/** @var PluginManager $pluginManager */
		$pluginManager = MediaWikiServices::getInstance()->getService( 'BSExtendedSearch.PluginManager' );
		/** @var IPostProcessorProvider $plugin */
		foreach ( $pluginManager->getPluginsImplementing( IPostProcessorProvider::class ) as $plugin ) {
			$processors = array_merge( $processors, $plugin->getPostProcessors( $postProcessor ) );
		}
		$postProcessor->setProcessors( $processors );
		return $postProcessor;
	}

	/**
	 * @param string $type
	 * @param Backend $backend
	 * @param Config $config
	 */
	protected function __construct( $type, $backend, $config ) {
		$this->searchType = $type;
		$this->backend = $backend;
		$this->config = $config;
	}

	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @param array $processors
	 * @throws Exception
	 */
	public function setProcessors( array $processors ) {
		$this->processors = $processors;
	}

	/**
	 * Get the search type currently being executed
	 *
	 * @return string
	 */
	public function getType() {
		return $this->searchType;
	}

	/**
	 * Mark re-sorting needed
	 */
	public function requestReSort() {
		$this->reSort = true;
	}

	/**
	 * @param SearchResult[] &$results
	 * @param Lookup $lookup
	 */
	public function process( &$results, $lookup ) {
		if ( empty( $results ) ) {
			return;
		}
		foreach ( $results as &$result ) {
			foreach ( $this->processors as $processor ) {
				$processor->process( $result, $lookup );
			}
		}

		if ( $this->reSort ) {
			$this->doReSort( $results, $lookup );
		}
	}

	/**
	 * @param array &$results
	 * @param Lookup $lookup
	 * @return array|void
	 */
	private function doReSort( &$results, $lookup ) {
		$sort = $lookup->getSort();
		if ( is_array( $sort ) && isset( $sort[0] ) ) {
			$primarySort = $sort[0];
			$field = array_keys( $primarySort )[0];
			$order = $primarySort[$field]['order'];

			$results = call_user_func_array(
				[ static::class, 'doSort' ],
				[ &$results, $field, $order ]
			);
			return $results;
		}
	}

	/**
	 * @param array &$results
	 * @param string $field
	 * @param string $order
	 * @return array
	 */
	private function doSort( &$results, $field, $order ) {
		usort( $results, static function ( $a, $b ) use ( $field, $order ) {
			if ( $field === '_score' ) {
				$fieldValueA = $a->getScore();
				$fieldValueB = $b->getScore();
			} else {
				$fieldValueA = $a->getData()[$field];
				$fieldValueB = $b->getData()[$field];
			}

			if ( $fieldValueA === $fieldValueB ) {
				return 0;
			}

			if ( $fieldValueA < $fieldValueB ) {
				return ( $order === 'desc' ) ? 1 : -1;
			} else {
				return ( $order === 'desc' ) ? -1 : 1;
			}
		} );
		return $results;
	}
}
