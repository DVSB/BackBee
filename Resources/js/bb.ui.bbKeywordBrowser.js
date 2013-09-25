/*
 * Keyword selector
 *  
 */

(function($,global){
    $.widget("ui.bbKeywordBrowser",{
    
        options : {
            editMode : true
        },
        webservice : {
            keyword : "local_ws_Keyword"
        },
        
        _templates : {
            main :$("<div class='main'><div class='bb5-windowpane-treewrapper-inner'></div></div>").clone(),
            keywordEditor : "<div class='keywordEditor'><form><fieldset><label for='bb-ui-keywordbrowser-form-title'>Mot clé</label>"
        +"<input type='text' style='width: 100%; margin-bottom: 10px;' value='' name='title' id='bb-ui-keywordbrowser-form-title'>"
        +"</fieldset></form></div>"
        },
        _init : function(){
            var self = this;
            this._popupDialog = bb.PopupManager.init().create("keywordBrowser",{
                title : this.options.title,
                resizable:true
            });
            this._popupDialog.setContent(this._templates.main);
            this.keyWordEditorDialog = bb.PopupManager.init().create("KeywordEditor",{
                title: bb.i18n.__('keywordbrowser.create'),
                content: $(this._templates.keywordEditor).clone()
            });
            
            this.keyWordEditorDialog.addButton({
                text: bb.i18n.__('popupmanager.button.save'),
                click:$.proxy(this.callbacks.saveKeyword,this)
            });
                
            this.keyWordEditorDialog.addButton({
                text: bb.i18n.__('popupmanager.button.cancel'),
                click:function(){
                    self.keyWordEditorDialog.close();
                }
            });
            this._initKeywordTree();
            this._bindEvents();
        },
        
        _bindEvents: function(){
            this.keyWordEditorDialog.on("close",$.proxy(this._resetEditorForm,this)); 
        },
        
        _create : function(){},
    
        callbacks : {
            nodeClickHandler : function(e){
                var context = this.getContext();
                var selectedNode = context.treeview.jstree('get_selected');
                if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                    var nodeType = $(selectedNode).attr('rel');
                    var isAContentType = (nodeType.search("contentType")==-1) ? false : true;
                    var nodeType = (!isAContentType) ? "cat" : "contentType";
                    var nodeId = $(selectedNode).attr("id").replace("node_");  
                    this._trigger("select",e,{
                        node_id:nodeId, 
                        node:context.treeview.jstree('get_selected'), 
                        nodeType:nodeType
                    });
                }
            },
            removeKeyword : function(keywordId){
              var context = this.getContext(); 
                 bb.webserviceManager.getInstance('ws_local_keyword').request("deleteKeyword",{
                     params : { keywordId : keywordId},
                    success: function(response){
                        context.jstree.treeview("reload");
                    },
                    error: function(response){
                        
                    }
                 });
            },
            
            showKeywordEditor :function(mode,nodeId){
                var context = this.getContext();
                var availableModes = ["create","edit"];
                var mode =(typeof mode!="undefined" && $.inArray(mode,availableModes)!=-1) ? mode : "create";
                this.keyWordEditorDialog.setExtra({
                    mode : mode, 
                    nodeId : nodeId
                });
                
                if(mode=="edit"){
                    var selectedNode = context.treeview.jstree("get_text","#node_"+nodeId);
                      this._populateForm(selectedNode);
                }
              
                this.keyWordEditorDialog.show();
            },
          
            saveKeyword : function(){
                var self = this;
                var keyword = this._parseKeywordForm();
                var context = this.getContext();
                var extra = this.keyWordEditorDialog.getExtra();
                var keywordInfos = {
                    keyword:keyword,
                    mode:extra.mode
                }  
                var nodeKey = (keywordInfos.mode=="create") ? "parentUid": "keywordUid"; 
                keywordInfos[nodeKey] = extra.nodeId;
               
                /*show mask*/ 
                $(self.keyWordEditorDialog.dialogUi).mask(bb.i18n.loading);
                bb.webserviceManager.getInstance('ws_local_keyword').request("postKeywordForm",{
                    params :{
                        keywordInfos : keywordInfos
                    },
                    success: function(response) {
                         var treeContainer = context.treeview.jstree("get_container");
                        if (keywordInfos.mode=="create"){
                            context.treeview.jstree('create_node', $(treeContainer).find('#node_' + extra.nodeId), 'first', response.result);
                        } else {
                            context.treeview.jstree('rename_node', $(treeContainer ).find('#node_' + extra.nodeId), response.result.data);
                        }
                        $(self.keyWordEditorDialog.dialogUi).unmask();
                        self.keyWordEditorDialog.close();    
                    },
                    error : function(response){
                        $(self.keyWordEditorDialog.dialogUi).unmask();
                    }
                });
            } 
            
        },
        
        _parseKeywordForm :function(){
            var keyword = this.keyWordEditorDialog.dialogUi.find("#bb-ui-keywordbrowser-form-title") || "";
            if(keyword.length==0) return;
            else{
                return keyword.val();
            }
        },
        
        _populateForm :function(keyword){
            var keyword = (typeof keyword=="string") ? keyword : false;
            if(keyword){
                this.keyWordEditorDialog.dialogUi.find("#bb-ui-keywordbrowser-form-title").val(keyword);
            }
        },
        _resetEditorForm :function(){
            this.keyWordEditorDialog.dialogUi.find("#bb-ui-keywordbrowser-form-title").val("");
        },
        
        _destroyTree: function() {
            var context = this.getContext();
            if (context.treeview) {
                context.treeview.jstree('destroy');
                $(this.element).find('#browser').children().remove();
                this.setContext(context);
            }
        },
        
        _initKeywordTree : function() {
            var myself = this,
            context = this.getContext();
            this._destroyTree();
            var plugins = [ 
            'themes', 'rpc_data', 'ui', 'crrm' ,'types', 'html_data'
            ];
                        
            if (myself.options.editMode) {
                plugins.push('dnd');
                plugins.push('contextmenu');
            } 
            
            /*Création de l'arbre*/
            context.treeview = $(myself._popupDialog.dialogUi).find('.bb5-windowpane-treewrapper-inner').jstree({   
                plugins : plugins,
                rpc_data : { 
                    ajax : {
                        webservice : {
                            instance: bb.webserviceManager.getInstance('ws_local_keyword'),
                            method: 'getKeywordTree'
                        },

                        data : function (n) { 
                            return { 
                                'root_uid' : n.attr ? n.attr('id').replace('node_','') : null
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
                    select_node: true,
                    show_at_node: false,
                    items: {
                        create: null,
                        rename: null,
                        remove: null,
                        edit: null,
                        ccp: null,
                        "bb5-context-menu-add": {
                            label:  bb.i18n.__('popupmanager.button.create'),
                            action: function(obj) {
                                if (obj){
                                    myself.callbacks.showKeywordEditor.call(myself,"create",obj.attr("id").replace("node_",""));  
                                }
                               
                            },
                            separator_before: false,
                            separator_after: false,
                            icon: 'bb5-context-menu-add'
                        },
                        "bb5-context-menu-edit": {
                            label: bb.i18n.__('popupmanager.button.edit'),
                            action: function(obj) {
                                var root = obj.parents('li:first');
                                    
                                if (obj){
                                    /*(root.length > 0) ? root.attr("id").replace("node_","") : null*/
                                    myself.callbacks.showKeywordEditor.call(myself,"edit",obj.attr("id").replace("node_",""));
                                }
                            },
                            separator_before: false,
                            separator_after: false,
                            icon: 'bb5-context-menu-edit'
                        },
                            
                        "bb5-context-menu-remove": {
                            label: bb.i18n.__('popupmanager.button.remove'),
                            icon: 'bb5-context-menu-remove',
                            action: function(obj) {
                                if (obj){
                                    myself.callbacks.removeKeyword.call(myself,obj.attr("id").replace("node_",""));
                                }
                                   

                            }
                        },
                            
                        "bb5-context-menu-cut": {
                            label: bb.i18n.__('popupmanager.button.cut'),
                            icon:"bb5-context-menu-cut",
                            action: function(obj) {
                                if (obj)
                                    myself.cutPage(obj.attr("id").replace("node_",""));
                            }
                        },
                            
                        "bb5-context-menu-paste" : {
                            label: bb.i18n.__('popupmanager.button.paste'),
                            icon:"bb5-context-menu-paste",
                            action: function(obj) {
                                if (obj)
                                    myself.pastePage(obj.attr("id").replace("node_",""));
                            }
                        }   
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
       
        getWidgetApi: function(){
            var self = this;
            var api = { 
                show : function(){
                    self._popupDialog.show();
                },
                
                close : function(){
                    self._popupDialog.close();
                }
            }
            return api;
        },
        
        setContext: function(context) {
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },
        
        getContext: function() {
            return ( (typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {} );
        }
    
    
       
    }); 
})(jQuery,window);


