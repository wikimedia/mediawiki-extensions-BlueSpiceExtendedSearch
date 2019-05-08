ve.ui.TagSearchInspectorTool = function VeUiTagSearchInspectorTool( toolGroup, config ) {
	ve.ui.TagSearchInspectorTool.super.call( this, toolGroup, config );
};
OO.inheritClass( ve.ui.TagSearchInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.TagSearchInspectorTool.static.name = 'tagSearchTool';
ve.ui.TagSearchInspectorTool.static.group = 'none';
ve.ui.TagSearchInspectorTool.static.autoAddToCatchall = false;
ve.ui.TagSearchInspectorTool.static.icon = 'markup';
ve.ui.TagSearchInspectorTool.static.title = OO.ui.deferMsg(
	'bs-extendedsearch-tagsearch-ve-tagsearch-title'
);
ve.ui.TagSearchInspectorTool.static.modelClasses = [ ve.dm.BSTagSearchNode, ve.dm.TagSearchNode ];
ve.ui.TagSearchInspectorTool.static.commandName = 'tagSearchCommand';
ve.ui.toolFactory.register( ve.ui.TagSearchInspectorTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'tagSearchCommand', 'window', 'open',
		{ args: [ 'tagSearchInspector' ], supportedSelections: [ 'linear' ] }
	)
);

