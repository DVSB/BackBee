    /*EditionToolsbar*/
    (function($) {
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
            ".bb5-ico-edit click" : "pagebrowser_editpage",
            ".bb5-ico-clone click" : "pagebrowser_clonepage",
            ".bb5-ico-del click" : "pagebrowser_delpage",
            ".bb5-ico-lib click" : "mediaselector",
            ".bb5-onlyShowBlocksBtn change" : "toggle_edition",
            ".bb5-ico-keyword-tree click" : "showkeywordBrowser"
        },

        _init: function() {
            bb.jquery('.bb5-onlyShowBlocksBtn').attr('checked', true);
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
                nodeInfos.contentId = bb.jquery(this._context.selectedBlock).attr("data-uid");
                nodeInfos.contentType = bb.jquery(this._context.selectedBlock).attr("data-type");
                var contentEditManager = bb.ManagersContainer.getInstance().getManager("ContentEditionManager");
                contentEditManager.showContentParamsEditor(nodeInfos);
            },

            "editContent" :function(){
                this._editContent(this._context.selectedBlock);
                return;
            }
        },

        showLinks : function(){
            var bbSelector = bb.jquery(this._context.linkSelector).data('bbSelector');
            bbSelector.open();
        }, 

        _initEditContentEvent : function(){
            /*moseover on content*/
            /*click on content edit-mode*/
            var self = this;
            var contentSelector = this._settings.contentClass+",."+this._settings.rootContentSetCls;
            bb.jquery(contentSelector).live("mouseenter",function(e,userData){
                if(!self._context.isEnable) return true;
                e.stopImmediatePropagation();
                var currentTarget = e.currentTarget;
                /*removeallhoverclass*/
                bb.jquery(self._settings.contentHoverClass).removeClass(self._settings.contentHoverClass.replace(".",""));
                /*addHover for current*/
                bb.jquery(currentTarget).addClass(self._settings.contentHoverClass.replace(".",""));
            });

            bb.jquery(contentSelector).live("mouseleave",function(e){
                if(!self._context.isEnable) return true;
                var currentTarget = bb.jquery(e.currentTarget);
                var parentToSelect = bb.jquery(currentTarget).parent(self._settings.contentClass);
                bb.jquery(currentTarget).removeClass(self._settings.contentHoverClass.replace(".",""));
                if((parentToSelect) && parentToSelect.length!=0){
                    bb.jquery(parentToSelect).trigger("mouseenter",{
                        userTrigger :true
                    });
                }
            });

            bb.jquery(contentSelector).live("click",function(e){
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
            bb.jquery(document).trigger("content:ItemClicked",[pathInfos]);*/
        },

        _updateselectedBlock : function(content){

            if(this._context.selectedBlock){
                bb.jquery(this._context.selectedBlock).removeClass(this._settings.selectedBlockCls);
            }
            this._context.selectedBlock = (bb.jquery(content).length) ? bb.jquery(content) : false;
            if(this._context.selectedBlock){
                bb.jquery(this._context.selectedBlock).addClass(this._settings.selectedBlockCls);  
            }

        },

        _buildContentActions : function(){

            var btnsContainer = bb.jquery("<div></div>").clone();
            btnsContainer.addClass(this._settings.actionCtnCls);
            var self = this;
            bb.jquery.each(this._availableBtns,function(key,btnConfig){
                var btn = bb.jquery("<button></button>").clone();
                bb.jquery(btn).addClass(btnConfig.btnClass).attr("title",btnConfig.btnTitle);
                bb.jquery(btn).attr("data-type",key);
                bb.jquery(btn).bind("click",bb.jquery.proxy(self._contentMenuAction[btnConfig.btnCallback],self));
                bb.jquery(btnsContainer).append(bb.jquery(btn));
            });
            return btnsContainer;
        },

        _actionsBeforeRender : function(selectedBlock,actionsBar){
            var newContent = actionsBar;
            if(bb.jquery(selectedBlock).hasClass(this._settings.rootContentSetCls)){
                bb.jquery(actionsBar).find("."+this._settings.delBtnCls).remove();
            } 
            return newContent;
        },

        _getselectedBlock : function(){
            return this._context.selectedBlock;
        },

        _showActionsForContent : function(clickedContent){
            /*hideAction*/
            bb.jquery(this._settings.actionCtnClass).remove(); //remove previous actions
            this._updateselectedBlock(clickedContent);
            var actionbar = this._buildContentActions(); //addFilters?
            var contentAction = this._actionsBeforeRender(clickedContent,actionbar);
            bb.jquery(clickedContent).css("position","relative");
            bb.jquery(contentAction).css("position","absolute");
            bb.jquery(clickedContent).append(contentAction);
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
                var context = bb.jquery('#bb5-dialog-treeview').bbPageBrowser('getContext');
                bb.jquery('#bb5-dialog-treeview').bbPageBrowser('editPage', null, context.selected, function(node) {
                    bb.jquery('#bb5-dialog-treeview').bbPageBrowser('browsePage', node.attr.id.replace('node_',''));
                }, 'toolbar');
            };

            this._callbacks["pagebrowser_editpage_action"] = function () {
                var breadcrumbids = bb.frontApplication.getBreadcrumbIds();
                if (1 <breadcrumbids.length) {
                    bb.jquery('#bb5-dialog-treeview').bbPageBrowser('editPage', breadcrumbids[breadcrumbids.length - 1], breadcrumbids[breadcrumbids.length - 2]);
                }            
            };

            this._callbacks["pagebrowser_clonepage_action"] = function () {
                var context = bb.jquery('#bb5-dialog-treeview').bbPageBrowser('getContext');
                //console.log(context.selected);
                //console.log(context.site);
                bb.jquery('#bb5-dialog-treeview').bbPageBrowser('clonePage', context.selected, function(node) {
                    bb.jquery('#bb5-dialog-treeview').bbPageBrowser('browsePage', node.attr.id.replace('node_',''));
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
                self.toggleEditionMode(bb.jquery('.bb5-onlyShowBlocksBtn').attr('checked'));
            };

            this._callbacks["showkeywordBrowser_action"] = function(){
                this.keywordBrowser.show();
            }

            /*afficher */
            this._callbacks["showLinks_action"] = bb.jquery.proxy(this.showLinks,this);
            /*initContent*/
            this._initEditContentEvent();


            /*dont follow any link*/
            bb.jquery(this._settings.contentClass+" a").bind("click",function(e){
                if(self._context.isEnable) e.preventDefault(); //désactiver les liens en mode édition
                return true;
            });

            /*bind close dialog*/
            this.contentFormDialog.on("close",function(){
                bb.jquery(this.dialog).find('input[value$="Element\\\\image"]').each(function(){
                    bb.jquery(this).bbMediaImageUpload('destroy');
                });
                bb.jquery(this.dialog).children().remove();            
            });

            /*change all image*/
            bb.jquery('[data-uid]').live('contentchange.image', function(e,data){
                if (typeof data != 'object')
                    return ;
                e.stopPropagation();
                var prevItem = bb.jquery(this);
                var nextItem = data.media;
                self.selectMedia(prevItem,nextItem);
            });
        },
        _createkeywordBrowser : function(){
            if(bb.jquery("#keywordbrowser")){
                var container = bb.jquery("<div id='keywordbrowser'></div>").clone(); 
            }
            bb.jquery("body").append(container);
            var keywordBrowser = bb.jquery(container).bbKeywordBrowser({
                title:"Keywords"
            }).bbKeywordBrowser("getWidgetApi");
            return keywordBrowser;
        },

        _beforeShowDatepicker : function(dateField,dpInfos){
            bb.jquery(dpInfos.dpDiv).css('z-index', 901021);
            bb.jquery(dpInfos.dpDiv).addClass('bb5-ui bb5-dialog-wrapper');
        },

        _updateDateField : function(dp){
            var selectedDate = (typeof bb.jquery(this).val() == "string" ) ? bb.jquery(this).val().length : false;
            var timestamp = "";
            var dateNow         = new Date();
            var hoursNow        = parseInt(dateNow.getHours());
            var minutesNow      = parseInt(dateNow.getMinutes());
            if(selectedDate){
                var date = new Date(dp.selectedYear,dp.selectedMonth,parseInt(dp.selectedDay), hoursNow, minutesNow);
                timestamp = date.getTime()/1000; //get ts in sec instead of milisec
            }

            bb.jquery(this).attr("data-value",timestamp);
            bb.jquery(this).trigger("change");
        },

        _updateDataBeforeSubmit: function() {
            bb.jquery('.date_pick_form').each(function (index, item) {
                var dateTime = bb.jquery(item).attr("data-value");
                bb.jquery(item).val(dateTime);
            });
        },

        _initDateWidgets : function(){
            var self = this;
            /*modifierdaterange*/
            bb.jquery('.date_pick_form').datepicker({
                dateFormat:"dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                beforeShow : bb.jquery.proxy(this._beforeShowDatepicker,this),
                onClose : function(selectedDate,dp){
                    self._updateDateField.call(this,dp);
                }
            });
        },


        bbSelectorHandlers :{
            openHandler : function(selectorType,e,ui){
                var selectorType = selectorType || "none"; 
                var widget = bb.jquery(this._context.linkSelector).bbSelector("getWidget",selectorType);  
                if(widget){
                    bb.jquery(widget).data()["bbPageSelector"].fixContainersHeight();
                }
            },      
            resizeStartHandler:function(selectorType,e,ui){
                this.resizeInfos.originalContainerHeight = bb.jquery(e.target).height();
                this.resizeInfos.resizeStep = 0;
            },
            resizeHandler : function(selectorType,e,ui){
                var selectorType = selectorType || "none"; 
                var delta =  bb.jquery(e.target).height() - this.resizeInfos.originalContainerHeight;
                var deltaStep = delta - this.resizeInfos.resizeStep;
                var widget = bb.jquery(this._context.linkSelector).bbSelector("getWidget",selectorType);
                if(widget){
                    bb.jquery(widget).data()["bbPageSelector"].updateListSize(deltaStep);  
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
                var linkContainer = bb.jquery('<div id="bb5-dialog-link-selector" class="bb5-selector-wrapper"></div>').clone();
                this._context.linkSelector = bb.jquery(linkContainer).bbSelector({
                    popup: true,
                    pageSelector: true,
                    linkSelector: true,
                    mediaSelector: false,
                    contentSelector : false,
                    selectorTitle : selectorLink,
                    resizable: false,
                    //site: bb.frontApplication.getSiteUid(),
                    callback: function(item) {
                        bb.jquery('#bb5-dialog-link-selector').bbSelector('close');
                    },
                    beforeWidgetInit:function(){
                        var bbSelector = bb.jquery(this.element).data('bbSelector');
                        /*for internal link*/
                        bbSelector.onWidgetInit(bbSelector._panel.INTERNAL_LINK, function () { 
                            var bbPageSelector = bb.jquery(this).data('bbPageSelector') || false;
                            if(bbPageSelector){
                                bbPageSelector.setCallback(function (params) {
                                    bb.jquery('input#' + self._settings.formInputLinkDataUid).val(params.value);
                                    bbSelector.close();
                                }); 
                            }
                        });
                        /*for External link*/
                        bbSelector.onWidgetInit(bbSelector._panel.EXTERNAL_LINK, function () {
                            var bbLinkSelector = bb.jquery(this).data('bbLinkSelector');
                            bbLinkSelector.setCallback(function (params) {
                                bb.jquery('input#' + self._settings.formInputLinkDataUid).val(params.value);
                                bbSelector.close();
                            });
                        });
                    }

                //open: bb.jquery.proxy(self.bbSelectorHandlers.openHandler,self,"bbLinkInternalContainer"),
                //resizeStart: bb.jquery.proxy(this.bbSelectorHandlers.resizeStartHandler,this,"bbLinkInternalContainer"),
                //resize : bb.jquery.proxy(this.bbSelectorHandlers.resizeHandler,this,"bbLinkInternalContainer")
                });
            }

            bb.jquery("form .bb5-ShowBBSelector").die().live('click', function () {
                self._settings.formInputLinkDataUid = bb.jquery(this).attr('data-uid');
                var bbSelector = bb.jquery(self._context.linkSelector).data('bbSelector');
                bbSelector.close();
                bbSelector.open();
            });

            // Sélecteur de médias
            if (!this._context.mediaSelector) {
                // console.log(this._settings.i18n);
                var mediaSelectorLabel = bb.i18n.__('toolbar.editing.mediaSelectorLabel');
                this._context.mediaSelector = bb.jquery('#bb5-dialog-media-selector').bbSelector({
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
                        bb.jquery('#bb5-dialog-media-selector').bbSelector('close');
                    }
                });
            }

            // Arborescence
            if (!this._context.pageBrowser) {
                this._context.pageBrowser = bb.jquery('#bb5-dialog-treeview').bbPageBrowser({
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
            this._handleContentEdition();
            // Contextmenu
            //var contentSelector = this._settings.contentClass;
            this.isActivated = true; 	
            return self;
        },

        _handleContentEdition : function(){
            var self = this;
            bb.jquery('[data-uid]').die("contentchange.content").live('contentchange.content', function(e) { 
                var content = bb.jquery(this);

                e.stopPropagation();

                bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                    params: {
                        name: bb.jquery(this).attr('data-type'),
                        mode: ((bb.jquery(this).attr('data-rendermode').length > 0) ? bb.jquery(this).attr('data-rendermode') : null),
                        uid: bb.jquery(this).attr('data-uid')
                    },

                    success: function(result) {
                        if (result.result) {
                            content.replaceWith(bb.jquery(result.result.render));
                            bb.ManagersContainer.getInstance().getManager("ContentManager").initDroppableImage(bb.jquery('[data-uid="' + content.attr('data-uid') + '"]'));

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
            bb.jquery('body').bind('click.contentEdit', function(e){
                if(!self._context.isEnable) return;
                if(bb.jquery(e.target).hasClass("contentNodeItem")) return;
                self._hideContextMenu();
                return true;            
            });
        },
        /*all resizeHandler*/       
        enable: function() {
            var self = this;
            this.toggleEditionMode(bb.jquery('.bb5-onlyShowBlocksBtn').attr('checked'));

            self.activate();

            // Upload drag'n'drop
            bb.jquery('[data-type^="Media\\\\"]').live('mouseover', function() {
                bb.jquery(this).addClass('aloha-editable-active');
            });

            bb.jquery('[data-type^="Media\\\\"]').live('mouseout', function() {
                bb.jquery(this).removeClass('aloha-editable-active');
            });

            bb.jquery('[data-type^="Media\\\\"]').live('click', function(e) {
                e.stopPropagation();
                var clickCallback = bb.jquery.proxy(self.selectMedia,self,e.currentTarget);
                self._context.mediaSelector.bbSelector('setCallback',clickCallback);
                /*addFilter if needed*/
                self._context.mediaSelector.bbSelector('open');
                return false;
            });

            //        bb.jquery('img[data-type="Element\\\\image"][data-library=""]').each(function () {
            //            self.mediaImageUploadFn(this)
            //        });
            //        
            //        bb.jquery('p[data-type="Media\\\\image"]').each(function () {
            //            self.mediaImageUploadFn(this)
            //        });
            //        
            //        bb.jquery('img[data-type="Element\\\\file"][data-library=""]').each(function () {
            //            self.mediaImageUploadFn(this)
            //        });
            //        
            //        bb.jquery('p[data-type="Media\\\\file"]').each(function () {
            //            self.mediaImageUploadFn(this)
            //        });

            bb.jquery('*[data-type="Media\\\\image"]').each(function () {
                self.mediaImageUploadFn(this)
            });
            bb.jquery('*[data-type="Media\\\\file"]').each(function () {
                self.mediaImageUploadFn(this)
            });

            /* bb.jquery(this._settings.contentClass).bind('contextmenu', function(e) {
                self._context.selectedBlock = bb.jquery(this);
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

            //AlohaManager.applyAloha();

            /* handle rte manager here use deferred */
            var self = this;
            bb.require(["ManagerFactory"], function(){
                try{
                    if(self.contentManager){ self.contentManager.enable(); return;}
                    self.contentManager = bb.core.getManager("rte");
                    self.contentManager.init().enable();
                }catch(e){
                    throw e;
                }
            });

            this._context.isEnable = true;   
        },

        handleNewBbMediaContent : function(prevMedia,newMedia){
            var bbPrevContent = $bb(prevMedia);
            var prevContentClass = bb.jquery(bbPrevContent.contentEl).attr("class");
            var newContent = bb.jquery(newMedia);

            newContent.attr("class",prevContentClass);
            var prevSrc = bb.jquery(newContent).find("img").eq(0).attr("src");
            var ctime = new Date();
            bb.jquery(newContent).find("img").eq(0).attr("src",prevSrc+"?"+ctime.getTime());
            var nodeName = bbPrevContent.get("element");
            var nodeParent = bbPrevContent.get("parent");
            bb.jquery(newContent).attr("data-element",nodeName);
            bb.jquery(newContent).attr("data-parent",nodeParent);
            bb.jquery(prevMedia).replaceWith(newContent);
            bbPrevContent.setContentEl(newContent);
            bbPrevContent.parentNode.updateData();
        },

        mediaImageUploadFn : function(el) {
            var self  = this;
            var successCallback = function(el,file,result) {
                if (result.result.uid) {
                    bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                        params: {
                            name: bb.jquery(el).attr('data-type'),
                            mode: ((bb.jquery(el).attr('data-rendermode').length > 0) ? bb.jquery(el).attr('data-rendermode') : null),
                            uid: result.result.uid
                        },
                        success: function(response) {
                            if (response.result) {
                                if(bb.jquery(el).bbMediaImageUpload){
                                    bb.jquery(el).bbMediaImageUpload('destroy');  
                                }
                                self.handleNewBbMediaContent(el,response.result.render);
                                //bb.jquery(el).replaceWith(response.result.render);
                                self.mediaImageUploadFn(bb.jquery('[data-uid="' + bb.jquery(response.result.render).attr('data-uid') + '"]'));
                            }
                        },

                        error: function(result) {
                        }
                    });
                }
            };
            successCallback = bb.jquery.proxy(successCallback,this,el);
            bb.jquery(el).bbMediaImageUpload({
                media_uid: bb.jquery(el).attr('data-uid'),
                media_classname: bb.jquery(el).attr('data-type'),

                ready: function(){},
                uploadFinishedSuccess: successCallback,
                uploadFinishedError: function(file, result) {},

                uploadError: function(file, message) {
                },

                uploadStarted: function(i, file, len) {
                },

                dragOver: function(e) {
                    bb.jquery(e.target).addClass('aloha-editable-active');
                },

                dragLeave: function(e) {
                    bb.jquery(e.target).removeClass('aloha-editable-active');
                },

                drop: function(e) {
                    bb.jquery(e.target).removeClass('aloha-editable-active');
                }
            });
        },
        selectMedia:  function(prevItem,item) {
            var myself = prevItem;
            var bbPrevContent = $bb(prevItem);        
            var self = this;
            this._context.mediaSelector.bbSelector('close');
            if (item.data.content.classname.replace('BackBuilder\\ClassContent\\', '') == bb.jquery(myself).attr('data-type')) {
                bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                    params: {
                        name: bb.jquery(myself).attr('data-type'),
                        mode: ((bb.jquery(myself).attr('data-rendermode').length > 0) ? bb.jquery(myself).attr('data-rendermode') : null),
                        uid: item.uid
                    },

                    success: function(response) {
                        if (response.result) {
                            if(bb.jquery(myself).bbMediaImageUpload){
                                bb.jquery(myself).bbMediaImageUpload('destroy');
                            }
                            self.handleNewBbMediaContent(myself,response.result.render);
                            self.mediaImageUploadFn(bb.jquery('[data-uid="' + bb.jquery(response.result.render).attr('data-uid') + '"]'));
                        }
                    },

                    error: function(result) {
                    }
                });
            } else {
                this.alerteBox.setContent(bb.jquery('<span/>').html(bb.i18n.__('toolbar.editing.media_notmatching')));
                this.alerteBox.show();
            }
            return false;
        },

        toggleEditionMode: function(edition) {
            var self = this;
            this._context.modeEdition = ((edition) ? true : false);

            bb.jquery(document).trigger("content:toggleEditionMode", this._context.modeEdition);

            /*radical blaze*/
            bb.jquery.each(this._getEditionBlocks(false), function() {
                if (self._context.modeEdition) bb.jquery(this).show();
                else bb.jquery(this).hide();
            });
        },

        _editContent: function(content) {
            var myself = this;
            var content = bb.jquery(content);

            /*persit changes before edit*/

            bb.ContentWrapper.persist(false); //async persist
            bb.webserviceManager.getInstance('ws_local_contentBlock').request('getContentEditionForm', {
                params: {
                    contentType: content.attr('data-type'),
                    contentUid: content.attr('data-uid')
                },

                success: function(result) {
                    if (result.result) {
                        var form = bb.jquery('<form>' + myself._renderContentForm([result.result]) + '</form>');
                        bb.jquery(form).find("input").focus();
                        var dialog = myself.contentFormDialog.dialog;
                        bb.jquery(form).find('legend').parent().css({
                            'border' : '1px solid #CCC'
                        });

                        bb.jquery(form).find("input:checkbox").each(function() {
                            var div = bb.jquery('div[id="' + bb.jquery(this).attr('id').replace('_delete', '') + '_container"]');
                            if (bb.jquery(this).is(':checked'))
                                div.hide();
                            else
                                div.show();
                        });

                        bb.jquery(form).find('input:checkbox').bind('click', function(e) {
                            var div = bb.jquery('div[id="' + bb.jquery(this).attr('id').replace('_delete', '') + '_container"]');

                            if (bb.jquery(this).is(':checked'))
                                div.hide('blind');
                            else
                                div.show('blind');

                            return true;
                        });

                        // Image upload
                        bb.jquery(form).find('input[value$="Element\\\\image"]').each(function() {
                            var dropbox = bb.jquery(form).find('div[id="' + bb.jquery(this).attr('id').replace('_type', '') + '_container"]');
                            bb.uploadManager.getInstance('ws_local_media').filedrop('uploadImage' , {
                                paramname: 'image',
                                maxfiles: 1,
                                maxfilesize: bb.config.maxFileSize,
                                data: {
                                },

                                uploadFinished:function(i, file, response) {
                                    myself.contentFormDialog.btnEnable();
                                    if (!response.error) {
                                        bb.jquery.data(file).addClass('done');
                                        bb.jquery(form).find('input[name="' + dropbox.attr('id').replace('_container', '') + '"]').val(JSON.stringify(response.result));
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
                                    var preview = bb.jquery(dropbox.parent().find('#imagebbselector-editpreview-tpl').find(".preview").clone()), 
                                    image = bb.jquery('img', preview); 

                                    var reader = new FileReader();

                                    reader.onload = function(e){
                                        image.attr('src', e.target.result);
                                    };
                                    reader.readAsDataURL(file);

                                    preview.appendTo(dropbox);
                                    bb.jquery.data(file, preview);
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
                        });

                        myself.contentFormDialog.setContent(bb.jquery(form));                  
                        var buttons = {};
                        myself.initAutoComplete(result.result);
                        buttons[bb.i18n.__('toolbar.editing.save')] = function() {
                            myself._updateDataBeforeSubmit();
                            var serialize = function(content) {
                                var values = {};
                                bb.jquery.each(content, function (key, item) {
                                    bb.jquery.each(item.bb5_form, function(index, value) {
                                        values[index] = new Array();
                                        if (value.bb5_type != "BackBuilder\\ClassContent\\Element\\keyword") {
                                            if (!value.bb5_fieldset) {
                                                if (value.bb5_uid) {
                                                    for (var i=0; i< value.bb5_uid.length; i++) {
                                                        values[index][i] = {};
                                                        values[index][i].type = bb.jquery(dialog).find('[id="' + value.bb5_uid[i] + '_type"]').val();
                                                        values[index][i].value = bb.jquery(dialog).find('[id="' + value.bb5_uid[i] + '"]').val();
                                                        values[index][i]['delete'] = bb.jquery(dialog).find('#[id="' + value.bb5_uid[i] + '_delete"]').is(':checked');
                                                        values[index][i]['parameters'] = [];
                                                        //console.log(values[index][i]['parameters'], 'id="' + value.bb5_uid[i] + '_container');
                                                        var paramfields = bb.jquery(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter');
                                                        var key = i;
                                                        bb.jquery.each(paramfields,function(i,field){
                                                            var fieldParams = {};
                                                            fieldParams.name = bb.jquery(field).attr("data-param");
                                                            var inputField = bb.jquery(field).find("input.param");
                                                            var value = "";
                                                            if(inputField.length > 1){
                                                                value = bb.jquery(inputField).filter(":checked").val(); 
                                                            }else{
                                                                value = inputField.val();  
                                                            }
                                                            fieldParams.value = value;
                                                            values[index][key]['parameters'].push(fieldParams);
                                                        });

                                                    /*if (bb.jquery(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter').attr('data-param')) {
                                                            values[index][i]['parameters'] = {
                                                                'name': bb.jquery(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter').attr('data-param'), 
                                                                'value': bb.jquery(dialog).find('[id="' + value.bb5_uid[i] + '_container"] .parameter input:checked.param').val()
                                                            };
                                                        //console.log(values[index][i]['parameters']);
                                                        }*/
                                                    }
                                                } else {
                                                    values[index][0] = {};
                                                    values[index][0].type = 'scalar';
                                                    values[index][0].value = bb.jquery(dialog).find('[id="' + item.bb5_uid + '_' + index + '"]').val();
                                                    values[index][0]['delete'] = bb.jquery(dialog).find('[id="' + item.bb5_uid + '_' + index + '_delete"]').is(':checked');
                                                    values[index][0]['parameters'] = {};
                                                    if (bb.jquery(dialog).find('[id="' + item.bb5_uid + '_container"] .parameter').attr('data-param'))
                                                        values[index][0]['parameters'] = {
                                                            'name': bb.jquery(dialog).find('[id="' + item.bb5_uid + '_container"] .parameter').attr('data-param'), 
                                                            'value': bb.jquery(dialog).find('[id="' + item.bb5_uid + '_container"] .parameter input:checked.param').val()
                                                        };
                                                }
                                            }
                                            else {
                                                bb.jquery.each(value.bb5_value, function(i, bb5_element) {
                                                    values[index][i] = {};
                                                    values[index][i].type = bb.jquery(dialog).find('[id="' + bb5_element.bb5_uid + '_type"]').val();
                                                    values[index][i]['delete'] = bb.jquery(dialog).find('[id="' + bb5_element.bb5_uid + '_delete"]').is(':checked');
                                                //                                              values[index][i].form = serialize(bb5_element.bb5_form);
                                                });
                                                values[index][0].form = serialize(value.bb5_value);
                                            }
                                        } else {
                                            bb.jquery('form input[name="keywords"]').each(function (indexKey, keyword) {
                                                //console.log(keyword);
                                                var uidNode = bb.jquery(keyword).attr('data-uid-keyword');
                                                values[index][indexKey] = {};
                                                values[index][indexKey].type = 'BackBuilder\\ClassContent\\Element\\keyword';
                                                values[index][indexKey].uid = uidNode;
                                                values[index][indexKey].value = bb.jquery(keyword).val();
                                                values[index][indexKey]['delete'] = bb.jquery(dialog).find('[id="' + uidNode + '_delete"]').is(':checked');
                                                values[index][indexKey]['parameters'] = [];
                                            });

                                        }
                                    });


                                });
                                return values;

                            };



                            bb.jquery(dialog).parents('.ui-dialog:first').mask(bb.i18n.__('loading'));
                            bb.webserviceManager.getInstance('ws_local_contentBlock').request('postContentEditionForm', {
                                params: {
                                    contentType: content.attr('data-type'),
                                    contentUid: content.attr('data-uid'),
                                    contentValues: JSON.stringify(serialize([result.result]))
                                },

                                success: function(result) {
                                    bb.jquery(dialog).parents('.ui-dialog:first').unmask();
                                    myself.contentFormDialog.close();
                                    if (result.result) {
                                        bb.jquery('[data-uid="' + content.attr('data-uid') + '"]').trigger('contentchange');
                                    }
                                },

                                error: function(result) {
                                    bb.jquery(dialog).parents('.ui-dialog:first').unmask();
                                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                                }
                            });
                        };
                        buttons[bb.i18n.__('toolbar.editing.close')] = function() {
                            myself.contentFormDialog.close();
                        };

                        bb.jquery(dialog).dialog('option','title', result.result.name);
                        bb.jquery(dialog).dialog('option', 'buttons', buttons );
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
                    bb.jquery.each(result.result, function (index, item) {
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
                if(bb.jquery.isArray(content)){
                    form +="<legend>"+content[0].bb5_name+"</legend>";
                }
            }
            bb.jquery.each(content, function (key, item) {
                if (true == item.bb5_media) {
                    form += '<input id="' + item.bb5_uid + '_delete" type="checkbox" style="margin-bottom: 25px;"><label for="' + item.bb5_uid + '_delete">&nbsp;' + bb.i18n.__('toolbar.editing.media_detach') + '</label>';
                }
                form += '<div id="' + item.bb5_uid + '_container">';
                bb.jquery.each(item.bb5_form, function(index, value) {
                    if (index == "keywords")
                        form += '<label style="font-weight: bold;">'+bb.i18n.__('contentmanager.keyword')+'</label><div class="contentNewKeywords"></div>'+bb.i18n.__('contentmanager.keyword')+': <input class="typeahead" type="text" name="serchKeyWord" value="" /><br />';
                    if (value.bb5_type != 'BackBuilder\\ClassContent\\Element\\keyword') {
                        if (!value.bb5_fieldset) {
                            if (value.bb5_uid) {
                                bb.jquery.each(value.bb5_value, function (keyResult, itemResult) {
                                    form += itemResult;
                                });
                            } else {
                                bb.jquery.each (value.bb5_value, function (indexValue, itemValue) {
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
                        bb.jquery.each(value.bb5_value, function (keyResult, itemResult) {
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
            bb.jquery('.newKeywordAutoComplete').live('click', function () {
                bb.jquery(this).parents('.alert-success').remove();
            });
        },

        initAutoComplete: function (content) {
            var myself = this;
            var cpt = 0;
            bb.jquery('input.typeahead').bind('keyup', function () {
                var inputVal = bb.jquery(this).val();

                if (inputVal.length >= 2) {

                    //bb.jquery('input.typeahead').autocomplete("destroy");
                    bb.jquery('input.typeahead').autocomplete({
                        source: myself.getDataKeyWordsFormated(inputVal),
                        select: function (ev, ui) {
                        var uidInput = bb.jquery(this).val('');
                        cpt++;
                        bb.jquery(this).parents('div#' + ui.item.value + '_container').append('<div class="removeKeyword" data-uid="' + ui.item.value + '">' + ui.item.label + '</div>');
                        var form = "";
                        form += '<div class="alert alert-success">' + ui.item.label + '<input type="hidden" data-uid-keyword="" name="keywords" value="' + ui.item.value + '"/><button type="button" class="close newKeywordAutoComplete">×</button><div>';
                        bb.jquery('.contentNewKeywords').append(form);
                        myself.updateContent(content, form);
                        myself.removeNewKeyword();
                        return false;
                        },
                        focus:function(ev,ui){
                        return false;
                        }
                        }).data( "autocomplete" )._renderItem = function( ul, item ) {
                        return bb.jquery( "<li class=\"keywordSelected\"></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label + "</a>" )
                        .appendTo( ul );
                    };
                }
            });
            bb.jquery('.removeKeyword').live('click', function () {
                var data_uid = bb.jquery(this).attr('data-uid');
                bb.jquery('input[name="'+ data_uid + '_type"]').remove();
                bb.jquery(this).remove();
            });

            return content;
        },

        _showMessage: function(title, message, icon) {
            var myself = this;

            if (!myself._context.messageBox) {
                bb.jquery('body').append(bb.jquery('<div id="bb5-ui-edition-message"/>')); 
                myself._context.messageBox = bb.jquery('#bb5-ui-edition-message');
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

            bb.jquery('[data-uid]').each(function() {
                if ( (bb.jquery(this).attr('data-type') != 'ContentSet') && (!bb.jquery(this).attr('contenteditable')) && (!bb.jquery(this).hasClass(self._settings.contentClass.replace('.', ''))) ) {

                    if (typeof isLoaded != 'undefined') {
                        if ( bb.jquery(this).attr('data-isloaded') == ((isLoaded) ? 'true' : 'false') ){
                            blocks.push(bb.jquery(this));
                        }       
                    } else {
                        blocks.push(bb.jquery(this));
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
            bb.jquery(this._settings.actionCtnClass).remove();
            if(this._context.selectedBlock){
                bb.jquery(this._context.selectedBlock).removeClass(this._settings.selectedBlockCls);
                bb.jquery(this._context.selectedBlock).removeClass(this._settings.selectedContentCls);
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

           console.log("inside disable ");
            //AlohaManager.stop(); 
            if(this.contentManager){
                this.contentManager.disable();
            }         
            if (this._context.mediaSelector) {
                bb.jquery('[data-type^="Media\\\\"]').die('mouseover');            
                bb.jquery('[data-type^="Media\\\\"]').die('mouseout');                
                bb.jquery('[data-type^="Media\\\\"]').die('click');         
                bb.jquery('img[data-type="Element\\\\image"]').bbMediaImageUpload('destroy');
                bb.jquery('[data-type="Media\\\\image"]').bbMediaImageUpload('destroy');
            }

            this._context.isEnable = false;
            //var contentSelector = this._settings.contentClass;
            //bb.jquery('body.blockEdit').unbind('click');
            if(this._contextMenu){
                this._contextMenu.disable();
            }
            this._unSelectContent();
            bb.jquery('body').unbind('click.contentEdit');
            this.isActivated = false;
        }
    });
    }) (bb.jquery);


    /*Edit contentset to show the contentLibrary*/
