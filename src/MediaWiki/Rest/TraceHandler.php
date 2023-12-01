<?php

namespace BS\ExtendedSearch\MediaWiki\Rest;

use BS\ExtendedSearch\SearchTracker;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\User\UserIdentity;
use RequestContext;
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
			throw new HttpException( $exception->getMessage(), $exception->getCode() );
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

	/**
	 * @param string $contentType
	 *
	 * @return JsonBodyValidator
	 * @throws HttpException
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType === 'application/json' ) {
			return new JsonBodyValidator( [
				'dbkey' => [
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'string'
				],
				'namespace' => [
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'int'
				]
			] );
		}
		throw new HttpException( 'Body content must be json' );
	}
}
