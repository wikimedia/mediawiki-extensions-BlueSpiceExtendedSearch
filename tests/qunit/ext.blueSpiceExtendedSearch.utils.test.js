( function () {
	QUnit.module( 'ext.blueSpiceExtendedSearch.utils', QUnit.newMwEnvironment() );

	QUnit.test( 'ext.blueSpiceExtendedSearch.utils.fragmentFunctions', ( assert ) => {
		// QUnit.expect( 1 );
		const obj = {
			a: 1000,
			b: [
				'A',
				'B',
				{
					c: 23
				}
			]
		};

		bs.extendedSearch.utils.setFragment( obj );
		const retrievedObj = bs.extendedSearch.utils.getFragment();

		assert.deepEqual( retrievedObj, obj, '#Fragment set/get works' );
	} );
}() );
