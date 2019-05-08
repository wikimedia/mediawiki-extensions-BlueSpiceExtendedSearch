ve.ce.TagSearchNode = function VeCeTagSearchNode() {
	// Parent constructor
	ve.ce.TagSearchNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.TagSearchNode, ve.ce.MWInlineExtensionNode );

/* Static properties */

ve.ce.TagSearchNode.static.name = 'tagsearch';

ve.ce.TagSearchNode.static.primaryCommandName = 'tagsearch';

ve.ce.TagSearchNode.static.rendersEmpty = true;

/**
 * @inheritdoc
 */
ve.ce.TagSearchNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.TagSearchNode.super.prototype.onSetup.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-tagsearchnode' );
};

/**
 * @inheritdoc ve.ce.GeneratedContentNode
 */
ve.ce.TagSearchNode.prototype.validateGeneratedContents = function ( $element ) {
	if ( $element.is( 'div' ) && $element.hasClass( 'bs-error' ) ) {
		return false;
	}
	return true;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.TagSearchNode );
