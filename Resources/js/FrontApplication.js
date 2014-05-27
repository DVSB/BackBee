/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var bb = (bb) ? bb : {};

bb.frontApplication = (function($,gExport){
    
    var _settings = {
        defaultGridSize : {
            colWidth:60, 
            gutterWidth:20
        },
        MAX_GRID_SIZE : 12,
        toolbarId: "#bb5-toolbar-wrapper",
        siteWrapperId : "#bb5-site-wrapper",
        contentId: "#bb5-site-wrapper",
        tplHeaderId : "#templateHeader",
        tplFooterId : "#templateFooter",
        mainLayoutId : "#bb5-mainLayoutRow",
        userInfo : '#bb5-topmost-login',
        dbName:"BB4",
        commonname: '',
        username: '',
        siteId : "",
        pageId : "",
        breadcrumbIds : "",
        layoutId : "",
        webServicesInfos:  {
            layoutWs : "ws_local_layout",
            lessWs : "ws_local_less",
            contentTypeWs :"ws_local_contentBlock"
        },
        selectedTab : "bb5-edit-tabs-data",
        editModeInfos: {
            contentCls:"bb5-editmode-content", 
            blockCls:"bb5-editmode-block"
        },
        TAB_STRUCTURE :"bb5-grid",
        TAB_ZONING :"bb5-blocks",
        TAB_EDITION : "bb5-editing",
        TAB_THEMING: "bb5-theming",
        TAB_PROFILE : "bb5-personal",
        TAB_STATUS :"bb5-status",
        TAB_BUNDLE : "bb5-bundle",
        TAB_EDITION_BLOCKS: "bb5-edit-tabs-blocks",
        TAB_EDITION_DATA: "bb5-edit-tabs-data",
        TAB_EDITION_PAGE: "bb5-edit-tabs-page"
    };
    
    var _toggleEditMode = function(editMode){
        _removeEditMode();
        var key = editMode+"Cls"||false;
        if(key) bb.jquery(_settings.siteWrapperId).addClass(_settings.editModeInfos[key]);
    }
                    
    var _removeEditMode = function(){
        bb.jquery(_settings.siteWrapperId).removeClass(_settings.editModeInfos.contentCls).removeClass(_settings.editModeInfos.blockCls);
    }
                    
    
    var _init = function(userConfig){
        
        /*give a chance to other to change lauching settings before app init*/
        bb.jquery(document).trigger("application:init",{
            settings : userConfig
        });        
        bb.jquery.extend(true,_settings,userConfig);	
        
        return new function(){
            this._init = function(){
                _settings.siteId = bb.jquery(_settings.toolbarId).attr('data-site');
                _settings.layoutId = bb.jquery(_settings.toolbarId).attr('data-layout');
                _settings.pageId = bb.jquery(_settings.toolbarId).attr('data-page');
                _settings.dbName = (typeof _settings.dbName=="string") ? _settings.dbName : "BB4";
                _settings.breadcrumbIds = bb.jquery(_settings.toolbarId).attr('data-breadcrumb').split('-');
                var availableTabs = [_settings.TAB_PROFILE,_settings.TAB_STRUCTURE,_settings.TAB_ZONING,_settings.TAB_USER,_settings.TAB_EDITION,_settings.TAB_EDITION_BLOCKS,_settings.TAB_EDITION_DATA,_settings.TAB_EDITION_PAGE,_settings.TAB_STATUS,_settings.TAB_BUNDLE];
                if(bb.jquery.inArray(_settings.selectedTab,availableTabs)==-1){
                    _settings.selectedTab = _settings.TAB_EDITION_BLOCKS;
                }
                /*keep previous selected tab*/
                this.wsInfos = _settings.webServicesInfos; 
                this.db = DbManager.init(_settings.dbName);
                
                var lastlang = this.db.get('selectedLang')||null;
                if (lastlang) bb.i18n.setLocale(lastlang);
                
                var lastTab = this.db.get("selectedTab")||null;
                if(lastTab) _settings.selectedTab = lastTab;
                
                this.contentManager = null;
                this.layoutWebservice = null;
                this.lessWebService = null;
                if (document.location.hash && bb.jquery.inArray(document.location.hash.replace('#', ''), availableTabs)) {
                    _settings.selectedTab = document.location.hash.replace('#', '');
                    document.location.hash = '';
                }
                
                /*set global parameters make everything available...everywhere*/
                bb.ManagersContainer.getInstance().register("GlobalParamsManager",this.publicApi);
                this.tabManager = BB4.ToolsbarManager.init({
                    selectedTab:_settings.selectedTab, 
                    onInit: bb.jquery.proxy(this.onTabManagerInit,this)
                });
               
                /*dialogs*/
                this.alertDialog = bb.PopupManager.init({
                    dialogSettings:{
                        modal: true
                    }
                });
                
                this.alertDialog = this.alertDialog.create("alertDialog",{
                    title: bb.i18n.__('popupmanager.title.alert'),
                    buttons:{
                        "OK": {
                            text: bb.i18n.__('popupmanager.button.ok'),
                            click: function(){
                                bb.jquery(this).dialog("close");
                                return;
                            }
                        }
                    }
                });
                bb.storedsitepadding = 1*bb.jquery(_settings.siteWrapperId).css('padding-top').replace('px', '');
                bb.jquery(_settings.toolbarId).show();
                bb.jquery(_settings.siteWrapperId).css('padding-top', (3 + bb.storedsitepadding + 1*bb.jquery(_settings.toolbarId).css('height').replace('px', '')) + 'px');
                bb.jquery(_settings.userInfo).prepend(_settings.commonname ? _settings.commonname : _settings.username);

                /*fix toolbars*/
                bb.jquery("#bb5-toolbar-wrapper").css("top","0px");
                window.scrollTo(0,212);
                bb.frontApplication = this.publicApi;
            };
            var self = this;
            this.publicApi = {
                init : function(){
                    return false;
                },//shouldn't have to be called twice
                getSiteUid : function(){
                    return _settings.siteId;
                },
                getLayoutId : function(){
                    return _settings.layoutId;
                },
                getPageId : function(){
                    return _settings.pageId;
                },
                getBreadcrumbIds :function(){
                    return _settings.breadcrumbIds;
                } 
            };
			
            this.onTabManagerInit = function(){
                var self = this;
                /*contentBlocks*/
                this.contentBlocksWebservice = bb.webserviceManager.getInstance(this.wsInfos.contentTypeWs);
                this.contentsBlockTb = BB4.ToolsbarManager.createToolsbar("contenttb",{});
                this.contentBlocksWebservice.request("getContentBlocks",{
                    params : {},
                    async : false, /*Make the script waits*/
                    useCache: true,
                    cacheTags:["userSession"],
                    success: function(response){
                        self.contentsBlockTb.setContentBlocks(response.result);
                        self.contentManager = bb.ContentManager.init({
                            contentWebservice : self.contentBlocksWebservice,
                            gridSizeInfos : _settings.defaultGridSize
                        });
                        
                        self.contentManager.disable();	
                        bb.jquery.each(response.result.contentList, function(index, classcontent) {
                            bb.jquery('#bb5-zonelayout-property-accept').append('<option value="'+classcontent.name+'">'+classcontent.label+'</option>');
                            bb.jquery('#bb5-zonelayout-property-defaultcontent').append('<option value="'+classcontent.name+'">'+classcontent.label+'</option>');
                        });
                    },
                    error : function(response){
                        bb.Utils.handleAppError(" FrontApplication: Error When calling onTabManagerInit",response);
                    }
                }); 
                
                this.contentEditionManager = ContentEditionManager.init();
                this.layoutToolsbar = BB4.ToolsbarManager.createToolsbar("layouttb",{});
                this.themeTb = BB4.ToolsbarManager.createToolsbar("themetb",{});
                this.editionTb = BB4.ToolsbarManager.createToolsbar("editiontb",{
                    _settings: {
                        siteId: _settings.siteId,
                        pageId: _settings.pageId,
                        breadcrumbIds: _settings.breadcrumbIds
                    }
                }); //.activate()
                
                this.statusTb = BB4.ToolsbarManager.createToolsbar('statustb',{});
                this.bundleTb = BB4.ToolsbarManager.createToolsbar('bundletb',{});

                /*Contents manager*/
                this.contentEditionManager.disable();
 
                this.layoutManager = null;
                this.urlSelector = null;
                this.layoutEditorDialog =  this.layoutToolsbar.getlayoutEditorDialog();
                this.zoneEditorDialog = this.layoutToolsbar.getZoneEditorDialog();
                //this._initWebservices();
                //this._initUserConf();
                this._bindEvents();  
            /*tab is selected here*/
            };
            
            this._initUserConf = function(){
                var self = this;
                
                this.lessWebservice.request("getGridConstant",{
                    params :{},
                    success: function(response){
                        var gridParams = response.result[0].attributes;
                        var gridInfos  = {
                            nbColumns : _settings.MAX_GRID_SIZE,
                            colWidth :  parseInt(gridParams.gridColumnWidth.value.replace("px","")),
                            gutterWidth : parseInt(gridParams.gridGutterWidth.value.replace("px",""))
                        };
                        _settings.defaultGridSize = gridInfos;
                        self.layoutToolsbar.setGridSize(gridInfos);
                    },
                    error :function(response){
                        bb.jquery(document).trigger("application:error", {
                            title: "An error occured", 
                            message: "Unable to load grid constants", 
                            error: response.error
                        } );
                    }
                });
            };
            
            this._initWebservices = function(){
                var self = this;
                
                /*layoutWebservice*/
                this.layoutWebservice =  bb.webserviceManager.getInstance(this.wsInfos.layoutWs);                

                /*lessWebservice*/
                this.lessWebservice = bb.webserviceManager.getInstance(this.wsInfos.lessWs); 
            };
            
            /*init layout Manager*/
            this._initManagers = function(){
                this.layoutManager = BB4.LayoutManager.init({
                    gridSize:_settings.defaultGridSize,
                    layoutId : _settings.layoutId, 
                    site:{
                        label:"",
                        uid:_settings.siteId
                    }
                });
                
                var currentTemplateTitle = this.layoutManager.getCurrentTemplateTitle();
                this.layoutToolsbar.updateCurrentTemplateTitle(currentTemplateTitle);
                
                /* var playGroundLayoutL = new BB4.LayoutItem({
                    title:"root",
                    id:"rootLayout",
                    target:"#mainLayoutRow",
                    gridSize : _settings.MAX_GRID_SIZE
                }); */
                /*header and footer*/
                bb.jquery(_settings.tplHeaderId).addClass("span"+_settings.MAX_GRID_SIZE);
                bb.jquery(_settings.tplFooterId).addClass("span"+_settings.MAX_GRID_SIZE);
                
            /*this.layoutManager.addLayout(playGroundLayoutL);
                this.layoutManager.drawAll();*/
            };
            
            /* Replacer dans LayoutManager */
            this.initLayoutManager = function(mode){
                var self = this;
                if (null == this.layoutWebservice)
                    this.layoutWebservice =  bb.webserviceManager.getInstance(this.wsInfos.layoutWs);
                
                /*load available layouts for current website*/
                this.layoutWebservice.request("getLayoutsFromSite",{
                    useCache: true,
                    cacheTags: ["userSession"],
                    params :{
                        siteId:_settings.siteId
                    },
                    success : function(response){
                        self.layoutToolsbar.setAppTemplates(response.result, null);
                    },
                    
                    error: function(response){
                        bb.jquery(document).trigger("application:error", {
                            title: "An error occured", 
                            message: "Unable to load layouts from website", 
                            error: response.error
                        } );
                    }
                });
                
                /*loadLayoutModels*/
                this.layoutWebservice.request("getModels",{
                    params :{},
                    useCache : true,
                    cacheTags: ["userSession"],
                    success : function(response){
                        self.layoutToolsbar.setTemplateModels(response.result); 
                    },
                    error : function(response){
                        bb.jquery(document).trigger("application:error", {
                            title: "An error occured", 
                            message: "Unable to load layout models", 
                            error: response.error
                        } );
                    }
                });
                
                /*init layout Manager*/
                this.layoutManager = BB4.LayoutManager.init({
                    contentId:_settings.contentId,
                    gridSize:_settings.MAX_GRID_SIZE,
                    site:{
                        label:"",
                        uid:_settings.siteId
                    }
                }).enable();
                self.layoutToolsbar._selectTemplate(_settings.layoutId);
                var currentTemplateTitle = this.layoutManager.getCurrentTemplateTitle();
                this.layoutToolsbar.updateCurrentTemplateTitle(currentTemplateTitle);
            };
            
            this._bindEvents = function(){
               
                var self = this;
                this._bindLayoutTbEvents(this.layoutToolsbar);
                this._bindDialogsEvents();
                this._bindContentTbEvents(this.contentsBlockTb);
                bb.jquery(document).bind("application:error", function(event, data) {
                    switch (typeof(data)) {
                        case 'string':
                            bb.jquery(self.alertDialog.dialog).html(data);
                            break;
                        case 'object':
                            if (data.title) bb.jquery(self.alertDialog.dialog).dialog('option', 'title', data.title);
                            if (data.message) bb.jquery(self.alertDialog.dialog).html(data.message);
                            if ('object' == typeof(data.error)) {
                                var detail = '<p class="error-detail"><strong>Error detail:</strong>';
                                detail += '<br/><span>Type:</span>';
                                detail += (data.error.type) ? data.error.type : 'undefined';
                                detail += '<br/><span>Code:</span>';
                                detail += (data.error.code) ? data.error.code : 'undefined';
                                detail += '<br/><span>Message:</span>';
                                detail += (data.error.message) ? data.error.message : 'undefined';
                                if (data.message) bb.jquery(self.alertDialog.dialog).append(detail);
                            }
                            break;
                    }
                    self.alertDialog.show();
                });
				
                /*global Events*/
                bb.jquery(document).bind("layout:currentTemplateChange", function(event){
                    self.layoutManager.getCurrentTemplate().isModified = true;
                });
                bb.jquery(document).bind("layout:itemSelected",function(event,data){
                    self.layoutToolsbar.enableZoneProperties(data);
                });
                bb.jquery(document).bind("layout:sizeChanged",function(event,data){
                    self.layoutManager.trigger("currentTemplateChange");
                });
                bb.jquery(document).bind("layout:noneItemSelected",function(event){
                    self.layoutToolsbar.disableZoneProperties();
                });
                bb.jquery(document).bind("layout:itemDeleted",function(){
                    self.layoutManager.trigger("currentTemplateChange");
                });
                bb.jquery(document).bind("layout:saveTemplate",function() {
                    self.layoutToolsbar.trigger('saveLayout');
                });
                bb.jquery(document).bind("layout:removeTempTemplate",function() {
                    self.layoutToolsbar.removeTempTemplate();
                });
				
               bb.jquery(document).bind("content:ItemClicked",function(event,data){
                    self.tabManager.updateContentPath(data);
                });
            
                bb.jquery(document).bind("content:toggleEditionMode", function(event, data){
                    self.contentEditionManager.toggleEditionMode(data);
                    self.contentManager.toggleEditionMode(data);
                });
				
                /*content drag events*/
                bb.jquery(document).bind("content:startDrag",function(event,data){
                    self.contentEditionManager.disable();
                });
        
                bb.jquery(document).bind("content:stopDrag",function(event,data){
                    self.contentEditionManager.enable();
                });
                bb.jquery(document).bind("content:overItem",function(event,data){
                
                    });
                /*item deleted*/
                bb.jquery(document).bind("content:ItemDeleted",function(event,data){
                    self.tabManager.hidePath();
                });
            
                /* clean emptyContent placeHolder*/
                bb.jquery(document).bind("content:newContentAdded",function(event,reciever,newContent,sender){
                    self.contentEditionManager.cleanEmptyPlaceHolder(reciever,newContent,sender);
                    return;
                });
				
                bb.jquery(document).bind("tabItem:click",function(e,selectedTab,previousTab){                    
                    /*save currentTab in localStorage*/
                    self.db.set("selectedTab", selectedTab);
                    
                    /*leaving Editing zone[content | blocks]*/
                    if((previousTab==_settings.TAB_EDITION_BLOCKS) || (previousTab==_settings.TAB_EDITION_DATA) ||  (previousTab==_settings.TAB_EDITION_PAGE)){
                        var subContents = [_settings.TAB_EDITION_BLOCKS,_settings.TAB_EDITION_DATA,_settings.TAB_EDITION_PAGE];
                        if(bb.jquery.inArray(selectedTab,subContents)==-1){
                            _removeEditMode();
                            bb.ContentWrapper.persist();
                        }
                    }

                    switch(previousTab){
                        case _settings.TAB_STRUCTURE:
                            if(self.layoutManager) self.layoutManager.disable(); 
                            if(self.tabManager) self.tabManager.hidePath();
                            break;
                        
                        case _settings.TAB_EDITION:
                            if (self.editionTb) {
                                self.editionTb.deactivate();
                                self.editionTb.disable();
                            }
							
                            if(self.contentManager){
                                self.contentManager.disable();
                            }
                            if(self.contentEditionManager){
                                self.contentEditionManager.disable();
                            }
                            /*persist*/
                            bb.ContentWrapper.persist();
                            _removeEditMode(); //clean edit mode
                            break;
                        
                        case _settings.TAB_ZONING:
                            if(self.contentManager) self.contentManager.disable();
                            if(self.contentEditionManager) self.contentEditionManager.disable();
                            if(self.tabManager) self.tabManager.hidePath();
                            break;
                            
                        case _settings.TAB_THEMING:
                            
                            break;
                            
                        case _settings.TAB_EDITION_BLOCKS:
                            if(self.contentManager){
                                self.contentManager.disable();
                            }
                            if(self.contentEditionManager){
                                self.contentEditionManager.disable();
                            }
                            if (self.editionTb) {
                                self.editionTb.deactivate();
                            }
                            break;
                         
                        case _settings.TAB_EDITION_DATA:
                            if(self.editionTb) {
                                self.editionTb.deactivate();
                                self.editionTb.disable();
                            }
                            break;
                        
                        case _settings.TAB_EDITION_PAGE:
                        case _settings.TAB_STATUS:
                            if (self.statusManager)
                                self.statusManager.disable();
                            break;
                        default:
                            break;
                    }
                    switch(selectedTab){
                        case _settings.TAB_STRUCTURE:
                            if(AlohaManager) AlohaManager.stop();
                            if(!self.layoutManager){
                                self.initLayoutManager(); 
                               
                            } else {
                                self.layoutManager.enable();
                                self.layoutToolsbar._selectTemplate(_settings.layoutId);
                            }
                            break;
                            
                        case _settings.TAB_EDITION_BLOCKS:
                            if (self.editionTb) {
                                self.editionTb.activate();
                                _toggleEditMode("block");
                            }
							
                            if(self.contentManager){
                                self.contentManager.enable();
                            }
                            if(self.contentEditionManager){
                                self.contentEditionManager.enable();
                            }
                            break;
                        
                        case _settings.TAB_EDITION_DATA:
                            if(self.editionTb) self.editionTb.enable();
                            _toggleEditMode("content");
                            break;
                        
                        case _settings.TAB_EDITION_PAGE:
                            if(!self.statusManager){
                                self.statusManager = bb.StatusManager.init({
                                    pageId: _settings.pageId,
                                    layoutId: _settings.layoutId
                                });
                            }
                            if(AlohaManager) AlohaManager.stop();
                            self.statusManager.enable();
                            _toggleEditMode("page");
                            break;
                            
                        case _settings.TAB_ZONING:
                            return false;
                            if(self.contentManager){
                                self.contentManager.enable();
                            }
                            if(self.contentEditionManager){
                                self.contentEditionManager.enable();
                            }
                            break;
                            
                        case _settings.TAB_USER:
                            break;
                            
                        case _settings.TAB_THEMING:
                            break;
                      
                        case _settings.TAB_EDITION:
                            /*which subTab is selected*/
                            var selectedEditTab = _settings.TAB_EDITION_BLOCKS;
                            var tbManager = bb.ManagersContainer.getInstance().getManager("ToolsbarManager");
                            if (tbManager){
                                selectedEditTab = tbManager.getSelectedEditTab();
                                selectedEditTab = bb.jquery(selectedEditTab.tabPanel).attr("id");
                            }
                            
                            if (self.editionTb) {
                                self.editionTb.activate();
                            }
							
                            if(selectedEditTab == _settings.TAB_EDITION_BLOCKS){
                                if(self.contentManager){
                                    self.contentManager.enable();
                                }
                                if(self.contentEditionManager){
                                    self.contentEditionManager.enable();
                                }
                                _toggleEditMode("block");
                            }
                            else if (selectedEditTab == _settings.TAB_EDITION_DATA){
                                if(self.editionTb){ 
                                    self.editionTb.enable();
                                    _toggleEditMode("content");
                                } 
                            } else if (selectedEditTab == _settings.TAB_EDITION_PAGE){
                                if(!self.statusManager){
                                    self.statusManager = bb.StatusManager.init({
                                        pageId: _settings.pageId
                                    });
                                }
                                if(AlohaManager) AlohaManager.stop();
                                self.statusManager.enable();
                                _toggleEditMode("page");
                            }				
                            self.db.set("selectedTab", selectedEditTab);
                            break;
                        
                        case _settings.TAB_STATUS:
                            if(!self.statusManager){
                                self.statusManager = bb.StatusManager.init({
                                    pageId: _settings.pageId
                                });
                            }
                            if(AlohaManager) AlohaManager.stop();
                            self.statusManager.enable();
                            break;
							
                        case _settings.TAB_BUNDLE:
                            if(AlohaManager) AlohaManager.stop();
                            self.bundleTb.enable();
                            break;
						
                        default:
                            break;
                         
                    }
					
                    return false;
                });
            }
            
            
            /*BindDialogsEvents*/
            this._bindDialogsEvents = function(){
                var self = this;
                /*layoutEditorDialog:Save*/
                this.layoutEditorDialog.on("save",function(){
                    var templateName = bb.jquery(this.dialog).find(".content").val().trim();
                    if(templateName.length==0){
                        bb.jquery(this).find(".content").css({
                            border: "1px solid red"
                        });
                        return false;
                    }
                    
                    bb.jquery(this.dialog).find(".content").val("");
                    this.close();
                    self.layoutToolsbar.updateCurrentTemplateTitle(templateName);
                    self.layoutManager.setCurrentTemplateTitle(templateName);
                });
                
                /*open*/
                this.layoutEditorDialog.on("open",function(){
                    bb.jquery(this.dialog).find(".content").val(self.layoutManager.getCurrentTemplateTitle());
                    return;
                });
                
                /*zoneEditorDialog*/
                this.zoneEditorDialog.on("open",function(){
                    bb.jquery(this.dialog).find(".content").css({
                        border:""
                    });
                    var currentLayout = self.layoutManager.getSelectedLayout() || false;
                    if(!currentLayout) return false;
                    bb.jquery(this.dialog).find(".content").val(currentLayout.getTitle());
                });
                
                this.zoneEditorDialog.on("save",function(){
                    var zoneName = bb.jquery(this.dialog).find(".content").val().trim(); 
                    var currentLayout = self.layoutManager.getSelectedLayout() || false;
                    if(!currentLayout) return false;
                    if(zoneName.length==0) {
                        bb.jquery(this.dialog).find(".content").css({
                            border:"1px solid red"
                        });
                        return false;
                    }
					
                    if (zoneName != currentLayout.getTitle()) {
                        currentLayout.setTitle(zoneName);
                        self.layoutToolsbar.setZoneName(zoneName);
                        self.layoutToolsbar.updatePath(currentLayout);
                        self.layoutManager.trigger("currentTemplateChange");
                    }
					
                    this.close();
                });
            };
            
            
            /*LayoutToolsbar Events*/
            this._bindLayoutTbEvents = function(layoutToolsbar){
                var self = this;
                
                layoutToolsbar.on("duplicateLayout",function(){
                    var currentTemplate = self.layoutManager.getCurrentTemplate();
                    var newTemplate = self.layoutManager.clone(currentTemplate);
                    newTemplate.templateTitle = 'Copy of '+currentTemplate.templateTitle;
					
                    var data = {};
                    data[newTemplate.uid] = newTemplate;
                    self.layoutToolsbar.addUserTemplate(data);
					
                    self.layoutToolsbar.trigger('templateClick', '#template_'+newTemplate.uid+':last');
                    self.layoutManager.trigger("currentTemplateChange");
                });
               
                layoutToolsbar.on("deleteLayout",function(template) {
                    self.layoutWebservice.request("deleteLayout",{
                        params: {
                            uid:template.uid
                        },
                        success :function(response){
                            self.layoutToolsbar.deleteCurrentTemplate();
                        /*                            self.layoutManager.reset();
                            var currentTemplateTitle = self.layoutManager.getCurrentTemplateTitle();
                            self.layoutToolsbar.updateCurrentTemplateTitle(currentTemplateTitle);*/
                        },
                        error: function(response){
                            bb.jquery(document).trigger("application:error", {
                                title: "An error occured", 
                                message: "One or more pages use this layout, you have to previously remove them"
                            } );
                        }
                    });
                });
     
                layoutToolsbar.on("editLayoutName",function(){
                    self.layoutEditorDialog.show();
                });
                
                layoutToolsbar.on("editZoneName",function(){
                    if (bb.jquery('.bbBtn_smallSquare.editZoneName').hasClass('bbBtnDisabled')) return false;
                    self.zoneEditorDialog.show();
                });
                
                layoutToolsbar.on("saveLayout",function(){
                    var savedTemplate = self.layoutManager.saveTemplate();
                    var editMode = (-1 != savedTemplate.uid.indexOf('Layout_')) ? "creation" : "edit";
                    self.layoutWebservice.request("putTemplate",{
                        params : {
                            data: savedTemplate,
                            saveAsModel : false //enregistrer comme model
                        },
                        success :function(response){
                            self.layoutToolsbar.handleUserTemplateAction(response.result,editMode);
                        },
                        error :function(response){
                            throw response.error;
                        }
                    });
                });
                
                layoutToolsbar.on("templateClick",function(currentTemplate){
                    BB4.LayoutManager.setTemplate(currentTemplate);
                    self.layoutToolsbar.disableZoneProperties();
                });
                
                layoutToolsbar.on("modelTemplateClick",function(currentTemplateModel){
                    var newTemplate = self.layoutManager.clone(currentTemplateModel);
					
                    var data = {};
                    data[newTemplate.uid] = newTemplate;
                    self.layoutToolsbar.addUserTemplate(data);
					
                    self.layoutToolsbar.trigger('templateClick', '#template_'+newTemplate.uid+':last');
                    self.layoutManager.trigger("currentTemplateChange");
                });
                
                layoutToolsbar.on("splitH",function(){
                    var selectedLayout = self.layoutManager.getSelectedLayout();
                    self.layoutManager.splitLayout(selectedLayout,"splitH");
                });
            
                layoutToolsbar.on("splitV",function(){
                    var selectedLayout = self.layoutManager.getSelectedLayout();
                    self.layoutManager.splitLayout(selectedLayout,"splitV");               
                }); 
            
                layoutToolsbar.on("deleteItem",function(){
                    var selectedLayout = self.layoutManager.getSelectedLayout();
                    if (null == selectedLayout) return false;
                    self.layoutManager.removeLayout(selectedLayout);
                }); 
                
                layoutToolsbar.on("selectPath",function(e){
                    var layoutPath = bb.jquery(e.target);
                    var layoutId = layoutPath.attr("id").replace("path_","");
                    if(!layoutPath.hasClass("bbPath_kindActive")){
                        var layoutItem = self.layoutManager.getLayoutById(layoutId);
                        self.layoutManager.selectLayout(layoutItem);
                    }
                });
                
                layoutToolsbar.on("showGrid",function(e){
                    var isSelected = (bb.jquery(e.currentTarget).is(":checked"))?true:false;
                    self.db.set("enableLayoutGrid",isSelected);
                    self.layoutManager.toggleGridbackground(e.currentTarget);
                });
                
                layoutToolsbar.on("changeGridSize",function(gridSize){
                    self.db.set("gridSize",gridSize);
                    BB4.LayoutManager.setGridSize(gridSize);
                    
                    self.lessWebservice.request("sendLessGridConstant",{
                        params:{
                            gridColumnWidth : gridSize.colWidth,
                            gridGutterWidth : gridSize.gutterWidth
                        },

                        success: function(response){
                            less.refresh(true);
                            less.refresh(true);
                            var dateNow = new Date();
                            bb.jquery("#supraWrapper").css('background-image', 'url("/ressources/img/grid.png?d='+ dateNow.toString() +'")');
                            
                        },

                        error: function(response){
                            throw response.error; 
                        }
                    });
                
                });
            },
            this._bindContentTbEvents = function(contentsBlock){
                var self = this;
                contentsBlock.on("selectPath",function(contentNodeId,node){
                    if(!contentNodeId) return false;
                    
                    /*contextual actions*/
                    var tabPanel = self.tabManager.getSelectedEditTab()["tabPanel"].attr("id");
                    
                    if(tabPanel == _settings.TAB_EDITION_DATA){
                        self.editionTb._selectNodeContent(node);
                    }
                    if(tabPanel == _settings.TAB_EDITION_BLOCKS){
                        self.contentEditionManager.selectContent(node);  
                    }
                   
                });
            }
            
            return this._init();
        };
    }
    
    return {
        init : _init
    };      
})(bb.jquery,window);



/**/