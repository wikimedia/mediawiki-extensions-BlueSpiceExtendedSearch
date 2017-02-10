( function ( mw, $ ) {
	QUnit.test( 'bs.extendedSearch.LookUp.testSetType', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp();
		lookup.setType( 'someType' );

		var obj = {
			"query": {
				"type": {
					"value": "someType"
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Setting type value works' );
	} );

	QUnit.test( 'bs.extendedSearch.LookUp.testClearType', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp({
			"query": {
				"type": {
					"value": "someType"
				}
			}
		});
		lookup.clearType();

		var obj = {
			"query": {}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing type value works' );
	} );

	QUnit.test( 'bs.extendedSearch.LookUp.test*etSimpleQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp();
		lookup.setSimpleQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		var obj = {
			"query": {
				"simple_query_string": {
					"query": '"fried eggs" +(eggplant | potato) -frittata',
					"default_operator": 'and'
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
		assert.deepEqual( lookup.getQueryDSL().query.simple_query_string, q, 'Setting SimpleQueryString value by object works' );
	} );

	QUnit.test( 'bs.extendedSearch.LookUp.testClearSimpleQueryString', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp({
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

	QUnit.test( 'bs.extendedSearch.LookUp.testAddSingleFilterValue', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp();
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

	QUnit.test( 'bs.extendedSearch.LookUp.testAddMultipleFilterValues', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp();
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

	QUnit.test( 'bs.extendedSearch.LookUp.testMergeMultipleFilterValues', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.LookUp({
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

	QUnit.test( 'bs.extendedSearch.LookUp.testRemoveSingleFilterValue', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp({
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

	QUnit.test( 'bs.extendedSearch.LookUp.testRemoveMultiFilterValues', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.LookUp({
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

	QUnit.test( 'bs.extendedSearch.LookUp.testClearFilter', function ( assert ) {
		QUnit.dump.maxDepth = 10;

		var lookup = new bs.extendedSearch.LookUp({
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

	QUnit.test( 'bs.extendedSearch.LookUp.testAddSort', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp();

		lookup.addSort( 'someField', bs.extendedSearch.LookUp.SORT_DESC );
		var obj = {
			"sort": [
				{ "someField": { "order": "desc" } }
			]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.LookUp.testRemoveSort', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp({
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

	QUnit.test( 'bs.extendedSearch.LookUp.testCleasSort', function ( assert ) {
		var lookup = new bs.extendedSearch.LookUp({
			"sort": [
				{ "someField": { "order": "desc" } }
			]
		});

		lookup.removeSort( 'someField' );
		var obj = {};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing all sort works' );
	} );

}( mediaWiki, jQuery ) );
