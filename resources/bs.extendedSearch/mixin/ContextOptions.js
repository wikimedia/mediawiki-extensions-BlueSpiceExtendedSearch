bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

bs.extendedSearch.mixin.ContextOptions = function () {
	this.$contextOptions = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-context' );

	if ( !this.searchTerm || !this.autocomplete.enableSearchContexts ) {
		return;
	}
	this.$contextOptions.append(
		new OO.ui.LabelWidget( { label: mw.msg( 'bs-extendedsearch-autocomplete-context-options-label' ) } ).$element
	);
	this.$contextOptions.addClass( 'has-items' );
	this.options = {};

	const contexts = mw.config.get( 'ESContexts' ) || {};
	for ( const contextKey in contexts ) {
		this.$contextOptions.append(
			this.getContextWidget( contextKey, contexts[ contextKey ] ).$element
		);
	}
};

OO.initClass( bs.extendedSearch.mixin.ContextOptions );

bs.extendedSearch.mixin.ContextOptions.prototype.getContextWidget = function ( key, data ) {
	const widget = new OO.ui.ButtonWidget( {
		framed: false,
		id: 'search-context-' + key,
		label: this.searchTerm,
		icon: 'search',
		classes: [ 'bs-extendedsearch-autocomplete-popup-context-option' ],
		data: {
			contextKey: key,
			contextDefinition: JSON.parse( data.definition || '{}' ),
			text: data.text,
			showCustomPill: data.showCustomPill
		}
	} );
	widget.connect( this, {
		click: function () {
			this.executeContextSearch( widget );
		}
	} );
	this.options[ 'search-context-' + key ] = widget;
	const contextLabel = new OO.ui.LabelWidget( {
		label: new OO.ui.HtmlSnippet( data.text ),
		classes: [ 'bs-extendedsearch-autocomplete-popup-context-option-label' ]
	} );

	widget.$element.append( contextLabel.$element );
	return widget;
};

bs.extendedSearch.mixin.ContextOptions.prototype.executeContextSearch = function ( widget ) {
	this.setLookupContext( widget );
	this.currentIndex = undefined;
	this.searchForm.submit();
};

bs.extendedSearch.mixin.ContextOptions.prototype.setLookupContextFromContextId = function ( id ) {
	if ( !this.options[ id ] ) {
		return;
	}
	this.setLookupContext( this.options[ id ] );
};

bs.extendedSearch.mixin.ContextOptions.prototype.setLookupContext = function ( widget ) {
	const data = widget.getData();
	if ( data.contextKey === 'none' ) {
		if ( this.autocomplete.lookupConfig.context ) {
			delete this.autocomplete.lookupConfig.context;
		}
		return;
	}
	data.contextDefinition = data.contextDefinition || {};
	this.autocomplete.lookupConfig.context = {
		key: data.contextKey,
		definition: data.contextDefinition,
		text: data.text,
		showCustomPill: data.showCustomPill
	};
};
