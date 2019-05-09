ve.ui.TagSearchInspector = function VeUiTagSearchInspector( config ) {
	// Parent constructor
	ve.ui.TagSearchInspector.super.call( this, ve.extendObject( { padded: true }, config ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.TagSearchInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */

ve.ui.TagSearchInspector.static.name = 'tagSearchInspector';

ve.ui.TagSearchInspector.static.title = OO.ui.deferMsg( 'bs-extendedsearch-tagsearch-ve-tagsearch-title' );

ve.ui.TagSearchInspector.static.modelClasses = [ ve.dm.BSTagSearchNode, ve.dm.TagSearchNode ];

ve.ui.TagSearchInspector.static.dir = 'ltr';

//This tag does not have any content
ve.ui.TagSearchInspector.static.allowedEmpty = true;
ve.ui.TagSearchInspector.static.selfCloseEmptyBody = true;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.TagSearchInspector.prototype.initialize = function () {

	// Parent method
	ve.ui.TagSearchInspector.super.prototype.initialize.call( this );
	//There must be a better way to disable tag body input
	this.input.$element.remove();

	// Index layout
	this.indexLayout = new OO.ui.PanelLayout( {
		scrollable: false,
		expanded: false,
		padded: true
	} );

	this.createFields();

	this.setLayouts();

	// Initialization
	this.$content.addClass( 've-ui-tagsearch-inspector-content' );

	this.indexLayout.$element.append(
		this.nsLayout.$element,
		this.catLayout.$element,
		this.placeholderLayout.$element,
		this.operatorLayout.$element,
		this.typeLayout.$element,
		this.generatedContentsError.$element
	);
	this.form.$element.append(
		this.indexLayout.$element
	);
};

ve.ui.TagSearchInspector.prototype.createFields = function() {
	this.nsInput = new OO.ui.TextInputWidget();
	this.catInput = new OO.ui.TextInputWidget();
	this.placeholderInput = new OO.ui.TextInputWidget();
	//Should these options be translated?
	this.operatorInput = new OO.ui.DropdownInputWidget( {
		options: [
			{
				data: '',
				label: ''
			},
			{
				data: 'AND',
				label: 'AND'
			},
			{
				data: 'OR',
				label: 'OR'
			}
		]
	} );
	this.typeInput = new OO.ui.TextInputWidget();
}

ve.ui.TagSearchInspector.prototype.setLayouts = function() {
	this.nsLayout = new OO.ui.FieldLayout( this.nsInput, {
		align: 'left',
		label: ve.msg( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-ns' ),
		help: mw.message( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-ns-help' ).plain()
	} );
	this.catLayout = new OO.ui.FieldLayout( this.catInput, {
		align: 'left',
		label: ve.msg( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-cat' ),
		help: mw.message( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-cat-help' ).plain()
	} );
	this.placeholderLayout = new OO.ui.FieldLayout( this.placeholderInput, {
		align: 'left',
		label: ve.msg( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder' )
	} );
	this.operatorLayout = new OO.ui.FieldLayout( this.operatorInput, {
		align: 'left',
		label: ve.msg( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-operator' ),
		help: mw.message( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-operator-help' ).text()
	} );
	this.typeLayout = new OO.ui.FieldLayout( this.typeInput, {
		align: 'left',
		label: ve.msg( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-type' ),
		help: mw.message( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-type-help' ).plain()
	} );
}

/**
 * @inheritdoc
 */
ve.ui.TagSearchInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.TagSearchInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var attributes = this.selectedNode.getAttribute( 'mw' ).attrs;

			this.nsInput.setValue( attributes.ns || '' );
			this.catInput.setValue( attributes.cat || '' );
			this.placeholderInput.setValue( attributes.placeholder || '' );

			if( attributes.operator ) {
				this.operatorInput.setValue(attributes.operator);
			}

			this.nsInput.on( 'change', this.onChangeHandler );
			this.catInput.on( 'change', this.onChangeHandler );
			this.placeholderInput.on( 'change', this.onChangeHandler );
			this.operatorInput.on( 'change', this.onChangeHandler );

			this.actions.setAbilities( { done: true } );
		}, this );
};

ve.ui.TagSearchInspector.prototype.updateMwData = function ( mwData ) {
	// Parent method
	ve.ui.TagSearchInspector.super.prototype.updateMwData.call( this, mwData );

	// Get data from inspector
	if( this.nsInput.getValue() !== '' ) {
		mwData.attrs.ns = this.nsInput.getValue();
	} else {
		delete( mwData.attrs.ns );
	}

	if( this.catInput.getValue() !== '' ) {
		mwData.attrs.cat = this.catInput.getValue();
	} else {
		delete( mwData.attrs.cat );
	}

	if( this.placeholderInput.getValue() !== '' ) {
		mwData.attrs.placeholder = this.placeholderInput.getValue();
	} else {
		delete( mwData.attrs.placeholder );
	}

	if( this.operatorInput.getValue() !== '' ) {
		mwData.attrs.operator = this.operatorInput.getValue();
	} else {
		delete( mwData.attrs.operator );
	}
};

/**
 * @inheritdoc
 */
ve.ui.TagSearchInspector.prototype.formatGeneratedContentsError = function ( $element ) {
	return $element.text().trim();
};

/**
 * Append the error to the current tab panel.
 */
ve.ui.TagSearchInspector.prototype.onTabPanelSet = function () {
	this.indexLayout.getCurrentTabPanel().$element.append( this.generatedContentsError.$element );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.TagSearchInspector );
