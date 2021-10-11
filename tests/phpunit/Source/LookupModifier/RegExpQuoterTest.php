<?php

namespace BS\ExtendedSearch\Tests\Source\LookupModifier;

use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Source\LookupModifier\RegExpQuoter;
use MediaWikiIntegrationTestCase;

class RegExpQuoterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider provideTestQueries
	 * @covers BS\ExtendedSearch\Source\LookupModifier\RegExpQuoter::apply
	 */
	public function testApply( $singleQuery, $singleQueryQuoted ) {
		$context = $this->createMock( 'IContextSource' );

		$lookup = new Lookup();
		$lookup->setQueryString( $singleQuery );

		$regExpQuoter = new RegExpQuoter( $lookup, $context );
		$regExpQuoter->apply();

		$query = $lookup->getQueryString();
		$this->assertEquals( $singleQueryQuoted, $query['query'] );
	}

	/**
	 * @dataProvider provideTestQueries
	 * @covers BS\ExtendedSearch\Source\LookupModifier\RegExpQuoter::undo
	 */
	public function testUndo( $singleQuery, $singleQueryQuoted ) {
		$context = $this->createMock( 'IContextSource' );

		$lookup = new Lookup();
		$lookup->setQueryString( $singleQuery );

		$regExpQuoter = new RegExpQuoter( $lookup, $context );
		$regExpQuoter->apply();

		$regExpQuoter->undo();

		$query = $lookup->getQueryString();
		$this->assertEquals( $singleQuery, $query['query'] );
	}

	public function provideTestQueries() {
		$baseTestQueries = [
			[ '19-02-2006', '"19-02-2006"' ],
			[ '15-02', '"15-02"' ],
			[ '19-02-2007 www sss 19-02', '"19-02-2007" www sss "19-02"' ],
			[ '19-02-2007 19-02 19-02-2008 2008-19-02 19-02', '"19-02-2007" "19-02" "19-02-2008" "2008-19-02" "19-02"' ],
			[ '10-20, привет  Å Ä 2001-10-1, 2001-1-1, 1-2001-1, 1-1-10', '"10-20", привет  Å Ä "2001-10-1", "2001-1-1", "1-2001-1", "1-1-10"' ],
			[ '10-20, привет  Å Ä 2001-10-1, 20-10, 汉字', '"10-20", привет  Å Ä "2001-10-1", "20-10", 汉字' ],
			[ '"5 S 178/58"', '"5 S 178/58"' ],
			[ 'lorem ipsum "2020-12-09 dolor"', 'lorem ipsum "2020-12-09 dolor"' ],
			[ 'lorem ipsum "2020-12-09 dolor" 2020-01-01', 'lorem ipsum "2020-12-09 dolor" "2020-01-01"' ]
		];

		$delimiters = [ '/', '.', '\\' ];
		$testQueries = [];
		foreach ( $delimiters as $delimiter ) {
			$modBaseTestQueries = [];
			foreach ( $baseTestQueries as $queryPair ) {
				$modBaseTestQueries[] = [
					str_replace( '-', $delimiter, $queryPair[0] ),
					str_replace( '-', $delimiter, $queryPair[1] ),
				];
			}
			$testQueries = array_merge( $modBaseTestQueries, $testQueries );
		}
		$testQueries = array_merge( $baseTestQueries, $testQueries );

		return $testQueries;
	}

}
