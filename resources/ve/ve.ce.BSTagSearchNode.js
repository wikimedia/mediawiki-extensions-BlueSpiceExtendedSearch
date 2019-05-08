ve.ce.BSTagSearchNode = function VeCeBSTagSearchNode() {
	// Parent constructor
	ve.ce.BSTagSearchNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.BSTagSearchNode, ve.ce.TagSearchNode );

/* Static properties */

ve.ce.BSTagSearchNode.static.name = 'bstagsearch';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.BSTagSearchNode );
