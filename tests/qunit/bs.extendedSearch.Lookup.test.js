( function ( mw, $ ) {
	QUnit.module( 'bs.extendedSearch.Lookup', QUnit.newMwEnvironment() );
	QUnit.dump.maxDepth = 10;

	QUnit.test( 'bs.extendedSearch.Lookup.test*etQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.setQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		var obj = {
			"query": {
				"bool": {
					"must": [{
						"query_string": {
							"query": '"fried eggs" +(eggplant | potato) -frittata',
							"default_operator": 'AND'
						}
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Setting QueryString value by string works' );
		assert.equal( lookup.getQueryString().query, '"fried eggs" +(eggplant | potato) -frittata', 'Getting QueryString works' );

		var q = {
			query: "Copy Paste",
			default_operator: "or"
		};
		lookup.setQueryString( q );

		assert.deepEqual( lookup.getQueryDSL().query.bool.must[0].query_string, q, 'Setting QueryString value by object works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"must": [{
						"query_string": {
							"query": '"fried eggs" +(eggplant | potato) -frittata',
							"default_operator": 'and'
						}
					}]
				}
			}
		});
		lookup.clearQueryString();

		var obj = {
			"query": {
				"bool": {
					"must": []
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing QueryString value works' );
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddShouldTerms', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addShouldTerms( 'someField', ['value1'] );

		var obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: ['value1'],
							boost: 1
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding "should temrs" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddMultipleShouldTerms', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addShouldTerms( 'someField', ['value1', 'value2'], 2, false );
		lookup.addShouldTerms( 'someField', ['value3'], 4, false );

		var obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: ['value1', 'value2'],
							boost: 2
						}
					}, {
						terms: {
							someField: ['value3'],
							boost: 4
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding "should temrs" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddShouldMatch', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addShouldMatch( 'someField', "value1", 4 );

		var obj = {
			query: {
				bool: {
					should: [ {
						match: {
							someField: {
								query: 'value1',
								boost: 4
							}
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding "should match" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveShouldTerms', function ( assert ) {
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

		lookup.removeShouldTerms( 'someField', 'value1' );

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

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing "should terms" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveShouldMatch', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			query: {
				bool: {
					should: [ {
						match: {
							someField: {
								query: 'value1',
								boost: 4
							}
						}
					} ]
				}
			}
		});

		lookup.removeShouldMatch( 'someField', 'value1' );

		var obj = {
			query: {
				bool: {
					should: []
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing "should match" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddHighlighter', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addHighlighter( 'someField' );

		var obj = {
			highlight: {
				fields: {
					someField: {
						matched_fields: 'someField',
						pre_tags: [ "<b>" ],
						post_tags: [ "</b>" ]
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSourceField', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addSourceField( [ 'someField', 'anotherField' ] );

		var obj = {
			_source: [ 'someField', 'anotherField' ]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a source field works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSourceField', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			_source: [ 'someField', 'anotherField' ]
		});

		lookup.removeSourceField( 'anotherField' );

		var obj = {
			_source: [ 'someField' ]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing a source field works' );

		lookup.removeSourceField( 'someField' );

		assert.deepEqual( lookup.getQueryDSL(), {}, 'Removing whole _source key when all fields are removed works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddBoolMustNotTerms', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.addBoolMustNotTerms( 'someField', 'someValue' );

		var obj = {
			query: {
				bool: {
					must_not: [
						{ terms: { someField: [ 'someValue' ] } }
					]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a bool query must not terms works' );

		lookup.addBoolMustNotTerms( 'someField', 'someOtherValue' );

		var obj = {
			query: {
				bool: {
					must_not: [
						{ terms: { someField: [ 'someValue', 'someOtherValue' ] } }
					]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a value to bool query must not terms works' );

		lookup.addBoolMustNotTerms( 'someOtherField', [ 'someValue', 'someOtherValue' ] );

		var obj = {
			query: {
				bool: {
					must_not: [
						{ terms: { someField: [ 'someValue', 'someOtherValue' ] } },
						{ terms: { someOtherField: [ 'someValue', 'someOtherValue' ] } }
					]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding another field in bool query must not terms works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveBoolMustNotTerm', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			query: {
				bool: {
					must_not: [
						{ terms: { someField: [ 'someValue', 'someOtherValue' ] } },
						{ terms: { someOtherField: [ 'someValue', 'someOtherValue' ] } }
					]
				}
			}
		});

		lookup.removeBoolMustNot( 'someField' );

		var obj = {
			query: {
				bool: {
					must_not: [
						{ terms: { someOtherField: [ 'someValue', 'someOtherValue' ] } }
					]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing a bool query must not term works' );
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

	QUnit.test( 'bs.extendedSearch.Lookup.testSetMatchQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({});

		lookup.setMatchQueryString( 'someField', 'someValue' );

		var obj = {
			query: {
				match: {
					someField: {
						query: 'someValue'
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding Match Query query string works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testSetBoolMatchQueryFuzziness', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();

		lookup.setBoolMatchQueryString( 'someField', 'someValue' );
		lookup.setBoolMatchQueryFuzziness( 'someField', 2, { option: 1 } );

		var obj = {
			query: {
				bool: {
					must: {
						match: {
							someField: {
								query: 'someValue',
								fuzziness: 2,
								option: 1
							}
						}
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding bool match query fuzziness works' );
	} );

}( mediaWiki, jQuery ) );
