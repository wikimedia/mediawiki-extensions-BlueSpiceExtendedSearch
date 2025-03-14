bs.util.registerNamespace( 'bs.extendedSearch.vec.util.tag' );
bs.extendedSearch.vec.util.tag.TagSearchDefinition = function () {
	bs.extendedSearch.vec.util.tag.TagSearchDefinition.super.call( this );
};

OO.inheritClass( bs.extendedSearch.vec.util.tag.TagSearchDefinition, bs.vec.util.tag.Definition );

bs.extendedSearch.vec.util.tag.TagSearchDefinition.prototype.getCfg = function () {
	const cfg = bs.extendedSearch.vec.util.tag.TagSearchDefinition.parent.prototype.getCfg.call( this );
	return $.extend( cfg, { // eslint-disable-line no-jquery/no-extend
		classname: 'TagSearch',
		name: 'tagsearch',
		tagname: 'bs:tagsearch',
		descriptionMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-desc',
		menuItemMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-title',
		toolGroup: 'object',
		attributes: [ {
			name: 'placeholder',
			labelMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder',
			helpMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder-help',
			type: 'text',
			default: '',
			placeholderMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder-placeholder'
		}, {
			name: 'type',
			labelMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-type',
			helpMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-type-help',
			type: 'custom',
			default: 'wikipage',
			widgetClass: bs.extendedSearch.vec.ui.SearchTypeInputWidget
		}, {
			name: 'namespace',
			labelMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-ns',
			helpMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-ns-help',
			type: 'text',
			default: '',
			placeholderMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-ns-placeholder'
		}, {
			name: 'category',
			labelMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-cat',
			helpMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-cat-help',
			type: 'text',
			default: '',
			placeholderMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-cat-placeholder'
		}, {
			name: 'operator',
			labelMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-operator',
			helpMsg: 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-operator-help',
			type: 'dropdown',
			options: [ {
				data: 'AND',
				label: 'AND'
			}, {
				data: 'OR',
				label: 'OR'
			} ],
			default: 'false'
		} ]
	} );
};

bs.vec.registerTagDefinition(
	new bs.extendedSearch.vec.util.tag.TagSearchDefinition()
);
