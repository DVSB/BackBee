//@ sourceURL=ressources/js/ContentEditionManager.js
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var ContentEditionManager = (function($){

    var _settings = {
        contentClass : ".bb5-content",
        actionCtnCls : "bb5-ui bb5-content-actions",
        actionCtnClass :".bb5-content-actions",
        actionBtnCls :".bb5-button",
        delBtnCls :"bb5-btnDel",
        paramBtnCls :"bb5-btnParam",
        selectedContentClass : "bb5-content-selected",
        emptyContentCls : "bb5-content-void",
        droppableItemClass : ".bb5-droppable-item",
        contentHoverClass :".bb5-content-hover",
        contextMenuClass: ".bb5-context-menu",
        contextBtnClass :".bb5-contextBtn",
        siteWrapperId : "#bb5-site-wrapper",
        rootContentSetCls : "rootContentSet",
        contentWebService : "ws_local_contentBlock",
        classContentWebService : "ws_local_classContent",
        mainRootContainer : null,
        i18n:{
            contentSelectorLabel: bb.i18n.__('contentmanager.content_selector')
        }
    };
    var _contentWs = null;
    var _classContentWs = null;
    var _paramsEditorPopup = null;
    var _paramsForm = null;
    var _contentInfosDialog = null;
    var _isEnable = false;
    var _selectedContent = false;
    var _currentContentInfos = {};
    var _contextMenu = null;
    var _pageLinkedZonesInfos = [];
    var _paramsContainer = null;
    var _zoneLinksDialog = null;
    var _actionProgress = false;

    /*l18n*/
    var _messages = {
        MSG_DELETE_ERROR : bb.i18n.__('contentmanager.deletion_error'),
        MSG_DELETE_CONFIRM : bb.i18n.__('contentmanager.deletion_confirm'),
        MSG_LINKDIALOG_TITLE : bb.i18n.__('contentmanager.linked_areas'),
        MSG_LINK_CONFIRM : bb.i18n.__('contentmanager.link_area_confirm'),
        MSG_UNLINK_CONFIRM : bb.i18n.__('contentmanager.unlink_area_confirm')
    };

    var _context = {
        contentSelector : null,
        useScroll : true
    };


    /*content plugins*/
    var _availablePluginsBtns = {};

    /*content default Actions*/
    var _availableBtns = {
        "bb5-ico-info" : {
            btnClass:"bb5-button bb5-ico-info bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('contentmanager.info_title'),
            btnCallback:"showContentInfo"
        },

        "bb5-ico-unlink":{
            btnClass:"bb5-button bb5-ico-unlink bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('contentmanager.unlink_area'),
            btnCallback:"unlinkColToParent"
        },

        "bb5-ico-link":{
            btnClass:"bb5-button bb5-ico-link bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('contentmanager.link_area'),
            btnCallback:"linkColToParent"
        },

        "bb5-ico-parameter" : {
            btnClass:"bb5-button bb5-ico-parameter bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('contentmanager.parameters'),
            btnCallback:"showContentParams"
        },

        "bb5-ico-lib":{
            btnClass:"bb5-button bb5-ico-lib bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('contentmanager.content_selector'),
            btnCallback:"showContentSelector"
        },

        "bb5-ico-edit" : {
            btnClass:"bb5-button bb5-ico-edit bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('popupmanager.button.edit'),
            btnCallback : "editContent"
        },

        "bb5-ico-del" : {
            btnClass:"bb5-button bb5-ico-del bb5-button-square bb5-invert",
            btnTitle: bb.i18n.__('popupmanager.button.remove'),
            btnCallback:"delContent"
        }
    };

    var _menuCallbacks = {

        /*Afficher l*/
        showContentParams: function(bbContent){
            if('infos' in bbContent){
                var bbContent = $bb(bbContent.infos.contentEl);
            }
            _selectContent(bbContent.contentEl);
            var contentParams = bbContent.getContentParams();
            // content is updated when params are saved
            //async persist before params edition
            //bb.ContentWrapper.persist(false);
            _showCurrentContentparams(contentParams);
        },

        showContentSelector : function(selectedNodeInfo){
            var bbContent = $bb(selectedNodeInfo.infos.contentEl);

            var filterParams = {
                maxentry:bbContent.getMaxEntry(),
                accept:bbContent.getAccept(),
                receiver:bbContent
            };

            // var nodeData = _collectReceiverDatas(selectedNodeInfo.infos.contentEl);
            //console.log(nodeData);
            //nodeData = bb.jquery.extend(nodeData,selectedNodeInfo.infos);

            var contentSelector = null;
            if(_context.contentSelector)
            {
                contentSelector =_context.contentSelector;
            }else{
                _createContentsSelector();
                contentSelector = _context.contentSelector;
            }

            var contentWidget = _getContentSelectorWidget();
            contentWidget.bbContentSelector("setFilterParameters",filterParams);
            _context.contentSelector.bbSelector("open");
        },


        showContentInfo : function(selectedNodeInfo){
            var ws = bb.webserviceManager.getInstance('ws_local_classContent');
            var params = {
                classname: selectedNodeInfo.infos.contentType,
                uid: selectedNodeInfo.infos.contentId
            };
            ws.request('find', {
                params: params,
                success: function(response){
                    if (response.result) {
                        var content = response.result;
                        content.contentPic = bb.baseurl+'ressources/img/contents/'+params.classname.replace('\\', '/')+'.png';
                        content.created = new Date(content.created.date);
                        content.modified = new Date(content.modified.date);
                        var pict = content.contentPic;
                        var name = content.properties.name;
                        var descLabel = bb.i18n.__('contentmanager.description');
                        ;
                        var desc = content.properties.description;
                        var cdateLabel = bb.i18n.__('contentmanager.creation_date');
                        var cdate = content.created.getDate() + '/' +  content.created.getMonth() + '/' +  content.created.getFullYear() + ' ' + content.created.getHours() + ':' + content.created.getMinutes();
                        var mdateLabel = bb.i18n.__('contentmanager.modification_date');
                        var mdate = content.modified.getDate() + '/' +  content.modified.getMonth() + '/' +  content.modified.getFullYear() + ' ' + content.modified.getHours() + ':' + content.modified.getMinutes();
                        var revLabel = bb.i18n.__('contentmanager.revision_number');
                        var rev = content.revision;
                        var html = '<div class="bb5-dialog-info"><p class="bb5-dialog-visualclue-x"><i style="background-image:url('+pict+')"></i></p><div class="bb5-dialog-info-desc"><p><strong>'+name+'</strong></p><p><em>'+descLabel+'</em><br>'+desc+'</p><p><em>'+cdateLabel+'</em><br>'+cdate+ '</p><p><em>'+mdateLabel+'</em><br>'+mdate+'</p><p><em>'+revLabel+'</em><br>'+rev+'</p></div>';
                        bb.jquery(_contentInfosDialog.dialog).html(html);
                        _contentInfosDialog.show();
                    }
                },
                error: function(response){
                }
            });
        },

        delContent : function(selectedNodeInfo){
            _deleteContent(selectedNodeInfo.infos);
        },

        selectContent : function(nodeInfos){
            _selectContent(nodeInfos.infos.contentEl);
        },

        editContent : function(nodeInfos){
            var editionTb = bb.ToolsbarManager.getTbInstance("editiontb");
            if(editionTb){
                editionTb._editContent(nodeInfos.infos.contentEl);
            }
        },

        unlinkColToParent : function(nodeInfos){

            /*unlinkClosure*/
            var unlinkClosure = function(){
                var ws = bb.webserviceManager.getInstance('ws_local_classContent');
                var pageId = bb.frontApplication.getPageId();
                var params = {
                    pageId : pageId,
                    contentSetId : nodeInfos.infos.contentId
                };

                ws.request("unlinkColToParent",{
                    params : params,
                    success:function(response){
                        if(response.result){
                            _pageLinkedZonesInfos.linkedZones.remove(nodeInfos.infos.contentId); //enlever de la liste des zones liées
                            _handleNewRootContent(nodeInfos,response);
                        }else{
                            _zoneLinksDialog.close();
                        }
                    },

                    error : function(){
                        _zoneLinksDialog.close();
                    }
                })
            }
            _zoneLinksDialog.setContent(bb.jquery("<p>"+_messages.MSG_UNLINK_CONFIRM+"</p>"));
            _zoneLinksDialog.setExtra({
                action:"unlink",
                callback:unlinkClosure
            });
            _zoneLinksDialog.show();
        },


        linkColToParent : function(nodeInfos){
            _zoneLinksDialog.setContent(bb.jquery("<p>"+_messages.MSG_LINK_CONFIRM+"</p>"));

            var linkClosure = function(){
                var ws = bb.webserviceManager.getInstance('ws_local_classContent');
                var params ={
                    pageId : bb.frontApplication.getPageId(),
                    contentSetId : nodeInfos.infos.contentId
                };
                ws.request("linkColToParent",{
                    params : params,
                    success : function(response){
                        if(response.result){
                            _pageLinkedZonesInfos.linkedZones.push(response.result.newContentUid);
                            _handleNewRootContent(nodeInfos,response);
                        }else{
                            _zoneLinksDialog.close();
                        }

                    },
                    error : function(){
                        _zoneLinksDialog.close();
                    }

                })
            }
            _zoneLinksDialog.setExtra({
                action:"link",
                callback:linkClosure
            });
            _zoneLinksDialog.show();

        }

    };

    var _handleNewRootContent = function(nodeInfos,response){
        var scripts = bb.jquery(response.result.render).find("script");
        var nwZone = bb.jquery(response.result.render).get(0);
        nwZone = bb.jquery(nwZone).clone();
        bb.jquery(nwZone).addClass(_settings.rootContentSetCls);
        /*clean new content here*/
        bb.jquery(nodeInfos.infos.contentEl).replaceWith(nwZone);
        if(scripts.length){
            bb.jquery(nwZone).append(bb.jquery(scripts));
        }
        var contentManager = bb.ManagersContainer.getInstance().getManager("ContentManager");
        /*Make is droppable or sortable etc*/
        contentManager.handleNewContent(bb.jquery(nwZone));
        _selectContent(bb.jquery(nwZone));
        _zoneLinksDialog.close();
    }

    var _init = function(userConfig){
        var userConfig = userConfig || {};
        bb.jquery.extend({},_settings,userConfig);
        var popupDialog = bb.PopupManager.init({});
        _paramsEditorPopup = popupDialog.create("contentParamsEditor",{
            
            title: bb.i18n.__('contentmanager.parameters'),
            buttons : {
                "Save" : {
                    text: bb.i18n.__('popupmanager.button.save'),
                    click: function(){
                        /* add validation */
                        var hasError = _paramsForm.validate();
                        if(hasError) return false;
                        var params = _paramsForm.parse();
                        if(_selectedContent){
                            var content = $bb(_selectedContent);

                            /*deep extendDefaultParams*/
                            var result = {};
                            bb.jquery.each(content.get("param"),function(key,item){
                                result[key] = bb.jquery.extend(true,item,params[key]);
                            });
                            /*content.set("param","")*/
                            bb.ContentWrapper.persist(false);//save first
                            content.updateContentRender(); //then update
                        }
                        _paramsEditorPopup.close();
                        return;
                    }
                },
                "Cancel" :  {
                    text : bb.i18n.__('popupmanager.button.cancel'),
                    click: function(){
                        _paramsEditorPopup.close();
                    }
                }
            },
            maxHeigh : 200
        });


        bb.ManagersContainer.getInstance().register("ContentEditionManager",publicApi);
        _contentInfosDialog = popupDialog.create("contentInfo",{
            title: bb.i18n.__('contentmanager.info_title'),
            dialogType : popupDialog.dialogType.INFO,
            buttons: {
                Fermer : function(){
                    bb.jquery(this).dialog('close');
                }
            }
        });

        var contentSelector = _settings.contentClass+',.'+_settings.rootContentSetCls;

        var cntxMenuActions = {

            "btn-infos":{
                btnCls:"bb5-button bb5-ico-info",
                btnLabel: bb.i18n.__('contentmanager.info_title'),
                btnCallback: function(e,node){
                    var nodeInfos = _getInfosFromNode(node);
                    _menuCallbacks.showContentInfo.call(publicApi,nodeInfos);
                }
            },

            "btn-params":{
                btnCls:"bb5-button bb5-ico-parameter",
                btnLabel: bb.i18n.__('contentmanager.parameters'),
                btnCallback: function(e,node){
                    var nodeInfos = _getInfosFromNode(node);
                    var bbContent = $bb(node);
                    _menuCallbacks.showContentParams.call(publicApi,bbContent);
                }
            },
            "btn-select":{
                btnCls:"bb5-button bb5-ico-select",
                btnLabel: bb.i18n.__('popupmanager.button.select'),
                btnCallback: function(e,node){
                    var nodeInfos = _getInfosFromNode(node);
                    _menuCallbacks.selectContent.call(publicApi,nodeInfos);
                }
            },
            "btn-lib":{
                btnCls:"bb5-button bb5-ico-lib",
                btnLabel: bb.i18n.__('contentmanager.content_selector'),
                btnCallback: function(e,node){
                    var nodeInfos = _getInfosFromNode(node);
                    _menuCallbacks.showContentSelector.call(publicApi,nodeInfos);
                }
            },

            "btn-edit":{
                btnCls:"bb5-button bb5-ico-edit",
                btnLabel: bb.i18n.__('popupmanager.button.edit'),
                btnCallback: function(e,node){
                    var nodeInfos = _getInfosFromNode(node);
                    _menuCallbacks.editContent.call(publicApi,nodeInfos);
                }
            },

            "btn-del":{
                btnCls:"bb5-button bb5-ico-del",
                btnLabel: bb.i18n.__('popupmanager.button.remove'),
                btnCallback: function(e,node){
                    var nodeInfos = _getInfosFromNode(node);
                    _menuCallbacks.delContent.call(publicApi,nodeInfos);
                }
            }
        };

        _contextMenu = new bb.LpContextMenu({
            contentSelector : contentSelector,
            menuActions: cntxMenuActions,
            beforeShow : function(node){
                //this == bb.LpContextMenu instance
                var bbContent = $bb(node);
                if(bbContent){
                    var filters = [];
                    if(!bbContent.isContentSet) filters.push("bb5-ico-lib"); //enlever le selecteur de contenus
                    if(bbContent.isAnAutoBlock) filters.push("bb5-ico-lib"); //désactiver le selecteur pour les autoblocs
                    if(bbContent.isARootContentSet) filters.push("bb5-ico-del"); //enlever le bouton effacer
                    //if(_isEnable) filters.push("bb5-ico-edit");
                    if (bbContent.forbidenActions) {
                        for (var i = 0; i < bbContent.forbidenActions.length; i = i + 1) {
                            filters.push('bb5-ico-' + bbContent.forbidenActions[i]);
                        }
                    }

                    this.setFilters(filters);
                }

            }
        });

        /* load linked zones */
        _getCurrentPageLinkedZones();
        _bindEvents();
        _markEmptyBlocks(_getMainRootContainer());
        _createZoneLinkConfirmDialog();


        return publicApi;
    }

    /**
     * get The rootcontentsets main container
     */
    var _getMainRootContainer = function(){
        if(_settings.mainRootContainer) return rootContainer;
        var rootContainers = bb.jquery("."+_settings.rootContentSetCls).eq(0);
        if( rootContainers.length > 0 ){
            var rootContainer = rootContainers.parents(".row,.row-fluid").eq(0);
            _settings.mainRootContainer = rootContainer;
        }
        return _settings.mainRootContainer;
    }

    var _createZoneLinkConfirmDialog = function(){
        var popupDialog = bb.PopupManager.init({});
        var title = _messages.MSG_LINKDIALOG_TITLE;
        _zoneLinksDialog = popupDialog.create("zoneLinkConfirmDialog",{
            title : title,
            modal :true,
            buttons :{
                "Oui": {
                    text: bb.i18n.__('popupmanager.button.yes'),
                    click: function(){
                        var extraParams = _zoneLinksDialog.getExtra();
                        if(extraParams && typeof extraParams.callback=="function"){
                            bb.jquery(_zoneLinksDialog.dialogUi).mask(bb.i18n.loading);
                            extraParams.callback();
                        }
                        return;
                    }
                },
                "Non": {
                    text: bb.i18n.__('popupmanager.button.no'),
                    click : function(){
                        _zoneLinksDialog.close();
                        return;
                    }
                }
            }
        });

        _zoneLinksDialog.on("close",function(){
            bb.jquery(_zoneLinksDialog.dialogUi).unmask();
        });
    }


    var _getCurrentPageLinkedZones = function(){
        var contentWs = _getClassContentWs();
        var globalParams = _getGlobalParamsContainer();
        contentWs.request('getPageLinkedZones', {
            params : {
                pageId : globalParams.getPageId()
            },
            async : false,
            success : function(response){
                if (response.result) {
                    _pageLinkedZonesInfos = response.result;
                }
            },
            error : function(response){}
        });
    }


    var _getGlobalParamsContainer = function(){
        var result = null;
        if(_paramsContainer){
            result = _paramsContainer;
        } else{
            result = bb.ManagersContainer.getInstance().getManager("GlobalParamsManager");
        }
        return result;
    }

    /*handle params menu here*/
    //    var _showCurrentContentparams = function(currentContentInfos){
    //        var contentWs = _getContentWs();
    //        contentWs.request("getContentParameters",{
    //            params :currentContentInfos,
    //            success : function(response){
    //                var form = _buildParamsForm(response.result);
    //                _paramsEditorPopup.setContent(bb.jquery(form));
    //                _paramsEditorPopup.show();
    //            },
    //            error: function(){}
    //        });
    //    }



    /*collect data*/
    var _collectReceiverDatas = function(contentNode){
        var result = {};
        var attributesToCollect = ["data-uid","data-accept","data-type","data-rendermode","data-maxentry"];
        bb.jquery.each(attributesToCollect,function(i,attributesToCollect){
            var key = attributesToCollect.replace("data-","");
            result[key] = bb.jquery(contentNode).attr(attributesToCollect);
        });
        result["isAContentSet"] = (bb.jquery(contentNode).hasClass(_settings.droppableItemClass.replace(".","")))?true:false;
        return result;
    }

    var originalContainerHeight = null;
    var resizeStep = 0;

    var _createContentsSelector = function(){
        _context.contentSelector = bb.jquery('#bb5-dialog-content-selector').bbSelector({
            popup: true,
            mediaSelector: false,
            modal: false,
            pageSelector: false,
            linkSelector: false,
            contentSelector: true,
            selectorTitle: _settings.i18n.contentSelectorLabel,
            resizable :false,
            draggable: false,
            //site: bb.frontApplication.getSiteUid(),
            callback: function(item) {
                bb.jquery('#bb5-dialog-content-selector').bbSelector('close');
            },

            close : function(){
                var widget = bb.jquery(_context.contentSelector).bbSelector("getWidget","bbContentsSelectorContainer");
                bb.jquery(widget).data()["bbContentSelector"].reset();
            }

        }).bbSelector("getWidgetApi");


        var widget = bb.jquery(_context.contentSelector).bbSelector("getWidget","bbContentsSelectorContainer");
        _context.contentSelectorWidget = widget;
        bb.jquery(_context.contentSelector).bind("bbcontentselectorselectcontent",function(event,data){
            var contents = [];
            /*clean contents here*/
            bb.jquery.each(data.selectedContent,function(key,contentNode){
                contents.push(bb.jquery(contentNode).data("content"));
            });

            data.receiver.append({
                content:contents
            });

        });

    };
    var _getContentSelectorWidget = function(){
        return _context.contentSelectorWidget;
    }

    var _enable = function(){
        _isEnable = true;
        _contextMenu.enable();

        bb.jquery("body").bind("click.blockEdit",function(){
            if(!_isEnable) return;
            _hideContextMenu();
            return true;
        });
    }


    var _disable = function(){
        bb.jquery(_selectedContent).removeClass(_settings.selectedContentClass);
        bb.jquery(_settings.actionCtnClass).remove();
        _contextMenu.disable();
        _selectedContent = false;
        _isEnable = false;
        bb.jquery("body").unbind("click.blockEdit");
    }



    /*setSelected content*/
    var _updateSelectedContent = function(content){
        if(_selectedContent){
            bb.jquery(_selectedContent).removeClass(_settings.selectedContentClass);
        }
        _selectedContent = (content) || false;
        if(_selectedContent){
            bb.jquery(_selectedContent).addClass(_settings.selectedContentClass);
        }

    }

    /*Update content params*/
    var _updateParams = function(params){
        var ws = _getContentWs();
        ws.request("updateContentparameters",{
            params : {
                params:params,
                contentInfos:_currentContentInfos.infos
            },
            success : function(response){
                if(response.error){
                    bb.Utils.handleAppError("Error while update content",response);
                    console.log(response.error);
                }
            },
            error : function(response){
                bb.Utils.handleAppError("Error while update content",response);
            }

        });
    }

    /*userfor*/
    var _getInfosFromNode = function(node){
        var contentUid = bb.jquery(node).attr("data-uid")||null;
        var contentType = bb.jquery(node).attr("data-type")||null;
        var _selectedNodeInfo = {};

        _selectedNodeInfo.infos = {
            contentType : contentType,
            contentId : contentUid,
            contentEl : node
        };
        return _selectedNodeInfo;

    }

    var _getContentWs = function(){
        if(!_contentWs){
            _contentWs = bb.webserviceManager.getInstance(_settings.contentWebService);
        }
        return _contentWs;
    }

    var _getClassContentWs = function(){
        if(!_classContentWs){
            _classContentWs = bb.webserviceManager.getInstance(_settings.classContentWebService);
        }
        return _classContentWs;
    }


    var _buildContentActions = function(filters){
        var filters = filters || [];
        var btnsContainer = bb.jquery("<div></div>").clone();
        btnsContainer.addClass(_settings.actionCtnCls);

        bb.jquery.each(_availableBtns,function(key,btnConfig){
            if(bb.jquery.inArray(key,filters) ==-1){
                var btn = bb.jquery("<button></button>").clone();
                bb.jquery(btn).addClass(btnConfig.btnClass).attr("title",btnConfig.btnTitle);
                bb.jquery(btn).attr("data-type",key);
                bb.jquery(btnsContainer).append(bb.jquery(btn));
            }
        });
        /*show plugins action here*/
        bb.jquery.each(_availablePluginsBtns,function(key,btnConfig){
            if(bb.jquery.inArray(key,filters) ==-1){
                var btn = bb.jquery("<button/>").clone();
                bb.jquery(btn).addClass(btnConfig.btnClass).attr("title",btnConfig.btnTitle);
                bb.jquery(btn).attr("data-type",key);
                bb.jquery(btn).bind("click",btnConfig.btnCallback);
                bb.jquery(btnsContainer).prepend(bb.jquery(btn));
            }
        });

        return btnsContainer;
    }

    /*put in a class */
    var _handleContentPluginActions = function(pluginActions){

        var pluginActions = bb.jquery.isArray(pluginActions) ? pluginActions : [];
        var actionsContainer = {};

        bb.jquery.each(pluginActions,function(i,actionInfos){
            var btnClass = "bb5-button #cls# bb5-button-square bb5-invert";
            btnClass = btnClass.replace("#cls#",actionInfos.icoCls);
            var action = {
                btnClass:"",
                btnTitle:"",
                btnCallback:""
            };
            action.btnClass = btnClass;
            action.btnTitle = actionInfos.label;
            action.btnCallback = actionInfos.command.execute;
            _availablePluginsBtns[actionInfos.icoCls] = action;
        });
        /* refresh content actions */
        _showActionsForContent(_selectedContent);
        /*reset available*/
        _availablePluginsBtns = {};
    }


    var _bindEvents = function(){
        var contentSelector = _settings.contentClass+',.'+_settings.rootContentSetCls;

        bb.jquery(contentSelector).live("mouseenter",function(e,userData){
            if(!_isEnable) return true;
            e.stopImmediatePropagation();
            var currentTarget = e.currentTarget;
            /*removeallhoverclass*/
            bb.jquery(_settings.contentHoverClass).removeClass(_settings.contentHoverClass.replace(".",""));

            /*addHover for current*/
            bb.jquery(document).trigger("bbcontent:contentSelected",{
                selected : currentTarget
            });
            bb.jquery(currentTarget).addClass(_settings.contentHoverClass.replace(".",""));
        /*if( bb.jquery(".bb5-droppable-place").length){
                bb.jquery(".bb5-droppable-place").show();
            }*/
        });

        bb.jquery(contentSelector).live("mouseleave",function(e){
            if(!_isEnable) return true;
            var currentTarget = bb.jquery(e.currentTarget);
            var parentToSelect = bb.jquery(currentTarget).parent(_settings.contentClass);
            bb.jquery(currentTarget).removeClass(_settings.contentHoverClass.replace(".",""));
            if((parentToSelect) && parentToSelect.length!=0){
                bb.jquery(parentToSelect).trigger("mouseenter",{
                    userTrigger :true
                });
            }

        });

        bb.jquery(contentSelector).live("click",function(e){
            if(!_isEnable) return true;
            e.stopPropagation();
            _hideContextMenu();
            /*creation d'un bbContent*/
            var bbContent = $bb(e.currentTarget);
            var currentContent = e.currentTarget;
            _selectContent(currentContent);
            /*  bb.jquery(document).trigger("bbcontent:clicked",{
                content : bbContent
            });*/
            //_selectNodeContent(currentContent);
            return false;
        });

        /*ActionsEvent*/
        _bindContentActionEvents();
        _bindContextActionEvents();

        /*content resize event*/
        bb.jquery(document).bind("ContentResized:onResizeStart",function(e,data){
            /*masques le content action s'il était visible*/
            _updateSelectedContent(data.contentEl);
            if(_selectedContent){
                bb.jquery(_settings.actionCtnClass).remove();
                return;
            }
        });

        bb.jquery(document).bind("ContentResized:onResizeStop",function(e,data){
            _updateSelectedContent(data.contentEl);
            if(_selectedContent){
                _showActionsForContent(_selectedContent);
            }
            return;
        });




    }


    var _buildNodeInfos = function(nodeInfos){
        var template = bb.jquery("<div></div>").clone();
        bb.jquery(template).addClass("contentInfos");

        bb.jquery.each(nodeInfos,function(key,value){
            var template = bb.jquery("<p class='confKey'></p>").clone();
        });
    }


    var _hideContextMenu = function(){
        bb.jquery(_settings.contextMenuClass).hide();
    }

    var _selectNodeContent = function(currentContent){
        _showActionsForContent(currentContent);
        
        /*chemin*/
        var path = _getContentPath(currentContent);
        var pathInfos = {
            selectedNode : currentContent,
            items : path,
            itemTitleKey : "data-type",
            itemClass : ".contentNodeItem",
            itemIdKey : "data-uid"
        };
        bb.jquery(document).trigger("content:ItemClicked",[pathInfos,$bb(currentContent)]);
        return true;
    }

    /*var _showContextMenu = function(contextMenuParams){
        _hideContextMenu();

        var _contextMenuExists = false;
        var contextMenu = null;
        var position = contextMenuParams.menuPosition;
        if(bb.jquery(_settings.contextMenuClass).length){
            contextMenu  = bb.jquery(_settings.contextMenuClass).first();
            _contextMenuExists = true;
        }else{
            var contextMenu = bb.jquery("<div></div>").clone();
            contextMenu.addClass(_settings.contextMenuClass.replace('.',''));
            bb.jquery(contextMenu).css({
                border: "1px solid red",
                width: "150px",
                height: "150px",
                position: "absolute",
                background : "white"
            });
            var actionContainer = bb.jquery("<ul></ul>").clone();
            actionContainer.append(bb.jquery("<li class='contextBtn btnShowInfos'><a>Infos</a></li>"));
            actionContainer.append(bb.jquery("<li class='contextBtn btnShowParams'><a>Paramètres</a></li>"));
            actionContainer.append(bb.jquery("<li class='contextBtn btnDelete'><a>Effacer</a></li>"));
            actionContainer.append(bb.jquery("<li class='contextBtn btnSelect'><a>Selectionner</a></li>"));
            bb.jquery(actionContainer).find("a").attr("href","javascript:;");
            contextMenu.append(actionContainer);
        }
        bb.jquery(contextMenu).css({
            top:position.top+"px",
            left:position.left+"px"
        });

        bb.jquery(contextMenu).data("currentContentInfos",contextMenuParams);
        if(!_contextMenuExists){
            bb.jquery(contextMenu).appendTo(bb.jquery("body"));
        }else{
            bb.jquery(contextMenu).show();
        }

        return bb.jquery(contextMenu);
    }*/



    /*actionEvents*/
    var _bindContentActionEvents = function(){

        bb.jquery(_settings.actionBtnCls).live("click",function(e){

            e.preventDefault();
            e.stopPropagation();
            if(!_selectedContent) return false;

            var contentUid = bb.jquery(_selectedContent).attr("data-uid")||null;
            var contentType = bb.jquery(_selectedContent).attr("data-type")||null;
            var _selectedNodeInfo = {};

            _selectedNodeInfo.infos = {
                contentType : contentType,
                contentId : contentUid,
                contentEl : _selectedContent
            };

            bb.jquery(e.currentTarget).css({
                position:"relative"
            });
            //_currentContentInfos = _selectedNodeInfo;

            var btnType = bb.jquery(e.currentTarget).attr("data-type")|| null;
            if(!btnType) return false;

            /*execute Callbacks*/
            var btnCallBack = _availableBtns[btnType]||null;
            if(!btnCallBack) return false;
            var btnCallback = _availableBtns[btnType].btnCallback;
            if(!typeof _menuCallbacks[btnCallback] =="function") return false;
            var currentNode = $bb(_selectedContent);
            _menuCallbacks[btnCallback].call(this,_selectedNodeInfo);
            return;

        });
    }

    var _bindContextActionEvents = function(){
        bb.jquery(_settings.contextBtnClass).live("click",function(e){

            var parent = bb.jquery(this).parents(_settings.contextMenuClass);
            var currentContentInfos = bb.jquery(parent).data("currentContentInfos");


            if(bb.jquery(this).hasClass("btnShowInfos")){
                var mc = bb.ManagersContainer.getInstance();
                var contentManager = mc.getManager("ContentManager");
            }

            if(bb.jquery(this).hasClass("btnShowParams")){
                _showCurrentContentparams(currentContentInfos.infos);
            }

            if(bb.jquery(this).hasClass("btnDelete")){
                _deleteContent(currentContentInfos.infos);
            }

            if(bb.jquery(this).hasClass("btnSelect")){
                _selectNodeContent(currentContentInfos.infos.contentEl);
            }

            _hideContextMenu();
        });
    }

    var _actionsBeforeRender = function(selectedContent,actionsBar){
        var newContent = actionsBar;
        if(bb.jquery(selectedContent).hasClass(_settings.rootContentSetCls)){
            bb.jquery(actionsBar).find("."+_settings.delBtnCls).remove();
        }
        return newContent;
    }


    var _checkEmptyBlocks = function(content){
        /*block avec des sous-contenus*/
        var emptyBlocks = bb.jquery(content).find(_settings.droppableItemClass).filter(function(){
            return !bb.jquery(this).children("not:"+_settings.contentClass).length;
        });
        if(emptyBlocks){
            bb.jquery(emptyBlocks).addClass(_settings.emptyContentCls);
        }
        return;
    }

    var _markEmptyBlocks = function(content){
        var containers = bb.jquery(content).find(_settings.droppableItemClass);
        bb.jquery.each(containers,function(i,item){
            _markEmptyBlocks(item);
        });

        if(containers.length==0){
            if(bb.jquery(content).hasClass(_settings.droppableItemClass.replace(".",""))){
                var bbContents = bb.jquery(content).children(_settings.contentClass);
                if(bbContents.length==0){
                    var bbContent = $bb(content);
                    bbContent.showEmptyZone();
                /*bb.jquery(content).addClass(_settings.emptyContentCls);*/
                //bb.jquery(content).animate({
                //    minHeight:"100px"
                //},"slow");
                }
            }
            return;
        }
    }

    var _handleEmptyContent = function(newContent){
        var newContent = (newContent) ? $bb(newContent) : false;
        if(!newContent) return false;
        var contentChildren = $bb(newContent).getChildren();
        if(contentChildren.length){
            bb.jquery.each(contentChildren,function(i,child){
                _handleEmptyContent(child);
            });
        }
        $bb(newContent).showEmptyZone();
    /*if(bb.jquery(newContent).hasClass(_settings.droppableItemClass.replace(".",""))){
            if(bb.jquery(newContent).find(_settings.contentClass).length==0){
                bb.jquery(newContent).addClass(_settings.emptyContentCls);
            bb.jquery(newContent).animate({
                minHeight:"100px"
            },"slow");
            }
        }*/
    //_checkEmptyBlocks(newContent);
    }

    var _toggleEditionMode = function(mode) {
        if (mode) {
            _markEmptyBlocks(_getMainRootContainer());
        } else {
            bb.jquery('.'+_settings.emptyContentCls).removeClass(_settings.emptyContentCls);
        }
    }

    /*handle path here*/
    var _getContentPath = function(content){
        var contentPath = [];

        if(content){
            var result = bb.jquery(content).parentsUntil('div[class *="span"]"');
            var total = result.length;
            var i = total-1;
            for(i; i>=0;i--){
                var node = result[i];
                if($(node).hasClass("rootContentSet") || $(node).hasClass(_settings.contentClass.replace(".",""))){
                    contentPath.push(node);
                }   
            }
            contentPath.push(content);
        }
        return contentPath;
    }


    var _hideActionMenu = function(){
        bb.jquery(_settings.actionCtnClass).remove(); //remove actions menu
    }

    var _isRootContentSetLinked = function(bbContent){
        if(bbContent && bbContent.isARootContentSet){
            var contentUid = bbContent.getUid();
            /*si la zone n'est pas liée ou si c'est la zone principale*/
            if(bb.jquery.inArray( contentUid,_pageLinkedZonesInfos.linkedZones )==-1 || (bb.jquery.inArray(contentUid,_pageLinkedZonesInfos.mainZones)!=-1)){
                return false;
            }else{
                return true;
            }
        }
    }
    var _isAMainZone = function(bbContent){
        if(bbContent && bbContent.isARootContentSet){
            var contentUid = bbContent.getUid();
            if(bb.jquery.inArray(contentUid,_pageLinkedZonesInfos.mainZones)==-1){
                return false;
            }else{
                return true;
            }

        }
    }

    var _showActionsForContent = function(clickedContent){
        /*hideAction*/
        bb.jquery(_settings.actionCtnClass).remove(); //remove previous actions
        _updateSelectedContent(clickedContent);
        var bbContent = $bb(clickedContent);

        var revieverData = _collectReceiverDatas(clickedContent);

        var filters = [];
        /*desactive le selection de contenu pour les contentSet et les autoBlock*/
        if(!bbContent.isContentSet || bbContent.isAnAutoBlock){
            filters.push("bb5-ico-lib");
        }
        /* Le contentSet ne peut pas être effacer */
        if(bbContent && bbContent.isARootContentSet){
            filters.push("bb5-ico-del");
        }

        /* unlink or link is only available for rootContentSet */
        if(bbContent && (!bbContent.isARootContentSet || _isAMainZone(bbContent))){
            filters.push("bb5-ico-unlink");
            filters.push("bb5-ico-link");
        }

        /*but not for all of them*/
        if(bbContent.isARootContentSet && !_isRootContentSetLinked(bbContent)){
            filters.push("bb5-ico-unlink");
        }

        if(bbContent.isARootContentSet && _isRootContentSetLinked(bbContent)){
            filters.push("bb5-ico-link");
        }




        /*disable edit in contentNode*/
        /*if(_isEnable){
            filters.push("bb5-ico-edit");
        }*/

        if (bbContent.forbidenActions) {
            for (var i = 0; i < bbContent.forbidenActions.length; i = i + 1) {
                filters.push('bb5-ico-' + bbContent.forbidenActions[i]);
            }
        }

        var contentAction = _buildContentActions(filters);
        var contentAction =_actionsBeforeRender(clickedContent,contentAction);
        bb.jquery(clickedContent).css("position","relative");
        bb.jquery(contentAction).css({
            "position":"absolute"
        });

        bb.jquery(clickedContent).append(contentAction);
    }

    var _deleteContent = function(currentContentInfos){
        if(confirm(_messages.MSG_DELETE_CONFIRM)){ //l18n
            var bbContent = $bb(currentContentInfos.contentEl);
            if(bbContent){
                bbContent.destroy({
                    onDestroy : function(){
                        _hideActionMenu();
                        _selectedContent = false;
                        bb.jquery(document).trigger("content:ItemDeleted",[currentContentInfos]);
                    }
                });
                return true;
            }
        }else{
            return false;
        }
    }
    /**
     *cf _deleteContent
     */
    var _cleanEmptyPlaceHolder = function(reciever,newContent,sender){
        if(reciever) $bb(reciever).hideEmptyZone();
        if(newContent) {
            _handleEmptyContent(newContent);
        }
        /*check sender*/
        //if(sender) $bb(sender).showEmptyZone();
        if(sender) _handleEmptyContent(sender);
    }

    var _showCurrentContentparams = function(contentParams){
        var form = _buildParamsForm(contentParams);
        _paramsEditorPopup.setContent(bb.jquery(form));
        _paramsEditorPopup.show();
    }


    var _buildParamsForm = function(params){
        var params = params || false;
        bb.FormBuilder.mainContainer = _paramsEditorPopup;
        _paramsForm = new bb.FormBuilder({
            params:params
        });

        var content = _paramsForm.render();
        return content;
    }



    var _selectContent = function(nodeInfo,scrollToContent){
        var block = nodeInfo || "";
        var node = (typeof nodeInfo =="string") ? bb.jquery('[data-uid="'+nodeInfo+'"]') : bb.jquery(nodeInfo);
        if(_isEnable) bb.Utils.scrollToContent(bb.jquery(node),1000,250);
        _selectNodeContent(node);
    }

    var publicApi = {
        enable : _enable,
        disable: _disable,
        selectContent : _selectContent,
        cleanEmptyPlaceHolder: _cleanEmptyPlaceHolder,
        hideContextMenu : _hideContextMenu,
        showContentParamsEditor : _showCurrentContentparams,
        toggleEditionMode: _toggleEditionMode,
        handlePluginsActions: _handleContentPluginActions,
        getContextMenu : function(){
            return _contextMenu;
        }
    };

    return {
        init:_init
    };

})(bb.jquery);

(function($) {
    /* Form Builder to put to a file*/
    FormBuilder = function(settings){
        this.settings = {
            formCls : "paramCls",
            noParams : bb.i18n.__('contentmanager.none_parameter')
        }
        this.cleanParams = null;
        this.disabledInfos = {};
        this.rendererArr = {};

        if(typeof this.init!="function"){
            FormBuilder.prototype.init = function(userSettings){
                this.settings = bb.jquery.extend(true,{},this.settings,userSettings);
                this.formTemplate = bb.jquery("<form></form>").clone();
                this.formId = bb.Utils.generateId("form");
                bb.jquery(this.formTemplate).addClass(this.settings.formCls);
                bb.jquery(this.formTemplate).attr("id",this.formId);
            }
        }

        // fixme how to process values
        /*this.valuesProcessor = {
        "array" : function(value){
            return JSON.stringify(value);
        },

        "scalar" : function(value){
            return value;
        },
        "select":function(){},

        process :function(keyType, value){
            return this[keyType].call(this,value);
        }
    };*/

        this.fieldsBuilder = {
            getWrapper : function(){
                var fieldWrapper = bb.jquery("<div></div>").clone();
                fieldWrapper.addClass("fromField");
                return fieldWrapper;
            },

            noParams : function(){
                var fieldWrapper = bb.jquery("<div></div>").clone();
                bb.jquery(fieldWrapper).addClass("fromField");
                var msg = "<p>"+this.settings.noParams+"</p>";
                bb.jquery(fieldWrapper).append(bb.jquery(msg));
                return fieldWrapper;
            }
        };

        /*handle param filters*/
        FormBuilder.prototype.applyParamsFilter = function(disabledParams){
            var self = this;
            var disabledParams = this.settings.params["disabledparams"]||[];
            disabledParams = ("array" in disabledParams) ? disabledParams.array : [];
            /*remove item if it's in the disable array*/
            var cloneParams = bb.jquery.extend({},this.settings.params);

            if(disabledParams.length){
                delete(cloneParams["disabledparams"]);
            }

            /*disable edit for some params*/
            bb.jquery.each(disabledParams,function(index,paramName){
                var keyInfos = paramName.split("::");
                if(keyInfos.length == 1){
                    delete(cloneParams[keyInfos[0]]);
                    self.disabledInfos[paramName] = true;
                }else{
                    if(!bb.jquery.isArray(self.disabledInfos[keyInfos[0]])){
                        self.disabledInfos[keyInfos[0]] = new Array();
                    }
                    var disabledProp = self.disabledInfos[keyInfos[0]];
                    disabledProp.push(keyInfos[1]); /*ref to new Array*/
                    var props = cloneParams[keyInfos[0]]["array"];
                    delete(props[keyInfos[1]]);
                }
            });
            return cloneParams;

        }

        FormBuilder.prototype.render = function(){
            var result = document.createDocumentFragment();
            var params = this.applyParamsFilter(this.settings.params);
            this.cleanParams = params;


            var self = this;
            if(!params){
                var msg = self.fieldsBuilder["noParams"].call(self);
                var fieldSet = bb.jquery("<fieldset></fieldset>").clone();
                bb.jquery(fieldSet).append(msg);
                result.appendChild(bb.jquery(fieldSet).get(0));
            }
            else{
                bb.jquery.each(params,function(key,param){
                    var fieldInfos = {};
                    fieldInfos.fieldLabel = key;
                    fieldInfos.param = param;
                    /*FieldInfos*/
                    var renderType = ( "array" in param ) ? param.array.rendertype : "scalar";

                    var renderer = FormBuilder.createRenderer(renderType,{
                        fieldInfos : fieldInfos,
                        formId : self.formId,
                        disabledFields : self.disabledInfos[key]
                    });

                    if(!renderer){
                        console.warn(" Renderer ["+renderType+"] Can't be found");
                        return true;
                    }
                    var formRender = renderer.render();
                    self.rendererArr[key] = renderer;

                    var hidden = "";
                    if ('undefined' != typeof (param.array) && 'undefined' != typeof (param.array.hidden)) {
                        if (param.array.hidden == true) {
                            hidden = "style='display:none;'";
                        }
                    }
                    var fieldSet = bb.jquery("<fieldset " + hidden + "></fieldset>").clone();
                 
                    bb.jquery(fieldSet).append(formRender);
                    result.appendChild(bb.jquery(fieldSet).get(0));
                });
            }
            /*wrap form*/
            result = bb.jquery(this.formTemplate).html(bb.jquery(result));
            return result;
        }

        FormBuilder.prototype.parse = function(){
            var result = {};
            bb.jquery.each(this.rendererArr,function(key,renderer){
                result[key] =  renderer.parse();
            });
            return result;
        }

        FormBuilder.prototype.validate = function(){
            var hasError = false;
            bb.jquery.each(this.rendererArr,function(key,renderer){
                if(!renderer.validate()){
                    hasError = true;
                    return true;
                }
            });
            return hasError;
        }




        /*init here*/
        this.init(settings);
    }

    /*RenderType Manager*/
    FormBuilder.rendererPlugins = [];
    FormBuilder.registerRenderTypePlugin = function(rendererName,rendererConfig){
        var renderName = rendererName || false;
        if(!renderName) throw " renderName can't be null";

        var AbstractPluginPrototype = {
            _initialize: function(){
                this.id = bb.Utils.generateId(rendererName);
                this.name = rendererName;
                this.mainContainer = FormBuilder.mainContainer;
                if(typeof this._init=="function"){
                    this._init();
                }
            },
            render : function(){
                return bb.jquery("<p>render function must be overwitten in <strong>"+rendererName+"</strong> plugin</p>").clone();
            },
            parse : function(){
                return bb.jquery("<p>parse function must be overwitten in <strong>"+rendererName+"</strong> plugin</p>").clone();
            },
            /**
         * valide le formulaire avant de le poster
         * si aucune fonction n'est fournie, le formulaire est considéré comme étant valide
         * return true
         */
            validate: function(){
                return true;
            },
            onOpen: function(){},
            onClose : function(){}
        }

        var MockFunc = function(){
            /*cleanRenderConfig*/
            var properties = {};
            for (property in rendererConfig){
                if(typeof property != "function"){
                    properties[property] = rendererConfig[property];
                }
            }
            bb.jquery.extend(true,this,rendererConfig);
        };

        /*Renderer Contructor*/
        var RendererConstructor = function(userSettings){
            MockFunc.call(this);
            this._settings = bb.jquery.extend(true,this._settings,userSettings);
            this._initialize();
            /*should bind only once*/
            this.mainContainer.unbind("open").on("open", this.onOpen,this);
            this.mainContainer.unbind("close").on("close",this.onClose,this);
        }

        /*all functions in prototype*/
        var protoFunc = {};
        for (prop in rendererConfig){
            if(typeof rendererConfig[prop]=="function") protoFunc[prop] = rendererConfig[prop];
        }
        var PluginPrototype = bb.jquery.extend({},AbstractPluginPrototype);
        RendererConstructor.prototype = bb.jquery.extend(true,PluginPrototype,protoFunc);
        FormBuilder.rendererPlugins[rendererName] = RendererConstructor;
    }

    FormBuilder.createSubformRenderer = function(paramName,paramsOption,mainFormId){
        /**
     * cf the yaml format of the params
     * Step 1. wrap params with an array
     * Step 2. adapt paramsOption for renderer
     **/
        if(!bb.jquery.isPlainObject(paramsOption)) throw "paramsOption MUST BE AN OBJECT [FormBuilder.createSubformRenderer]";
        if(typeof paramName != "string") throw "paramName MUST BE A STRING [FormBuilder.createSubformRenderer]";
        if(typeof mainFormId != "string") throw "mainFormId MUST BE A STRING [FormBuilder.createSubformRenderer]";
        if(typeof paramsOption.rendertype=="string"){
            var paramsWithArr = {
                "array" : paramsOption
            };
            var cleanParams = {};
            cleanParams[paramName] = paramsWithArr;
        }


        /*paramsOption*/
        var fieldInfos = {};
        fieldInfos.fieldLabel = paramName;
        fieldInfos.param = paramsWithArr;

        var renderer = FormBuilder.createRenderer(paramsOption.rendertype,{
            fieldInfos : fieldInfos,
            formId : mainFormId,
            disabledFields :[]
        });
        if(!renderer){
            console.warn(" Renderer ["+paramsOption.renderType+"] Can't be found");
            return false;
        }
        return renderer;




    }

    /*get renderer*/
    FormBuilder.createRenderer = function(renderName,userConfig){
        try{
            var renderer = FormBuilder.rendererPlugins[renderName]|| false;
            if(!renderer) throw "RENDERER NOT FOUND";
            return new FormBuilder.rendererPlugins[renderName](userConfig);
        }catch(e){
            console.log(e+" "+renderName+" not found");
        }
    }

    bb.FormBuilder = FormBuilder;

}) (bb.jquery);
