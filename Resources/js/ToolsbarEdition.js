/*EditionToolsbar*/
BB4.ToolsbarManager.register("editiontb",{
    _settings: {
        mainContainer: "#bb5-editing",
        contentClass : ".bb5-content",
        rootContentSetCls : "rootContentSet",
        siteWrapperId:"#bb5-site-wrapper",
        actionCtnCls : "bb5-ui bb5-content-actions",
        contentHoverClass :".bb5-content-hover",
        actionCtnClass :".bb5-content-actions",
        actionBtnCls :".bb5-button",
        selectedBlockCls : "bb5-content-selected-editMode",
        selectedContentCls: "bb5-content-selected",
        showpageBrowserOnLoad: false,
        webservices: {
            
        },
        siteId : null,
        pageId : null,
        breadcrumbIds : null,
        i18n: {
            alert: bb.i18n.__('toolbar.editing.alert'),
            media_notmatching: bb.i18n.__('toolbar.editing.media_notmatching'),
            save: bb.i18n.__('toolbar.editing.save'),
            close: bb.i18n.__('toolbar.editing.close'),
            'delete': bb.i18n.__('toolbar.editing.delete'),
            notice: bb.i18n.__('toolbar.editing.notice'),
            error: bb.i18n.__('toolbar.editing.error'),
            mediaSelectorLabel: bb.i18n.__('toolbar.editing.mediaSelectorLabel'),
            linkselectorLabel: bb.i18n.__('toolbar.editing.linkselectorLabel'),
            contentSelectorLabel: bb.i18n.__('toolbar.editing.contentSelectorLabel'),
            upload_browser_not_supported: bb.i18n.__('toolbar.editing.upload_browser_not_supported'),
            upload_too_many_file: bb.i18n.__('toolbar.editing.upload_too_many_file'),
            upload_file_too_large: bb.i18n.__('toolbar.editing.upload_file_too_large'),
            upload_only_image_allowed: bb.i18n.__('toolbar.editing.upload_only_image_allowed')
        },
        formInputLinkDataUid: null
    },
    
    resizeInfos :{
        originalContainerHeight : null,
        resizeStep : 0
    },
    
    _context: {
        mediaSelector: null,
        linkSelector: null,
        pageBrowser: null,
        isEnable : false,
        modeEdition: true,
        selectedBlock: null,
        messageBox: null,
        contextMenu: null,
        popupManager: null
    },
    
    _events: {
        ".bb5-ico-tree click" : "pagebrowser",
        ".bb5-ico-new click" : "pagebrowser_newpage",
        ".bb5-ico-clone click" : "pagebrowser_clonepage",
        ".bb5-ico-del click" : "pagebrowser_delpage",
        ".bb5-ico-lib click" : "mediaselector",
        ".bb5-onlyShowBlocksBtn change" : "toggle_edition",
        ".bb5-ico-keyword-tree click" : "showkeywordBrowser"
    },
    
    _init: function() {
        $('.bb5-onlyShowBlocksBtn').attr('checked', true);
        this.contentFormDialog = bb.PopupManager.init().create("contentFormsEditor",{
            title:"",
            autoOpen: false,
            width: 650,
            height: 450,
            zIndex: 601000,
            modal: true,
            resizable: false
        });
        //this._createContextMenu();
        this._initEvents();
        this.keywordBrowser = this._createkeywordBrowser();
    },
    _availableBtns : {
        "bb5-ico-parameter" : {
            btnClass:"bb5-button bb5-ico-parameter bb5-button-square bb5-invert", 
            btnTitle: bb.i18n.__('contentmanager.parameters'), 
            btnCallback : "showContentParams"
        },
        "bb5-ico-edit" : {
            btnClass:"bb5-button bb5-ico-edit bb5-button-square bb5-invert", 
            btnTitle: bb.i18n.__('contentmanager.edit'), 
            btnCallback : "editContent"
        }
    },
    
    
    _contentMenuAction : {
        "showContentParams" : function(contentNode){
            var nodeInfos = {};
            nodeInfos.contentEl = this._context.selectedBlock;
            nodeInfos.contentId = $(this._context.selectedBlock).attr("data-uid");
            nodeInfos.contentType = $(this._context.selectedBlock).attr("data-type");
            var contentEditManager = bb.ManagersContainer.getInstance().getManager("ContentEditionManager");
            contentEditManager.showContentParamsEditor(nodeInfos);
        },
        
        "editContent" :function(){
            this._editContent(this._context.selectedBlock);
            return;
        }
    },
    
    showLinks : function(){
        var bbSelector = $(this._context.linkSelector).data('bbSelector');
        bbSelector.open();
    }, 
    _initEditContentEvent : function(){
        /*moseover on content*/
        /*click on content edit-mode*/
        var self = this;
        var contentSelector = this._settings.contentClass+",."+this._settings.rootContentSetCls;
        $(contentSelector).live("mouseenter",function(e,userData){
            if(!self._context.isEnable) return true;
            e.stopImmediatePropagation();
            var currentTarget = e.currentTarget;
            /*removeallhoverclass*/
            $(self._settings.contentHoverClass).removeClass(self._settings.contentHoverClass.replace(".",""));
            /*addHover for current*/
            $(currentTarget).addClass(self._settings.contentHoverClass.replace(".",""));
        });
     
        $(contentSelector).live("mouseleave",function(e){
            if(!self._context.isEnable) return true;
            var currentTarget = $(e.currentTarget);
            var parentToSelect = $(currentTarget).parent(self._settings.contentClass);
            $(currentTarget).removeClass(self._settings.contentHoverClass.replace(".",""));
            if((parentToSelect) && parentToSelect.length!=0){
                $(parentToSelect).trigger("mouseenter",{
                    userTrigger :true
                });
            }
        });
        
        $(contentSelector).live("click",function(e){
            if(!self._context.isEnable) return true;
            e.stopPropagation();
            //_hideContextMenu();
            var currentContent = e.currentTarget;
            self._selectNodeContent(currentContent);
            return true;
        });
    },
    
    _selectNodeContent : function(currentContent){
        bb.ManagersContainer.getInstance().getManager("ContentEditionManager").selectContent(currentContent,false); //false --> don't scoll to content
        this._updateselectedBlock(currentContent);
        //this._showActionsForContent(currentContent);
        return;
    /*
        var path = _getContentPath(currentContent);
        var pathInfos = {
            selectedNode : currentContent, 
            items : path,
            itemTitleKey : "data-type",
            itemClass : ".contentNodeItem",
            itemIdKey : "data-uid"
        };
        $(document).trigger("content:ItemClicked",[pathInfos]);*/
    },
    
    _updateselectedBlock : function(content){
       
        if(this._context.selectedBlock){
            $(this._context.selectedBlock).removeClass(this._settings.selectedBlockCls);
        }
        this._context.selectedBlock = ($(content).length) ? $(content) : false;
        if(this._context.selectedBlock){
            $(this._context.selectedBlock).addClass(this._settings.selectedBlockCls);  
        }
         
    },
    
    _buildContentActions : function(){
        
        var btnsContainer = $("<div></div>").clone();
        btnsContainer.addClass(this._settings.actionCtnCls);
        var self = this;
        $.each(this._availableBtns,function(key,btnConfig){
            var btn = $("<button></button>").clone();
            $(btn).addClass(btnConfig.btnClass).attr("title",btnConfig.btnTitle);
            $(btn).attr("data-type",key);
            $(btn).bind("click",$.proxy(self._contentMenuAction[btnConfig.btnCallback],self));
            $(btnsContainer).append($(btn));
        });
        return btnsContainer;
    },
    
    _actionsBeforeRender : function(selectedBlock,actionsBar){
        var newContent = actionsBar;
        if($(selectedBlock).hasClass(this._settings.rootContentSetCls)){
            $(actionsBar).find("."+this._settings.delBtnCls).remove();
        } 
        return newContent;
    },
     
    _getselectedBlock : function(){
        return this._context.selectedBlock;
    },
     
    _showActionsForContent : function(clickedContent){
        /*hideAction*/
        $(this._settings.actionCtnClass).remove(); //remove previous actions
        this._updateselectedBlock(clickedContent);
        var actionbar = this._buildContentActions(); //addFilters?
        var contentAction = this._actionsBeforeRender(clickedContent,actionbar);
        $(clickedContent).css("position","relative");
        $(contentAction).css("position","absolute");
        $(clickedContent).append(contentAction);
    },
    
    
    _initEvents: function() {
        var self = this;
        /* Aborescence */
        this._callbacks["pagebrowser_action"] = function(){
            //if(!this._context.isEnable) return false;
            
            if (self._context.pageBrowser)
                self._context.pageBrowser.dialog('open');
        };
        
        /* Créer une nouvelle page */
        this._callbacks["pagebrowser_newpage_action"] = function(){
            //if(!this._context.isEnable) return false;  
            var context = $('#bb5-dialog-treeview').bbPageBrowser('getContext');
            $('#bb5-dialog-treeview').bbPageBrowser('editPage', null, context.selected, function(node) {
                $('#bb5-dialog-treeview').bbPageBrowser('browsePage', node.attr.id.replace('node_',''));
            }, 'toolbar');
        };
        
        this._callbacks["pagebrowser_clonepage_action"] = function () {
            var context = $('#bb5-dialog-treeview').bbPageBrowser('getContext');
            //console.log(context.selected);
            //console.log(context.site);
            $('#bb5-dialog-treeview').bbPageBrowser('clonePage', context.selected, function(node) {
                $('#bb5-dialog-treeview').bbPageBrowser('browsePage', node.attr.id.replace('node_',''));
            });
        };
		
        /* Supprimer la page  courante */
        this._callbacks["pagebrowser_delpage_action"] = function(){
            return bb.StatusManager.getInstance().remove();
        };
        
        /* Médiathèque */
        this._callbacks["mediaselector_action"] = function(){
            //if(!this._context.isEnable) return false;
            if (self._context.mediaSelector) {
                self._context.mediaSelector.bbSelector('setCallback', null);
                self._context.mediaSelector.bbSelector('open');
            }
        };
        
        this._callbacks["toggle_edition_action"] = function(){
            self.toggleEditionMode($('.bb5-onlyShowBlocksBtn').attr('checked'));
        };
        
        this._callbacks["showkeywordBrowser_action"] = function(){
            this.keywordBrowser.show();
        }
        
        /*afficher */
        this._callbacks["showLinks_action"] = $.proxy(this.showLinks,this);
        /*initContent*/
        this._initEditContentEvent();
        
       
        /*dont follow any link*/
        $(this._settings.contentClass+" a").bind("click",function(e){
            if(self._context.isEnable) e.preventDefault(); //désactiver les liens en mode édition
            return true;
        });
        
        /*bind close dialog*/
        this.contentFormDialog.on("close",function(){
            $(this.dialog).find('input[value$="Element\\\\image"]').each(function(){
                $(this).bbMediaImageUpload('destroy');
            });
            $(this.dialog).children().remove();            
        });
        
        /*change all image*/
        $('[data-uid]').live('contentchange.image', function(e,data){
            if (typeof data != 'object')
                return ;
            e.stopPropagation();
            var prevItem = $(this);
            var nextItem = data.media;
            self.selectMedia(prevItem,nextItem);
        });
    },
    _createkeywordBrowser : function(){
        if($("#keywordbrowser")){
            var container = $("<div id='keywordbrowser'></div>").clone(); 
        }
        $("body").append(container);
        var keywordBrowser = $(container).bbKeywordBrowser({
            title:"Keywords"
        }).bbKeywordBrowser("getWidgetApi");
        return keywordBrowser;
    },
    
    _beforeShowDatepicker : function(dateField,dpInfos){
        $(dpInfos.dpDiv).css('z-index', 901021);
        $(dpInfos.dpDiv).addClass('bb5-ui bb5-dialog-wrapper');
    },
    
    _updateDateField : function(dp){
        var selectedDate = (typeof $(this).val() == "string" ) ? $(this).val().length : false;
        var timestamp = "";
        var dateNow         = new Date();
        var hoursNow        = parseInt(dateNow.getHours());
        var minutesNow      = parseInt(dateNow.getMinutes());
        if(selectedDate){
            var date = new Date(dp.selectedYear,dp.selectedMonth,parseInt(dp.selectedDay), hoursNow, minutesNow);
            timestamp = date.getTime()/1000; //get ts in sec instead of milisec
        }
        
        $(this).attr("data-value",timestamp);
        $(this).trigger("change");
    },
    
    _updateDataBeforeSubmit: function() {
        $('.date_pick_form').each(function (index, item) {
            var dateTime = $(item).attr("data-value");
            $(item).val(dateTime);
        });
    },
    
    _initDateWidgets : function(){
        var self = this;
        /*modifierdaterange*/
        $('.date_pick_form').datepicker({
            dateFormat:"dd/mm/yy",
            changeMonth: true,
            changeYear: true,
            beforeShow : $.proxy(this._beforeShowDatepicker,this),
            onClose : function(selectedDate,dp){
                self._updateDateField.call(this,dp);
            }
        });
    },

    
    bbSelectorHandlers :{
        openHandler : function(selectorType,e,ui){
            var selectorType = selectorType || "none"; 
            var widget = $(this._context.linkSelector).bbSelector("getWidget",selectorType);  
            if(widget){
                $(widget).data()["bbPageSelector"].fixContainersHeight();
            }
        },      
        resizeStartHandler:function(selectorType,e,ui){
            this.resizeInfos.originalContainerHeight = $(e.target).height();
            this.resizeInfos.resizeStep = 0;
        },
        resizeHandler : function(selectorType,e,ui){
            var selectorType = selectorType || "none"; 
            var delta =  $(e.target).height() - this.resizeInfos.originalContainerHeight;
            var deltaStep = delta - this.resizeInfos.resizeStep;
            var widget = $(this._context.linkSelector).bbSelector("getWidget",selectorType);
            if(widget){
                $(widget).data()["bbPageSelector"].updateListSize(deltaStep);  
            }
            this.resizeInfos.resizeStep = delta;
        }
    },
    
    activate: function() {
        var self = this;
        if(this.isActivated) return;
        // Messagebox
        if (!this._context.popupManager) {
            this._context.popupManager = bb.PopupManager.init({}); 
            this.alerteBox = this._context.popupManager.create("alertDialog",{
                dialogType : this._context.popupManager.dialogType.ALERT,
                title: bb.i18n.__('toolbar.editing.alert'),
                buttons : {
                    "Fermer" : {
                        text: bb.i18n.__('popupmanager.button.close'),
                        click: function(){
                            self.alerteBox.close(); //?
                        }
                    }
                }
            });
        }
        
        // Sélecteur de liens
        if (!this._context.linkSelector) {
            var selectorLink = bb.i18n.__('toolbar.editing.linkselectorLabel');
            var linkContainer = $('<div id="bb5-dialog-link-selector" class="bb5-selector-wrapper"></div>').clone();
            this._context.linkSelector = $(linkContainer).bbSelector({
                popup: true,
                pageSelector: true,
                linkSelector: true,
                mediaSelector: false,
                contentSelector : false,
                selectorTitle : selectorLink,
                resizable: false,
                //site: bb.frontApplication.getSiteUid(),
                callback: function(item) {
                    $('#bb5-dialog-link-selector').bbSelector('close');
                },
                beforeWidgetInit:function(){
                    var bbSelector = $(this.element).data('bbSelector');
                    /*for internal link*/
                    bbSelector.onWidgetInit(bbSelector._panel.INTERNAL_LINK, function () { 
                        var bbPageSelector = $(this).data('bbPageSelector') || false;
                        if(bbPageSelector){
                            bbPageSelector.setCallback(function (params) {
                                $('input#' + self._settings.formInputLinkDataUid).val(params.value);
                                bbSelector.close();
                            }); 
                        }
                    });
                    /*for External link*/
                    bbSelector.onWidgetInit(bbSelector._panel.EXTERNAL_LINK, function () {
                        var bbLinkSelector = $(this).data('bbLinkSelector');
                        bbLinkSelector.setCallback(function (params) {
                            $('input#' + self._settings.formInputLinkDataUid).val(params.value);
                            bbSelector.close();
                        });
                    });
                }
                
            //open: $.proxy(self.bbSelectorHandlers.openHandler,self,"bbLinkInternalContainer"),
            //resizeStart: $.proxy(this.bbSelectorHandlers.resizeStartHandler,this,"bbLinkInternalContainer"),
            //resize : $.proxy(this.bbSelectorHandlers.resizeHandler,this,"bbLinkInternalContainer")
            });
        }
      
        $("form .bb5-ShowBBSelector").die().live('click', function () {
            self._settings.formInputLinkDataUid = $(this).attr('data-uid');
            var bbSelector = $(self._context.linkSelector).data('bbSelector');
            bbSelector.close();
            bbSelector.open();
        });
        
        // Sélecteur de médias
        if (!this._context.mediaSelector) {
            // console.log(this._settings.i18n);
            var mediaSelectorLabel = bb.i18n.__('toolbar.editing.mediaSelectorLabel');
            this._context.mediaSelector = $('#bb5-dialog-media-selector').bbSelector({
                popup: true,
                pageSelector: false,
                linkSelector: false,
                mediaSelector: true,
                contentSelector : false,
                resizable: false,
                draggable: false,
                selectorTitle: mediaSelectorLabel,
                //site: bb.frontApplication.getSiteUid(),
                callback: function(item) {
                    $('#bb5-dialog-media-selector').bbSelector('close');
                }
            });
        }
        
        // Arborescence
        if (!this._context.pageBrowser) {
            this._context.pageBrowser = $('#bb5-dialog-treeview').bbPageBrowser({
                popup: {
                    width: 220,
                    height: 500,
                    position: [0, 60]
                },
                editMode: true,
                site: this._settings.siteId,
                breadcrumb: this._settings.breadcrumbIds,
                ready: function() {
                },
                select: function(e, data)  {
                    
                }
            });
        } else {
            if(this._settings.showpageBrowserOnLoad){
                this._context.pageBrowser.dialog('open');
            }
            
        }
        
        // Contextmenu
        //var contentSelector = this._settings.contentClass;
        this.isActivated = true; 	
        return self;
    },
	
    /*all resizeHandler*/     
        
    enable: function() {
        var self = this;
        this.toggleEditionMode($('.bb5-onlyShowBlocksBtn').attr('checked'));
        
        self.activate();
		
        $('[data-uid]').die("contentchange.content").live('contentchange.content', function(e) { 
            var content = $(this);
            
            e.stopPropagation();
            
            bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                params: {
                    name: $(this).attr('data-type'),
                    mode: (($(this).attr('data-rendermode').length > 0) ? $(this).attr('data-rendermode') : null),
                    uid: $(this).attr('data-uid')
                },

                success: function(result) {
                    if (result.result) {
                        content.replaceWith($(result.result.render));
                        bb.ManagersContainer.getInstance().getManager("ContentManager").initDroppableImage($('[data-uid="' + content.attr('data-uid') + '"]'));

                        /*n'activer aloha que si l'onglet contenu est sélectionné*/
                        if(self._context.isEnable){
                            self.disable();
                            self.enable();
                        }
                    }
                },

                error: function(result) {
                }
            });
        });
        
        
        /*click on body*/
        $('body').bind('click.contentEdit', function(e){
            if(!self._context.isEnable) return;
            if($(e.target).hasClass("contentNodeItem")) return;
            self._hideContextMenu();
            return true;            
        });
        
                                
        // Upload drag'n'drop
        $('[data-type^="Media\\\\"]').live('mouseover', function() {
            $(this).addClass('aloha-editable-active');
        });
                                
        $('[data-type^="Media\\\\"]').live('mouseout', function() {
            $(this).removeClass('aloha-editable-active');
        });
         
        $('[data-type^="Media\\\\"]').live('click', function(e) {
            e.stopPropagation();
            var clickCallback = $.proxy(self.selectMedia,self,e.currentTarget);
            self._context.mediaSelector.bbSelector('setCallback',clickCallback);
            /*addFilter if needed*/
            self._context.mediaSelector.bbSelector('open');
            return false;
        });
        
        //        $('img[data-type="Element\\\\image"][data-library=""]').each(function () {
        //            self.mediaImageUploadFn(this)
        //        });
        //        
        //        $('p[data-type="Media\\\\image"]').each(function () {
        //            self.mediaImageUploadFn(this)
        //        });
        //        
        //        $('img[data-type="Element\\\\file"][data-library=""]').each(function () {
        //            self.mediaImageUploadFn(this)
        //        });
        //        
        //        $('p[data-type="Media\\\\file"]').each(function () {
        //            self.mediaImageUploadFn(this)
        //        });
        
        $('*[data-type="Media\\\\image"]').each(function () {
            self.mediaImageUploadFn(this)
        });
        $('*[data-type="Media\\\\file"]').each(function () {
            self.mediaImageUploadFn(this)
        });

        /* $(this._settings.contentClass).bind('contextmenu', function(e) {
            self._context.selectedBlock = $(this);
            e.stopPropagation();
            e.preventDefault();
            var contextMenu = self._showContextMenu(e, self._context.selectedBlock);
            return true;
        });*/
        
        /*context menu*/
        if(!this._contextMenu){
            this._contextMenu = bb.ManagersContainer.getInstance().getManager("ContentEditionManager").getContextMenu();
            this._contextMenu.enable();
        }else{
            this._contextMenu.enable();
        }

        // context menu
        // $('body').append($('<div class="bb-edition-context-menu"/>'));
            
        // Actions
        /*var actionContainer = $('<ul></ul>').clone();
        actionContainer.append($('<li class=""><ins class="edit">&nbsp;</ins><a href="#" rel="edit">Edition</a></li>'));
        $(actionContainer).find('a').attr('href', 'javascript:;');
        $('.bb-edition-context-menu').append(actionContainer);
        $('.bb-edition-context-menu').appendTo($('body'));
        $('.bb-edition-context-menu').hide();
        
        this._context.contextMenu = $('.bb-edition-context-menu');
            
        $('.bb-edition-context-menu a[rel="edit"]').live('click', function(e) {
            self._editContent(self._context.contextMenu.data('content'));
        });*/
         
        AlohaManager.applyAloha();
        this._context.isEnable = true;   
    },
    
    handleNewBbMediaContent : function(prevMedia,newMedia){
        var bbPrevContent = $bb(prevMedia);
        var prevContentClass = $(bbPrevContent.contentEl).attr("class");
        var newContent = $(newMedia);
        newContent.attr("class",prevContentClass);
        var prevSrc = $(newContent).find("img").eq(0).attr("src");
        var ctime = new Date();
        $(newContent).find("img").eq(0).attr("src",prevSrc+"?"+ctime.getTime());
        var nodeName = bbPrevContent.get("element");
        var nodeParent = bbPrevContent.get("parent");
        $(newContent).attr("data-element",nodeName);
        $(newContent).attr("data-parent",nodeParent);
        $(prevMedia).replaceWith(newContent);
        bbPrevContent.setContentEl(newContent);
        bbPrevContent.parentNode.updateData();
    },
    
    mediaImageUploadFn : function(el) {
        var self  = this;
        var successCallback = function(el,file,result) {
            if (result.result.uid) {
                bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                    params: {
                        name: $(el).attr('data-type'),
                        mode: (($(el).attr('data-rendermode').length > 0) ? $(el).attr('data-rendermode') : null),
                        uid: result.result.uid
                    },
                    success: function(response) {
                        if (response.result) {
                            if($(el).bbMediaImageUpload){
                                $(el).bbMediaImageUpload('destroy');  
                            }
                            self.handleNewBbMediaContent(el,response.result.render);
                            //$(el).replaceWith(response.result.render);
                            self.mediaImageUploadFn($('[data-uid="' + $(response.result.render).attr('data-uid') + '"]'));
                        }
                    },

                    error: function(result) {
                    }
                });
            }
        };
        successCallback = $.proxy(successCallback,this,el);
        $(el).bbMediaImageUpload({
            media_uid: $(el).attr('data-uid'),
            media_classname: $(el).attr('data-type'),
            
            ready: function(){},
            uploadFinishedSuccess: successCallback,
            uploadFinishedError: function(file, result) {},
       
            uploadError: function(file, message) {
            },
        
            uploadStarted: function(i, file, len) {
            },
        
            dragOver: function(e) {
                $(e.target).addClass('aloha-editable-active');
            },
       
            dragLeave: function(e) {
                $(e.target).removeClass('aloha-editable-active');
            },
        
            drop: function(e) {
                $(e.target).removeClass('aloha-editable-active');
            }
        });
    },
    selectMedia:  function(prevItem,item) {
        var myself = prevItem;
        var bbPrevContent = $bb(prevItem);        
        var self = this;
        this._context.mediaSelector.bbSelector('close');
        if (item.data.content.classname.replace('BackBuilder\\ClassContent\\', '') == $(myself).attr('data-type')) {
            bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                params: {
                    name: $(myself).attr('data-type'),
                    mode: (($(myself).attr('data-rendermode').length > 0) ? $(myself).attr('data-rendermode') : null),
                    uid: item.uid
                },

                success: function(response) {
                    if (response.result) {
                        if($(myself).bbMediaImageUpload){
                            $(myself).bbMediaImageUpload('destroy');
                        }
                        self.handleNewBbMediaContent(myself,response.result.render);
                        self.mediaImageUploadFn($('[data-uid="' + $(response.result.render).attr('data-uid') + '"]'));
                    }
                },

                error: function(result) {
                }
            });
        } else {
            this.alerteBox.setContent($('<span/>').html(bb.i18n.__('toolbar.editing.media_notmatching')));
            this.alerteBox.show();
        }
        return false;
    },
    
    toggleEditionMode: function(edition) {
        var self = this;
        this._context.modeEdition = ((edition) ? true : false);
        
        $(document).trigger("content:toggleEditionMode", this._context.modeEdition);
		
        /*radical blaze*/
        $.each(this._getEditionBlocks(false), function() {
            if (self._context.modeEdition) $(this).show();
            else $(this).hide();
        });
    },
    
    _editContent: function(content) {
        var myself = this;
        var content = $(content);
        
        /*persit changes before edit*/
        
        bb.ContentWrapper.persist(false); //async persist
        bb.webserviceManager.getInstance('ws_local_contentBlock').request('getContentEditionForm', {
            params: {
                contentType: content.attr('data-type'),
                contentUid: content.attr('data-uid')
            },

            success: function(result) {
                if (result.result) {
                    var form = $('<form>' + myself._renderContentForm([result.result]) + '</form>');
                    $(form).find("input").focus();
                    var dialog = myself.contentFormDialog.dialog;
                    $(form).find('legend').parent().css({
                        'border' : '1px solid #CCC'
                    });
                  
                    $(form).find("input:checkbox").each(function() {
                        var div = $('div[id="' + $(this).attr('id').replace('_delete', '') + '_container"]');
                        if ($(this).is(':checked'))
                            div.hide();
                        else
                            div.show();
                    });
                    
                    $(form).find('input:checkbox').bind('click', function(e) {
                        var div = $('div[id="' + $(this).attr('id').replace('_delete', '') + '_container"]');
                       
                        if ($(this).is(':checked'))
                            div.hide('blind');
                        else
                            div.show('blind');
                       
                        return true;
                    });
                    
                    // Image upload
                    $(form).find('input[value$="Element\\\\image"]').each(function() {
                        var dropbox = $(form).find('div[id="' + $(this).attr('id').replace('_type', '') + '_container"]');
                        bb.uploadManager.getInstance('ws_local_media').filedrop('uploadImage' , {
                            paramname: 'image',
                            maxfiles: 1,
                            maxfilesize: bb.config.maxFileSize,
                            data: {
                            },

                            uploadFinished:function(i, file, response) {
                                myself.contentFormDialog.btnEnable();
                                if (!response.error) {
                                    $.data(file).addClass('done');
                                    $(form).find('input[name="' + dropbox.attr('id').replace('_container', '') + '"]').val(JSON.stringify(response.result));
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
                                if(!file.type.match(/^image\//)) {
                                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), bb.i18n.__('toolbar.editing.upload_only_image_allowed'), 'alert');
                                    return false;
                                }
                            },

                            uploadStarted:function(i, file, len) {
                                myself.contentFormDialog.btnDisable();
                                dropbox.empty();
                                var preview = $(dropbox.parent().find('#imagebbselector-editpreview-tpl').find(".preview").clone()), 
                                image = $('img', preview); 
                               
                                var reader = new FileReader();

                                reader.onload = function(e){
                                    image.attr('src', e.target.result);
                                };
                                reader.readAsDataURL(file);

                                preview.appendTo(dropbox);
                                $.data(file, preview);
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
                    });
                   
                    myself.contentFormDialog.setContent($(form));                  
                    var buttons = {};
                    myself.initAutoComplete(result.result);
                    buttons[bb.i18n.__('toolbar.editing.save')] = function() {
                        myself._updateDataBeforeSubmit();
                        var serialize = function(content) {
                            var values = {};
                            $.each(content, function (key, item) {
                                $.each(item.bb5_form, function(index, value) {
                                    values[index] = new Array();
                                    if (value.bb5_type != "BackBuilder\\ClassContent\\Element\\keyword") {
                                        if (!value.bb5_fieldset) {
                                            if (value.bb5_uid) {
                                                for (var i=0; i< value.bb5_uid.length; i++) {
                                                    values[index][i] = {};
                                                    values[index][i].type = $(dialog).find('[id="' + value.bb5_uid[i] + '_type"]').val();
                                                    values[index][i].value = $(dialog).find('[id="' + value.bb5_uid[i] + '"]').val();
                                                    values[index][i]['delete'] = $(dialog).find('#[id="' + value.bb5_uid[i] + '_delete"]').is(':checked');
                                                    values[index][i]['parameters'] = [];
                                                    //console.log(values[index][i]['parameters'], 'id="' + value.bb5_uid[i] + '_container');
                                                    var paramfields = $(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter');
                                                    var key = i;
                                                    jQuery.each(paramfields,function(i,field){
                                                        var fieldParams = {};
                                                        fieldParams.name = $(field).attr("data-param");
                                                        var inputField = $(field).find("input.param");
                                                        var value = "";
                                                        if(inputField.length > 1){
                                                            value = $(inputField).filter(":checked").val(); 
                                                        }else{
                                                            value = inputField.val();  
                                                        }
                                                        fieldParams.value = value;
                                                        values[index][key]['parameters'].push(fieldParams);
                                                    });
                                                    
                                                /*if ($(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter').attr('data-param')) {
                                                        values[index][i]['parameters'] = {
                                                            'name': $(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter').attr('data-param'), 
                                                            'value': $(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter input:checked.param').val()
                                                        };
                                                    //console.log(values[index][i]['parameters']);
                                                    }*/
                                                }
                                            } else {
                                                values[index][0] = {};
                                                values[index][0].type = 'scalar';
                                                values[index][0].value = $(dialog).find('[id="' + item.bb5_uid + '_' + index + '"]').val();
                                                values[index][0]['delete'] = $(dialog).find('[id="' + item.bb5_uid + '_' + index + '_delete"]').is(':checked');
                                                values[index][0]['parameters'] = {};
                                                if ($(dialog).find('[id="' + item.bb5_uid + '_container"] .parameter').attr('data-param'))
                                                    values[index][0]['parameters'] = {
                                                        'name': $(dialog).find('[id="' + item.bb5_uid + '_container"] .parameter').attr('data-param'), 
                                                        'value': $(dialog).find('[id="' + item.bb5_uid + '_container"] .parameter input:checked.param').val()
                                                    };
                                            }
                                        }
                                        else {
                                            $.each(value.bb5_value, function(i, bb5_element) {
                                                values[index][i] = {};
                                                values[index][i].type = $(dialog).find('[id="' + bb5_element.bb5_uid + '_type"]').val();
                                                values[index][i]['delete'] = $(dialog).find('[id="' + bb5_element.bb5_uid + '_delete"]').is(':checked');
                                            //                                              values[index][i].form = serialize(bb5_element.bb5_form);
                                            });
                                            values[index][0].form = serialize(value.bb5_value);
                                        }
                                    } else {
                                        $('form input[name="keywords"]').each(function (indexKey, keyword) {
                                            //console.log(keyword);
                                            var uidNode = $(keyword).attr('data-uid-keyword');
                                            values[index][indexKey] = {};
                                            values[index][indexKey].type = 'BackBuilder\\ClassContent\\Element\\keyword';
                                            values[index][indexKey].uid = uidNode;
                                            values[index][indexKey].value = $(keyword).val();
                                            values[index][indexKey]['delete'] = $(dialog).find('[id="' + uidNode + '_delete"]').is(':checked');
                                            values[index][indexKey]['parameters'] = [];
                                        });
                                        
                                    }
                                });
                            
                            
                            });
                            return values;
                            
                        };
                        
                        
                        
                        $(dialog).parents('.ui-dialog:first').mask(bb.i18n.__('loading'));
                        bb.webserviceManager.getInstance('ws_local_contentBlock').request('postContentEditionForm', {
                            params: {
                                contentType: content.attr('data-type'),
                                contentUid: content.attr('data-uid'),
                                contentValues: JSON.stringify(serialize([result.result]))
                            },
                
                            success: function(result) {
                                $(dialog).parents('.ui-dialog:first').unmask();
                                myself.contentFormDialog.close();
                                if (result.result) {
                                    $('[data-uid="' + content.attr('data-uid') + '"]').trigger('contentchange');
                                }
                            },
                
                            error: function(result) {
                                $(dialog).parents('.ui-dialog:first').unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };
                    buttons[bb.i18n.__('toolbar.editing.close')] = function() {
                        myself.contentFormDialog.close();
                    };
                    
                    $(dialog).dialog('option','title', result.result.name);
                    $(dialog).dialog('option', 'buttons', buttons );
                    myself.contentFormDialog.show();
                    // date for form
                    myself._initDateWidgets();
                }    
            },
                
            error: function(result) {
                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
            }
        });
    },
    
    getDataKeyWordsFormated: function (cond) {
        var options = []
        bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataKeywordsAutoComplete', {
            params: {
                where: cond
            },
            async: false,
            success: function(result) {
                //console.log(result);
                $.each(result.result, function (index, item) {
                    var obj = {};
                    obj[item.uid] = item.name;
                    options.push(obj); 
                });
                options = result.result;
            },
            error: function (error) {
                

            }
        });
        return options;
    },
    
    _renderContentForm: function(content, isLoaded) {
        
        var myself = this,
        form = '';
        
        form += '<fieldset>';
        
        if (typeof isLoaded != 'undefined') {
            form += '<legend>' + content.name + '</legend>';
            form += '<input id="' + content.uid + '_type" type="hidden" value="' + content.type + '">';
            form += '<input id="' + content.uid + '_delete" type="checkbox" ' + (!isLoaded ? 'checked="checked"' : '') + ' style="margin-bottom: 25px;"><label for="' + content.uid + '_delete">&nbsp;' + bb.i18n.__('toolbar.editing.delete') + '</label>';
        }
        else{
            if($.isArray(content)){
                form +="<legend>"+content[0].bb5_name+"</legend>";
            }
        }
        $.each(content, function (key, item) {
            if (true == item.bb5_media) {
                form += '<input id="' + item.bb5_uid + '_delete" type="checkbox" style="margin-bottom: 25px;"><label for="' + item.bb5_uid + '_delete">&nbsp;' + bb.i18n.__('toolbar.editing.media_detach') + '</label>';
            }
            form += '<div id="' + item.bb5_uid + '_container">';
            $.each(item.bb5_form, function(index, value) {
                if (index == "keywords")
                    form += '<label style="font-weight: bold;">'+bb.i18n.__('contentmanager.keyword')+'</label><div class="contentNewKeywords"></div>'+bb.i18n.__('contentmanager.keyword')+': <input class="typeahead" type="text" name="serchKeyWord" value="" /><br />';
                if (value.bb5_type != 'BackBuilder\\ClassContent\\Element\\keyword') {
                    if (!value.bb5_fieldset) {
                        if (value.bb5_uid) {
                            $.each(value.bb5_value, function (keyResult, itemResult) {
                                form += itemResult;
                            });
                        } else {
                            $.each (value.bb5_value, function (indexValue, itemValue) {
                                //console.log(indexValue, itemValue);
                                form += '<label for="' + item.bb5_uid + '_' + index + '" style="font-weight: bold;">' + index +' '+item.bb5_name+'</label>';
                                form += '<div style="text-align: right;"><input id="' + itemValue.bb5_uid + '_' + index + '_delete" type="checkbox" ' + ((!value.isLoaded) ? 'checked="checked"': '') + '><label for="' + item.bb5_uid + '_' + index + '_delete" style="min-width: 0px;">&nbsp;' + myself._settings.i18n['delete'] + '</label></div>';
                                form += '<div id="' + item.bb5_uid + '_' + index + '_container">';
                                if (index == "date")
                                    form += '<input class="date_pick_form" id="' + item.bb5_uid + '_' + index + '" name="' + item.bb5_uid + '_' + index + '" type="text" value="' + itemValue + '" style="width: 100%; margin-bottom: 10px;"/>';
                                else if (value.bb5_type == "BackBuilder\\ClassContent\\Element\\link")
                                    form += '<label>target</label><input type="checkbox" value="0" name="target">';
                                else {
                                    if ('value' != index)
                                        form += '<input id="' + item.bb5_uid + '_' + index + '" name="' + item.bb5_uid + '_' + index + '" type="text" value="' + itemValue + '" style="width: 100%; margin-bottom: 10px;"/>';
                                    else
                                        form += '<textarea id="' + item.bb5_uid + '_' + index + '" name="' + item.bb5_uid + '_' + index + '" type="text" style="width: 100%; height: 250px; margin-bottom: 10px;">' + itemValue + '</textarea>';
                                }
                                form += '</div>';
                            });
                        }
                    }
                    else {
                        form += myself._renderContentForm(value.bb5_value, value.isLoaded);
                    }
                } else {
                    $.each(value.bb5_value, function (keyResult, itemResult) {
                        form += itemResult;
                    });
                }
            });
        });
        
        form += '</div>';
        form += '</fieldset>';
        return form;          
    },
    
    updateContent: function (content, item) {
        if (content.bb5_form.keywords.bb5_value) {
            content.bb5_form.keywords.bb5_value.push(item);
            content.bb5_form.keywords.bb5_type = "BackBuilder\\ClassContent\\Element\\keyword";
        }
        else {
            var obj = {
                bb5_value: [], 
                bb5_type: "BackBuilder\\ClassContent\\Element\\keyword"
            };
            obj.bb5_value.push(item);
            content.bb5_form['keywords'] = obj;
        }
    },
    
    removeNewKeyword: function () {
        $('.newKeywordAutoComplete').live('click', function () {
            $(this).parents('.alert-success').remove();
        });
    },
    
    initAutoComplete: function (content) {
        var myself = this;
        var cpt = 0;
        $('input.typeahead').bind('keyup', function () {
            var inputVal = $(this).val();

            if (inputVal.length >= 2) {
              
                //$('input.typeahead').autocomplete("destroy");
                $('input.typeahead').autocomplete({
                    source: myself.getDataKeyWordsFormated(inputVal),
                    select: function (ev, ui) {
                    var uidInput = $(this).val('');
                    cpt++;
                    $(this).parents('div#' + ui.item.value + '_container').append('<div class="removeKeyword" data-uid="' + ui.item.value + '">' + ui.item.label + '</div>');
                    var form = "";
                    form += '<div class="alert alert-success">' + ui.item.label + '<input type="hidden" data-uid-keyword="" name="keywords" value="' + ui.item.value + '"/><button type="button" class="close newKeywordAutoComplete">×</button><div>';
                    $('.contentNewKeywords').append(form);
                    myself.updateContent(content, form);
                    myself.removeNewKeyword();
                    return false;
                    },
                    focus:function(ev,ui){
                    return false;
                    }
                    }).data( "autocomplete" )._renderItem = function( ul, item ) {
                    return $( "<li class=\"keywordSelected\"></li>" )
                    .data( "item.autocomplete", item )
                    .append( "<a>" + item.label + "</a>" )
                    .appendTo( ul );
                };
            }
        });
        $('.removeKeyword').live('click', function () {
            var data_uid = $(this).attr('data-uid');
            $('input[name="'+ data_uid + '_type"]').remove();
            $(this).remove();
        });
        
        return content;
    },
    
    _showMessage: function(title, message, icon) {
        var myself = this;
        
        if (!myself._context.messageBox) {
            $('body').append($('<div id="bb5-ui-edition-message"/>')); 
            myself._context.messageBox = $('#bb5-ui-edition-message');
        }
            
        myself._context.messageBox.html('<p><span class="ui-icon ui-icon-' + icon + '" style="float:left; margin:0 7px 50px 0;"></span>' + message + '</p>');
           
        myself._context.messageBox.dialog({
            title: title,
            dialogClass: 'bb5-dialog-wrapper',
            autoOpen: true,
            width: 350,
            height: 'auto',
            modal: true,
            resizable: false,
            buttons: {},
            close: function() {
            }
        });
            
        var buttons = {};
        buttons[bb.i18n.__('toolbar.editing.close')] = function() {
            myself._context.messageBox.dialog('close');
        };
                    
        myself._context.messageBox.dialog('option', 'buttons', buttons );
    },
        
    _getEditionBlocks: function(isLoaded) {
        var self = this;
        var blocks = [];
        
        $('[data-uid]').each(function() {
            if ( ($(this).attr('data-type') != 'ContentSet') && (!$(this).attr('contenteditable')) && (!$(this).hasClass(self._settings.contentClass.replace('.', ''))) ) {
                
                if (typeof isLoaded != 'undefined') {
                    if ( $(this).attr('data-isloaded') == ((isLoaded) ? 'true' : 'false') ){
                        blocks.push($(this));
                    }       
                } else {
                    blocks.push($(this));
                }
            }
        });
        
        return blocks;
    },
    
    _hideContextMenu: function() {
        if(this._contextMenu){
            this._contextMenu.hide();
        }
    },
    
    _unSelectContent:function(){
        $(this._settings.actionCtnClass).remove();
        if(this._context.selectedBlock){
            $(this._context.selectedBlock).removeClass(this._settings.selectedBlockCls);
            $(this._context.selectedBlock).removeClass(this._settings.selectedContentCls);
            this._context.selectedBlock = null;
        }    
    },
    
    _showContextMenu: function(e, content) {
        this._hideContextMenu();
        
        this._context.contextMenu.show();
        
        var position = {
            left:e.pageX,
            top:e.pageY
        };
            
        this._context.contextMenu.css({
            border: '1px solid red',
            width: '150px',
            position: 'absolute',
            top: position.top + 'px',
            left: position.left + 'px',
            background : 'white'
        });
        
        // Data
        this._context.contextMenu.data('content', content)
    },
    
    deactivate: function() {
        if (this._context.pageBrowser) {
        //this._context.pageBrowser.dialog('close');
        }
        this.isActivated = false;
    },

    disable: function() {
       
        AlohaManager.stop(); 
        //$('[data-uid]').die('contentchange');
         
        if (this._context.mediaSelector) {
            $('[data-type^="Media\\\\"]').die('mouseover');            
            $('[data-type^="Media\\\\"]').die('mouseout');                
            $('[data-type^="Media\\\\"]').die('click');         
            $('img[data-type="Element\\\\image"]').bbMediaImageUpload('destroy');
            $('[data-type="Media\\\\image"]').bbMediaImageUpload('destroy');
        }
        
        this._context.isEnable = false;
        //var contentSelector = this._settings.contentClass;
        //$('body.blockEdit').unbind('click');
        if(this._contextMenu){
            this._contextMenu.disable();
        }
        this._unSelectContent();
        $('body').unbind('click.contentEdit');
        this.isActivated = false;
    }
});



/*Edit contentset to show the contentLibrary*/
