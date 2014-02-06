(function($){
    bb.jquery.widget('ui.bbMediaSelector', {
        options: {
            callback: null,
            editMode: false,
            pagerContainerClass:".bb5-windowpane-main-toobar-container",
            searchEngineContainerClass:".bb5-windowpane-main-toolbar-screener",
            viewMode : "list", //grid,
            showContextActions: true,
            searchToggleBtnClass : ".bb5-windowpane-main-toolbar-screener-toggle a",
            listContainerClass: ".bb5-listContainer",
            treewrapperClass :".bb5-windowpane-treewrapper"
        },

        _statesWatchable : {
            viewMode : "list",
            tree: {},
            elementsPerPage: 50
        },

        _statesRestore : {
            viewMode: function (self, value) {
                self.options.viewMode = value;
            },
            tree: function (self, values) {
                bb.jquery(self).bind('tree-loaded', function (e, obj, jstree) {
                    var all_opened = 0,
                    count_open = 0;
                    for (uid in values) {
                        if (values[uid] === 'open') {
                            count_open = count_open +1;
                            jstree.open_node('#' + uid, function () {},true);
                        }

                        if (jstree.is_open('#' + uid)) {
                            all_opened = all_opened +1;
                        }
                    }
                    if (all_opened === count_open) {
                        bb.jquery(self).unbind('tree-loaded');
                    }
                });
            }
        },

        _statesInterpretor : {
            tree: function (self, selector) {
                if (selector !== -1) {
                    var state = bb.jquery(selector).attr('class').replace('jstree-last', '');
                    state = state.replace(' ', '');
                    state = state.replace('jstree-', '');

                    var tree = self.getStateTree();
                    tree[bb.jquery(selector).attr('id')] = state;
                    return tree;
                }
            }
        },

        i18n: {
            all:"Tous",
            medias: 'media(s)',
            new_node: 'New folder',
            multiple_selection: 'Multiple selection',
            create: 'Create folder',
            rename: 'Rename folder',
            remove: 'Delete folder',
            utils :"Outils",
            addMediaPrefix :"Ajouter un",
            create_media: 'Add media',
            import_media: 'Import from server',
            delete_media: 'Delete media',
            delete_media_confirm: "You\'re about to remove a media!<br/>Continue?",
            save: 'Save',
            close: 'Close',
            notice: 'Notice',
            error: 'Error',
            upload_browser_not_supported: 'Your browser does not support HTML5 file uploads!',
            upload_too_many_file: 'Too many files!',
            upload_file_too_large: ' is too large!',
            upload_only_type_allowed: 'Only #type# are allowed!'
        },

      
      

        _create: function() {
            this.cachedResult = false;
            
            this._templates = {
                panel: $('#bb5-ui-bbmediaselector-panel-tpl').clone(),
                view: $('#bb5-ui-bbmediaselector-view-tpl').clone(),
                viewgrid: null,
                viewlist: null
            };
            this._context = {
                selected: null,
                treeview: null,
                messageBox: null,
                confirmBox: null,
                availableMedias: null,
                callback: null,
                mediaPager : null,
                searchEngine: null
            };

            var myself = bb.StateManager.extend(this, 'uibbMediaSelector');
            var viewMode = (bb.jquery.inArray(this.options.viewMode,["list","grid"]!=-1)) ? this.options.viewMode : "list";
            this.setContext({
                selected: null,
                treeview: null,
                messageBox: null,
                availableMedias: null,
                viewMode :viewMode
            });

            /*si le contenu est cloné ne modifier que les classes des zones: */
            var contentId = bb.Utils.generateId("mediaSelector");
            bb.jquery(this._templates.panel).attr("id",contentId);
            this.element.html(bb.jquery(this._templates.panel).show());
            
            /*select ther right view btn*/
            var test = bb.jquery(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper .bb5-button').removeClass('bb5-button-selected');
            bb.jquery(this.element).find(".bb5-ico-sortas"+viewMode).addClass("bb5-button-selected");

            /*view panels*/
            this._templates.viewgrid = bb.jquery(this.element).find(".bb5-list-media-is-grid");
            this._templates.viewlist = bb.jquery(this.element).find(".bb5-list-media-is-list");
            if (!this.options.editMode) {
                bb.jquery(this.element).find('.bbActionWrapper').remove();
            }

            /*hide searchengine*/
            // bb.jquery(this.element).find(this.options.searchEngineContainerClass).hide();
            this._bindEvents();
            this._isCreate = true;
            this.isLoaded = false;
        },

        _bindEvents: function(){
            var myself = this;
            bb.jquery(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper .bb5-button').bind('click', function() {
                var context = myself.getContext();
                bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-sort-wrapper .bb5-button').removeClass('bb5-button-selected');
                bb.jquery(this).addClass('bb5-button-selected');
                context.viewMode = (bb.jquery(this).hasClass("bb5-ico-sortasgrid"))?"grid":"list";
                myself.setStateViewMode(context.viewMode);
                myself.setContext(context);
                if(myself.cachedResult) myself._populateView(myself.cachedResult);
            });
            
            /*Apply */
            this.getParent().on('open', function () {
                
                if(!myself.isLoaded){
                    bb.jquery(myself.element).find(".bb5-windowpane-wrapper").layout({ 
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
                
                    var selector = bb.jquery(myself.element).find('.maxPerPageSelector');
                    bb.jquery(selector).change(function () {
                        myself.setStateElementsPerPage(bb.jquery(this).val());
                    });
                    bb.jquery(selector).find('option[value="' + myself.getStateElementsPerPage() + '"]').attr('selected', 'selected');
                    myself.isLoaded = true;
                    myself._initTree();
                }
                 
            });
           

            /*click on item action*/
            bb.jquery(this.element).delegate(".bb5-button","click",function(e){
                var currentItem = e.currentTarget;
                var context = myself.getContext();
                var data = myself._getDataFromBtn(currentItem);

                if(bb.jquery(currentItem).hasClass("bb5-ico-preview") || bb.jquery(currentItem).hasClass("bb5-ico-export")){
                    e.stopPropagation();
                    return window.open(bb.jquery(currentItem).attr('data-uri'), 'preview');
                }

                if(bb.jquery(currentItem).hasClass("bb5-ico-edit")){
                    e.stopPropagation();
                    myself._editMedia(data.content.classname,data.mediafolder_uid,data.id);
                    return false;
                }

                if(bb.jquery(currentItem).hasClass("bb5-ico-del")){
                    e.stopPropagation();
                    myself._deleteMedia(data.content.classname,data.mediafolder_uid,data.id);
                    return false;
                }
            });

            bb.jquery(this.element).delegate(".bb5-selector-item","click",function(e){
                e.preventDefault();
                var itemData = bb.jquery(e.currentTarget).data("media");
                myself._selectItem(itemData,e.currentTarget);
            });

            /*handle click on search engine*/
            bb.jquery(this.element).find(this.options.searchToggleBtnClass).bind("click",bb.jquery.proxy(this.toggleSearchEngine,this));
        },
        toggleSearchEngine : function(e){
            var target = bb.jquery(e.currentTarget);
            var mainContainer = bb.jquery(this.element).find(".bb5-windowpane-wrapper").get(0);
            bb.jquery(target).toggleClass('opened');
            bb.jquery(this.element).find(this.options.searchEngineContainerClass).toggle();
            var delta = (bb.jquery(target).hasClass("opened")) ? -45 : 45; //toggle filter's size
            //bb.jquery(mainContainer).height(bb.jquery(mainContainer).height() + delta);
            bb.jquery(this.listContainer).height( bb.jquery(this.listContainer).height() + delta);
        },
        /*this function is used to insert a menu on the toolbar
         * return an html node
         *
         **/
        initToolsbarMenu : function(){
            var self = this;
            var context = this.getContext();
            var utilsMenu = bb.jquery("<span></span>").clone();
            bb.jquery(utilsMenu).addClass("bb5-dialog-title-tools");
            var utilsBtn = bb.jquery("<a></a>").clone();
            bb.jquery(utilsBtn).addClass("bb5-button-link bb5-button bb5-toolsbtn bb5-button-square bb5-invert").attr("href","javascript:;").text(bb.i18n.__('mediaselector.utils'));
            utilsBtn.appendTo(bb.jquery(utilsMenu));
            this.utilsMenu = bb.jquery(utilsMenu);
            bb.jquery(this.utilsMenu).find('a.bb5-toolsbtn').bind('click', function(e) {
                if ((context.selected) && (context.treeview) ) {
                    context.treeview.jstree('show_contextmenu', bb.jquery(self.element).find('#node_' + context.selected), e.pageX, e.pageY + 10);
                }
                return false;
            });
            return this.utilsMenu;
        },
        _initMultipleSelections : function(){
            this.initBasket();
        },

        _getDataFromBtn : function(btn){
            var data = bb.jquery(btn).parents(".bb5-selector-item").data("media");
            return data;
        },

        _selectItem : function(data,htmlData){
            var context = this.getContext();
            if (context.callback) {
                context.callback({
                    type: 'media',
                    uid: data.content.uid,
                    title: data.title,
                    value: data.content.url,
                    render: bb.jquery(htmlData).clone(),
                    target: '_self',
                    data: data
                });
            } else {
                //edit
                this._editMedia(data.content.classname, data.mediafolder_uid, data.id);
            }
        },

        _init: function() {
            var context = this.getContext();
            this.utilsMenu = null;
            var myself = this;

            context.callback = this.options.callback;
            this.dialogMng = bb.PopupManager.init();
            //tree
            //this._initTree();/*Choose content to load here*/
            this.setContext(context);
            /*showContext*/
            bb.jquery(document).bind("context_show.vakata",function(e){});
            this.listContainer = bb.jquery(this.element).find(this.options.listContainerClass).get(0);
            this.treeWrappers = bb.jquery(this.element).find(this.options.treewrapperClass);
            this.isloaded = true;
        },

        fixContainersHeight :function(){
            var context = this.getContext();
            if(context.sizeIsFixed!="undefined" && context.sizeIsFixed) return false;
            var SEARCH_ENGINE = 45;
            bb.jquery(this.listContainer).height(bb.jquery(this.listContainer).height() + SEARCH_ENGINE);
            context.sizeIsFixed = true;
            this.setContext(context);
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
        filterSearchEngineBy :function(){

        },

        _initSearchEngine : function(typeFilters){
            var tf = typeFilters || [];
            var context = this.getContext();
            var myself = this;
            this.searchEngine = bb.jquery(this.element).find(this.options.searchEngineContainerClass).eq(0).bbSearchEngine({
                defaultFilterTypes : tf,
                onSearch:function(e,criteria){
                    criteria.mediafolder_uid = context.selected;
                    myself._initView(criteria);
                }
            }).bbSearchEngine("getWidgetApi");
        },

        _mask: function() {
            bb.jquery(this.element).mask(bb.i18n.__('loading'));
        },

        _unmask: function() {
            bb.jquery(this.element).unmask();
        },

        _getMediaType :function(mediaClass){
            var regex = /[\\]([\w]+)$/g;
            var result = false;
            if(regex.test(mediaClass)) {
                var matches = mediaClass.match(regex);
                result = matches[0].replace("\\","");
            }
            return result;
        },
           
        _initTree: function() {
            var myself = this,
            context = this.getContext();

            //tree
            this._destroyTree();

            bb.webserviceManager.getInstance('ws_local_media').request('getBBSelectorAvailableMedias', {
                useCache : true,
                cacheTags : ["userSession"],
                success: function(result) {
                    var plugins = [
                    'themes', 'rpc_data', 'ui', 'crrm' ,'types', 'html_data'
                    ];

                    if (myself.options.editMode) {
                        plugins.push('dnd');
                        plugins.push('contextmenu');
                    }

                    var current_node,
                    create_submenu = {};
                    context.availableMedias = {};
                    var typeFilters = [{
                        value:"all",
                        label : bb.i18n.__('mediaselector.all')
                    }];
                    bb.jquery.each(result.result, function(index, available_media) {
                        context.availableMedias[available_media.classname] = {
                            label: available_media.label
                        };
                        var btnRel = "bb5-context-menu-addmedia-"+myself._getMediaType(available_media.classname);
                        create_submenu[btnRel] = {
                            label : bb.i18n.__('mediaselector.addMediaPrefix') +" "+available_media.label.toLowerCase(),
                            action: function(obj) {
                                myself._editMedia(available_media.classname, obj.attr('id').replace('node_',''), null);
                            },
                            separator_before: false,
                            separator_after: false,
                            icon : btnRel
                        };
                        var filterObject = {};
                        filterObject["value"] = available_media.classname;
                        filterObject["label"] = available_media.label;
                        typeFilters.push(filterObject);
                    });

                    myself._initSearchEngine(typeFilters);
                    context.treeview = bb.jquery(myself.element).find('.bb5-windowpane-treewrapper-inner').jstree({
                        plugins : plugins,
                        rpc_data : {
                            ajax : {
                                webservice : {
                                    instance: bb.webserviceManager.getInstance('ws_local_mediafolder'),
                                    method: 'getBBBrowserTree'
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
                                loading: bb.i18n.__('loading'),
                                new_node: bb.i18n.__('mediaselector.new_node'),
                                multiple_selection: bb.i18n.__('mediaselector.multiple_selection')
                            }
                        },

                        contextmenu: bb.jquery.extend(true,{
                            select_node: true,
                            show_at_node: false,
                            items: {
                                create: null,
                                rename: null,
                                remove: null,
                                edit: null,
                                ccp: null,
                                "bb5-context-menu-add": {
                                    label: bb.i18n.__('popupmanager.button.create'),
                                    icon: 'bb5-context-menu-add',
                                    action : function(obj){
                                        this.create(obj);
                                    }
                                },
                                "bb5-context-menu-edit": {
                                    label: bb.i18n.__('popupmanager.button.rename'),
                                    icon: 'bb5-context-menu-edit',
                                    action : function(obj){
                                        this.rename(obj);
                                    }
                                },
                                "bb5-context-menu-remove": {
                                    label: bb.i18n.__('popupmanager.button.remove'),
                                    icon: 'bb5-context-menu-remove',
                                    action: function(obj){
                                        this.remove(obj);
                                    }

                                }

                            /*"bb5-context-menu-addmedia": {
                                    label: myself.i18n.create_media,
                                    separator_before: true,
                                    separator_after: true,
                                    icon: 'bb5-context-menu-addmedia'
                                }*/


                            /*,
                                "bb5-context-menu-importfromserver": {
                                    label: myself.i18n.import_media,
                                    action: function(obj) {
                                    },
                                    separator_before: false,
                                    separator_after: true,
                                    icon: 'bb5-context-menu-importfromserver'
                                }
                                 */
                            }
                        },{
                            items:create_submenu
                        }) //ajouter les types de media au menu
                    }).bind('loaded.jstree', function (e, data) {
                        if (bb.jquery(e.target).find('ul > li:first').length > 0) {
                            data.inst.select_node('ul > li:first');
                            myself._selectNode(bb.jquery(e.target).find('ul > li:first').attr('id').replace('node_',''));
                        }

                        //disable specific actions for node
                        bb.jquery(document).live('context_show.vakata', function(e) {
                            bb.jquery('#vakata-contextmenu').find('li a[rel="import_media"]').addClass('disabled');
                            //root
                            if (bb.jquery.vakata.context.par.attr('rel') == 'root') {
                                bb.jquery('#vakata-contextmenu').find('li a[rel="rename"]').addClass('disabled');
                                bb.jquery('#vakata-contextmenu').find('li a[rel="remove"]').addClass('disabled');
                            }
                            else if (context.selected == bb.jquery.vakata.context.par.attr('id').replace('node_','')) {
                                bb.jquery('#vakata-contextmenu').find('li a[rel="remove"]').addClass('disabled');
                            }
                        });

                        myself._trigger('ready');
                    }).bind('click.jstree', function (e) {
                        if ((bb.jquery(e.target).parents('a:first').hasClass('jstree-clicked')) || (bb.jquery(e.target).hasClass('jstree-clicked'))) {
                            myself._selectNode(bb.jquery(context.treeview.jstree('get_selected')).attr('id').replace('node_',''));
                        }
                        if (!(bb.jquery(e.target).parent().hasClass('jstree-leaf'))) {
                            myself.setStateTree(bb.jquery(e.target).parent());
                        }
                    }).bind("rpc_data_loaded.jstree", function (e, obj, tree) {
                        myself.setStateTree(obj);
                        bb.jquery(myself).trigger('tree-loaded', [e.target, tree]);
                    }).bind("create.jstree", function (e, data) {
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
                    }).bind("rename.jstree", function (e, data) {
                        bb.webserviceManager.getInstance('ws_local_mediafolder').request('renameBBBrowserTree', {
                            params: {
                                title: data.rslt.new_name,
                                mediafolder_uid: data.rslt.obj.attr("id").replace("node_","")
                            },
                            success: function(result) {
                                if (!result.result)
                                    bb.jquery.jstree.rollback(data.rlbk);
                            }
                        });
                    }).bind("remove.jstree", function (e, data) {
                        var context = myself.getContext();
                        if ((data.rslt.obj)) {
                            bb.webserviceManager.getInstance('ws_local_mediafolder').request('delete', {
                                params: {
                                    uid: data.rslt.obj.attr("id").replace("node_","")
                                },
                                success: function(result) {
                                    if (!result.result) {
                                        bb.jquery.jstree.rollback(data.rlbk);
                                        if (result.error)
                                            myself._showMessage(bb.i18n.__('toolbar.editing.error'), bb.i18n.__(result.error.message), 'alert');
                                    }
                                }
                            });
                        } else {
                            bb.jquery.jstree.rollback(data.rlbk);
                        }
                    }).bind("move_node.jstree", function (e, data) {
                        data.rslt.o.each(function (i) {
                            var myself = this;
                            if (data.rslt.cr !== -1) {
                                bb.webserviceManager.getInstance('ws_local_mediafolder').request('moveBBBrowserTree', {
                                    params: {
                                        mediaflorder_uid: bb.jquery(myself).attr('id').replace('node_',''),
                                        root_uid: data.rslt.np.attr('id').replace('node_',''),
                                        next_uid: (((data.rslt.or.length == 0) ? null : data.rslt.or.attr('id').replace('node_','')))
                                    },
                                    success: function(result) {
                                        if (!result.result)
                                            bb.jquery.jstree.rollback(data.rlbk);
                                    }
                                });
                            } else {
                                bb.jquery.jstree.rollback(data.rlbk);
                            }
                        });
                    });
                }
            });

            this.setContext(context);
        },

        _destroyTree: function() {
            var context = this.getContext();

            if (context.treeview) {
                context.treeview.jstree('destroy');
                bb.jquery(this.element).find('#browserLinks').children().remove();
                this.setContext(context);
            }
        },

        _selectNode: function(mediafolder_uid) {
            var context = this.getContext();
            if (mediafolder_uid) {
                context.selected = mediafolder_uid;
                this.searchEngine.reset();
                var params = {
                    "mediafolder_uid" : mediafolder_uid
                };
                this._initView(params);
                this.setContext(context);
            }
        },

        _changeNode: function(mediafolder_uid) {
            var context = this.getContext();
            context.treeview.jstree('deselect_node', bb.jquery(this.element).find('#browserMedia').find('#node_' + context.selected));
            this._selectNode(mediafolder_uid);
            context.treeview.jstree('select_node', bb.jquery(this.element).find('#browserMedia').find('#node_' + mediafolder_uid));
            this.setContext(context);
        },

        _initView: function(params) {
            var myself = this,
            context = this.getContext();
            var viewbtn = bb.jquery(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper button.bb5-button-selected').get(0);

            /*pager here*/
            bb.jquery(this.element).find('.bb5-windowpane-main-toolbar-caption').empty();
            bb.jquery(this.element).find('.bb5-listContainer').empty();
            this._mask();
            var pagerParams = {
                params :params,
                mediafolder_uid:params.mediafolder_uid,
                order_sort: '_title',
                order_dir: 'asc',
                limit:myself.getStateElementsPerPage(),
                start:0
            };

            var callback = bb.jquery.proxy(this._populateView,this);
            this._initPager(pagerParams,callback);
            return;
        },

        _initPager : function(pagerParams,successCallback){
            var myself = this;
            var successCallback = (typeof successCallback!= "function")? new Function("console.log('successCallback function must be provided');") : successCallback;
            var pagerParams = pagerParams || false;
            if(!pagerParams) return false;

            if(this._context.mediaPager){
                bb.jquery(this._context.mediaPager).bbUtilsPager("updatePostParams",pagerParams);
            }
            else{
                var pagerService = bb.webserviceManager.getInstance('ws_local_mediafolder');
                var pagerCtn = bb.jquery(this.element).find(this.options.pagerContainerClass).get(0);
                this._context.mediaPager = bb.jquery(pagerCtn).bbUtilsPager({
                    maxPerPageSelector : myself.getStateElementsPerPage(),
                    postParams: pagerParams,
                    onSelect:  function(){
                        bb.jquery(myself.element).find(".bb5-windowpane-main").mask(bb.i18n.__('loading'));
                        return;
                    },
                    dataWebserviceParams :{
                        wb:pagerService,
                        method:"getBBSelectorView"
                    },
                    callback: successCallback
                });
            }
        },

        _populateView : function(response){
            var myself = this;
            this.cachedResult = response;
            var context = this.getContext();

            bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(context.treeview.jstree('get_text', context.treeview.jstree('get_selected')));
            bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(bb.jquery(myself.element).find('.bb5-windowpane-main-toolbar-caption').get(0).innerHTML + ' - ' + response.result.numResults + ' ' + myself.i18n.medias);


            /*clean current*/
            bb.jquery(myself.element).find('.bb5-listContainer').empty();
            if(context.viewMode=="grid") var resultView = bb.jquery(myself._templates.viewgrid).empty();
            if(context.viewMode=="list") var resultView = bb.jquery(myself._templates.viewlist).empty();

            bb.jquery(myself.element).find('.bb5-listContainer').append(bb.jquery(resultView));
            bb.jquery.each(response.result.views, function(index, media) {
                bb.jquery(myself.element).find('.bb5-windowpane-main ul:first').append(media.html).bb_i18nparse();
                bb.jquery(myself.element).find('.bb5-windowpane-main ul:first').eq(0);
       
                //formating ??
                var viewrow = bb.jquery(myself.element).find('.bb5-windowpane-main ul:first li:last');
                if (viewrow) {
                    viewrow.find('img').attr('alt', media.media.title);
                    viewrow.data('media', media.media);
                    if (!myself.options.editMode) {
                        viewrow.find('p:last').remove();
                    }
                }
            });
            bb.jquery(myself.element).find(".bb5-windowpane-main").unmask();
            myself._unmask();

        },

        _destroyView: function() {
            bb.jquery(this.element).find('.bb5-windowpane-main').empty();
        },
        
        // fixme utilser un validator type: data-validator:notempty
        _formIsValid : function(){
            var content = this.getContext();
            var mediaForm = content.mediaFormDialog.dialog;
            var errors = [];
            var fields = bb.jquery(mediaForm).find('[data-validate]');
            if(fields.length){
                bb.jquery.each(fields,function(i,field){
                    if(bb.jquery.trim(bb.jquery(field).val())=="" || bb.jquery.trim(bb.jquery(field).html())==""){
                        errors.push(field); 
                    } 
                });
            }
            /*empêcher le champ média d'être vide*/
            var uploadedContent = bb.jquery(mediaForm).find(".uploadedmedia").eq(0).val();
            var MediapreviewCtn = bb.jquery(mediaForm).find(".bbMediaPreview").eq(0); 
            if(bb.jquery.trim(uploadedContent)==""){
            // errors.push(MediapreviewCtn);
            }
            
            if(errors.length){
                this._handleErrors(errors);
                return false;
            }
            return true;
        },
        
        _handleErrors : function(errors){
            if(bb.jquery.isArray(errors)){
                bb.jquery.each(errors, function(i,node){
                    bb.jquery(node).addClass("hasError").unbind("click").bind("click",function(){
                        bb.jquery(this).removeClass("hasError");
                    });
                });
            /*preview error*/
                
            }
            
        },
        
        _editMedia: function(media_classname, mediafolder_uid, media_id) {

            var myself = this;
            var context = this.getContext();
            bb.webserviceManager.getInstance('ws_local_media').request('getBBSelectorForm', {
                params: {
                    mediafolder_uid: mediafolder_uid,
                    media_classname: media_classname,
                    media_id: media_id
                },

                success: function(result) {
                    myself.imageHasChanged = false;
                    if(!context.mediaFormDialog){
                        var popupManager = bb.PopupManager.init();
                        context.mediaFormDialog = popupManager.create("mediaFormEditor",{
                            title: context.availableMedias[media_classname].label,
                            autoOpen: false,
                            width: 355,
                            height: 'auto',
                            modal: true,
                            resizable: false,
                            draggable:false,
                            position:["center","center"],
                            close: function() {
                                bb.jquery(this).empty();
                            //bb.jquery('#bb5-ui-bbmediaselector-editmedia').children().remove();
                            }
                        });
                    }
                    context.mediaFormDialog.setOption("title",context.availableMedias[media_classname].label);
                    context.mediaFormDialog.setContent(bb.jquery(result.result));
                    
                    var dropbox = bb.jquery(context.mediaFormDialog.dialog).find(".bbMediaPreview").eq(0);
                    var mediaType = bb.jquery(dropbox).attr("data-mediatype") || null;
                    if(!mediaType) throw "Le type du média n'a pas pu être trouvé.";
                    var maxSize = (mediaType in bb.config.mediaFileSize) ? parseInt(bb.config.mediaFileSize[mediaType]) : 1;  

                    /*
                     *var mediaUploader = mediaUploader.init(type);
                    var mediaUploadhandler = mediaUpload.handler("pdf",{callback : function(){}});
                    /************************************************************************/
                    bb.uploadManager.getInstance('ws_local_media').filedrop('uploadMedia' , {
                        paramname: 'uploadedmedia',
                        maxfiles: 1,
                        maxfilesize: maxSize,
                        data: {},

                        uploadFinished:function(i, file, response) {
                            context.mediaFormDialog.btnEnable();
                            if (!response.error) {
                                bb.jquery.data(file).addClass('done');
                                bb.jquery(context.mediaFormDialog.dialog).find('input[name="uploadedmedia"]').val(JSON.stringify(response.result));
                                myself.imageHasChanged = true;
                            } else {
                                dropbox.empty();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), response.error.message, 'alert');
                            }
                        },

                        error: function(err, file) {
                             dropbox.empty();
                            switch(err) {
                                case 'BrowserNotSupported':
                                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), bb.i18n.__('toolbar.editing.upload_browser_not_supported'), 'alert');
                                    break;
                                case 'TooManyFiles':
                                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), bb.i18n.__('toolbar.editing.upload_too_many_file'), 'alert');
                                    break;
                                case 'FileTooLarge':
                                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), file.name + bb.i18n.__('toolbar.editing.upload_file_too_large'), 'alert');
                                    break;
                                default:
                                    break;
                            }
                        },
                        
                        beforeEach: function(file) {
                            if(!file.type.match(mediaType)) {
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), bb.i18n.__('toolbar.editing.upload_only_type_allowed').replace("#type#",mediaType), 'alert');
                                return false;
                            }
                        },

                        uploadStarted:function(i, file, len) {
                            context.mediaFormDialog.btnDisable();
                            myself._createPreview(file, dropbox);
                        },

                        progressUpdated: function(i, file, progress) {
                            bb.jquery.data(file).find('.progress').width(progress);
                        },

                        dragOver: function(e) {
                            bb.jquery(e.target).addClass('hover');
                        },

                        dragLeave: function(e) {
                            bb.jquery(e.target).removeClass('hover');
                        },

                        drop: function(e) {
                            bb.jquery(e.target).removeClass('hover');
                        }

                    }, dropbox);

                    var buttons = {};
                    buttons[bb.i18n.__('popupmanager.button.save')] = function() {
                        //bb.jquery('#bb5-ui-bbmediaselector-editmedia').parents('.ui-dialog:first').mask(bb.i18n.loading);
                        if(!myself._formIsValid()) return;
                        context.mediaFormDialog.dialogUi.mask(bb.i18n.__('loading'));
                        /*update form here*/
                        var form = bb.jquery(context.mediaFormDialog.dialog).find("form").get(0);
                        var uploadedField = bb.jquery(form).find(".uploadedmedia").eq(0) || false;
                        if(!uploadedField) throw "uploadedmedia field can't be found. Please add the class [uploadedmedia] to the media field.";

                        var fieldname = bb.jquery(uploadedField).attr("data-fieldname") || false;
                        if(!fieldname) throw "data-fieldname attribute can't be found";
                        bb.jquery(uploadedField).attr("name",fieldname);
                        
                        /*validate mediaform here*/
                        
                        
                        bb.webserviceManager.getInstance('ws_local_media').request('postBBSelectorForm', {
                            params: {
                                mediafolder_uid: mediafolder_uid,
                                media_classname: media_classname,
                                media_id: media_id,
                                content_values: JSON.stringify(bb.jquery(context.mediaFormDialog.dialog).find("form").eq(0).serializeArray())
                            },

                            success: function(response) {
                                //bb.jquery('#bb5-ui-bbmediaselector-editmedia').parents('.ui-dialog:first').unmask();
                                context.mediaFormDialog.dialogUi.unmask();
                                //bb.jquery('#bb5-ui-bbmediaselector-editmedia').dialog("close");

                                myself._changeNode(mediafolder_uid);
                                /*update content next*/
                                var imageUid = response.result.content.uid;
                                var changedMedia = {
                                    type: 'media',
                                    uid: imageUid,
                                    title: response.result.title,
                                    value: imageUid,
                                    target: '_self',
                                    data: response.result
                                };
                                bb.jquery('[data-uid="' + imageUid + '"]').trigger('contentchange.image',{
                                    media : changedMedia
                                });
                                context.mediaFormDialog.close();
                            },

                            error: function(result) {
                                //bb.jquery('#bb5-ui-bbmediaselector-editmedia').parents('.ui-dialog:first').unmask();
                                context.mediaFormDialog.dialogUi.unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };

                    buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                        //bb.jquery('#bb5-ui-bbmediaselector-editmedia').dialog("close");
                        context.mediaFormDialog.close();
                    };
                    context.mediaFormDialog.setOption("buttons",buttons);
                    context.mediaFormDialog.show();
                    myself.setContext(context);

                },

                error: function(result) {
                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                }
            });
        },

        _deleteMedia: function(media_classname, mediafolder_uid, media_id) {
            var myself = this,
            context = this.getContext();

            if (!context.confirmBox) {
                var popupManager = bb.PopupManager.init();
                context.confirmBox = popupManager.create("confirmDialog",{
                    autoOpen: false,
                    width: 350,
                    dialogType: popupManager.dialogType.CONFIRMATION,
                    height: 'auto',
                    modal: true,
                    resizable: false,
                    buttons: {},
                    close: function() {}
                });
            }

            context.confirmBox.setContent('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 50px 0;"></span>' + bb.i18n.__('mediaselector.delete_media_confirm') + '</p>');

            var buttons = {};
            buttons[bb.i18n.__('mediaselector.delete_media')] = function() {
                context.confirmBox.dialogUi.mask(bb.i18n.__('loading'));
                bb.webserviceManager.getInstance('ws_local_media').request('delete', {
                    params: {
                        media_id: media_id
                    },

                    success: function(result) {
                        context.confirmBox.dialogUi.unmask();
                        context.confirmBox.close();
                        myself._changeNode(mediafolder_uid);
                        var html = bb.i18n.__('statusmanager.reload_page');
                        var popupDialog = bb.PopupManager.init({
                            dialogSettings:{
                                modal: true
                            }
                        });
                        var dialog = popupDialog.create("confirmDialog",{
                            title: bb.i18n.__('mediaselector.media_deleted'),
                            buttons:{
                                "Reload": {
                                    text: bb.i18n.__('popupmanager.button.reload'),
                                    click: function() {
                                        bb.jquery(this).dialog("close");
                                        document.location.reload();
                                    }
                                },
                                "Cancel": {
                                    text: bb.i18n.__('popupmanager.button.cancel'),
                                    click: function(a){
                                        bb.jquery(this).dialog("close");
                                        return false;
                                    }
                                }
                            }
                        });
                        bb.jquery(dialog.dialog).html(html);
                        dialog.show();
                    },

                    error: function(result) {
                        context.confirmBox.dialogUi.unmask();
                        context.confirmBox.close();
                        myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                    }
                });
            };

            buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                context.confirmBox.close();
            };

            context.confirmBox.setOption( 'buttons', buttons );
            context.confirmBox.setOption('title',bb.i18n.__('mediaselector.delete_media'));
            context.confirmBox.show();
            this.setContext(context);
        },

        /* move outside*/
        _createPreview: function(file, dropbox) {
            dropbox.empty();
            var fileType = (typeof bb.jquery(dropbox).attr("data-mediatype")=="string") ? bb.jquery(dropbox).attr("data-mediatype") : false;  
            if(fileType) new Error("Media type .");
            var methodName = fileType.charAt(0).toUpperCase() + fileType.slice(1);
            var methodToCall = "_create{mediaType}Preview".replace("{mediaType}",methodName);
            if(typeof this[methodToCall]=="function"){
                var preview = this[methodToCall].call(this,file,dropbox);
                preview.appendTo(dropbox);
                bb.jquery.data(file, preview);
            }
            return;
        },

        /* fixme Place preview here.. or in a class implementing an useful interface*/
        _createZipPreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = bb.jquery(bb.jquery(context.mediaFormDialog.dialog).find("#filebbselector-editpreview-tpl .preview").clone());
                if("name" in file) bb.jquery(preview).find(".filename").text(file.name);
                return preview;
            }
        },
                
        _createPdfPreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = bb.jquery(bb.jquery(context.mediaFormDialog.dialog).find("#filebbselector-editpreview-tpl .preview").clone());
                if("name" in file) bb.jquery(preview).find(".filename").text(file.name);
                return preview;
            }
        },
       
        _createFlashPreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = bb.jquery(bb.jquery(context.mediaFormDialog.dialog).find("#swfbbselector-editpreview-tpl .preview").clone());
                if("name" in file) bb.jquery(preview).find(".filename").text(file.name);
                return preview;
            }
        },
                
        _createImagePreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = bb.jquery(bb.jquery(context.mediaFormDialog.dialog).find("#imagebbselector-editpreview-tpl .preview").clone());
                var image = bb.jquery('img', preview);
                var reader = new FileReader();
                reader.onload = function(e){
                    image.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
                return preview;
            }
        },


        _showMessage: function(title, message, icon) {
            var myself = this,
            context = this.getContext();

            var popupManager = bb.PopupManager.init({});

            if (!context.messageBox) {
                context.messageBox = popupManager.create("mediaSelectorAlert",{
                    autoOpen: false,
                    width: 350,
                    dialogType : popupManager.dialogType.INFO,
                    height: 'auto',
                    modal: true,
                    resizable: false,
                    buttons: {},
                    close: function() {}
                });
            }
            context.messageBox.setContent(bb.jquery('<p><span class="ui-icon ui-icon-' + icon + '" style="float:left; margin:0 7px 50px 0;"></span>' + message + '</p>'));

            var buttons = {};
            buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                context.messageBox.close();
            };

            context.messageBox.setOption('buttons', buttons );
            context.messageBox.setOption("title",title);
            context.messageBox.setOption("position","center");
            context.messageBox.show();

            this.setContext(context);
        },

        setCallback: function(callback) {
            var context = this.getContext();
            context.callback = callback;
            this.setContext(context);
        },

        setContext: function(context) {
            return bb.jquery(this.element).data('context', bb.jquery.extend(bb.jquery(this.element).data('context'), context));
        },

        getContext: function() {
            return ( (typeof bb.jquery(this.element).data('context') != 'undefined') ? bb.jquery(this.element).data('context') : {} );
        },

        destroy: function(){
            var context = this.getContext();

            bb.jquery(this.element).find('.bbPaneTree').resizable('destroy');

            this._destroyTree();
            this._destroyView();
            context.messageBox.dialog('destroy');
            context.messageBox.remove();
            bb.jquery(this.element).empty();
            context.selected = null;
            context.treeview = null;
            context.messageBox = null;
            context.availableMedias = null;
            bb.jquery.Widget.prototype.destroy.apply(this, arguments);

            this.setContext(context);
        }
    })
})(bb.jquery);
