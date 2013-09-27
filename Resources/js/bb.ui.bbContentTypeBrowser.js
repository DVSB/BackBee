(function($){
    $.widget('ui.bbContentTypeBrowser',{
        
        options: {
            popup: {
                width: 200,
                height: null,
                position: null  
            },
            site: [],
            editMode: false,
            dialogClass : 'bb5-ui bb5-dialog-wrapper'
        },
        i18n: {
            utils : "Outils",
            loading: "Chargement...",
            selelectorName : "Types de contenu"
        },
        _context: {
            site: null,
            selected: null,
            treeview: null,
            layouts: null
        },
        
        
        _create : function(){
            var context = {
                site: null,
                treeview: null,
                layouts: null  
            };   
            this.setContext(context);
        },
        
        _destroyTree: function() {
            var context = this.getContext();
            if (context.treeview) {
                context.treeview.jstree('destroy');
                (this.element).find(".bb5-windowpane-treewrapper-inner").html("");
                this.setContext(context);
            }
        },
        
        _init :function(){ 
            var myself = this;
            if(this.options.popup){
                $(this.element).dialog({
                    title : bb.i18n.__('contenttypebrowser.title'),
                    dialogClass: 'bb5-ui bb5-dialog-wrapper bb5-dialog-treeviewer',
                    width: this.options.popup.width,
                    minWidth: 200,
                    minHeight: 147,
                    autoOpen : false,
                    closeOnEscape: false,
                    zIndex: 500001,
                    create: function(event, ui) {
                        var context = myself.getContext();
                        $(event.target).parent().css('position', 'fixed');
                    }
                    
                });
                
                  // fixme save dialog Position
                  $(this.element).bind("dialogresizestop",function(event,ui){
                    var position = [(Math.floor(ui.position.left) - $(window).scrollLeft()),
                    (Math.floor(ui.position.top) - $(window).scrollTop())];
                    $(event.target).parent().css('position', 'fixed');
                    $(myself.element).dialog('option','position',position);
                });
                
                if (this.options.popup.height)
                    $(this.element).dialog('option', 'height', this.options.popup.height);
                
                if (this.options.popup.position)
                    $(this.element).dialog('option', 'position', this.options.popup.position);
                
                $(this.element).dialog('open');
            }else{
                $(this.element).show();
            }
            this._initContentTree();
            this._bindEvents();   
        },
        _bindEvents : $.noop,
        callbacks : {
            nodeClickHandler : function(e){
                var context = this.getContext();
                var selectedNode = context.treeview.jstree('get_selected');
                if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                    var nodeType = $(selectedNode).attr('rel');
                    var isAContentType = (nodeType.search("contentType")==-1) ? false : true;
                    var nodeType = (!isAContentType)?"cat":"contentType";
                    var nodeId = $(selectedNode).attr("id").replace("node_");  
                    this._trigger("select",e,{node_id:nodeId, node:context.treeview.jstree('get_selected'), nodeType:nodeType});
                }
            },
            createHandler :function(){
               
            }
        },
        
        _initContentTree: function() {
            var myself = this,
            context = this.getContext();
            var filters = {
                maxEntry:999, 
                accept:'all'
            }; 
            this._destroyTree();
            var plugins = [ 
            'themes', 'rpc_data', 'ui', 'crrm' ,'types', 'html_data'
            ];
                        
            if (myself.options.editMode) {
                plugins.push('dnd');
                plugins.push('contextmenu');
            } 
            
            /*Création de l'arbre*/
            context.treeview = $(myself.element).find('.bb5-windowpane-treewrapper-inner').jstree({   
                plugins : plugins,
                rpc_data : { 
                    ajax : {
                        webservice : {
                            instance: bb.webserviceManager.getInstance('ws_local_contentBlock'),
                            method: 'getBBContentBrowserTree'
                        },

                        data : function (n) { 
                            return { 
                                //'root_uid' : n.attr ? n.attr('id').replace('node_','') : null
                                filters : filters //accepted type
                            }; 
                        }
                    }
                },
                    
                types: {
                    valid_children : ['root'],
                    types: {
                        root: {
                            valid_children : [ 'default', 'folder' ],
                            start_drag: false,
                            move_node: false,
                            delete_node: false,
                            remove: false
                        }
                    }
                },
                    
                themes : {
                    theme: 'bb5',
                    dots: false
                },

                ui : {
                    select_limit: 1
                },
                    
                core : {
                    strings: {
                        loading: bb.i18n.__('loading')
                    /*new_node: myself.i18n.new_node,
                        multiple_selection: myself.i18n.multiple_selection*/
                    }
                },
                    
                contextmenu: {
                    select_node: false,
                    show_at_node: true,
                    items: {
                        create: null,
                        rename: null,
                        remove: null,
                        edit: null,
                        ccp: null
                    }
                }
            }).bind('loaded.jstree', function (e, data) {
                if ($(e.target).find('ul > li:first').length > 0) {
                    data.inst.select_node('ul > li:first');
                }
                myself._trigger('ready');
                        
            }).bind('click.jstree',$.proxy(myself.callbacks.nodeClickHandler,myself)).bind("create.jstree", $.proxy(myself.callbacks.createHandler,myself));
            this.setContext(context);
        },
        
        setContext: function(context) {
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },
        
        getContext: function() {
            return ( (typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {} );
        },
        
        destroy: function(){
            var context = this.getContext();
            this._destroyTree();
            context.site = null;
            
            if (this.options.popup) {
                $(this.element).dialog('destroy');
            } else {
                $(this.element).hide();
            }
            $.Widget.prototype.destroy.apply(this, arguments);
            this.setContext(context);
        },
        
        close : function(){
            $(this.element).dialog("close");
        },
        
        open:function(){
            $(this.element).dialog("open");
        }
        
        
        
        
        
    });
})(jQuery);