<?php

namespace BS\ExtendedSearch\MediaWiki\Rest;

use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;

class InsertSearchTraceHandler extends TraceHandler {

	/**
	 * @param int $ns
	 * @param string $dbkey
	 * @param UserIdentity $user
	 *
	 * @return bool
	 */
	protected function doExecute( int $ns, string $dbkey, UserIdentity $user ): bool {
		return $this->getTracker()->trace( $ns, $dbkey, $user );
	}

	/**
	 * @return string
	 */
	protected function getGenericFailureMessage(): string {
		return Message::newFromKey( 'bs-extendedsearch-rest-trace-insert-failure' )->text();
	}
}
