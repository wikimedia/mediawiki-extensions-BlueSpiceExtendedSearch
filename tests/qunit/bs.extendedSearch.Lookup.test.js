( function ( mw, $ ) {
	QUnit.module( 'bs.extendedSearch.Lookup', QUnit.newMwEnvironment() );

	QUnit.test( 'bs.extendedSearch.Lookup.test*etTypes', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.setTypes( 'someType' );

		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "_type": [ "someType" ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Setting type values works #1'  );

		var newTypes = lookup.getTypes();
		newTypes.push( 'someOtherType' );

		assert.deepEqual( lookup.getTypes(), [ "someType", "someOtherType" ], 'Setting type values works #2' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearTypes', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "_type": [ "someType" ] }
					}]
				}
			}
		});
		lookup.clearTypes();

		assert.deepEqual( lookup.getTypes(), [], 'Clearing type values works' );
	} );

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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSingleFilterValue', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.addFilter( 'someField', 'someValue' );
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddMultipleFilterValues', function ( assert ) {
		var lookup = new bs.extendedSearch.Lookup();
		lookup.addFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding multiple filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testMergeMultipleFilterValues', function ( assert ) {
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

		lookup.addFilter( 'someField', [ 'someValue2', 'someValue3' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2', 'someValue3' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Merging multiple filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSingleFilterValue', function ( assert ) {
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

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing single filter value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveMultiFilterValues', function ( assert ) {
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

		lookup.removeFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue3' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing multiple filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearFilter', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.Lookup({
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someField": [ 'someValue1', 'someValue2', 'someValue3' ] }
					},{
						"terms": { "someOtherField": [ 'someValue1' ] }
					}]
				}
			}
		});

		lookup.removeFilter( 'someField', [ 'someValue1', 'someValue2', 'someValue3' ] );
		var obj = {
			"query": {
				"bool": {
					"filter": [{
						"terms": { "someOtherField": [ 'someValue1' ] }
					}]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing a whole filter works' );
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

}( mediaWiki, jQuery ) );
