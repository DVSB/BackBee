(function($){
    $.widget('ui.bbMediaSelector', {
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
                $(self).bind('tree-loaded', function (e, obj, jstree) {
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
                        $(self).unbind('tree-loaded');
                    }
                });
            }
        },

        _statesInterpretor : {
            tree: function (self, selector) {
                if (selector !== -1) {
                    var state = $(selector).attr('class').replace('jstree-last', '');
                    state = state.replace(' ', '');
                    state = state.replace('jstree-', '');

                    var tree = self.getStateTree();
                    tree[$(selector).attr('id')] = state;
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

        _templates: {
            panel: $('#bb5-ui-bbmediaselector-panel-tpl').clone(),
            view: $('#bb5-ui-bbmediaselector-view-tpl').clone(),
            viewgrid: null,
            viewlist: null
        },
        cachedResult : false, //useful for grid list
        _context: {
            selected: null,
            treeview: null,
            messageBox: null,
            confirmBox: null,
            availableMedias: null,
            callback: null,
            mediaPager : null,
            searchEngine: null
        },

        _create: function() {
            var myself = bb.StateManager.extend(this, 'uibbMediaSelector');
            var viewMode = ($.inArray(this.options.viewMode,["list","grid"]!=-1)) ? this.options.viewMode : "list";
            this.setContext({
                selected: null,
                treeview: null,
                messageBox: null,
                availableMedias: null,
                viewMode :viewMode
            });

            /*si le contenu est cloné ne modifier que les classes des zones: */
            var contentId = bb.Utils.generateId("mediaSelector");
            $(this._templates.panel).attr("id",contentId);
            this.element.html($(this._templates.panel).show());
            
            /*select ther right view btn*/
            var test = $(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper .bb5-button').removeClass('bb5-button-selected');
            $(this.element).find(".bb5-ico-sortas"+viewMode).addClass("bb5-button-selected");

            /*view panels*/
            this._templates.viewgrid = $(this.element).find(".bb5-list-media-is-grid");
            this._templates.viewlist = $(this.element).find(".bb5-list-media-is-list");
            if (!this.options.editMode) {
                $(this.element).find('.bbActionWrapper').remove();
            }

            /*hide searchengine*/
            // $(this.element).find(this.options.searchEngineContainerClass).hide();
            this._bindEvents();
            this._isCreate = true;
            this.isLoaded = false;
        },

        _bindEvents: function(){
            var myself = this;
            $(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper .bb5-button').bind('click', function() {
                var context = myself.getContext();
                $(myself.element).find('.bb5-windowpane-main-toolbar-sort-wrapper .bb5-button').removeClass('bb5-button-selected');
                $(this).addClass('bb5-button-selected');
                context.viewMode = ($(this).hasClass("bb5-ico-sortasgrid"))?"grid":"list";
                myself.setStateViewMode(context.viewMode);
                myself.setContext(context);
                if(myself.cachedResult) myself._populateView(myself.cachedResult);
            });
            
            /*Apply */
            this.getParent().on('open', function () {
                
                if(!myself.isLoaded){
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
                
                    var selector = $(myself.element).find('.maxPerPageSelector');
                    $(selector).change(function () {
                        myself.setStateElementsPerPage($(this).val());
                    });
                    $(selector).find('option[value="' + myself.getStateElementsPerPage() + '"]').attr('selected', 'selected');
                    myself.isLoaded = true;
                    myself._initTree();
                }
                 
            });
           

            /*click on item action*/
            $(this.element).delegate(".bb5-button","click",function(e){
                var currentItem = e.currentTarget;
                var context = myself.getContext();
                var data = myself._getDataFromBtn(currentItem);

                if($(currentItem).hasClass("bb5-ico-preview") || $(currentItem).hasClass("bb5-ico-export")){
                    e.stopPropagation();
                    return window.open($(currentItem).attr('data-uri'), 'preview');
                }

                if($(currentItem).hasClass("bb5-ico-edit")){
                    e.stopPropagation();
                    myself._editMedia(data.content.classname,data.mediafolder_uid,data.id);
                    return false;
                }

                if($(currentItem).hasClass("bb5-ico-del")){
                    e.stopPropagation();
                    myself._deleteMedia(data.content.classname,data.mediafolder_uid,data.id);
                    return false;
                }
            });

            $(this.element).delegate(".bb5-selector-item","click",function(e){
                e.preventDefault();
                var itemData = $(e.currentTarget).data("media");
                myself._selectItem(itemData,e.currentTarget);
            });

            /*handle click on search engine*/
            $(this.element).find(this.options.searchToggleBtnClass).bind("click",$.proxy(this.toggleSearchEngine,this));
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
        /*this function is used to insert a menu on the toolbar
         * return an html node
         *
         **/
        initToolsbarMenu : function(){
            var self = this;
            var context = this.getContext();
            var utilsMenu = $("<span></span>").clone();
            $(utilsMenu).addClass("bb5-dialog-title-tools");
            var utilsBtn = $("<a></a>").clone();
            $(utilsBtn).addClass("bb5-button-link bb5-button bb5-toolsbtn bb5-button-square bb5-invert").attr("href","javascript:;").text(bb.i18n.__('mediaselector.utils'));
            utilsBtn.appendTo($(utilsMenu));
            this.utilsMenu = $(utilsMenu);
            $(this.utilsMenu).find('a.bb5-toolsbtn').bind('click', function(e) {
                if ((context.selected) && (context.treeview) ) {
                    context.treeview.jstree('show_contextmenu', $(self.element).find('#node_' + context.selected), e.pageX, e.pageY + 10);
                }
                return false;
            });
            return this.utilsMenu;
        },
        _initMultipleSelections : function(){
            this.initBasket();
        },

        _getDataFromBtn : function(btn){
            var data = $(btn).parents(".bb5-selector-item").data("media");
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
                    render: jQuery(htmlData).clone(),
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
            $(document).bind("context_show.vakata",function(e){});
            this.listContainer = $(this.element).find(this.options.listContainerClass).get(0);
            this.treeWrappers = $(this.element).find(this.options.treewrapperClass);
            this.isloaded = true;
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
        filterSearchEngineBy :function(){

        },

        _initSearchEngine : function(typeFilters){
            var tf = typeFilters || [];
            var context = this.getContext();
            var myself = this;
            this.searchEngine = $(this.element).find(this.options.searchEngineContainerClass).eq(0).bbSearchEngine({
                defaultFilterTypes : tf,
                onSearch:function(e,criteria){
                    criteria.mediafolder_uid = context.selected;
                    myself._initView(criteria);
                }
            }).bbSearchEngine("getWidgetApi");
        },

        _mask: function() {
            $(this.element).mask(bb.i18n.__('loading'));
        },

        _unmask: function() {
            $(this.element).unmask();
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
                    $.each(result.result, function(index, available_media) {
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
                    context.treeview = $(myself.element).find('.bb5-windowpane-treewrapper-inner').jstree({
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

                        contextmenu: $.extend(true,{
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
                        if ($(e.target).find('ul > li:first').length > 0) {
                            data.inst.select_node('ul > li:first');
                            myself._selectNode($(e.target).find('ul > li:first').attr('id').replace('node_',''));
                        }

                        //disable specific actions for node
                        $(document).live('context_show.vakata', function(e) {
                            $('#vakata-contextmenu').find('li a[rel="import_media"]').addClass('disabled');
                            //root
                            if ($.vakata.context.par.attr('rel') == 'root') {
                                $('#vakata-contextmenu').find('li a[rel="rename"]').addClass('disabled');
                                $('#vakata-contextmenu').find('li a[rel="remove"]').addClass('disabled');
                            }
                            else if (context.selected == $.vakata.context.par.attr('id').replace('node_','')) {
                                $('#vakata-contextmenu').find('li a[rel="remove"]').addClass('disabled');
                            }
                        });

                        myself._trigger('ready');
                    }).bind('click.jstree', function (e) {
                        if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                            myself._selectNode($(context.treeview.jstree('get_selected')).attr('id').replace('node_',''));
                        }
                        if (!($(e.target).parent().hasClass('jstree-leaf'))) {
                            myself.setStateTree($(e.target).parent());
                        }
                    }).bind("rpc_data_loaded.jstree", function (e, obj, tree) {
                        myself.setStateTree(obj);
                        $(myself).trigger('tree-loaded', [e.target, tree]);
                    }).bind("create.jstree", function (e, data) {
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
                    }).bind("rename.jstree", function (e, data) {
                        bb.webserviceManager.getInstance('ws_local_mediafolder').request('renameBBBrowserTree', {
                            params: {
                                title: data.rslt.new_name,
                                mediafolder_uid: data.rslt.obj.attr("id").replace("node_","")
                            },
                            success: function(result) {
                                if (!result.result)
                                    $.jstree.rollback(data.rlbk);
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
                                        $.jstree.rollback(data.rlbk);
                                        if (result.error)
                                            myself._showMessage(bb.i18n.__('toolbar.editing.error'), bb.i18n.__(result.error.message), 'alert');
                                    }
                                }
                            });
                        } else {
                            $.jstree.rollback(data.rlbk);
                        }
                    }).bind("move_node.jstree", function (e, data) {
                        data.rslt.o.each(function (i) {
                            var myself = this;
                            if (data.rslt.cr !== -1) {
                                bb.webserviceManager.getInstance('ws_local_mediafolder').request('moveBBBrowserTree', {
                                    params: {
                                        mediaflorder_uid: $(myself).attr('id').replace('node_',''),
                                        root_uid: data.rslt.np.attr('id').replace('node_',''),
                                        next_uid: (((data.rslt.or.length == 0) ? null : data.rslt.or.attr('id').replace('node_','')))
                                    },
                                    success: function(result) {
                                        if (!result.result)
                                            $.jstree.rollback(data.rlbk);
                                    }
                                });
                            } else {
                                $.jstree.rollback(data.rlbk);
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
                $(this.element).find('#browserLinks').children().remove();
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
            context.treeview.jstree('deselect_node', $(this.element).find('#browserMedia').find('#node_' + context.selected));
            this._selectNode(mediafolder_uid);
            context.treeview.jstree('select_node', $(this.element).find('#browserMedia').find('#node_' + mediafolder_uid));
            this.setContext(context);
        },

        _initView: function(params) {
            var myself = this,
            context = this.getContext();
            var viewbtn = $(this.element).find('.bb5-windowpane-main-toolbar-sort-wrapper button.bb5-button-selected').get(0);

            /*pager here*/
            $(this.element).find('.bb5-windowpane-main-toolbar-caption').empty();
            $(this.element).find('.bb5-listContainer').empty();
            this._mask();
            var pagerParams = {
                params :params,
                mediafolder_uid:params.mediafolder_uid,
                order_sort: '_title',
                order_dir: 'asc',
                limit:myself.getStateElementsPerPage(),
                start:0
            };

            var callback = $.proxy(this._populateView,this);
            this._initPager(pagerParams,callback);
            return;
        },

        _initPager : function(pagerParams,successCallback){
            var myself = this;
            var successCallback = (typeof successCallback!= "function")? new Function("console.log('successCallback function must be provided');") : successCallback;
            var pagerParams = pagerParams || false;
            if(!pagerParams) return false;

            if(this._context.mediaPager){
                $(this._context.mediaPager).bbUtilsPager("updatePostParams",pagerParams);
            }
            else{
                var pagerService = bb.webserviceManager.getInstance('ws_local_mediafolder');
                var pagerCtn = $(this.element).find(this.options.pagerContainerClass).get(0);
                this._context.mediaPager = $(pagerCtn).bbUtilsPager({
                    maxPerPageSelector : myself.getStateElementsPerPage(),
                    postParams: pagerParams,
                    onSelect:  function(){
                        $(myself.element).find(".bb5-windowpane-main").mask(bb.i18n.__('loading'));
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

            $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html(context.treeview.jstree('get_text', context.treeview.jstree('get_selected')));
            $(myself.element).find('.bb5-windowpane-main-toolbar-caption').html($(myself.element).find('.bb5-windowpane-main-toolbar-caption').get(0).innerHTML + ' - ' + response.result.numResults + ' ' + myself.i18n.medias);


            /*clean current*/
            $(myself.element).find('.bb5-listContainer').empty();
            if(context.viewMode=="grid") var resultView = $(myself._templates.viewgrid).empty();
            if(context.viewMode=="list") var resultView = $(myself._templates.viewlist).empty();

            $(myself.element).find('.bb5-listContainer').append($(resultView));
            $.each(response.result.views, function(index, media) {
                $(myself.element).find('.bb5-windowpane-main ul:first').append(media.html).bb_i18nparse();
                $(myself.element).find('.bb5-windowpane-main ul:first').eq(0);
       
                //formating ??
                var viewrow = $(myself.element).find('.bb5-windowpane-main ul:first li:last');
                if (viewrow) {
                    viewrow.find('img').attr('alt', media.media.title);
                    viewrow.data('media', media.media);
                    if (!myself.options.editMode) {
                        viewrow.find('p:last').remove();
                    }
                }
            });
            $(myself.element).find(".bb5-windowpane-main").unmask();
            myself._unmask();

        },

        _destroyView: function() {
            $(this.element).find('.bb5-windowpane-main').empty();
        },
        
        // fixme utilser un validator type: data-validator:notempty
        _formIsValid : function(){
            var content = this.getContext();
            var mediaForm = content.mediaFormDialog.dialog;
            var errors = [];
            var fields = $(mediaForm).find('[data-validate]');
            if(fields.length){
                $.each(fields,function(i,field){
                    if($.trim($(field).val())=="" || $.trim($(field).html())==""){
                        errors.push(field); 
                    } 
                });
            }
            /*empêcher le champ média d'être vide*/
            var uploadedContent = $(mediaForm).find(".uploadedmedia").eq(0).val();
            var MediapreviewCtn = $(mediaForm).find(".bbMediaPreview").eq(0); 
            if($.trim(uploadedContent)==""){
               // errors.push(MediapreviewCtn);
            }
            
            if(errors.length){
                this._handleErrors(errors);
                return false;
            }
            return true;
        },
        
        _handleErrors : function(errors){
            if($.isArray(errors)){
                $.each(errors, function(i,node){
                    $(node).addClass("hasError").unbind("click").bind("click",function(){
                        $(this).removeClass("hasError");
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
                                $(this).empty();
                            //$('#bb5-ui-bbmediaselector-editmedia').children().remove();
                            }
                        });
                    }
                    context.mediaFormDialog.setOption("title",context.availableMedias[media_classname].label);
                    context.mediaFormDialog.setContent($(result.result));
                    
                    var dropbox = $(context.mediaFormDialog.dialog).find(".bbMediaPreview").eq(0);
                    var mediaType = $(dropbox).attr("data-mediatype") || null;
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
                                $.data(file).addClass('done');
                                $(context.mediaFormDialog.dialog).find('input[name="uploadedmedia"]').val(JSON.stringify(response.result));
                                myself.imageHasChanged = true;
                            } else {
                                dropbox.empty();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), response.error.message, 'alert');
                            }
                        },

                        error: function(err, file) {
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
                            $.data(file).find('.progress').width(progress);
                        },

                        dragOver: function(e) {
                            $(e.target).addClass('hover');
                        },

                        dragLeave: function(e) {
                            $(e.target).removeClass('hover');
                        },

                        drop: function(e) {
                            $(e.target).removeClass('hover');
                        }

                    }, dropbox);

                    var buttons = {};
                    buttons[bb.i18n.__('popupmanager.button.save')] = function() {
                        //$('#bb5-ui-bbmediaselector-editmedia').parents('.ui-dialog:first').mask(bb.i18n.loading);
                        if(!myself._formIsValid()) return;
                        context.mediaFormDialog.dialogUi.mask(bb.i18n.__('loading'));
                        /*update form here*/
                        var form = $(context.mediaFormDialog.dialog).find("form").get(0);
                        var uploadedField = $(form).find(".uploadedmedia").eq(0) || false;
                        if(!uploadedField) throw "uploadedmedia field can't be found. Please add the class [uploadedmedia] to the media field.";

                        var fieldname = $(uploadedField).attr("data-fieldname") || false;
                        if(!fieldname) throw "data-fieldname attribute can't be found";
                        $(uploadedField).attr("name",fieldname);
                        
                        /*validate mediaform here*/
                        
                        
                        bb.webserviceManager.getInstance('ws_local_media').request('postBBSelectorForm', {
                            params: {
                                mediafolder_uid: mediafolder_uid,
                                media_classname: media_classname,
                                media_id: media_id,
                                content_values: JSON.stringify($(context.mediaFormDialog.dialog).find("form").eq(0).serializeArray())
                            },

                            success: function(response) {
                                //$('#bb5-ui-bbmediaselector-editmedia').parents('.ui-dialog:first').unmask();
                                context.mediaFormDialog.dialogUi.unmask();
                                //$('#bb5-ui-bbmediaselector-editmedia').dialog("close");

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
                                $('[data-uid="' + imageUid + '"]').trigger('contentchange.image',{
                                    media : changedMedia
                                });
                                context.mediaFormDialog.close();
                            },

                            error: function(result) {
                                //$('#bb5-ui-bbmediaselector-editmedia').parents('.ui-dialog:first').unmask();
                                context.mediaFormDialog.dialogUi.unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };

                    buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                        //$('#bb5-ui-bbmediaselector-editmedia').dialog("close");
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
                                        $(this).dialog("close");
                                        document.location.reload();
                                    }
                                },
                                "Cancel": {
                                    text: bb.i18n.__('popupmanager.button.cancel'),
                                    click: function(a){
                                        $(this).dialog("close");
                                        return false;
                                    }
                                }
                            }
                        });
                        $(dialog.dialog).html(html);
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
            var fileType = (typeof $(dropbox).attr("data-mediatype")=="string") ? $(dropbox).attr("data-mediatype") : false;  
            if(fileType) new Error("Le type du media n'a pas pu être trouvé.");
            var methodName = fileType.charAt(0).toUpperCase() + fileType.slice(1);
            var methodToCall = "_create{mediaType}Preview".replace("{mediaType}",methodName);
            if(typeof this[methodToCall]=="function"){
                var preview = this[methodToCall].call(this,file,dropbox);
                preview.appendTo(dropbox);
                $.data(file, preview);
            }
            return;
        },

        /* fixme Place preview here.. or in a class implementing an useful interface*/
        _createZipPreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = $($(context.mediaFormDialog.dialog).find("#filebbselector-editpreview-tpl .preview").clone());
                if("name" in file) $(preview).find(".filename").text(file.name);
                return preview;
            }
        },
                
        _createPdfPreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = $($(context.mediaFormDialog.dialog).find("#filebbselector-editpreview-tpl .preview").clone());
                if("name" in file) $(preview).find(".filename").text(file.name);
                return preview;
            }
        },
       
        _createFlashPreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = $($(context.mediaFormDialog.dialog).find("#swfbbselector-editpreview-tpl .preview").clone());
                if("name" in file) $(preview).find(".filename").text(file.name);
                return preview;
            }
        },
                
        _createImagePreview : function(file,dropbox){
            if(file){
                var context = this.getContext();
                var preview = $($(context.mediaFormDialog.dialog).find("#imagebbselector-editpreview-tpl .preview").clone());
                var image = $('img', preview);
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
            context.messageBox.setContent($('<p><span class="ui-icon ui-icon-' + icon + '" style="float:left; margin:0 7px 50px 0;"></span>' + message + '</p>'));

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
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },

        getContext: function() {
            return ( (typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {} );
        },

        destroy: function(){
            var context = this.getContext();

            $(this.element).find('.bbPaneTree').resizable('destroy');

            this._destroyTree();
            this._destroyView();
            context.messageBox.dialog('destroy');
            context.messageBox.remove();
            $(this.element).empty();

            context.selected = null;
            context.treeview = null;
            context.messageBox = null;
            context.availableMedias = null;
            $.Widget.prototype.destroy.apply(this, arguments);

            this.setContext(context);
        }
    })
})(jQuery);
