( function ( mw, $ ) {
	QUnit.module( 'bs.extendedSearch.Lookup', QUnit.newMwEnvironment() );
	QUnit.dump.maxDepth = 10;

	QUnit.test( 'bs.extendedSearch.Lookup.test*etSimpleQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.setSimpleQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		var obj = {
			"query": {
				"bool": {
					"must": [{
						"simple_query_string": {
							"query": '"fried eggs" +(eggplant | potato) -frittata',
							"default_operator": 'and'
						}
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Setting SimpleQueryString value by string works' );
		assert.equal( lookup.getSimpleQueryString().query, '"fried eggs" +(eggplant | potato) -frittata', 'Getting SimpleQueryString works' );

		var q = {
			query: "Copy Paste",
			default_operator: "or"
		};
		lookup.setSimpleQueryString( q );

		assert.deepEqual( lookup.getQueryDSL().query.bool.must[0].simple_query_string, q, 'Setting SimpleQueryString value by object works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearSimpleQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"simple_query_string": {
					"query": "Lorem ipsum dolor sit amet"
				}
			}
		});
		lookup.clearSimpleQueryString();

		var obj = {
			"query": {}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing SimpleQueryString value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSingleTermsFilterValue', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.addTermsFilter( 'someField', 'someValue' );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding single filter value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddMultipleTermsFilterValues', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.addTermsFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding multiple terms filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testMergeMultipleTermsFilterValues', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2' ] }
					}]
				}
			}
		});

		lookup.addTermsFilter( 'someField', [ 'someValue2', 'someValue3' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2', 'someValue3' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Merging multiple terms filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddTermFilter', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.addTermFilter( 'someField', 'someValue' );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"term": { "someField": 'someValue' }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding term filter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSingleTermsFilterValue', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2' ] }
					}]
				}
			}
		});

		lookup.removeFilter( 'someField', 'someValue2' );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing single terms filter value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveTermFilter', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"term": { "someField": 'someValue1' }
					},{
						"term": { "someField": 'someValue2' }
					}]
				}
			}
		});

		lookup.removeFilter( 'someField', 'someValue1' );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"term": { "someField": 'someValue2' }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing term filter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveMultiTermsFilterValues', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2', 'someValue3' ] }
					}]
				}
			}
		});

		lookup.removeTermsFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue3' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing multiple terms filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearFilter', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2', 'someValue3' ] }
					},{
						"term": { "someOtherField": 'someValue1' }
					},{
						"terms": { "anotherField": [ 'someValue4' ] }
					}]
				}
			}
		});

		lookup.clearFilter( 'someField' );
		lookup.clearFilter( 'someOtherField' );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "anotherField": [ 'someValue4' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing a whole filter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testGetFilters', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2', 'someValue3' ] }
					},{
						"term": { "someOtherField": 'someValue1' }
					},{
						"terms": { "anotherField": [ 'someValue4' ] }
					},{
						"term": { "someOtherField": 'someValue2' }
					}]
				}
			}
		});

		var obj = {
			"terms": {
				"someField": [ 'someValue1', 'someValue2', 'someValue3' ],
				"anotherField": [ 'someValue4' ]
			},
			"term": {
				"someOtherField": [ 'someValue1', 'someValue2' ]
			}
		};

		assert.deepEqual( lookup.getFilters(), obj, 'Filters are returned in correct structure' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSort', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addSort( 'someField', bs.extendedSearch.Lookup.SORT_DESC );
		var obj = {
			"sort": [
				{ "someField": { "order": "desc" } }
			]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSort', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"sort": [
				{ "someField": { "order": "desc" } },
				{ "someField2": { "order": "asc" } }
			]
		});

		lookup.removeSort( 'someField2' );
		var obj = {
			"sort": [
				{ "someField": { "order": "desc" } }
			]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing a sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearSort', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"sort": [
				{ "someField": { "order": "desc" } }
			]
		});

		lookup.removeSort( 'someField' );
		var obj = {};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing all sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddShould', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addShould( 'someField', ['value1'] );

		var obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: ['value1']
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding "should" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveShould', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			query: {
				bool: {
					should: [ {
						terms: {
							someField: ['value1', 'value2', 'value3']
						}
					} ]
				}
			}
		});

		lookup.removeShould( 'someField', 'value1' );

		var obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: ['value2', 'value3']
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing "should" clauseworks' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddHighlighter', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addHighlighter( 'someField' );

		var obj = {
			highlight: {
				fields: {
					someField: {
						matched_fields: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a highlighter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveHighlighter', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			highlight: {
				fields: {
					someField: {
						matched_fields: 'someField'
					},
					anotherField: {
						matched_fields: 'anotherField'
					}
				}
			}
		});

		lookup.removeHighlighter( 'anotherField' );

		var obj = {
			highlight: {
				fields: {
					someField: {
						matched_fields: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing a highlighter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddAutocompleteSuggest', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addAutocompleteSuggest( 'someField', 'someValue' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding autocomplete suggest works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggest', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				},
				anotherField: {
					prefix: 'otherValue',
					completion: {
						field: 'anotherField'
					}
				}
			}
		});

		lookup.removeAutocompleteSuggest( 'anotherField' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing autocomplete suggest sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddAutocompleteSuggestContext', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		});

		lookup.addAutocompleteSuggestContext( 'someField', 'anotherField', 'value2' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						contexts: {
							anotherField: [ 'value2' ]
						}
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding autocomplete suggest context works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggestContext', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						contexts: {
							anotherField: [ 'value2' ]
						}
					}
				}
			}
		});

		lookup.removeAutocompleteSuggestContext( 'someField', 'anotherField' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing autocomplete suggest context works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggestContextValue', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						contexts: {
							anotherField: [ 'value2', 'value3' ],
							yetAnotherField: [ 'value2' ]
						}
					}
				}
			}
		});

		lookup.removeAutocompleteSuggestContextValue( 'someField', 'anotherField', 'value2' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						contexts: {
							anotherField: [ 'value3' ],
							yetAnotherField: [ 'value2' ]
						}
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing autocomplete suggest context value from multi-context setting works' );

		lookup.removeAutocompleteSuggestContextValue( 'someField', 'anotherField', 'value3' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						contexts: {
							yetAnotherField: [ 'value2' ]
						}
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing last autocomplete suggest context value of a context works' );

		lookup.removeAutocompleteSuggestContextValue( 'someField', 'yetAnotherField', 'value2' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing last value of last autocomplete suggest context works' );

	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddAutocompleteSuggestFuzziness', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		});

		lookup.addAutocompleteSuggestFuzziness( 'someField', 2 );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						fuzzy: {
							fuzziness: 2
						}
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding autocomplete fuzziness works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggestFuzziness', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						fuzzy: {
							fuzziness: 2
						}
					}
				}
			}
		});

		lookup.removeAutocompleteSuggestFuzziness( 'someField' );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing autocomplete fuzziness works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testSetAutocompleteSuggestSize', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						fuzzy: {
							fuzziness: 2
						}
					}
				}
			}
		});

		lookup.setAutocompleteSuggestSize( 'someField', 9 );

		var obj = {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField',
						fuzzy: {
							fuzziness: 2
						},
						size: 9
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Setting autocomplete suggester size works' );
	} );

}( mediaWiki, jQuery ) );
