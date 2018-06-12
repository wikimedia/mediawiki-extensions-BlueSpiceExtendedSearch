<?php

namespace BS\ExtendedSearch\Tests;

class LookupTest extends \MediaWikiTestCase {
	/*FILTERS*/
	public function testAddSingleTermsFilterValue() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->addTermsFilter( 'someField', 'someValue' );

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

	public function testAddMultipleTermsFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->addTermsFilter( 'someField', [ 'someValue1', 'someValue2' ] );
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

	public function testAddTermFilterValue() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->addTermFilter( 'someField', 'someValue' );

		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"term" => [ "someField" => 'someValue' ]
					]]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}


	public function testMergeMultipleTermsFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup( [
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		]);

		$oLookup->addTermsFilter( 'someField', [ 'someValue2', 'someValue3' ] );
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

	public function testRemoveSingleTermsFilterValue() {
		$oLookup= new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2' ] ]
					]]
				]
			]
		]);

		$oLookup->removeTermsFilter( 'someField', 'someValue2' );
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

	public function testRemoveMultiTermsFilterValues() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					]]
				]
			]
		]);

		$oLookup->removeTermsFilter( 'someField', [ 'someValue1', 'someValue2' ] );
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

	public function testRemoveTermFilterValue() {
		$oLookup= new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						"term" => [ "someField" => 'someValue1' ]
					],
					[
						"term" => [ "someField" => 'someValue2' ]
					]]
				]
			]
		]);

		$oLookup->removeTermFilter( 'someField', 'someValue1' );
		$aExpected = [
			"query" => [
				"bool" => [
					"filter" => [[
						"term" => [ "someField" => 'someValue2' ]
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
						"terms" => [ "someOtherField" => [ 'someValue1' ], "anotherField" => ['someValue2', 'someValue3'] ]
					],
					[
						"term" => [ "yetAnotherField" => 'someValue1' ]
					]]
				]
			]
		]);

		$oLookup->clearFilter( 'someField' );
		$oLookup->clearFilter( 'anotherField' );
		$oLookup->clearFilter( 'yetAnotherField' );
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

	public function testGetFilters() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"filter" => [[
						"terms" => [ "someField" => [ 'someValue1', 'someValue2', 'someValue3' ] ]
					],[
						"terms" => [ "someOtherField" => [ 'someValue1' ], "someField" => ['someValue4'] ]
					],
					[
						"term" => [ "yetAnotherField" => 'someValue1' ]
					]]
				]
			]
		]);

		$aExpected = [
			"terms" => [
				"someField" => [ 'someValue1', 'someValue2', 'someValue3', 'someValue4' ],
				"someOtherField" => [ 'someValue1' ]
			],
			"term" => [
				"yetAnotherField" => [ 'someValue1' ]
			]
		];
		$this->assertArrayEquals( $aExpected, $oLookup->getFilters() );
	}

	/*SORTING*/
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

	/*SHOULD*/
	public function testAddShouldTerms() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addShouldTerms( 'someField', [ "value1" ] );
		$aExpected= [
			"query" => [
				"bool" => [
					"should" => [
						[
							"terms" => [
								"someField" => [ "value1" ],
								"boost" => 1
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testMultipleAddShouldTerms() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addShouldTerms( 'someField', [ "value1", "value2" ], 2, false );
		$oLookup->addShouldTerms( 'someField', [ "value3" ], 4, false );
		$aExpected= [
			"query" => [
				"bool" => [
					"should" => [
						[
							"terms" => [
								"someField" => [ "value1", "value2" ],
								"boost" => 2
							]
						],
						[
							"terms" => [
								"someField" => [ "value3" ],
								"boost" => 4
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testAddShouldMatch() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addShouldMatch( 'someField', "someValue", 4 );
		$aExpected= [
			"query" => [
				"bool" => [
					"should" => [
						[
							"match" => [
								"someField" => [
									"query" => "someValue",
									"boost" => 4
								]
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveShouldTerms() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"should" => [
						[
							"terms" => [
								"someField" => [ "value1", "value2" ]
							]
						],
						[
							"terms" => [
								"anotherField" => [ "value3" ]
							]
						]
					]
				]
			]
		]);

		$oLookup->removeShouldTerms( 'someField', "value1" );
		$oLookup->removeShouldTerms( 'anotherField' );
		$aExpected = [
			"query" => [
				"bool" => [
					"should" => [
						[
							"terms" => [
								"someField" => [ "value2" ]
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveShouldMatch() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"should" => [
						[
							"match" => [
								"someField" => [
									"query" => "someValue",
									"boost" => 4
								]
							]
						],
						[
							"match" => [
								"anotherField" => [
									"query" => "someValue",
									"boost" => 4
								]
							]
						]
					]
				]
			]
		]);

		$oLookup->removeShouldMatch( 'someField' );
		$aExpected = [
			"query" => [
				"bool" => [
					"should" => [
						[
							"match" => [
								"anotherField" => [
									"query" => "someValue",
									"boost" => 4
								]
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	/*AGGREAGTION*/
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

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL(), 'Sub-aggregation should have been removed' );

		$oLookup->removeBucketTermsAggregation( '_type' );
		$oLookup->removeBucketTermsAggregation( 'someField' );

		$this->assertArrayEquals( [], $oLookup->getQueryDSL(), 'No aggregations should have remained' );
	}

	/*HIGHLIGHTER*/
	public function testAddHighlighter() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addHighlighter( 'someField/anotherField' );
		$aExpected= [
			"highlight" => [
				"fields" => [
					"someField" => [
						"matched_fields" => ["someField"],
						"pre_tags" => ['<b>'],
						"post_tags" => ['</b>']
						],
					"anotherField" => [
						"matched_fields" => [ "anotherField" ],
						"pre_tags" => ['<b>'],
						"post_tags" => ['</b>']
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveHighlighter() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"highlight" => [
				"fields" => [
					"someField" => [ "matched_fields" => [ "someField" ] ],
					"anotherField" => [ "matched_fields" => [ "anotherField" ] ]
				]
			]
		]);

		$oLookup->removeHighlighter( "someField" );

		$aExpected = [
			"highlight" => [
				"fields" => [
					"anotherField" => [ "matched_fields" => [ "anotherField" ] ]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	/*SOURCE FIELD*/
	public function testAddSourceField() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addSourceField( [ 'test', 'anotherTest' ] );
		$aExpected= [
			"_source" => [
				"test", "anotherTest"
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveSourceField() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"_source" => [
				"test", "anotherTest"
			]
		]);

		$oLookup->removeSourceField( "test" );

		$aExpected = [
			"_source" => [
				"anotherTest"
			]
		];

		$oLookup->removeSourceField( "anotherTest" );

		$this->assertArrayEquals( [], $oLookup->getQueryDSL() );
	}

	public function testAddBoolMustNotTerms() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		//Add single term
		$oLookup->addBoolMustNotTerms( 'testField', 'testValue' );

		$aExpected = [
			"query" => [
				"bool" => [
					"must_not" => [
						[ "terms" => [ "testField" => [ "testValue" ] ] ]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );

		//Add another term
		$oLookup->addBoolMustNotTerms( 'testField', 'anotherValue' );

		$aExpected = [
			"query" => [
				"bool" => [
					"must_not" => [
						[ "terms" => [ "testField" => [ "testValue", "anotherValue" ] ] ]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );

		//Add different field
		$oLookup->addBoolMustNotTerms( 'anotherField', [ "testValue", "anotherValue" ] );

		$aExpected = [
			"query" => [
				"bool" => [
					"must_not" => [
						[ "terms" => [ "testField" => [ "testValue", "anotherValue" ] ] ],
						[ "terms" => [ "anotherField" => [ "testValue", "anotherValue" ] ] ]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveBoolMustNotTerms() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"query" => [
				"bool" => [
					"must_not" => [
						[ "terms" => [ "testField" => [ "testValue", "anotherValue" ] ] ],
						[ "terms" => [ "anotherField" => [ "testValue", "anotherValue" ] ] ]
					]
				]
			]
		]);

		$oLookup->removeBoolMustNot( 'testField' );

		$aExpected = [
			"query" => [
				"bool" => [
					"must_not" => [
						[ "terms" => [ "anotherField" => [ "testValue", "anotherValue" ] ] ]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	/*AUTOCOMPLETE*/
	public function testAddAutocompleteSuggest() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->addAutocompleteSuggest( "someField", "Test" );
		$aExpected= [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [ "field" => "someField" ]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveAutocompleteSuggest() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [ "field" => "someField" ]
				],
				"anotherField" => [
					"prefix" => "Demo",
					"completion" => [ "field" => "anotherField" ]
				]
			]
		]);

		$oLookup->removeAutocompleteSuggest( "someField" );

		$aExpected = [
			"suggest" => [
				"anotherField" => [
					"prefix" => "Demo",
					"completion" => [ "field" => "anotherField" ]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );

		$oLookup->removeAutocompleteSuggest( "anotherField" );

		$this->assertArrayEquals( [], $oLookup->getQueryDSL() );
	}

	public function testAddAutocompleteSuggestContext() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [ "field" => "someField" ]
				]
			]
		]);

		$oLookup->addAutocompleteSuggestContext( "someField", "anotherField", "Value1" );

		$aExpected = [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"contexts" => [
							"anotherField" => ["Value1"]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveAutocompleteSuggestContext() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"contexts" => [
							"anotherField" => ["Value1"]
						]
					]
				]
			]
		]);

		$oLookup->removeAutocompleteSuggestContext( 'someField', 'anotherField' );

		$aExpected = [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField"
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveAutocompleteSuggestContextValue() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"contexts" => [
							"anotherField" => ["Value1", "Value2"]
						]
					]
				]
			]
		]);

		$oLookup->removeAutocompleteSuggestContextValue( 'someField', 'anotherField', "Value2" );

		$aExpected = [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"contexts" => [
							"anotherField" => ["Value1"]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testAddAutocompleteSuggestFuzziness() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField"
					]
				]
			]
		]);

		$oLookup->addAutocompleteSuggestFuzziness( 'someField', 2 );

		$aExpected = [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"fuzzy" => [
							"fuzziness" => 2
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testRemoveAutocompleteSuggestFuzziness() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"fuzzy" => [
							"fuzziness" => 2
						]
					]
				],
				"anotherField" => [
					"prefix" => "Demo",
					"completion" => [
						"field" => "anotherField",
						"fuzzy" => [
							"fuzziness" => 3
						]
					]
				]
			]
		]);

		$oLookup->removeAutocompleteSuggestFuzziness( 'anotherField' );

		$aExpected = [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField",
						"fuzzy" => [
							"fuzziness" => 2
						]
					]
				],
				"anotherField" => [
					"prefix" => "Demo",
					"completion" => [
						"field" => "anotherField"
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testSetAutocompleteSuggestSize() {
		$oLookup = new \BS\ExtendedSearch\Lookup([
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField"
					]
				],
				"anotherField" => [
					"prefix" => "Demo",
					"completion" => [
						"field" => "anotherField",
						"fuzzy" => [
							"fuzziness" => 3
						]
					]
				]
			]
		]);

		$oLookup->setAutocompleteSuggestSize( 'anotherField', 9 );

		$aExpected = [
			"suggest" => [
				"someField" => [
					"prefix" => "Test",
					"completion" => [
						"field" => "someField"
					]
				],
				"anotherField" => [
					"prefix" => "Demo",
					"completion" => [
						"field" => "anotherField",
						"fuzzy" => [
							"fuzziness" => 3
						],
						"size" => 9
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	/*SIMPLE QUERY STRING*/
	public function testXQueryString() {
		$oLookup = new \BS\ExtendedSearch\Lookup();
		$oLookup->setQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		$aExpected = [
			"query" => [
				"bool" => [
					"must" => [
						[
							"query_string" => [
								"query" => '"fried eggs" +(eggplant | potato) -frittata',
								"default_operator" => 'AND'
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
		$aQS = $oLookup->getQueryString();
		$this->assertEquals( $aQS['query'], '"fried eggs" +(eggplant | potato) -frittata' );

		$aExpected = [
			'query' => "Copy Paste",
			'default_operator' => "OR"
		];
		$oLookup->setQueryString( $aExpected );
		$aDSL = $oLookup->getQueryDSL();
		$this->assertArrayEquals( $aExpected, $aDSL['query']['bool']['must'][0]['query_string'] );
	}

	public function testClearQueryString() {
		$oLookup = new \BS\ExtendedSearch\Lookup( [
			"query" => [
				"bool" => [
					"must" => [
						[
							"query_string" => [
								"query" => "Lorem ipsum dolor sit amet"
							]
						]
					]
				]
			]
		]);
		$oLookup->clearQueryString();

		$aExpected = [
			"query" => [
				"bool" => [
					"must" => []
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testSetMatchQueryString() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->setMatchQueryString( 'someField', 'someValue' );

		$aExpected = [
			"query" => [
				"match" => [
					"someField" => [
						"query" => 'someValue'
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}

	public function testSetBoolMatchQueryFuzziness() {
		$oLookup = new \BS\ExtendedSearch\Lookup();

		$oLookup->setBoolMatchQueryString( 'someField', 'someValue' );
		$oLookup->setBoolMatchQueryFuzziness( 'someField', 2, ["option" => 1] );

		$aExpected = [
			"query" => [
				"bool" => [
					"must" => [
						"match" => [
							"someField" => [
								"query" => 'someValue',
								"fuzziness" => 2,
								"option" => 1
							]
						]
					]
				]
			]
		];

		$this->assertArrayEquals( $aExpected, $oLookup->getQueryDSL() );
	}
}