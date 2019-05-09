ve.dm.BSTagSearchNode = function VeDmBSTagSearchNode() {
	// Parent constructor
	ve.dm.BSTagSearchNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.BSTagSearchNode, ve.dm.TagSearchNode );

/* Static members */

ve.dm.BSTagSearchNode.static.name = 'bstagsearch';

ve.dm.BSTagSearchNode.static.tagName = 'bs:tagsearch';

// Name of the parser tag
ve.dm.BSTagSearchNode.static.extensionName = 'bs:tagsearch';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.BSTagSearchNode );
