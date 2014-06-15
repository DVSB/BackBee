//@ sourceURL=ressources/js/bb.ui.bbContentSelector.js
(function($){
    bb.jquery.widget('ui.bbContentSelector',{
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
            selectedContent :null,
            site: null
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
            mainPanel : bb.jquery("#bb5-ui-bbcontentselector-panel-tpl").clone(),
            viewgrid : null,
            viewlist: null
        },
        
        _mask : function(action){
            var action = action || "none";
            var availableActions = ["show","hide"];
            if( bb.jquery.inArray(action,availableActions) == -1 ) return;
            if(action=="show") bb.jquery(this.element).mask(bb.i18n.loading);
            if(action=="hide") bb.jquery(this.element).unmask();     
        },
        
        
        _initTemplates : function(item){
            /*List item template*/
            var itemTemplate = '<li data-uid="${uid}" class="bb5-content-item">'
            +'<p><a title="${completeTitle}" href="javascript:;"><img alt="${type}" src="${ico}"></a></p>'
            +'<p><a title="${completeTitle}" href="javascript:;">${title}</a></p>'
            +"<p>Date de crÃ©ation: <strong>${created}</strong></p>"
            +'<p>\n\
                    <button data-i18n="popupmanager.button.view" class="bb5-button bb5-ico-preview">Voir</button>\n\
                    <button class="bb5-button bb5-ico-add addClose">Ajouter et fermer</button>\n\
                    <button class="bb5-button bb5-ico-save addToList">Ajouter à ma sélection</button>\n\
                    <button class="bb5-button bb5-ico-del deleteContent">Effacer le contenu</button>\n\
              </p>'
            +'</li>';
            this._templates.contentItemTemplate = bb.jquery.template(itemTemplate);
            
            /*List selected item*/
            var selectionTpl = "<li class='bb5-content-list-item' data-uid='${uid}'><span><strong>${type}</strong><span><p>${completeTitle}<p><p><button class='bb5-button bb5-ico-del bb5-button-removeItem' href='javascript:;'>Effacer</button></li>";  
            this._templates.selectedItemTemplate = bb.jquery.template(selectionTpl);
        },
        
        _create : function(){
            var contentId = bb.Utils.generateId('contentSelector');
            bb.jquery(this._templates.mainPanel).attr("id",contentId); 
            this.element.html(bb.jquery(this._templates.mainPanel).show()); 
            this._templates.treeview = null;
            this._templates.viewgrid = bb.jquery(this.element).find(".bb5-list-media-is-grid");
            this._templates.viewlist = bb.jquery(this.element).find(".bb5-list-media-is-list");
            this.selectedListCtn = bb.jquery(this.element).find(this.options.cszParams.wrapperClass);
            bb.jquery(this.element).find(this.options.searchEngineContainerClass).hide(); //hide search engine
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
                onChange  : bb.jquery.proxy(this._onSelectedContentChange,this),
                onDestroy : bb.jquery.proxy(this._onDestroyScContainer,this),
                onDelete : bb.jquery.proxy(this._onSelectedContentChange,this)
            });
            context.site = bb.frontApplication.getSiteUid();

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
            this.searchEngine = bb.jquery(this.element).find(this.options.searchEngineContainerClass).eq(0).bbSearchEngine({
                onSearch : function(e,criteria){
                    var context = self.getContext();
                    if(("typeField" in criteria) && criteria.typeField){
                        if(context.selectedPageId) criteria.selectedPageId = context.selectedPageId;
                        self._showContent(criteria);
                    } 
                }
            }).bbSearchEngine("getWidgetApi");
            
            /*container list useful for resize*/
            this.listContainer = bb.jquery(this.element).find(this.options.listContainerClass).eq(0);
            this.treeWrappers = bb.jquery(this.element).find(this.options.treewrapperClass);  
            
            /*set search engine type*/
            this.searchEngine.setFilterTypes(this.typeFilters.contentType);
            this.setContext(context);
            this._initTemplates();
            this._initSelectContentTabs();
            bb.jquery(this.element).disableSelection();
            
            var sitesMenu = bb.jquery("<select class='bb5-available-sites'><option value='' data-i18n='toolbar.selector.select_site'>Sélectionner un site ...</option></select>").clone();
            bb.jquery(self.element).find('.bb5-windowpane-tree-inner').prepend(sitesMenu);
            bb.webserviceManager.getInstance('ws_local_site').request('getBBSelectorList', {    
                useCache:true,
                cacheTags:["userSession"],
                async : false, 
                success: function(result) {
                    var context = self.getContext();
                    var select = bb.jquery(self.element).find('.bb5-available-sites').eq(0);
                    
                    //click focus (FIREFOX bug)
                    if ($.browser.mozilla) {
                        select.unbind('click').click(function() {
                            select.focus();
                        });
                    }
                
                    //select change event
                    select.bind('change', function() {
                        if (bb.jquery(this).val()) {
                            context.site = bb.jquery(this).val();
                            if(self.pageTree){
                                self._initPageTree(bb.jquery(this).val());
                            }
                            
                            context.selectedPageId = null;
                            self.searchEngine.setSelectedPage(context.selectedPageId);
                            var criteria = self.searchEngine.getSearchCriteria();
                            if(context.selected){
                                criteria.typeField = context.selected;
                                self._showContent(criteria);
                            }                      
                            return true;
                        } else {
                            return false;
                        }
                    });

                    //select sites populating
                    //select.empty();
                    bb.jquery.each(result.result, function(index, site) {
                        var option = bb.jquery("<option></option>").clone();
                        bb.jquery(option).attr("value",index).text(site);
                        select.append(option);         
                    });

                    //select current site if configured
                    if (null !== context.site) {
                        select.val(context.site);
                    }

                    select.trigger("change");
                    self._trigger('ready');
                }
            });

            /*bb.jquery(this.element).find('.bb5-windowpane-tree').resizable({
                handles: 'e, w',
                maxWidth: 400,
                minWidth: 165,
                helper:"bb5-content-resizable-helper",
                resize: function(event, ui) {
                    bb.jquery(self.element).find('.bb5-windowpane-wrapper').css('padding-left', bb.jquery(self.element).find('.bb5-windowpane-tree').width() + 'px');
                } ,
                stop: function(event,ui){
                    bb.jquery(self.element).find('.bb5-windowpane-wrapper').css('padding-left', bb.jquery(self.element).find('.bb5-windowpane-tree').width() + 'px');
                    var newWidth = bb.jquery(this).width();
                    bb.jquery(this).attr("style","");
                    bb.jquery(this).width(newWidth);
                }
            });*/
            this.getParent().on("open",function(){
                bb.jquery(self.element).find(".bb5-windowpane-wrapper").layout({ 
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
            this.treeTabs.onShow = bb.jquery.proxy(this._initPageTree,this);
        },
        
        _initPageTree : function(){
            var self = this;
            //            if(!this.pageTree){
            var  pageTree = bb.jquery(this.element).find(this.options.pageTreeContaineClass).eq(0);
            this.pageTree = bb.jquery(pageTree).bbPageBrowser({
                popup : false,
                editMode : false,
                site : self.getContext().site,
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
                
        //            }
        },
          
        selectModeView :function(viewMode){
            var viewMode = viewMode || "none";
            var availableMode = ["list","grid"];
            if(bb.jquery.inArray(viewMode,availableMode)==-1){
                this.options.viewMode = "list"; 
            }else{
                this.options.viewMode = viewMode;
            }
            bb.jquery(this.element).find(this.viewInfos.btnClass).removeClass("bb5-button-selected");
            var btnToSelect = bb.jquery(this.element).find(this.viewInfos[this.options.viewMode]);
            bb.jquery(btnToSelect).addClass("bb5-button-selected");
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
            bb.jquery.each(this.treeWrappers,function(i,treeWrapper){
                var oldHeight = bb.jquery(treeWrapper).height();
                bb.jquery(treeWrapper).height(oldHeight+delta);
            });
             
            /*update list's height*/
            var prevListHeight = bb.jquery(this.listContainer).height(); 
            var listHeight = prevListHeight + delta;
            bb.jquery(this.listContainer).height(listHeight);
            
            /*update mainPanel's height*/
            var prevMainPaneHeight = bb.jquery(this.element).height();
            var mainPanelHeight = prevMainPaneHeight + delta;
            bb.jquery(this.element).height(mainPanelHeight);
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
            bb.jquery.each(data,function(key,item){
                var contentData = bb.jquery(item).data("content") || {};
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
            //bb.jquery(this.element).find(".bb5-listContainer").css("height","305px"); //useful for resize
            bb.jquery(this.element).find(".bb5-listContainer").html("");
            bb.jquery(this.element).find(".bb5-windowpane-main-toolbar-caption").html("");
            bb.jquery(this.element).find(".bb5-windowpane-main-toolbar-nav").html("");
            bb.jquery(this.element).find(".maxPerPageSelector").html("");
            bb.jquery(this.element).find(this.options.searchEngineContainerClass).hide();
            bb.jquery(this.element).find(".bb5-selectedItems-container").hide();
            /*cancel request*/
            
            this.selectedContent.reset();
        },   
        
        _bindEvents : function(){
            var self = this;
            bb.jquery(this.element).delegate(this.options.contentItemClass,"click",bb.jquery.proxy(this.callbacks["contentClickHandler"],this));
            bb.jquery(this.element).delegate(this.options.btnClass,'click',bb.jquery.proxy(this.callbacks["btnActionHandler"],this));
            bb.jquery(this.element).delegate(this.options.cszParams.captionWrapper,'click',bb.jquery.proxy(this.callbacks["showBasket"],this));
            bb.jquery(this.element).delegate(this.options.cszParams.confirmBtnClass,'click',bb.jquery.proxy(this.callbacks["confirmSelectionHandler"],this));
            bb.jquery(this.element).delegate(this.options.cszParams.closeListClass,'click',function(e){
                self.selectedListCtn.find(self.options.cszParams.containerClass).hide();
            });
            bb.jquery(this.element).delegate(this.options.cszParams.removeItemClass,"click",bb.jquery.proxy(this.callbacks["removeItemHandler"],this)); 
            bb.jquery(this.element).delegate(this.options.searchToggleBtnClass,"click",bb.jquery.proxy(this.callbacks["toggleSearchEngine"],this));
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
            bb.jquery(this.listContainer).height(bb.jquery(this.listContainer).height() + SEARCH_ENGINE);
            context.sizeIsFixed = true;
            this.setContext(context);
        },
        
        _updateSelectionList : function(selectedContent){
            var nbSelection = this.selectedContent.getSize();
            this.selectedListCtn.find(".bb5-selectedItemsNbs").text(nbSelection);  
        },
        
        callbacks : {
            
            toggleSearchEngine : function(e){
                var target = bb.jquery(e.currentTarget);
                var mainContainer = bb.jquery(this.element).find(".bb5-windowpane-wrapper").get(0);
                bb.jquery(target).toggleClass('opened');
                bb.jquery(this.element).find(this.options.searchEngineContainerClass).toggle();
                var delta = (bb.jquery(target).hasClass("opened")) ? -45 : 45; //toggle filter's size
                //bb.jquery(mainContainer).height(bb.jquery(mainContainer).height() + delta);
                bb.jquery(this.listContainer).height( bb.jquery(this.listContainer).height() + delta);   
            },
            
            showBasket : function(e){
                if(!this.selectedContent.getSize()) return false;
                this.callbacks.showSelectedhandler.call(this);
            },
            
            removeItemHandler : function(e){
                var itemId = bb.jquery(e.currentTarget).parents(".bb5-content-list-item").eq(0).attr("data-uid");
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
                var item = bb.jquery(e.currentTarget).parents(this.options.contentItemClass);
                var itemUid = bb.jquery(item).attr("data-uid");
                var data = $(item).data();
                if(bb.jquery(e.currentTarget).hasClass("addClose")){
                    this.selectedContent.set(itemUid,item);
                    this.setContext(context);
                    this._notifySelection();
                    this.close();
                }
               
                if(bb.jquery(e.currentTarget).hasClass("addToList")){
                    this.selectedContent.set(itemUid,bb.jquery(item).clone(true));
                }
               
                if(bb.jquery(e.currentTarget).hasClass("bb5-ico-sortaslist")){
                    this._mask("show");
                    this.selectModeView("list");
                    this._populateView(this.data);
                }
                
                if(bb.jquery(e.currentTarget).hasClass("bb5-ico-sortasgrid")){
                    this._mask("show");
                    this.selectModeView("grid");
                    this._populateView(this.data);
                }
                
                if(bb.jquery(e.currentTarget).hasClass("bb5-ico-preview")){
                    if($.isPlainObject(data)){
                        this._showContentPreview(data.content); 
                    }
                }
                
                if(bb.jquery(e.currentTarget).hasClass("bb5-ico-del")){
                    this._showDeleteDialog(data.content);
                }
            },
            
            contentClickHandler : function(e){
                e.stopPropagation();
                var context = this.getContext();
                var item = bb.jquery(e.currentTarget);
                var itemUid = bb.jquery(item).attr("data-uid");
                this.selectedContent.set(itemUid,item);
                this.setContext(context);
                this._notifySelection();
                this.close();
                return false;
            },
            
            nodeClickHandler :function (e) {
                var context = this.getContext();
                e = e || context.lastEvent; //useful if we want to recall  
                if ((bb.jquery(e.target).parents('a:first').hasClass('jstree-clicked')) || (bb.jquery(e.target).hasClass('jstree-clicked'))) {
                    /*do nothing for root*/
                    var isRoot = (bb.jquery(context.treeview.jstree('get_selected')).attr('rel').toUpperCase() == "ROOT") ? true : false;
                    if(isRoot) return; //do nothing for root                    
                    var tree = bb.jquery.jstree._reference(context.treeview);
                    var selectedChildren = tree._get_children(context.treeview.jstree('get_selected'));
                    this._selectContentNode(bb.jquery(context.treeview.jstree('get_selected')).attr('rel'));
                    this._updateSearchEngineTypeFilters(selectedChildren);
                    var searchCriteria = this.searchEngine.getSearchCriteria();
                    this._showContent(searchCriteria);
                }
                context.lastEvent = e;
                this.setContext(context);
            },
            
            createHandler : function(e, data) {
                bb.webserviceManager.getInstance('ws_local_mediafolder').request('insertBBBrowserTree', {  
                    params: {
                        title: data.rslt.name,
                        root_uid: data.rslt.parent.attr("id").replace("node_","")
                    },
                    success: function(result) {
                        if (!result.result)
                            bb.jquery.jstree.rollback(data.rlbk);
                        else {
                            bb.jquery(data.rslt.obj).attr('id', result.result.attr.id);
                            bb.jquery(data.rslt.obj).attr('rel', result.result.attr.rel);
                        }
                    }
                });  
            }
        },
                
        destroy : function(){
            bb.jquery.Widget.prototype.destroy.call(this);
        }, 
        /* proxies */
        publicApi : {},
        getContext: function() {
            return ( (typeof bb.jquery(this.element).data('context') != 'undefined') ? bb.jquery(this.element).data('context') : {} );
        },
        
        setContext: function(context) {
            return bb.jquery(this.element).data('context', bb.jquery.extend(bb.jquery(this.element).data('context'), context));
        },
        
        _showDeleteDialog: function(content){
            var self = this;
            bb.require(["ManagerFactory"], function(ContentManager){
                try{
                    if(!self.contentManager){
                        self.contentManager = ContentManager.getManager("content"); 
                        self.contentManager.init({
                            ws: bb.webserviceManager.getInstance("ws_local_classContent"),
                            onDeleteContent: function(){
                                self.callbacks.nodeClickHandler.call(self);
                            }
                        });  
                    }
                    self.contentManager.showDeleteDialog(content);
                }catch(e){
                    throw e;
                }
            });
            
        },
        
        
        
        _showContentPreview: function(content){
            var self = this;
            bb.require(["ManagerFactory"], function(Manager){
                try{
                    self.contentPreviewManager = Manager.getManager("contentpreview");
                    self.contentPreviewManager.init({
                        ws :bb.webserviceManager.getInstance('ws_local_contentBlock')
                    });
                    self.contentPreviewManager.showPreview(content.uid, content.type);
                }catch(e){
                    throw e;
                }
            });
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
            bb.jquery.each(contents,function(i,node){
                var obj = {};
                var value = bb.jquery(node).attr("rel");
                obj.value = value;
                obj.label = value.replace("contentType_","");
                filters.push(obj);
            });
            this.searchEngine.setFilterTypes(filters);
        },
        
        _showContent : function(criteria){
            var myself = this,
            context = this.getContext();
            var viewbtn = bb.jquery(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper button.bb5-button-selected').get(0);
            bb.jquery(this.element).find('.bb5-windowpane-main-toolbar-caption').empty();
            bb.jquery(this.element).find('.bb5-listContainer').empty();
            //this.element.find(".bb5-windowpane-main").mask(bb.i18n.loading);
            this._mask("show");
            var limit = $(this.element).find(".maxPerPageSelector").eq(0).val() || 50;
            var pagerParams = {
                params : criteria,
                site: context.site,
                order_sort: '_title',
                order_dir: 'asc',
                limit: limit,
                start:0
            };
            $(this.element).find("")
            var onLoad = function(data){
                this._populateView(data);
                this._mask("hide");
            } 
            this._initPager(pagerParams,bb.jquery.proxy(onLoad,myself));
            return;               
        } ,
        
        /*request here*/
        _initPager : function(pagerParams,successCallback){
            var myself = this;
            var context = this.getContext();
            var successCallback = (typeof successCallback!= "function")? new Function("console.log('successCallback function must be provided');") : successCallback; 
            pagerParams = pagerParams || false;
            if(!pagerParams) return false;
            if(context.contentPager){
                bb.jquery(context.contentPager).bbUtilsPager("updatePostParams",pagerParams);
            }
            else{
                var pagerService = bb.webserviceManager.getInstance('ws_local_contentBlock');
                var pagerCtn = bb.jquery(this.element).find(this.options.pagerContainerClass).get(0);
                context.contentPager = bb.jquery(pagerCtn).bbUtilsPager({
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
                        alert("The server has sent an error message, please check your request.");
                        myself._mask("hide");
                    } 
                });
                this.setContext(context); 
            }
             
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
            bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(context.treeview.jstree('get_text', context.treeview.jstree('get_selected')));
            bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-caption').get(0).innerHTML + ' - ' + response.result.numResults + ' ' + myself.i18n.contents);                    
            
            /*list*/
            bb.jquery(myself._templates.viewgrid).empty();
            bb.jquery(myself._templates.viewlist).empty();
            var view = (this.options.viewMode=="list") ? bb.jquery(myself._templates.viewlist) : bb.jquery(myself._templates.viewgrid);
            bb.jquery(myself.element).find('.bb5-listContainer').append(bb.jquery(view));
            
            /*populate items here*/
            bb.jquery.each(response.result.rows, function(index, content) {
                content.ico = content.ico.replace('\\', '/');
                var contentRow = myself._templates.contentItemTemplate.apply(content);
                contentRow = bb.jquery(contentRow).clone();
                bb.jquery(contentRow).data('content',content);
                bb.jquery(view).append(contentRow);
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
            context.treeview = bb.jquery(myself.element).find('.bb5-windowpane-treewrapper-inner').jstree({   
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
                                filters : filters, //accepted type
                                site : bb.frontApplication.getSiteUid()
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
                if (bb.jquery(e.target).find('ul > li:first').length > 0) {
                    data.inst.select_node('ul > li:first');
                /*myself._selectContentNode(bb.jquery(e.target).find('ul > li:first').attr('id').replace('node_',''));*/
                    
                }
                myself._trigger('ready');
            }).bind('click.jstree',bb.jquery.proxy(myself.callbacks.nodeClickHandler,myself)).bind("create.jstree", bb.jquery.proxy(myself.callbacks.createHandler,myself));
            this.setContext(context);
        }
    });         
    
})(bb.jquery);
