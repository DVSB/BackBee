(function($){
    $.widget('ui.bbPageSelector', {
        options: {
            callback: null,
            pagerContainerClass : ".bb5-windowpane-main-toobar-container",//.bb5-windowpane-main-toolbar-screener",
            searchEngineContainerClass:".bb5-windowpane-main-toolbar-screener",
            searchToggleBtnClass : ".bb5-windowpane-main-toolbar-screener-toggle a",
            listContainerClass: ".bb5-listContainer",
            treewrapperClass :".bb5-windowpane-treewrapper",
            btnClass :".bb5-button",
            viewMode : "grid",
            availableSitesClass : ".bb5-available-sites",
            site:null
        },
        viewInfos : {
            "btnClass":".bb5-viewMode",
            "list":".bb5-ico-sortaslist",
            "grid":".bb5-ico-sortasgrid"
        },
          
        i18n: {
            pages: 'page(s)',
            multiple_selection: 'Multiple selection',
            create: 'Create',
            rename: 'Rename',
            remove: 'Delete',
            all:"Tous"
        },
        
        _templates: {
            panel: '#bb5-ui-linksselector-panel-tpl',
            view: '#bb-ui-bbpageselector-view-tpl'
        },
        
        _context: {
            site: null,
            selected: null,
            treeview: null,
            view: null,
            availableMedias: null,
            callback: null,
            pager: null
        },
        
        
        _createTemplates : function(){
            var pageRowTpl = '<tr>'
            +'<td><a href="" target="_blank"><span></span>${title}</a></td>'
            +'<td>${created}</td>'
            +'<td>${modified}</td>'
            +'<td><button value="${uid}" class="bb5-button bb5-ico-basket bb5-button-square"></button></td>'
            +'</tr>';
            this._templates.viewRow = $.template(pageRowTpl);
        },
        
        _create: function() {
            var myself = this;
            this._createTemplates();
            this.setContext({
                selected:  null,
                treeview:  null,
                messageBox:null,
                availableMedias: null,
                callback:  null,
                site_uid : myself.options.site,
                page_uid : null
            });
            
            //panel
            var template = $(this._templates.panel).clone();
            var contentId = bb.Utils.generateId("pageSelector");
            $(template).attr("id",contentId); 
            this._templates.panel = template;
            this.element.html($(this._templates.panel).show());
            //site droopdown
            this._mask();
            
            /*populate sites*/
            bb.webserviceManager.getInstance('ws_local_site').request('getBBSelectorList', {    
                useCache:true,
                cacheTags:["userSession"],
                async : false, 
                success: function(result) {
                    context = myself.getContext();
                    select = $(myself.element).find('.bb5-available-sites').eq(0);
                    
                    //select change event
                    select.bind('change', function() {
                        myself._initTree($(this).val());
                    });
                    
                    //select sites populating
                    select.empty();
                    $.each(result.result, function(index, site) {
                        var option = $("<option></option>").clone();
                        $(option).attr("value",index).text(site);
                        select.append(option);         
                    });
                    
                    //select current site if configured
                    if (null != context.site_uid) {
                        select.val(context.site_uid);
                    }
                    
                    select.trigger("change");
                    
                    myself._unmask();
                    myself._trigger('ready');
                }
            });
            
            $(this.element).find('.bb5-windowpane-main table.bb5-table-data0x thead tr th:first').addClass('headerSortUp');
            $(this.element).find('.bb5-windowpane-main table.bb5-table-data0x thead tr th:last').removeClass('header');
            $(this.element).find(this.options.searchEngineContainerClass).hide();
            
        /*init layouts*/
        },
        
        _init: function() {
            var myself = this,
            context = this.getContext();
            this.data = null;    
            context.callback = this.options.callback;
            this.selectModeView(this.options.viewMode);
            context.view = this._templates.viewRow;
            $(this.element).find('.bb5-windowpane-main table.bb5-table-data0x tbody tr td button').live('click', function() {
                var viewrow = $(this).parents('tr:first');
                var context = myself.getContext();
                
                if (context.callback) {
                    context.callback({
                        type: 'page',
                        uid: viewrow.data('page').uid,
                        title: viewrow.data('page').title,
                        value: viewrow.data('page').url,
                        target: '_self',
                        data: viewrow.data('page')
                    });
                }
            });
            
            //this._trigger('selectedPage', null , )
            
            $(this.element).find('.header').bind('click', function() {
                if ($(this).hasClass('headerSortUp') || $(this).hasClass('headerSortDown')) {
                    if ($(this).hasClass('headerSortUp'))
                        $(this).removeClass('headerSortUp').addClass('headerSortDown');
                    else
                        $(this).removeClass('headerSortDown').addClass('headerSortUp');
                } else {
                    $(this).parents('tr:first').find('th.header').removeClass('headerSortUp').removeClass('headerSortDown');
                    $(this).addClass('headerSortUp'); 
                }
                myself._initView({
                    site_uid: context.site, 
                    page_uid: context.selected
                });
            });
            
            this.listContainer = $(this.element).find(this.options.listContainerClass).eq(0);
            this._initSearchEngine();
            this.treeWrappers = $(this.element).find(this.options.treewrapperClass); 
            $(this.element).find(this.options.searchToggleBtnClass).bind("click",$.proxy(this.toggleSearchEngine,this));
            $(this.element).find(this.options.btnClass).bind("click",$.proxy(this.btnHandler,this));
            
            /*parent is bbSelector*/
            this.getParent().on("open",function(){
                $(myself.element).find(".bb5-windowpane-wrapper").layout({ 
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
                
        btnHandler:function(e){
            var btn = e.target;
            var self = this;
            if($(btn).hasClass(this.viewInfos.list.replace(".",""))){
                this.selectModeView("list");
                if(!self.data==null) this._populateView(this.data);
                return false;
            }
            if($(btn).hasClass(this.viewInfos.grid.replace(".",""))){
                this.selectModeView("grid");
                if(!self.data==null) this._populateView(this.data);
                return false;
            }
        },
        
        toggleSearchEngine : function(e){
            var target = $(e.currentTarget);
            var mainContainer = $(this.element).find(".bb5-windowpane-wrapper").get(0);
            $(target).toggleClass('opened');
            $(this.element).find(this.options.searchEngineContainerClass).toggle();
            var delta = ($(target).hasClass("opened")) ? -45 : 45; //toggle filter's size
            //$(mainContainer).height($(mainContainer).height() + delta);
            $(this.listContainer).height( $(this.listContainer).height() + delta);   
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
        
        
        fixContainersHeight :function(){
            var context = this.getContext();
            if(context.sizeIsFixed!="undefined" && context.sizeIsFixed) return false;
            var SEARCH_ENGINE = 45;
            $(this.listContainer).height($(this.listContainer).height() + SEARCH_ENGINE);
            context.sizeIsFixed = true;
            this.setContext(context);
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
        _getFiltersFromServiceResult: function (result) {
            var myself = this;
            var typeFilters = [{
                value:"all",
                label : myself.i18n.all
            }];
            $.each(result.result, function(index, type_statu) {
                var filterObject = {};
                filterObject["value"] = type_statu;
                filterObject["label"] = index;
                typeFilters.push(filterObject); 
            });
            
            return typeFilters;
        },
        
        _initSearchEngine : function() {
            var tf = [];
            var context = this.getContext();
            var myself = this;
            bb.webserviceManager.getInstance('ws_local_page').request('getListAvailableStatus', {
                useCache: true,
                cacheTags: ["userSession"],
                success: function(result) {
                    tf = myself._getFiltersFromServiceResult(result);
                    var searchEngine = $(myself.element).find(myself.options.searchEngineContainerClass).eq(0).bbSearchEngine({
                        defaultFilterTypes : tf,
                        onSearch:function(e,criteria){
                            //var criteria = self.searchEngine.getSearchCriteria();
                            criteria.page_uid = context.selected;
                            criteria.site_uid = context.site;
                            myself._initView(criteria);
                        } 
                    }).bbSearchEngine("getWidgetApi");
                }
            });
        },
        
        _initTree: function(site_uid) {
            var myself = this,
            context = this.getContext();
            
            //tree
            this._destroyTree();
            context.selected = null;
            context.site = null;
            
            if ((site_uid) && (site_uid.length > 0)) {
                
                context.site = site_uid;
                context.treeview = $(this.element).find('.bb5-windowpane-treewrapper-inner').jstree({   
                    plugins : [ 
                    'themes', 'rpc_data', 'ui', 'crrm' ,'types', 'html_data'
                    ],

                    rpc_data : { 
                        ajax : {
                            webservice : {
                                instance: bb.webserviceManager.getInstance('ws_local_page'),
                                method: 'getBBBrowserTree'
                            },

                            data : function (n) { 
                                return { 
                                    'site_uid': site_uid,
                                    'root_uid' : n.attr ? n.attr('id').replace('node_','') : null,
                                    'current_uid': bb.frontApplication.getPageId()
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

                    ui : {},
                    
                    core : {
                        strings: {
                            loading: bb.i18n.loading
                        }
                    }
                }).bind('click.jstree', function (e) {
                    if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                        context.selected = $(context.treeview.jstree('get_selected')).attr('id').replace('node_','');
                        myself._initView({
                            site_uid: context.site, 
                            page_uid: context.selected
                        });
                        myself.setContext(context);
                    }
                }).bind('loaded.jstree', function(e, data) {
                    myself._initView({
                        'site_uid': site_uid, 
                        'page_uid': null
                    });
                });
            } else {
                myself._initView({
                    'site_uid': null, 
                    'page_uid': null
                });
            }
            
            this.setContext(context);
        },
        
        _destroyTree: function() {
            var context = this.getContext();
            
            if (context.treeview) {
                context.treeview.jstree('destroy');
                $(this.element).find(".bb5-windowpane-treewrapper-inner").empty();
                this.setContext(context);
            }
        },
        
        _initView: function(input_params) {
            var myself = this,
            context = this.getContext();
            if (input_params.site_uid) {
                var order = $(this.element).find('.bb5-windowpane-main th[class*="headerSort"]');
               
                var order_sort = '_title';
                var order_dir = 'asc';
                    
                if (order.hasClass('sort_created'))
                    order_sort = '_created';
                else if (order.hasClass('sort_modified'))
                    order_sort = '_modified';
                
                if (order.hasClass('headerSortDown'))
                    order_dir = 'desc';
                
                //this._mask();
                context.site_uid = input_params.site_uid||null;
                context.page_uid = input_params.page_uid||null;
                //console.log(input_params);
                var pagerParams = {
                    params : {
                        afterPubdateField: input_params.afterPubdateField,
                        beforePubdateField: input_params.beforePubdateField,
                        searchField: input_params.searchField,
                        typeField: input_params.typeField,
                        site_uid: input_params.site_uid,
                        page_uid: input_params.page_uid,
                        order_sort: order_sort,
                        order_dir: order_dir
                    }
                };
                
                var successCallback = $.proxy(this._populateView,this); //this,page_uid,site_uid
                myself._initPager(pagerParams,successCallback);
                this.setContext(context);
                return;
            }
        },
        
        _initPager : function(pagerParams,successCallback){
            var myself = this;
            var successCallback = (typeof successCallback!= "function")? new Function("console.log('successCallback function must be provided');") : successCallback; 
            var pagerParams = pagerParams || false;
            if(!pagerParams) return false;
            var context = this.getContext();
            if(context.pagePager){
                /*update pager info*/
                $(context.pagePager).bbUtilsPager("updatePostParams",pagerParams);
            }
            else{
                var pagerService = bb.webserviceManager.getInstance('ws_local_page');
                var pagerCtn = $(this.element).find(this.options.pagerContainerClass).get(0);
                
                //console.log('toto', pagerCtn);
                context.pagePager = $(pagerCtn).bbUtilsPager({
                    maxPerPageSelector : 5,
                    postParams: pagerParams,
                    onSelect:  function(){
                        $(myself.element).find(".bb5-windowpane-main").mask(bb.i18n.loading);
                        return;
                    },
                    dataWebserviceParams :{
                        wb:pagerService, 
                        method:"getBBSelectorView"
                    },
                    callback: successCallback
                });
            }
            this.setContext(context);  
        }, 
        
        
        _populateView : function(response, mainobject){ 
            /*vider caption --> vider results*/
            $(this.element).find('bb5-windowpane-main-toolbar-caption').html('&nbsp');
            $(this.listContainer).find("tbody").empty();
            var context = this.getContext();
            var site_uid = context.site_uid || null;
            var page_uid = context.page_uid || null;
            var myself = this;
            if (!page_uid){
                var selectedSite = $(myself.element).find('.bb5-available-sites option:selected');
                var text = selectedSite.get(0).innerHTML;
                $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(text);
            }
            else{
                var selectedNode = context.treeview.jstree('get_text', context.treeview.jstree('get_selected'));
                $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(context.treeview.jstree('get_text', context.treeview.jstree('get_selected'))); 
            }
            var prevCaption = $(myself.element).find('.bb5-windowpane-main-toolbar-caption').get(0).innerHTML;
            $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(prevCaption+' - ' + response.result.numResults + ' ' + myself.i18n.pages);      
            $.each(response.result.views, function(index, page) {
                myself.listContainer.find("tbody").eq(0).append(context.view.apply(page));
                           
                //formating
                var viewrow = $(myself.element).find('.bb5-windowpane-main table.bb5-table-data0x tbody tr:last');
                if (viewrow) {
                    viewrow.find('a').attr('href', page.url);
                    viewrow.data('page', page); 
                }
            });
            myself._unmask();
          
          
        },
       
        _destroyView: function() {
            $(this.element).find('.bb5-windowpane-main').empty();
        },
        
        _mask: function() {
            $(this.element).mask(bb.i18n.loading);
        },
        
        _unmask: function() {
            $(this.element).unmask();
        },
        
        setCallback: function(callback) {
            if(typeof callback=="function"){
                var context = this.getContext();
                context.callback = callback;
                this.options.callback = callback;
                this.setContext(context);  
            } 
        },
                
        setContext: function(context) {
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },
        
        getContext: function() {
            return ( (typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {} );
        },
        
        destroy: function(){
            var context = this.getContext();
            
            $(this.element).find('.bb5-windowpane-tree').resizable('destroy');
            
            this._destroyTree();
            this._destroyView();
            $(this.element).empty();
            
            context.site = null;
            context.selected = null;
            context.view = null;
            $.Widget.prototype.destroy.apply(this, arguments);
            
            this.setContext(context);
        }
    })
})(jQuery);