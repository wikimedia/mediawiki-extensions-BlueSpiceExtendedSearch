<?php

namespace BS\ExtendedSearch\MediaWiki\Rest;

use BS\ExtendedSearch\SearchTracker;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\UserIdentity;
use Wikimedia\ParamValidator\ParamValidator;

abstract class TraceHandler extends SimpleHandler {
	/**
	 * @var SearchTracker
	 */
	private $tracker;

	/**
	 * @param SearchTracker $tracker
	 */
	public function __construct( SearchTracker $tracker ) {
		$this->tracker = $tracker;
	}

	public function execute() {
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isRegistered() ) {
			// Very much edge case, since anons should not be able to get here anyway (blocked by parent class)
			throw new HttpException( 'Cannot trace searches of anonymous users' );
		}
		$data = $this->getValidatedBody();

		try {
			$res = $this->doExecute( (int)$data['namespace'], $data['dbkey'], $user );
		} catch ( \Throwable $exception ) {
			throw new HttpException( $exception->getMessage(), 500 );
		}
		if ( $res ) {
			return $this->getResponseFactory()->createJson( [ 'success' => true ] );
		}
		throw new HttpException( $this->getGenericFailureMessage() );
	}

	/**
	 * @return SearchTracker
	 */
	protected function getTracker(): SearchTracker {
		return $this->tracker;
	}

	/**
	 * @param int $ns
	 * @param string $dbkey
	 * @param UserIdentity $user
	 *
	 * @return bool
	 */
	abstract protected function doExecute( int $ns, string $dbkey, UserIdentity $user ): bool;

	/**
	 * @return string
	 */
	abstract protected function getGenericFailureMessage(): string;

	/** @inheritDoc */
	public function getBodyParamSettings(): array {
		return [
			'dbkey' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string'
			],
			'namespace' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'integer'
			],
			'url' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_TYPE => 'string'
			],
		];
	}
}
