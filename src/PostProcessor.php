<?php

namespace BS\ExtendedSearch;

use BlueSpice\Services;
use BS\ExtendedSearch\Source\Base as SourceBase;
use Elastica\Result;
use Exception;
use MWException;
use Config;

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
	 * @return PostProcessor
	 */
	public static function factory( $type, $backend ) {
		$postProcessor = new static(
			$type, $backend, Services::getInstance()->getConfigFactory()->makeConfig( 'bsg' )
		);
		$processors = [];
		foreach ( $backend->getSources() as $key => $source ) {
			$processors[$key] = $source->getPostProcessor( $postProcessor );
		}
		$postProcessor->addProcessors( $processors );
		return $postProcessor;
	}

	/**
	 * @param string $type
	 * @param Backend $backend
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
	public function addProcessors( array $processors ) {
		foreach ( $processors as $sourceKey => $processor ) {
			$this->addProcessor( $sourceKey, $processor );
		}
	}

	/**
	 * @param string $sourceKey
	 * @param IPostProcessor $processor
	 * @throws Exception
	 * @throws MWException
	 */
	public function addProcessor( $sourceKey, IPostProcessor $processor ) {
		if ( $this->backend->getSource( $sourceKey ) instanceof SourceBase ) {
			$this->processors[$sourceKey] = $processor;
		} else {
			throw new MWException(
				"Postprocessor " . get_class( $processor ) . " is not an instance of " . IPostProcessor::class
			);
		}
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
	 * @param Result[] $results
	 * @param Lookup $lookup
	 */
	public function process( &$results, $lookup ) {
		if ( empty( $results ) ) {
			return;
		}
		foreach ( $results as &$result ) {
			$type = $result->getType();
			if ( isset( $this->processors[$type] ) ) {
				$this->processors[$type]->process( $result, $lookup );
			}
		}

		if ( $this->reSort ) {
			$this->doReSort( $results, $lookup );
		}
	}

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

	private function doSort( &$results, $field, $order ) {
		usort( $results, function ( $a, $b ) use ( $field, $order ) {
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
