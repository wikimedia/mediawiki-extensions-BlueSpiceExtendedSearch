<?php

namespace BS\ExtendedSearch\Source\Job;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ExternalIndexFactory;
use BS\ExtendedSearch\IExternalIndex;
use BS\ExtendedSearch\ISearchDocumentProvider;
use BS\ExtendedSearch\ISearchSource;
use Exception;
use Job;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use MWException;

abstract class UpdateJob extends Job {
	public const ACTION_DELETE = 'delete';
	public const ACTION_UPDATE = 'update';

	/** @var string */
	protected $action = self::ACTION_UPDATE;

	/** @var string */
	protected $sBackendKey = 'local';
	/** @var string */
	protected $sSourceKey = '';

	/**
	 * @var ISearchDocumentProvider
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

		return true;
	}

	/**
	 * @return mixed
	 */
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
	 * @return ISearchSource
	 * @throws Exception
	 */
	protected function getSource() {
		return $this->getBackend()->getSource( $this->getSourceKey() );
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

	/**
	 * @param string $identifier
	 *
	 * @return string
	 */
	protected function getDocumentId( string $identifier ): string {
		return md5( $identifier );
	}

}
