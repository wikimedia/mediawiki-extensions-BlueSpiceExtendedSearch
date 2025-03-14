/* eslint-disable camelcase */
( function () {
	QUnit.module( 'bs.extendedSearch.Lookup', QUnit.newMwEnvironment() );
	QUnit.dump.maxDepth = 10;

	QUnit.test( 'bs.extendedSearch.Lookup.test*etQueryString', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();
		lookup.setQueryString( '"fried eggs" +(eggplant | potato) -frittata' );

		const obj = {
			query: {
				bool: {
					must: [ {
						query_string: {
							query: '"fried eggs" +(eggplant | potato) -frittata',
							default_operator: 'AND'
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Setting QueryString value by string works' );
		assert.equal( lookup.getQueryString().query, '"fried eggs" +(eggplant | potato) -frittata', 'Getting QueryString works' );

		const q = {
			query: 'Copy Paste',
			default_operator: 'or'
		};
		lookup.setQueryString( q );

		assert.deepEqual( lookup.getQueryDSL().query.bool.must[ 0 ].query_string, q, 'Setting QueryString value by object works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearQueryString', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					must: [ {
						query_string: {
							query: '"fried eggs" +(eggplant | potato) -frittata',
							default_operator: 'and'
						}
					} ]
				}
			}
		} );
		lookup.clearQueryString();

		const obj = {
			query: {
				bool: {
					must: []
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing QueryString value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSingleTermsFilterValue', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();
		lookup.addTermsFilter( 'someField', 'someValue' );
		const obj = {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue' ] }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding single filter value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddMultipleTermsFilterValues', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();
		lookup.addTermsFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		const obj = {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2' ] }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding multiple terms filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testMergeMultipleTermsFilterValues', ( assert ) => {
		QUnit.dump.maxDepth = 10;

		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2' ] }
					} ]
				}
			}
		} );

		lookup.addTermsFilter( 'someField', [ 'someValue2', 'someValue3' ] );
		const obj = {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2', 'someValue3' ] }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Merging multiple terms filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddTermFilter', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();
		lookup.addTermFilter( 'someField', 'someValue' );
		const obj = {
			query: {
				bool: {
					filter: [ {
						term: { someField: 'someValue' }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding term filter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSingleTermsFilterValue', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2' ] }
					} ]
				}
			}
		} );

		lookup.removeFilter( 'someField', 'someValue2' );
		const obj = {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1' ] }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing single terms filter value works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveTermFilter', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					filter: [ {
						term: { someField: 'someValue1' }
					}, {
						term: { someField: 'someValue2' }
					} ]
				}
			}
		} );

		lookup.removeFilter( 'someField', 'someValue1' );
		const obj = {
			query: {
				bool: {
					filter: [ {
						term: { someField: 'someValue2' }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing term filter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveMultiTermsFilterValues', ( assert ) => {
		QUnit.dump.maxDepth = 10;

		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2', 'someValue3' ] }
					} ]
				}
			}
		} );

		lookup.removeTermsFilter( 'someField', [ 'someValue1', 'someValue2' ] );
		const obj = {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue3' ] }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing multiple terms filter values works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearFilter', ( assert ) => {
		QUnit.dump.maxDepth = 10;

		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2', 'someValue3' ] }
					}, {
						term: { someOtherField: 'someValue1' }
					}, {
						terms: { anotherField: [ 'someValue4' ] }
					} ]
				}
			}
		} );

		lookup.clearFilter( 'someField' );
		lookup.clearFilter( 'someOtherField' );
		const obj = {
			query: {
				bool: {
					filter: [ {
						terms: { anotherField: [ 'someValue4' ] }
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing a whole filter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testGetFilters', ( assert ) => {
		QUnit.dump.maxDepth = 10;

		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					filter: [ {
						terms: { someField: [ 'someValue1', 'someValue2', 'someValue3' ] }
					}, {
						term: { someOtherField: 'someValue1' }
					}, {
						terms: { anotherField: [ 'someValue4' ] }
					}, {
						term: { someOtherField: 'someValue2' }
					} ]
				}
			}
		} );

		const obj = {
			terms: {
				someField: [ 'someValue1', 'someValue2', 'someValue3' ],
				anotherField: [ 'someValue4' ]
			},
			term: {
				someOtherField: [ 'someValue1', 'someValue2' ]
			}
		};

		assert.deepEqual( lookup.getFilters(), obj, 'Filters are returned in correct structure' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSort', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addSort( 'someField', bs.extendedSearch.Lookup.SORT_DESC );
		const obj = {
			sort: [
				{ someField: { order: 'desc' } }
			]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSort', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			sort: [
				{ someField: { order: 'desc' } },
				{ someField2: { order: 'asc' } }
			]
		} );

		lookup.removeSort( 'someField2' );
		const obj = {
			sort: [
				{ someField: { order: 'desc' } }
			]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing a sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testClearSort', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			sort: [
				{ someField: { order: 'desc' } }
			]
		} );

		lookup.removeSort( 'someField' );
		const obj = {};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Clearing all sort works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddShouldTerms', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addShouldTerms( 'someField', [ 'value1' ] );

		const obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: [ 'value1' ],
							boost: 1
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding "should temrs" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddMultipleShouldTerms', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addShouldTerms( 'someField', [ 'value1', 'value2' ], 2, false );
		lookup.addShouldTerms( 'someField', [ 'value3' ], 4, false );

		const obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: [ 'value1', 'value2' ],
							boost: 2
						}
					}, {
						terms: {
							someField: [ 'value3' ],
							boost: 4
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding "should temrs" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddShouldMatch', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addShouldMatch( 'someField', 'value1', 4 );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveShouldTerms', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: [ 'value1', 'value2', 'value3' ]
						}
					} ]
				}
			}
		} );

		lookup.removeShouldTerms( 'someField', 'value1' );

		const obj = {
			query: {
				bool: {
					should: [ {
						terms: {
							someField: [ 'value2', 'value3' ]
						}
					} ]
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing "should terms" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveShouldMatch', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.removeShouldMatch( 'someField', 'value1' );

		const obj = {
			query: {
				bool: {
					should: []
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing "should match" clause works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddHighlighter', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addHighlighter( 'someField' );

		const obj = {
			highlight: {
				fields: {
					someField: {
						matched_fields: 'someField',
						pre_tags: [ '<b>' ],
						post_tags: [ '</b>' ]
					}
				}
			}
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a highlighter works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveHighlighter', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.removeHighlighter( 'anotherField' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddSourceField', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addSourceField( [ 'someField', 'anotherField' ] );

		const obj = {
			_source: [ 'someField', 'anotherField' ]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Adding a source field works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveSourceField', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			_source: [ 'someField', 'anotherField' ]
		} );

		lookup.removeSourceField( 'anotherField' );

		const obj = {
			_source: [ 'someField' ]
		};

		assert.deepEqual( lookup.getQueryDSL(), obj, 'Removing a source field works' );

		lookup.removeSourceField( 'someField' );

		assert.deepEqual( lookup.getQueryDSL(), {}, 'Removing whole _source key when all fields are removed works' );
	} );

	QUnit.test( 'bs.extendedSearch.Lookup.testAddBoolMustNotTerms', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addBoolMustNotTerms( 'someField', 'someValue' );

		let obj = {
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

		obj = {
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

		obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveBoolMustNotTerm', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			query: {
				bool: {
					must_not: [
						{ terms: { someField: [ 'someValue', 'someOtherValue' ] } },
						{ terms: { someOtherField: [ 'someValue', 'someOtherValue' ] } }
					]
				}
			}
		} );

		lookup.removeBoolMustNot( 'someField' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddAutocompleteSuggest', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.addAutocompleteSuggest( 'someField', 'someValue' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggest', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.removeAutocompleteSuggest( 'anotherField' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddAutocompleteSuggestContext', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		} );

		lookup.addAutocompleteSuggestContext( 'someField', 'anotherField', 'value2' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggestContext', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.removeAutocompleteSuggestContext( 'someField', 'anotherField' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggestContextValue', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.removeAutocompleteSuggestContextValue( 'someField', 'anotherField', 'value2' );

		let obj = {
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

		obj = {
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

		obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testAddAutocompleteSuggestFuzziness', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
			suggest: {
				someField: {
					prefix: 'someValue',
					completion: {
						field: 'someField'
					}
				}
			}
		} );

		lookup.addAutocompleteSuggestFuzziness( 'someField', 2 );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testRemoveAutocompleteSuggestFuzziness', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.removeAutocompleteSuggestFuzziness( 'someField' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testSetAutocompleteSuggestSize', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {
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
		} );

		lookup.setAutocompleteSuggestSize( 'someField', 9 );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testSetMatchQueryString', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup( {} );

		lookup.setMatchQueryString( 'someField', 'someValue' );

		const obj = {
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

	QUnit.test( 'bs.extendedSearch.Lookup.testSetBoolMatchQueryFuzziness', ( assert ) => {
		const lookup = new bs.extendedSearch.Lookup();

		lookup.setBoolMatchQueryString( 'someField', 'someValue' );
		lookup.setBoolMatchQueryFuzziness( 'someField', 2, { option: 1 } );

		const obj = {
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

}() );
