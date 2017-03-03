<?php

namespace BS\ExtendedSearch\Tests;

class LookupTest extends \MediaWikiTestCase {
	public function testXetTypes() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->setTypes(  [ 'someType' ] );

		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						 'terms' => [ '_type' => [ "someType" ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );

		$aNewTypes = $oLookup->getTypes();
		$aNewTypes[] = 'someOtherType';
		$oLookup->setTypes( $aNewTypes );

		$this->assertArrayEquals( [ "someType", "someOtherType" ], $oLookup->getTypes() );
	}

	public function testClearTypes() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						'terms' => [ '_type' => [ "someType" ] ]
					]]
				]
			]
		]);
		$oLookup->clearTypes();

		$this->assertArrayEquals( [], $oLookup->getTypes() );
	}

	public function testXSimpleQueryString() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->setSimpleQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		$aExpected = [
			"query" => [
				"bool" => [
					"must" => [
						[
							"simple_query_string" => [
								"query" => '"fried eggs" +(eggplant | potato) -frittata',
								"default_operator" => 'and'
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
		$aSQS = $oLookup->getSimpleQueryString();
		$this->assertEquals( $aSQS['query'], '"fried eggs" +(eggplant | potato) -frittata' );

		$aExpected = [
			'query' => "Copy Paste",
			'default_operator' => "or"
		];
		$oLookup->setSimpleQueryString( $aExpected );
		$aDSL = $oLookup->getQueryDSL();
		$this->assertArrayEquals( $aExpected, $aDSL['query']['bool']['must'][0]['simple_query_string'] );
	}

	public function testClearSimpleQueryString() {
		$oLookup = new \BS\ExtendedSearch\Lookup( [
			"query" => [
				"simple_query_string" => [
					"query" => "Lorem ipsum dolor sit amet"
				]
			]
		]);
		$oLookup->clearSimpleQueryString();

		$aExpected = [
			"query" => []
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testAddSingleFilterValue() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->addFilter( 'someField', 'someValue' );

		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testAddMultipleFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->addFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField"  => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
		
	}

	public function testMergeMultipleFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup( [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		]);

		$oLookup->addFilter( 'someField', [ 'someValue2', 'someValue3' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveSingleFilterValue() {
		$oLookup= new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		]);

		$oLookup->removeFilter( 'someField', 'someValue2' );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveMultiFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					]]
				]
			]
		]);

		$oLookup->removeFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue3' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveAllFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
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

		$oLookup->removeFilter( 'someField', [ 'someValue1', 'someValue2', 'someValue3' ] );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someOtherField" => [ 'someValue1' ] ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testAddSort() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addSort( 'someField', \BS\ExtendedSearch\Lookup::SORT_DESC );
		$aExpected= [
			"sort" => [
				[ "someField" => [ "order" => "desc" ] ]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveSort() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"sort" => [
				[ "someField" => [ "order" => "desc" ] ],
				[ "someField2" => [ "order" => "asc" ] ]
			]
		]);

		$oLookup->removeSort( 'someField2' );
		$aExpected = [
			"sort" => [
				[ "someField" => [ "order" => "desc" ] ]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testClearSort() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"sort" => [
				[ "someField" => [ "order" => "desc"  ] ]
			]
		]);

		$oLookup->removeSort( 'someField' );

		$this->assertArrayEquals( [], $oLookup->getQueryDSL() );
	}

	public function testSetBucketTermsAggregation() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->setBucketTermsAggregation( '_type/extension' );

		$aExpected = [
			"aggs" => [
				"field__type" => [
					"terms" => [
						"field" => "_type"
					],
					"aggs" => [
						"field_extension" => [
							"terms" => [
								"field" => "extension"
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveTermAggregation() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"aggs" => [
				"field__type" => [
					"terms" => [
						"field" => "_type"
					],
					"aggs" => [
						"field_extension" => [
							"terms" => [
								"field" => "extension"
							]
						]
					]
				],
				"field_someField" => [
					"terms" => [
						"field" => "someField"
					]
				]
			]
		]);
		$oLookup->removeBucketTermsAggregation( '_type/extension' );

		$aExpected = [
			"aggs" => [
				"field__type" => [
					"terms" => [
						"field" => "_type"
					],
				],
				"field_someField" => [
					"terms" => [
						"field" => "someField"
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL(), 'Sub-aggregation should habe been removed' );

		$oLookup->removeBucketTermsAggregation( '_type' );
		$oLookup->removeBucketTermsAggregation( 'someField' );

		$this->assertArrayEquals( [], $oLookup->getQueryDSL(), 'No aggregations should have remained' );
	}
}