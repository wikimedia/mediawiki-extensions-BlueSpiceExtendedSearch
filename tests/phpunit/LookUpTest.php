<?php

namespace BS\ExtendedSearch\Tests;

class LookUpTest extends \MediaWikiTestCase {
	public function testSetType() {
		$oLookUp = new \BS\ExtendedSearch\LookUp();
		$oLookUp->setType( 'someType' );

		$aExpected = [
			"query" => [
				"type" => [
					"value" => "someType"
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testClearType() {
		$oLookUp = new \BS\ExtendedSearch\LookUp([
			"query" => [
				"type" => [
					"value" => "someType"
				]
			]
		]);
		$oLookUp->clearType();

		$aExpected = [
			"query" => []
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testXSimpleQueryString() {
		$oLookUp = new \BS\ExtendedSearch\LookUp();
		$oLookUp->setSimpleQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		$aExpected = [
			"query" => [
				"simple_query_string" => [
					"query" => '"fried eggs" +(eggplant | potato) -frittata',
					"default_operator" => 'and'
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
		$aSQS = $oLookUp->getSimpleQueryString();
		$this->assertEquals( $aSQS['query'], '"fried eggs" +(eggplant | potato) -frittata' );

		$aExpected = [
			'query' => "Copy Paste",
			'default_operator' => "or"
		];
		$oLookUp->setSimpleQueryString( $aExpected );
		$aSQS = $oLookUp->getQueryDSL();
		$this->assertArrayEquals( $aExpected, $aSQS['query']['simple_query_string'] );
	}

	public function testClearSimpleQueryString() {
		$oLookUp = new \BS\ExtendedSearch\LookUp( [
			"query" => [
				"simple_query_string" => [
					"query" => "Lorem ipsum dolor sit amet"
				]
			]
		]);
		$oLookUp->clearSimpleQueryString();

		$aExpected = [
			"query" => []
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testAddSingleFilterValue() {
		$oLookUp = new \BS\ExtendedSearch\LookUp();
		$oLookUp->addFilter( 'someField', 'someValue' );

		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testAddMultipleFilterValues() {
		$oLookUp = new \BS\ExtendedSearch\LookUp();
		$oLookUp->addFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField"  => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
		
	}

	public function testMergeMultipleFilterValues() {
		$oLookUp = new \BS\ExtendedSearch\LookUp( [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		]);

		$oLookUp->addFilter( 'someField', [ 'someValue2', 'someValue3' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testRemoveSingleFilterValue() {
		$oLookUp= new \BS\ExtendedSearch\LookUp([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		]);

		$oLookUp->removeFilter( 'someField', 'someValue2' );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testRemoveMultiFilterValues() {
		$oLookUp = new \BS\ExtendedSearch\LookUp([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					]]
				]
			]
		]);

		$oLookUp->removeFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue3' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testClearFilter() {
		$oLookUp = new \BS\ExtendedSearch\LookUp([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					],[
						"terms" => [ "someOtherField" => [ 'someValue1' ] ]
					]]
				]
			]
		]);

		$oLookUp->removeFilter( 'someField', [ 'someValue1', 'someValue2', 'someValue3' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someOtherField" => [ 'someValue1' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testAddSort() {
		$oLookUp = new \BS\ExtendedSearch\LookUp();

		$oLookUp->addSort( 'someField', \BS\ExtendedSearch\LookUp::SORT_DESC );
		$aExpected= [
			"sort" => [
				[ "someField" => [ "order" => "desc" ] ]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testRemoveSort() {
		$oLookUp = new \BS\ExtendedSearch\LookUp([
			"sort" => [
				[ "someField" => [ "order" => "desc" ] ],
				[ "someField2" => [ "order" => "asc" ] ]
			]
		]);

		$oLookUp->removeSort( 'someField2' );
		$aExpected = [
			"sort" => [
				[ "someField" => [ "order" => "desc" ] ]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookUp->getQueryDSL() );
	}

	public function testClearSort() {
		$oLookUp = new \BS\ExtendedSearch\LookUp([
			"sort" => [
				[ "someField" => [ "order" => "desc"  ] ]
			]
		]);

		$oLookUp->removeSort( 'someField' );

		$this->assertArrayEquals( [], $oLookUp->getQueryDSL() );
	}
}