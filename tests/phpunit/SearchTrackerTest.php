<?php

namespace BS\ExtendedSearch\Tests;

use BS\ExtendedSearch\SearchTracker;
use IDatabase;
use MediaWiki\SpecialPage\SpecialPageFactory;
use PHPUnit\Framework\TestCase;
use SpecialPage;
use Title;
use TitleFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class SearchTrackerTest extends TestCase {
	/**
	 * @covers \BS\ExtendedSearch\SearchTracker::trace
	 * @return void
	 */
	public function testTrace() {
		$lb = $this->getLoadBalancer();
		$tf = $this->createMock( TitleFactory::class );
		$spf = $this->createMock( SpecialPageFactory::class );
		$user = $this->getUserMock();

		$db = $lb->getConnection( DB_PRIMARY );
		$db->method( 'insert' )->willReturn( true );
		$db->method( 'update' )->willReturn( true );
		$db->method( 'timestamp' )->willReturn( '123' );
		$db->expects( $this->exactly( 2 ) )->method( 'selectRow' )->withConsecutive(
			[
				$this->equalTo( 'bs_extendedsearch_trace' ),
				$this->equalTo( [ 'est_count' ] ),
				$this->equalTo( [
					'est_namespace' => 0,
					'est_title' => 'Test',
					'est_user' => 1,
				] ),
				$this->equalTo( SearchTracker::class . '::trace' )
			],
			[
				$this->equalTo( 'bs_extendedsearch_trace' ),
				$this->equalTo( [ 'est_count' ] ),
				$this->equalTo( [
					'est_namespace' => 0,
					'est_title' => 'Test',
					'est_user' => 1,
				] ),
				$this->equalTo( SearchTracker::class . '::trace' )
			]
		)->willReturnOnConsecutiveCalls(
			false,
			(object)[ 'est_count' => 1 ]
		);

		$db->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( 'bs_extendedsearch_trace' ),
				$this->equalTo( [
					'est_namespace' => 0,
					'est_title' => 'Test',
					'est_user' => 1,
					'est_count' => 1,
					'est_last_view' => '123',
					'est_type' => 'wikipage'
				] ),
				$this->equalTo( SearchTracker::class . '::trace' )
			)
			->willReturn( true );

		$db->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->equalTo( 'bs_extendedsearch_trace' ),
				$this->equalTo( [
					'est_count' => 2,
					'est_last_view' => '123',
				] ),
				$this->equalTo( [
					'est_namespace' => 0,
					'est_title' => 'Test',
					'est_user' => 1,
				] ),
				$this->equalTo( SearchTracker::class . '::trace' )
			)
			->willReturn( true );

		$tracker = new SearchTracker( $lb, $tf, $spf );
		// Insert first trace
		$tracker->trace( 0, 'Test', $user );
		// Insert second trace (update count)
		$tracker->trace( 0, 'Test', $user );
	}

	/**
	 * @covers \BS\ExtendedSearch\SearchTracker::getForUser
	 * @return void
	 */
	public function testGetForUser() {
		$tracker = $this->getSearchTrackerForRetrieval( [ 'est_namespace', 'est_title', 'est_type' ], [
			'est_user' => 1,
		], 'queryForUser', [ 'ORDER BY' => 'est_last_view DESC', 'LIMIT' => 10 ] );

		$result = $tracker->getForUser( $this->getUserMock() );

		foreach ( $result as $res ) {
			$this->assertInstanceOf( Title::class, $res );
		}
	}

	/**
	 * @covers \BS\ExtendedSearch\SearchTracker::getForAnonymous
	 * @return void
	 */
	public function testGetForAnonymous() {
		$tracker = $this->getSearchTrackerForRetrieval(
			[ 'est_namespace', 'est_title', 'est_type', 'SUM(est_count) as count' ], [], 'getForAnonymous', [
			'GROUP BY' => 'est_title, est_namespace, est_type',
			'ORDER BY' => 'est_last_view DESC, count DESC',
			'LIMIT' => 10
		] );
		$result = $tracker->getForAnonymous();

		foreach ( $result as $res ) {
			$this->assertInstanceOf( Title::class, $res );
		}
	}

	/**
	 * @param array $fields
	 * @param array $conds
	 * @param string $method
	 * @param array $options
	 *
	 * @return SearchTracker
	 */
	protected function getSearchTrackerForRetrieval( array $fields, array $conds, string $method, array $options ) {
		$lb = $this->getLoadBalancer();
		$db = $lb->getConnection( DB_PRIMARY );
		$db->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->equalTo( 'bs_extendedsearch_trace' ),
				$this->equalTo( $fields ),
				$this->equalTo( $conds ),
				$this->equalTo( SearchTracker::class . "::$method" ),
				$this->equalTo( $options )
			)
			->willReturn( new \FakeResultWrapper( [
				[
					'est_namespace' => 0,
					'est_title' => 'Test',
					'est_type' => 'wikipage',
					'count' => 1
				],
				[
					'est_namespace' => -1,
					'est_title' => 'DummySpecial',
					'est_type' => 'specialpage',
					'count' => 2
				]
			] ) );

		$tf = $this->createMock( TitleFactory::class );
		$tf->expects( $this->once() )->method( 'makeTitle' )->with(
			$this->equalTo( 0 ),
			$this->equalTo( 'Test' )
		)->willReturn( $this->createMock( Title::class ) );

		$spf = $this->createMock( SpecialPageFactory::class );
		$spMock = $this->createMock( SpecialPage::class );
		$spMock->method( 'getPageTitle' )->willReturn( $this->createMock( Title::class ) );
		$spf->expects( $this->once() )->method( 'getPage' )->with(
			$this->equalTo( 'DummySpecial' )
		)->willReturn( $spMock );

		return new SearchTracker( $lb, $tf, $spf );
	}

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|ILoadBalancer
	 */
	protected function getLoadBalancer() {
		$db = $this->createMock( IDatabase::class );
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $db );
		return $lb;
	}

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|\User
	 */
	private function getUserMock() {
		$user = $this->createMock( \User::class );
		$user->method( 'getId' )->willReturn( 1 );
		return $user;
	}
}
