define( [

    // js
    'aloha',
    'aloha/jquery',
    'aloha/plugin',
    'aloha/pluginmanager',
    'aloha/floatingmenu',
    'link/link-plugin',
    // i18n
    'i18n!bbselector/nls/i18n',
    'i18n!aloha/nls/i18n'

    ], function( Aloha,
        jQuery,
        Plugin,
        PluginManager,
        FloatingMenu,
        Links,
        i18n,
        i18nCore ) {
	              
        var BBSelectorPlugin = Plugin.create( 'bbselector', {
            dependencies: [ 'link' ],

            browser: null,

            init: function () {
                var config = {
                    repositoryManager : Aloha.RepositoryManager,	
                    rootPath : Aloha.getPluginUrl( 'browser' ) + '/'
                };
                
                var repositoryButton = new Aloha.ui.Button( {
                    iconClass : 'aloha-button-big aloha-button-tree',
                    size      : 'large',
                    onclick   : function () {
                        var selectorLink = bb.i18n.__('toolbar.editing.linkselectorLabel');
                        var linkContainer = $('<div id="bb5-param-linksselector" class="bb5-selector-wrapper"></div>').clone();
                        
                        var linkSelector = $(linkContainer).bbSelector({
                            popup: true,
                            pageSelector: true,
                            linkSelector: true,
                            mediaSelector: false,
                            contentSelector : false,
                            selectorTitle : selectorLink,
                            resizable: false,
                            site: bb.frontApplication.getSiteUid(),
                            callback: function(item) {
                                linkSelector.bbSelector('close');
                            },
                            beforeWidgetInit:function(){
                                var bbSelector = $(this.element).data('bbSelector');
                                /*for internal link*/
                                bbSelector.onWidgetInit(bbSelector._panel.INTERNAL_LINK, function () { 
                                    var bbPageSelector = $(this).data('bbPageSelector') || false;
                                    if(bbPageSelector){
                                        bbPageSelector.setCallback(function (item) {
                                            Links.hrefField.setAttribute('title', item.title);
                                            Links.hrefField.setAttribute('href', item.value);
                                            Links.hrefField.setAttribute('target', item.target);
                                            linkSelector.bbSelector('close');
                                        }); 
                                    }
                                });
                                /*for External link*/
                                bbSelector.onWidgetInit(bbSelector._panel.EXTERNAL_LINK, function () {
                                    var bbLinkSelector = $(this).data('bbLinkSelector');
                                    bbLinkSelector.setCallback(function (item) {
                                        Links.hrefField.setAttribute('title', item.title);
                                        Links.hrefField.setAttribute('href', item.value);
                                        Links.hrefField.setAttribute('target', item.target);
                                        linkSelector.bbSelector('close');
                                    });
                                });
                            }
                        });
                        
                        linkSelector.bbSelector('open');
                    },
                    tooltip   : i18n.t( 'button.addlink.tooltip' ),
                    toggle    : false
                } );
			
                FloatingMenu.addButton(
                    'Aloha.continuoustext',
                    repositoryButton,
                    i18n.t( 'floatingmenu.tab.link' ),
                    1
                    );
            
                repositoryButton.hide();
                            
                Aloha.bind( 'aloha-link-selected', function ( event, rangeObject ) {
                    repositoryButton.show();
                    FloatingMenu.doLayout();
                });
                Aloha.bind( 'aloha-link-unselected', function ( event, rangeObject ) {
                    repositoryButton.hide();
                    FloatingMenu.doLayout();
                });
        
            }
        } );

        return BBSelectorPlugin;
    });
