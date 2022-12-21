<?php

namespace BS\ExtendedSearch\Source\Job;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ExternalIndexFactory;
use BS\ExtendedSearch\IExternalIndex;
use BS\ExtendedSearch\Source\Base;
use Exception;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MWException;
use Status;

abstract class UpdateBase extends \Job {
	public const ACTION_DELETE = 'delete';
	public const ACTION_UPDATE = 'update';

	/** @var string */
	protected $action = self::ACTION_UPDATE;

	/** @var string */
	protected $sBackendKey = 'local';
	/** @var string */
	protected $sSourceKey = '';

	/**
	 * @var \BS\ExtendedSearch\Source\DocumentProvider\Base
	 */
	protected $dp;

	/**
	 * Run the job
	 * @return bool Success
	 */
	public function run() {
		if ( $this->shouldSkipProcessing() ) {
			return true;
		}
		try {
			$dC = $this->doRun();
		} catch ( MWException $ex ) {
			$this->setLastError( $ex->getMessage() );
			return false;
		}

		if ( !empty( $dC ) && is_array( $dC ) ) {
			$status = $this->pushToExternal( $dC );
			if ( !$status->isOK() ) {
				$this->setLastError( $status->getMessage() );
			}
		}
		$dC = null;
		unset( $dC );
		$this->destroyDP();
		$this->destroySource();

		return true;
	}

	abstract protected function isDeletion();

	/**
	 *
	 * @return Backend
	 */
	protected function getBackend() {
		return MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
	}

	/**
	 *
	 * @return Base
	 * @throws Exception
	 */
	protected function getSource() {
		return $this->getBackend()->getSource( $this->getSourceKey() );
	}

	protected function destroySource() {
		$this->getBackend()->destroySource( $this->getSourceKey() );
	}

	/**
	 *
	 * @return string
	 */
	protected function getBackendKey() {
		if ( isset( $this->params['backend'] ) ) {
			return $this->params['backend'];
		}
		return $this->sBackendKey;
	}

	/**
	 *
	 * @return string
	 */
	protected function getSourceKey() {
		if ( isset( $this->params['source'] ) ) {
			return $this->params['source'];
		}
		return $this->sSourceKey;
	}

	/**
	 * Execute this job and return the data config as an array
	 * @return array
	 */
	abstract protected function doRun();

	/**
	 *
	 * @param array $dC
	 * @return Status
	 */
	protected function pushToExternal( $dC ) {
		$status = Status::newGood();
		$dC[IExternalIndex::FIELD_SOURCE_KEY] = $this->getSourceKey();
		$dC[IExternalIndex::FIELD_BACKEND_KEY] = $this->getBackendKey();
		$dC[IExternalIndex::FIELD_INDEX_NAME] = $this->getBackend()->getConfig()->get(
			'index'
		);
		foreach ( $this->getExternalIndexFactory()->getTypes() as $type ) {
			try {
				$externalIndex = $this->getExternalIndexFactory()->getExternalIndex( $type, $dC );
				if ( !$externalIndex ) {
					continue;
				}
				$status->merge( $externalIndex->push(
					$this->isDeletion() ? static::ACTION_DELETE : static::ACTION_UPDATE
				) );
			} catch ( Exception $e ) {
				$status->error( $e );
			}
		}
		$dC = null;
		return $status;
	}

	/**
	 *
	 * @return ExternalIndexFactory
	 */
	protected function getExternalIndexFactory() {
		return MediaWikiServices::getInstance()->getService(
			'BSExtendedSearchExternalIndexFactory'
		);
	}

	protected function shouldSkipProcessing() {
		$job = $this;
		$skip = $this->skipProcessing();
		$this->getHookContainer()->run(
			'BSExtendedSearchIndexDocumentSkip',
			[
				$job,
				&$skip
			]
		);

		return $skip;
	}

	/**
	 * @return HookContainer
	 */
	protected function getHookContainer() {
		return MediaWikiServices::getInstance()->getHookContainer();
	}

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		return false;
	}

	protected function destroyDP() {
		$this->dp = null;
		unset( $this->dp );
	}

}
