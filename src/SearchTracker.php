<?php

namespace BS\ExtendedSearch;

use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentity;
use Wikimedia\Rdbms\ILoadBalancer;

class SearchTracker {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var SpecialPageFactory
	 */
	private $specialPageFactory;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param TitleFactory $titleFactory
	 * @param SpecialPageFactory $specialPageFactory
	 */
	public function __construct(
		ILoadBalancer $loadBalancer, TitleFactory $titleFactory, SpecialPageFactory $specialPageFactory
	) {
		$this->loadBalancer = $loadBalancer;
		$this->titleFactory = $titleFactory;
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @param int $namespace
	 * @param string $dbKey
	 * @param UserIdentity $user
	 *
	 * @return bool
	 */
	public function trace( int $namespace, string $dbKey, UserIdentity $user ): bool {
		$data = [
			'est_namespace' => $namespace,
			'est_title' => $dbKey,
			'est_user' => $user->getId(),
		];

		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$row = $dbw->selectRow(
			'bs_extendedsearch_trace',
			[ 'est_count' ],
			$data,
			__METHOD__
		);

		if ( !$row ) {
			return $dbw->insert(
				'bs_extendedsearch_trace',
				$data + [
					'est_count' => 1,
					'est_last_view' => $dbw->timestamp(),
					'est_type' => $namespace === -1 ? 'specialpage' : 'wikipage'
				],
				__METHOD__
			);
		} else {
			return $dbw->update(
				'bs_extendedsearch_trace',
				[
					'est_count' => (int)$row->est_count + 1,
					'est_last_view' => $dbw->timestamp(),
				],
				$data,
				__METHOD__
			);
		}
	}

	/**
	 * @param int $namespace
	 * @param string $dbKey
	 * @param UserIdentity $user
	 *
	 * @return bool
	 */
	public function remove( int $namespace, string $dbKey, UserIdentity $user ): bool {
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		return $dbw->delete(
			'bs_extendedsearch_trace', [
				'est_namespace' => $namespace,
				'est_title' => $dbKey,
				'est_user' => $user->getId(),
			],
			__METHOD__
		);
	}

	/**
	 * @param UserIdentity $user
	 *
	 * @return Title[]
	 */
	public function getForUser( UserIdentity $user ): array {
		// Get most viewed pages in last 2 weeks
		$res = $this->queryForUser( [
			'est_user' => $user->getId()
		] );
		if ( empty( $res ) ) {
			return $this->queryForUser( [
				'est_user' => $user->getId()
			] );
		}

		return $res;
	}

	/**
	 * @return array
	 */
	public function getForAnonymous(): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			'bs_extendedsearch_trace',
			[ 'est_namespace', 'est_title', 'est_type', 'SUM(est_count) as count' ],
			[],
			__METHOD__,
			[
				'GROUP BY' => 'est_title, est_namespace, est_type',
				'ORDER BY' => 'est_last_view DESC, count DESC',
				'LIMIT' => 10
			]
		);

		$result = [];
		foreach ( $res as $row ) {
			$result[] = $this->createForType( (int)$row->est_namespace, $row->est_title, $row->est_type );
		}

		return array_filter( $result );
	}

	/**
	 * @param array $conds
	 *
	 * @return array
	 */
	private function queryForUser( array $conds ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			'bs_extendedsearch_trace',
			[ 'est_namespace', 'est_title', 'est_type' ],
			$conds,
			__METHOD__,
			[ 'ORDER BY' => 'est_last_view DESC', 'LIMIT' => 10 ]
		);
		$result = [];
		foreach ( $res as $row ) {
			$result[] = $this->createForType( (int)$row->est_namespace, $row->est_title, $row->est_type );
		}

		// Filter out null values
		return array_filter( $result );
	}

	/**
	 * @param int $ns
	 * @param string $title
	 * @param string $type
	 *
	 * @return Title|null
	 */
	private function createForType( int $ns, string $title, string $type ): ?Title {
		switch ( $type ) {
			case 'wikipage':
				return $this->titleFactory->makeTitle( $ns, $title );
			case 'specialpage':
				$specialPage = $this->specialPageFactory->getPage( $title );
				if ( !$specialPage ) {
					return null;
				}
				return $specialPage->getPageTitle();
		}
		return null;
	}
}
