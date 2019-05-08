ve.dm.TagSearchNode = function VeDmTagSearchNode() {
	// Parent constructor
	ve.dm.TagSearchNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.TagSearchNode, ve.dm.MWInlineExtensionNode );

/* Static members */

ve.dm.TagSearchNode.static.name = 'tagsearch';

ve.dm.TagSearchNode.static.tagName = 'tagsearch';

// Name of the parser tag
ve.dm.TagSearchNode.static.extensionName = 'tagsearch';

// This tag renders without content
ve.dm.TagSearchNode.static.childNodeTypes = [];
ve.dm.TagSearchNode.static.isContent = true;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TagSearchNode );
