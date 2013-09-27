(function($){
    $.widget('ui.bbContentSelector',{
        options : {
            showSearchEngine : false,
            contentItemClass:".bb5-content-item",
            pagerContainerClass : ".bb5-windowpane-main-toobar-container",//.bb5-windowpane-main-toolbar-screener",
            searchEngineContainerClass:".bb5-windowpane-main-toolbar-screener",
            btnClass :".bb5-button",
            viewMode: "grid",
            tabsContainerClass :".bb5-contentdialog-tab-wrapper",
            pageTreeContaineClass : ".bb5-windowpane-pagetreewrapper-inner",
            searchToggleBtnClass : ".bb5-windowpane-main-toolbar-screener-toggle a",
            listContainerClass: ".bb5-listContainer",
            treewrapperClass :".bb5-windowpane-treewrapper",
            cszParams: { 
                wrapperClass :".bb5-basket-wrapper-inner",
                containerClass:".bb5-basket-wrapper-innest",
                captionWrapper :".bb5-ico-basket", //bb5-basket-toggle
                nbItemClass :".bb5-selectedItemsNbs",
                listClass :".bb5-content-selection",
                confirmBtnClass : ".bb5-button-confirmSelections",
                removeItemClass :".bb5-button-removeItem",
                closeListClass :".bb5-button-closeList"
            }
        },
        
        viewInfos : {
            "btnClass":".bb5-viewMode",
            "list":".bb5-ico-sortaslist",
            "grid":".bb5-ico-sortasgrid"
        },
        data :null, //data cache
        _context : {
            mainTemplate : null,  
            categoryTree : null,
            filterParameters : null,
            selectedContent :null
        },
        
        i18n: {
            contents: 'élément(s)',
            all: "Tous",
            state_offline :"Hors ligne",
            state_online : "En ligne",
            state_hidden : "Caché",
            state_deleted :"Effacé"
        },
    
        _templates :{
            mainPanel : $("#bb5-ui-bbcontentselector-panel-tpl").clone(),
            viewgrid : null,
            viewlist: null
        },
        
        _mask : function(action){
            var action = action || "none";
            var availableActions = ["show","hide"];
            if( $.inArray(action,availableActions) == -1 ) return;
            if(action=="show") $(this.element).mask(bb.i18n.loading);
            if(action=="hide") $(this.element).unmask();     
        },
        
        
        _initTemplates : function(item){
            /*List item template*/
            var itemTemplate = '<li data-uid="${uid}" class="bb5-content-item">'
            +'<p><a title="${completeTitle}" href="javascript:;"><img alt="${type}" src="'+bb.baseurl+'ressources/img/contents/${ico}.png"></a></p>'
            +'<p><a title="${completeTitle}" href="javascript:;">${title}</a></p>'
            +"<p>Date de création: <strong>${created}</strong></p>"
            +'<p><button class="bb5-button bb5-ico-add addClose">Ajouter et fermer</button><button class="bb5-button bb5-ico-save addToList">Ajouter à ma sélection</button></p>'
            +'</li>';
            this._templates.contentItemTemplate = $.template(itemTemplate);
            
            /*List selected item*/
            var selectionTpl = "<li class='bb5-content-list-item' data-uid='${uid}'><span><strong>${type}</strong><span><p>${completeTitle}<p><p><button class='bb5-button bb5-ico-del bb5-button-removeItem' href='javascript:;'>Effacer</button></li>";  
            this._templates.selectedItemTemplate = $.template(selectionTpl);
        },
        
        _create : function(){
            var contentId = bb.Utils.generateId('contentSelector');
            $(this._templates.mainPanel).attr("id",contentId); 
            this.element.html($(this._templates.mainPanel).show()); 
            this._templates.treeview = null;
            this._templates.viewgrid = $(this.element).find(".bb5-list-media-is-grid");
            this._templates.viewlist = $(this.element).find(".bb5-list-media-is-list");
            this.selectedListCtn = $(this.element).find(this.options.cszParams.wrapperClass);
            $(this.element).find(this.options.searchEngineContainerClass).hide(); //hide search engine
            this._bindEvents();
        },
        
        _init : function(){   
            var self = this
            var context = {};
            context.selected = null;
            context.selectedPageId = null; 
            this.selectModeView(this.options.viewMode);
            context.selectedContent = [];
            this.searchCriteria = null;
            this.selectedContent = new bb.SmartList({
                keyId:"uid",
                onChange  : $.proxy(this._onSelectedContentChange,this),
                onDestroy : $.proxy(this._onDestroyScContainer,this),
                onDelete : $.proxy(this._onSelectedContentChange,this)
            });
            
            this.typeFilters = {
                contentType:[

                {
                    label:this.i18n.all, 
                    value:"all"
                },

                {
                    label:this.i18n.state_offline, 
                    value:0
                },

                {
                    label:this.i18n.state_online, 
                    value:1
                },

                {
                    label:this.i18n.state_hidden, 
                    value:2
                },

                {
                    label:this.i18n.state_deleted, 
                    value:4
                }
                ],
                pages: []
            };
            
            var searchWebservice = bb.webserviceManager.getInstance('ws_local_contentBlock');
            this.searchEngine = $(this.element).find(this.options.searchEngineContainerClass).eq(0).bbSearchEngine({
                onSearch : function(e,criteria){
                    var context = self.getContext();
                    if(("typeField" in criteria) && criteria.typeField){
                        if(context.selectedPageId) criteria.selectedPageId = context.selectedPageId;
                        self._showContent(criteria);
                    } 
                }
            }).bbSearchEngine("getWidgetApi");
            
            /*container list useful for resize*/
            this.listContainer = $(this.element).find(this.options.listContainerClass).eq(0);
            this.treeWrappers = $(this.element).find(this.options.treewrapperClass);  
            
            /*set search engine type*/
            this.searchEngine.setFilterTypes(this.typeFilters.contentType);
            this.setContext(context);
            this._initTemplates();
            this._initSelectContentTabs();
            $(this.element).disableSelection();
            
          
            /*$(this.element).find('.bb5-windowpane-tree').resizable({
                handles: 'e, w',
                maxWidth: 400,
                minWidth: 165,
                helper:"bb5-content-resizable-helper",
                resize: function(event, ui) {
                    $(self.element).find('.bb5-windowpane-wrapper').css('padding-left', $(self.element).find('.bb5-windowpane-tree').width() + 'px');
                } ,
                stop: function(event,ui){
                    $(self.element).find('.bb5-windowpane-wrapper').css('padding-left', $(self.element).find('.bb5-windowpane-tree').width() + 'px');
                    var newWidth = $(this).width();
                    $(this).attr("style","");
                    $(this).width(newWidth);
                }
            });*/
            this.getParent().on("open",function(){
                $(self.element).find(".bb5-windowpane-wrapper").layout({ 
                    applyDemoStyles: true,
                    defaults: {
                        closable : false
                    },
                    west:{
                        size: 264
                    },
                    center__paneSelector:".bb5-windowpane-main",
                    west__paneSelector:".bb5-windowpane-tree"
                });
            });
        },
        
        
        
        _initSelectContentTabs : function(){
            var tabContainer = this.element.find(this.options.tabsContainerClass).eq(0);
            this.treeTabs = new bb.LpTabs({
                mainContainer:tabContainer
            });
            this.treeTabs.onShow = $.proxy(this._initPageTree,this);
        },
        
        _initPageTree : function(){
            var self = this;
            if(!this.pageTree){
                var  pageTree = $(this.element).find(this.options.pageTreeContaineClass).eq(0);
                this.pageTree = $(pageTree).bbPageBrowser({
                    popup : false,
                    editMode : false,
                    site : bb.frontApplication.getSiteUid(),
                    // breadcrumb : bb.frontApplication.getBreadcrumbIds(),
                    enableNavigation : false,
                    select:function(e,nodeInfos){
                        /*do nothing on root*/
                        var context = self.getContext();
                        context.selectedPageId = nodeInfos.node_id;
                        self.searchEngine.setSelectedPage(context.selectedPageId);
                        var criteria = self.searchEngine.getSearchCriteria();
                        if(context.selected){
                            criteria.typeField = context.selected;
                            self._showContent(criteria);
                        }                      
                        self.setContext(context);
                    }
                });
                
            }
        },
          
        selectModeView :function(viewMode){
            var viewMode = viewMode || "none";
            var availableMode = ["list","grid"];
            if($.inArray(viewMode,availableMode)==-1){
                this.options.viewMode = "list"; 
            }else{
                this.options.viewMode = viewMode;
            }
            $(this.element).find(this.viewInfos.btnClass).removeClass("bb5-button-selected");
            var btnToSelect = $(this.element).find(this.viewInfos[this.options.viewMode]);
            $(btnToSelect).addClass("bb5-button-selected");
        },
        
        setFilterParameters : function(parameters){
            /*initTree here*/
            var params = parameters || {};
            var context = this.getContext();
            context.filterParameters = parameters;
            this.selectedContent.setMaxEntry(parseInt(context.filterParameters.maxentry));
            this.setContext(context);
            this._initContentsTree();
        }, 
        
        updateListSize: function(delta){
            var delta = (typeof delta=="number") ? delta : 0;
            
            /*tree wrappers*/
            $.each(this.treeWrappers,function(i,treeWrapper){
                var oldHeight = $(treeWrapper).height();
                $(treeWrapper).height(oldHeight+delta);
            });
             
            /*update list's height*/
            var prevListHeight = $(this.listContainer).height(); 
            var listHeight = prevListHeight + delta;
            $(this.listContainer).height(listHeight);
            
            /*update mainPanel's height*/
            var prevMainPaneHeight = $(this.element).height();
            var mainPanelHeight = prevMainPaneHeight + delta;
            $(this.element).height(mainPanelHeight);
            return;
        },
       
        /*onDestroy*/
        _onDestroyScContainer:function(){
            var context = this.getContext();
            this.selectedListCtn.find(this.options.cszParams.listClass).html("");
            this.selectedListCtn.find(this.options.cszParams.nbItemClass).html(0);//nbElements
            context.selectedContent = [];
            this.setContext(context); 
        },
        
        /*onChange*/
        _onSelectedContentChange : function(data,listName,newContent){
            var context = this.getContext();
            var nbSelections = this.selectedContent.getSize();
            this.selectedListCtn.find(this.options.cszParams.listClass).html("");
            var self = this;
            $.each(data,function(key,item){
                var contentData = $(item).data("content") || {};
                var content = self._templates.selectedItemTemplate.apply(contentData);
                self.selectedListCtn.find(self.options.cszParams.listClass).append(content);
            });
            self.selectedListCtn.find(self.options.cszParams.nbItemClass).html(nbSelections);
            context.selectedContent = self.selectedContent.toArray(true);
            this.setContext(context);  
        },
        
        close : function(){
            this.reset();
            this._trigger("close");
        } ,
        
        reset : function(){
            var context = this.getContext();
            context.filterParameters = null;
            context.selectedContent = [];
            context.mode = "hide";
            this.setContext(context);
            //$(this.element).find(".bb5-listContainer").css("height","305px"); //useful for resize
            $(this.element).find(".bb5-listContainer").html("");
            $(this.element).find(".bb5-windowpane-main-toolbar-caption").html("");
            $(this.element).find(".bb5-windowpane-main-toolbar-nav").html("");
            $(this.element).find(".maxPerPageSelector").html("");
            $(this.element).find(this.options.searchEngineContainerClass).hide();
            $(this.element).find(".bb5-selectedItems-container").hide();
            /*cancel request*/
            
            this.selectedContent.reset();
        },   
        
        _bindEvents : function(){
            var self = this;
            $(this.element).delegate(this.options.contentItemClass,"click",$.proxy(this.callbacks["contentClickHandler"],this));
            $(this.element).delegate(this.options.btnClass,'click',$.proxy(this.callbacks["btnActionHandler"],this));
            $(this.element).delegate(this.options.cszParams.captionWrapper,'click',$.proxy(this.callbacks["showBasket"],this));
            $(this.element).delegate(this.options.cszParams.confirmBtnClass,'click',$.proxy(this.callbacks["confirmSelectionHandler"],this));
            $(this.element).delegate(this.options.cszParams.closeListClass,'click',function(e){
                self.selectedListCtn.find(self.options.cszParams.containerClass).hide();
            });
            $(this.element).delegate(this.options.cszParams.removeItemClass,"click",$.proxy(this.callbacks["removeItemHandler"],this)); 
            $(this.element).delegate(this.options.searchToggleBtnClass,"click",$.proxy(this.callbacks["toggleSearchEngine"],this));
        },
        
        _notifySelection:  function(){
            var context = this.getContext();
            this._trigger("selectcontent",0,{
                receiver:context.filterParameters.receiver, 
                selectedContent:context.selectedContent
            }); 
        },
        
        fixContainersHeight : function(){
            var context = this.getContext();
            if(context.sizeIsFixed!="undefined" && context.sizeIsFixed) return false;
            var SEARCH_ENGINE = 45;
            $(this.listContainer).height($(this.listContainer).height() + SEARCH_ENGINE);
            context.sizeIsFixed = true;
            this.setContext(context);
        },
        
        _updateSelectionList : function(selectedContent){
            var nbSelection = this.selectedContent.getSize();
            this.selectedListCtn.find(".bb5-selectedItemsNbs").text(nbSelection);  
        },
        
        callbacks : {
            
            toggleSearchEngine : function(e){
                var target = $(e.currentTarget);
                var mainContainer = $(this.element).find(".bb5-windowpane-wrapper").get(0);
                $(target).toggleClass('opened');
                $(this.element).find(this.options.searchEngineContainerClass).toggle();
                var delta = ($(target).hasClass("opened")) ? -45 : 45; //toggle filter's size
                //$(mainContainer).height($(mainContainer).height() + delta);
                $(this.listContainer).height( $(this.listContainer).height() + delta);   
            },
            
            showBasket : function(e){
                if(!this.selectedContent.getSize()) return false;
                this.callbacks.showSelectedhandler.call(this);
            },
            
            removeItemHandler : function(e){
                var itemId = $(e.currentTarget).parents(".bb5-content-list-item").eq(0).attr("data-uid");
                this.selectedContent.deleteItemById(itemId);
                var nbItems = this.selectedContent.getSize();
                if(!nbItems){
                    this.callbacks.showSelectedhandler.call(this);
                }
            },
            
            confirmSelectionHandler:function(e){
                var nbContent = this.selectedContent.getSize();
                if(!nbContent) return false;
                this.callbacks.showSelectedhandler.call(this);
                this._notifySelection();
                this.close();
            },
            
            showSelectedhandler : function(e){
                /*fake toggle*/
                if(this.selectedListCtn.find(this.options.cszParams.containerClass).eq(0).hasClass("hidden")){
                    this.selectedListCtn.find(this.options.cszParams.containerClass).show().removeClass("hidden");
                }
                else{
                    this.selectedListCtn.find(this.options.cszParams.containerClass).hide().addClass("hidden");
                }
                
            },
            
            btnActionHandler : function(e){
                e.stopPropagation();
                var context = this.getContext();
                var item = $(e.currentTarget).parents(this.options.contentItemClass);
                var itemUid = $(item).attr("data-uid");
                
                if($(e.currentTarget).hasClass("addClose")){
                    this.selectedContent.set(itemUid,item);
                    this.setContext(context);
                    this._notifySelection();
                    this.close();
                }
               
                if($(e.currentTarget).hasClass("addToList")){
                    this.selectedContent.set(itemUid,$(item).clone(true));
                }
               
                if($(e.currentTarget).hasClass("bb5-ico-sortaslist")){
                    this._mask("show");
                    this.selectModeView("list");
                    this._populateView(this.data);
                }
                
                if($(e.currentTarget).hasClass("bb5-ico-sortasgrid")){
                    this._mask("show");
                    this.selectModeView("grid");
                    this._populateView(this.data);
                }
            },
            
            contentClickHandler : function(e){
                e.stopPropagation();
                var context = this.getContext();
                var item = $(e.currentTarget);
                var itemUid = $(item).attr("data-uid");
                this.selectedContent.set(itemUid,item);
                this.setContext(context);
                this._notifySelection();
                this.close();
                return false;
            },
            
            nodeClickHandler :function (e) {
                var context = this.getContext();
                if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                    /*do nothing for root*/
                    var isRoot = ($(context.treeview.jstree('get_selected')).attr('rel').toUpperCase() == "ROOT") ? true : false;
                    if(isRoot) return; //do nothing for root                    
                    var tree = $.jstree._reference(context.treeview);
                    var selectedChildren = tree._get_children(context.treeview.jstree('get_selected'));
                    this._selectContentNode($(context.treeview.jstree('get_selected')).attr('rel'));
                    this._updateSearchEngineTypeFilters(selectedChildren);
                    var searchCriteria = this.searchEngine.getSearchCriteria();
                    this._showContent(searchCriteria);
                }
            },
            
            createHandler : function(e, data) {
                bb.webserviceManager.getInstance('ws_local_mediafolder').request('insertBBBrowserTree', {  
                    params: {
                        title: data.rslt.name,
                        root_uid: data.rslt.parent.attr("id").replace("node_","")
                    },
                    success: function(result) {
                        if (!result.result)
                            $.jstree.rollback(data.rlbk);
                        else {
                            $(data.rslt.obj).attr('id', result.result.attr.id);
                            $(data.rslt.obj).attr('rel', result.result.attr.rel);
                        }
                    }
                });  
            }
        },
                
        destroy : function(){
            $.Widget.prototype.destroy.call(this);
        }, 
    
        /* proxies */
        publicApi : {},
        
        getContext: function() {
            return ( (typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {} );
        },
        
        setContext: function(context) {
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },
       
        _destroyTree: function() {
            var context = this.getContext();
            if (context.treeview) {
                context.treeview.jstree('destroy');
                (this.element).find(".bb5-windowpane-treewrapper-inner").html("");
                this.setContext(context);
            }
        },
        
        _selectContentNode : function(catName){
            var context = this.getContext();
            if(catName){
                context.selected = catName;
                this.searchEngine.setUserParams({
                    catName : catName
                }); 
                this.setContext(context);
            }
        },
        
        _updateSearchEngineTypeFilters : function(contents){
            var context = this.getContext();
            var filters = [];
            var obj = {
                value : context.selected, 
                label : context.selected.replace("contentType_","")
            }; //selected content
            filters.push(obj);
            $.each(contents,function(i,node){
                var obj = {};
                var value = $(node).attr("rel");
                obj.value = value;
                obj.label = value.replace("contentType_","");
                filters.push(obj);
            });
            this.searchEngine.setFilterTypes(filters);
        },
        
        _showContent : function(criteria){
            var myself = this,
            context = this.getContext();
            var viewbtn = $(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper button.bb5-button-selected').get(0);
            $(this.element).find('.bb5-windowpane-main-toolbar-caption').empty();
            $(this.element).find('.bb5-listContainer').empty();
            //this.element.find(".bb5-windowpane-main").mask(bb.i18n.loading);
            this._mask("show");
            var pagerParams = {
                params : criteria,
                order_sort: '_title',
                order_dir: 'asc',
                limit:5,
                start:0
            };
            var onLoad = function(data){
                this._populateView(data);
                this._mask("hide");
            } 
            this._initPager(pagerParams,$.proxy(onLoad,myself));
            return;               
        } ,
        
        /*request here*/
        _initPager : function(pagerParams,successCallback){
            var myself = this;
            var context = this.getContext();
            var successCallback = (typeof successCallback!= "function")? new Function("console.log('successCallback function must be provided');") : successCallback; 
            var pagerParams = pagerParams || false;
            if(!pagerParams) return false;
            if(context.contentPager){
                $(context.contentPager).bbUtilsPager("updatePostParams",pagerParams);
            }
            else{
                var pagerService = bb.webserviceManager.getInstance('ws_local_contentBlock');
                var pagerCtn = $(this.element).find(this.options.pagerContainerClass).get(0);
                context.contentPager = $(pagerCtn).bbUtilsPager({
                    maxPerPageSelector : 5,
                    postParams: pagerParams,
                    onSelect:  function(){
                        myself._mask("show");
                        /*do update here*/
                        return;
                    },
                    dataWebserviceParams :{
                        wb:pagerService, 
                        method:"searchContent"
                    },
                    callback: successCallback,
                    errorCallback : function(response){
                        alert("The server sent an error message, please check your request.");
                        myself._mask("hide");
                    } 
                });
            }
            this.setContext(context);   
        }, 
        
        _populateView : function(response){
            /*data*/
            if(!response){
                this._mask("hide");
                return;
            }
            this.data = response;
            var myself = this;
            var context = this.getContext();
            
            /*captions*/
            $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(context.treeview.jstree('get_text', context.treeview.jstree('get_selected')));
            $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html($(myself.element).find('.bb5-windowpane-main-toolbar-caption').get(0).innerHTML + ' - ' + response.result.numResults + ' ' + myself.i18n.contents);                    
            
            /*list*/
            $(myself._templates.viewgrid).empty();
            $(myself._templates.viewlist).empty();
            var view = (this.options.viewMode=="list") ? $(myself._templates.viewlist) : $(myself._templates.viewgrid);
            $(myself.element).find('.bb5-listContainer').append($(view));
            
            /*populate items here*/
            $.each(response.result.rows, function(index, content) {
                content.ico = content.ico.replace('\\', '/');
                var contentRow = myself._templates.contentItemTemplate.apply(content);
                contentRow = $(contentRow).clone();
                $(contentRow).data('content',content);
                $(view).append(contentRow);
            });         
            myself._mask("hide");
        },
        
       
        
        _initContentsTree: function() {
            var myself = this,
            context = this.getContext();
            var maxEntry = context.filterParameters.maxentry || 0;
            var filters = {
                maxEntry:maxEntry, 
                accept:context.filterParameters.accept||'all'
            }; 
            this._destroyTree();
            myself.searchEngine.reset();
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
                        loading: bb.i18n.loading,
                        new_node: myself.i18n.new_node,
                        multiple_selection: myself.i18n.multiple_selection
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
                /*myself._selectContentNode($(e.target).find('ul > li:first').attr('id').replace('node_',''));*/
                    
                }
                myself._trigger('ready');
            }).bind('click.jstree',$.proxy(myself.callbacks.nodeClickHandler,myself)).bind("create.jstree", $.proxy(myself.callbacks.createHandler,myself));
            this.setContext(context);
        }
    });         
    
})(jQuery);
