/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor
 * Gestion des grids
 * 
 * 
 */
var BB4 = (BB4) ? BB4 : {};

(function($) {

BB4.LayoutManager = (function(){
    var _layoutsContainer = [];
    var _currentLayout = false;
    var _isrendered = false;
    var _enable = true;
    var _cache = '';
    var _propDialog;
    var _context = {
        enableGrid: false
    };
    
    var _currentTemplate = {};
    
    var _settings = {  
        defaultContainer : "#bb5-mainLayoutRow",
        layoutClass : ".bb4ResizableLayout, .bb5-resizableLayout", //bb4 <-> bb5
        selectedLayoutClass :"bb5-layout-selected", //selectedLayout
        actionsBtnClass : ["bbBtnEditSmall","bbBtnSplitH","bbBtnSplitV","bbBtnDisabled","bbPath_kindBlock"],
        layoutActionCls :"bb5-layoutActionContainer",
        layoutId : null
        
    };
    
    var _init = function(userSettings){
        _context.enableGrid = (DbManager.init("BB4").get("enableLayoutGrid"))?DbManager.init("BB4").get("enableLayoutGrid"):false;  
        bb.jquery.extend(true,_settings,userSettings);
        _initDialogs();
        _bindEvents();
        _load();
        return publicApi;
    };
    
    /**
    *Charger le layout actuel 
    *s'il est diponible sinon un template
    *par défaut
    **/
    var _load = function(){
        if(_settings.layoutId && typeof _settings.layoutId=="string"){
        }
    }
    
    var _initDialogs = function() {
        var popupDialog = bb.PopupManager.init({
            dialogSettings:{
                modal: true
            }
        });
        popupDialog.registerDialogType("propertiesLayout", bb.jquery("#bb5-zonelayout-properties").html());
        _propDialog = popupDialog.create("propertiesLayout",{
            title: bb.i18n.__('layoutmanager.properties'),
            buttons:{
                "Save" : {
                    text: bb.i18n.__('popupmanager.button.save'),
                    click : function(){
                        if (_currentLayout) {
                            var currentParams = {
                                _mainZone: _currentLayout._mainZone,
                                _accept: _currentLayout._accept.join('|'),
                                _maxentry: _currentLayout._maxentry,
                                _defaultClassContent: _currentLayout._defaultClassContent
                            };

                            _currentLayout._mainZone = (0 < bb.jquery(this).find('#bb5-zonelayout-property-ismainzone:checked').length);
                            _currentLayout._accept = [];
                            bb.jquery.each(bb.jquery(this).find('#bb5-zonelayout-property-accept option:selected'), function(index, option) {
                                _currentLayout._accept.push(option.value);
                            });
                            _currentLayout._maxentry = bb.jquery(this).find('#bb5-zonelayout-property-maxentry').val();
                            _currentLayout._defaultClassContent = bb.jquery(this).find('#bb5-zonelayout-property-defaultcontent').val() || null;

                            if (currentParams._mainZone != _currentLayout._mainZone
                                || currentParams._accept != _currentLayout._accept.join('|')
                                || currentParams._maxentry != _currentLayout._maxentry
                                || currentParams._defaultClassContent != _currentLayout._defaultClassContent) {
                                _notify("currentTemplateChange");
                            }
                        }

                        bb.jquery(this).dialog("close");
                        return;
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
		
        _propDialog.on('open', function() {
            if (!_currentLayout) bb.jquery(this.dialog).dialog('close');
            console.log(_currentLayout);
            bb.jquery(this.dialog).find('#bb5-zonelayout-property-ismainzone').removeAttr('checked');
            bb.jquery(this.dialog).find('#bb5-zonelayout-property-accept option').removeAttr('selected');
            bb.jquery(this.dialog).find('#bb5-zonelayout-property-maxentry').val('');
            bb.jquery(this.dialog).find('#bb5-zonelayout-property-defaultcontent option').removeAttr('selected');
			
            if (_currentLayout._mainZone)
                bb.jquery(this.dialog).find('#bb5-zonelayout-property-ismainzone').attr('checked', 'checked');
			
            if (0 == _currentLayout._accept.length) {
                bb.jquery(this.dialog).find('#bb5-zonelayout-property-accept option').first().attr('selected', 'selected');
            } else {
                for(var i=0; i<_currentLayout._accept.length; i++) {
                    bb.jquery(this.dialog).find('#bb5-zonelayout-property-accept option[value="'+_currentLayout._accept[i].replace('\\', '\\\\')+'"]').attr('selected', 'selected');
                }
            }
			
            bb.jquery(this.dialog).find('#bb5-zonelayout-property-maxentry').val(_currentLayout._maxentry);
			
            if( null == _currentLayout._defaultClassContent)
                bb.jquery(this.dialog).find('#bb5-zonelayout-property-defaultcontent option').first().attr('selected', 'selected');
            else {
                var optionToSelect = $("#bb5-zonelayout-property-defaultcontent").val(_currentLayout._defaultClassContent);
                //bb.jquery(this.dialog).find('#bb5-zonelayout-property-defaultcontent option[value="'+_currentLayout._defaultClassContent+'"]').attr('selected', 'selected');
            }
        });
    };
	
    var _setTemplateTitle = function(newTplTitle){
        var tplTitle = newTplTitle || bb.i18n.__('layoutmanager.untitled_template');
		
        if (_currentTemplate.templateTitle != tplTitle) {
            _currentTemplate.templateTitle = tplTitle;
            _notify("currentTemplateChange");
        }
    };

    /*mettre template ici*/
    var _setTemplate = function(templateItem){
        _currentTemplate = templateItem;
        _currentTemplate.isModified = false;
    
        if(!_currentTemplate.templateLayouts) throw "templateLayouts can't be found";
        if((_currentTemplate.templateLayouts) && _currentTemplate.templateLayouts.length > 0){
            _loadLayoutStructure(_currentTemplate.templateLayouts);
        }
    };
    
    var _reset = function(){
        var _defautTemplate = {
            uid : null,
            templateTitle:bb.i18n.__('layoutmanager.untitled_template'), 
            templateLayouts:[{
                title:"root",
                id:"rootLayout",
                target:"#bb5-mainLayoutRow",
                gridSize:12
            }],
            gridSize : _settings.gridSize, //old size
            picpath : "",
            site : _settings.site
        };
        
        _setTemplate(_defautTemplate);
    };
    
    var _bindEvents = function(){
        bb.jquery(document).bind("layout:sizeChanged",bb.jquery.proxy(_initLayoutResizable));
        bb.jquery(document).bind("layout:ItemDeleted",bb.jquery.proxy(_initLayoutResizable));
        
        /*click on Layout Item*/
        bb.jquery(_settings.layoutClass).die().live("click",function(e){
            if(!_enable) return false;
            var selectedLayout = _findLayoutById(bb.jquery(e.currentTarget).attr("id"));
            return _selectLayout(selectedLayout);
        });
        
        /*Click elsewhere*/
        bb.jquery(document).bind("click",function(e){
            if(!bb.jquery(e.currentTarget).hasClass("bb5-resizableLayout")){
                var keepSelected = (0 < bb.jquery(e.target).parents().filter('#bbWrapper, .bb5-dialog-wrapper').length);
                bb.jquery.each(_settings.actionsBtnClass,function(i,className){
                    if(bb.jquery(e.target).hasClass(className)){
                        keepSelected = true;
                        return;
                    }
                });                 
                if(!keepSelected && _currentLayout){
                    _currentLayout.unSelect();
                    bb.jquery("."+_settings.layoutActionCls).remove();
                } 
            }  
        });
    };


    /*Afficher les actions pour la zone*/
    var _showZoneActions = function(selectZone){
    
        /*remove previous action*/
        bb.jquery(".bb5-layoutActionContainer").remove();
        var actionCtn = bb.jquery('<div class="bb5-ui bb5-content-actions bb5-layoutActionContainer">'
            +'<button class="bb5-button bb5-ico-parameter bb5-button-square bb5-invert" title="'+bb.i18n.__('layoutmanager.properties')+'"></button>'
            +'<button class="bb5-button bb5-ico-del bb5-button-square bb5-invert" title="'+bb.i18n.__('popupmanager.button.remove')+'"></button>'
            +'</div>').clone();
    
        /*bind event here*/
        bb.jquery(actionCtn).find(".bb5-ico-parameter").unbind().bind("click",function(e){
            selectZone.layoutManager.editZoneProperties();
            return;
        });
    
        bb.jquery(actionCtn).find(".bb5-ico-del").unbind().bind("click",function(e){
            selectZone.layoutManager.removeLayout();
            return;
        });
        /*add to zone*/
        bb.jquery(actionCtn).appendTo(bb.jquery(selectZone._layout));
    }



    var _selectLayout = function(selectedLayout){
        if(!selectedLayout) return false;
        if(_currentLayout) _currentLayout.unSelect();
        _currentLayout = selectedLayout;
        _currentLayout.select();
        var pathToTop = _currentLayout.getPath();
        var layoutsOnPath = _preparePath(pathToTop);
        layoutsOnPath.push(_currentLayout);
        var pathInfos = {
            selectedLayout:_currentLayout, 
            pathInfos:layoutsOnPath
        };
        
        /*show action*/
        _showZoneActions(selectedLayout);
        /*notify layout selection*/
        _notify("itemSelected",pathInfos);
        return false;
    };
   
    var _saveLayoutStructure = function(){
        var savedLayout = _walkThrough(bb.jquery(_settings.defaultContainer));
        _currentTemplate.templateLayouts = savedLayout;
        _currentTemplate.gridSize = _settings.gridSize;
        _currentTemplate.isModified = false;
        return _currentTemplate;
    };
    
    /*
     * Enregistrer la structure des layouts
     */
    var _walkThroughTree = function(node,data,isRoot){
        var data = data || [];
        var nodeList = bb.jquery(node).children(_settings.layoutClass);
    
        bb.jquery.each(nodeList,function(i,layout){
            var nodeInfo = {};
            nodeInfo.id = bb.jquery(layout).attr("id");
            if(isRoot==true){
                nodeInfo.type = "rootChild";  
            }else{
                nodeInfo.type = (bb.jquery(layout).hasClass("vChild"))?"vChild":"hChild"; 
            }
            nodeInfo.layoutSettings = _findLayoutById(nodeInfo.id).serialize(); 
            nodeInfo.children = _walkThrough(layout,[]);
            data.push(nodeInfo);    
        });
 
        if(isRoot==true){
            var rootNode = {};
            rootNode.id = bb.jquery(node).attr("id");
            rootNode.type = "root";
            rootNode.children = data;
            return rootNode;
        }
        return data;
    };
    
    /**/
    var _walkThrough = function(rootNode, buildFromDom){
        var data = []; 
        var buildFromDom = buildFromDom || false;
        var _walkThroughRec = function(rootNode){
            var nodeList = bb.jquery(rootNode).children(_settings.layoutClass);
            if(nodeList.length>0){
                
                var parentId = bb.jquery(rootNode).attr("id");
                parentId = parentId || _generateId();
                bb.jquery(rootNode).attr("id",parentId);

                bb.jquery.each(nodeList,function(i,layoutNode){
                    var id = bb.jquery(layoutNode).attr("id");
                    var cpt = i+1;
                    if(!buildFromDom){
                        var serializedLayout = _findLayoutById(id).serialize();
                        data.push(serializedLayout);   
                    }
                    else{
                        id = id || _generateId();
                        bb.jquery(layoutNode).attr("id",id);
                        var sizePattern = /col-md-.*/gi;
                        console.log(sizePattern);
                        var classes = bb.jquery(layoutNode).attr("class");
                        var currentSize = parseInt(sizePattern.exec(classes)[0].replace("col-md-",""));
                        
                        var layoutNodeInfo = {};
                        layoutNodeInfo.id = id;
                        layoutNodeInfo.title = ( bb.jquery(layoutNode).attr("title") && bb.jquery(layoutNode).attr("title").length > 0)? bb.jquery(layoutNode).attr("title") :_generateId("zone"); 
                        layoutNodeInfo.position = "none";
                        layoutNodeInfo.gridSize = currentSize;
                        layoutNodeInfo.target = "#"+parentId;
                        layoutNodeInfo.alphaClass = (bb.jquery(layoutNode).hasClass("alpha")) ? "alpha" : "";
                        layoutNodeInfo.omegaClass = (bb.jquery(layoutNode).hasClass("omega")) ? "omega" : "";
						
                        layoutNodeInfo.mainZone = layoutNode._mainZone;
                        layoutNodeInfo.accept = layoutNode._accept;
                        layoutNodeInfo.maxentry = layoutNode._maxentry;
                        layoutNodeInfo.dafault = layoutNode._defaultClassContent;
                      
                        /*------------------------------Hchild------------------------------*/
                        if(bb.jquery(layoutNode).hasClass("hChild")){
                            var hSibling = bb.jquery(rootNode).children(".hChild").not(layoutNode);
                            layoutNodeInfo.typeClass = "hChild";
                            layoutNodeInfo.clearAfter = 1;
                            layoutNodeInfo.resizable = false;
                            layoutNodeInfo.height = "computed";// enfant horizontal half-parent size
                            if(hSibling){
                                var siblingId = bb.jquery(hSibling).attr("id") || _generateId();
                                bb.jquery(hSibling).attr(id,"id");
                                layoutNodeInfo.hSibling = siblingId;
                            }
                        }
                        
                        /*------------------------------VChild------------------------------*/
                        if(bb.jquery(layoutNode).hasClass("vChild")){
                            if(bb.jquery(layoutNode).hasClass("omega")){
                                layoutNodeInfo.clearAfter = 1;
                                layoutNodeInfo.typeClass = "vChild";
                            } 
                        }
                        
                        data.push(layoutNodeInfo);              
                    }
                });
                
                bb.jquery.each(nodeList,function(i,layoutNode){
                    _walkThroughRec(layoutNode);
                });
            }
        };
        _walkThroughRec(rootNode,buildFromDom);
        return data;        
    };

    var _preparePath = function(path){
        var layoutsOnPath = [];
        var total = path.length;
        var i = total-1; 
        if(path.length==0) return layoutsOnPath;
        for(i; i>=0;i--){
            var node = path[i];
            var layoutItem = _findLayoutById(bb.jquery(node).attr("id"));
            layoutsOnPath.push(layoutItem);
        }
        return layoutsOnPath;
    };
    
    /*faire function update path*/ 
    var _notify = function(eventName, eventData){
        var eventKey = "layout:"+eventName;
        var eventData = eventData || false;
        bb.jquery(document).trigger(eventKey,[eventData]);
    };
    
    var _getLayoutInfos = function(){
        var container = [];
        bb.jquery.each(_layoutsContainer,function(i,layoutItem){
            if(!layoutItem._isDeleted){
                container.push(layoutItem.toJson());
            } 
        });
        return container;
    };
    
    
    var _generateId = (function(){
        var genPrefix = "Layout_";
        var current = 0;
        return function(prefix){
            var currentPrefix = prefix || genPrefix;
            var currentTime = new Date().getTime();
            return currentPrefix+'_'+currentTime+'_'+current++;
        }
    })();
    
    var _createLayout = function(config){
        config.id = (!config.id) ? _generateId() : config.id; 
        config.target = (config.target) ? config.target : _settings.defaultContainer;
        config.defaultContainer = _settings.defaultContainer;
        config.layoutManager = publicApi;
        config.selectedClass = _settings.selectedLayoutClass;
        var layoutItem = new BB4.LayoutItem(config);
        _layoutsContainer.push(layoutItem);
        return layoutItem;
    };
    
    var _drawAll = function(){
        /*use layoutItems*/
        bb.jquery.each(_layoutsContainer, function(i,layout){
            layout.create();
        });          
        _initLayoutResizable();
    };
    
    /**
     *Charger et aficher les layouts
     **/
    var _loadLayoutStructure = function(layoutStructure){
        var layoutStructure = layoutStructure || false;
        if(!layoutStructure) return false;
        /*reset container*/
        _layoutsContainer = [];
        bb.jquery(_settings.defaultContainer).html("");
        bb.jquery.each(layoutStructure,function(i,nodeConfig){
            _createLayout(nodeConfig);
        });
        _drawAll();
    };
    
    /*initResizable*/
    var _initLayoutResizable = function(){
        bb.jquery.each(_layoutsContainer,function(i,layout){
            var handles = [];
            
            if(layout.getChildType() == "vChild"){
                bb.jquery(layout._layout).removeClass("alpha omega");
                var isAvChild = true;
            }
            
            var hasSiblings = layout.hasSiblings();
            if(!hasSiblings.hasRightSibling){
                handles.push("w");
            }
           
            if(hasSiblings.hasRightSibling){
                handles.push("e");
            } 
            
           
            if(isAvChild){
                 
                if(!hasSiblings.hasRightSibling){
                    bb.jquery(layout._layout).addClass("omega");//last one
                    delete(layout._settings.alphaClass);
                    if(!bb.jquery(layout._layout).next().hasClass("clear")){
                        bb.jquery(layout._layout).after("<div class='clear'></div>");
                    }
                }
                if(!hasSiblings.hasLeftSibling){
                    bb.jquery(layout._layout).addClass("alpha");
                    delete(layout._settings.omegaClass);
                } 
            }
            
            var layoutHasTarget =(layout.getTarget().length > 0)? bb.jquery(layout.getTarget()).attr("id"):false;
            if(!layoutHasTarget){
                if(layout.getParent()){

                    layout._settings.target = "#"+layout.getParent().getId();
                }else{
                    layout._settings.target = layout._settings.defaultContainer;
                }
            }
                
            /*cas splitH ni right ni left*/
            if(!hasSiblings.hasRightSibling && !hasSiblings.hasLeftSibling){
                handles = ["e"];  
            } 
            layout.setResizableHandlesRegion(handles);
        });
        
        
        /*met à jour les infos des layouts avec des nouvelles*/
        var layoutInfos = _getLayoutInfos();
        bb.jquery(document).trigger("LayoutManager:layoutsDraw",[layoutInfos]);
    };
    
    var _addLayout = function(layout){
        layout._settings.defaultContainer = _settings.defaultContainer;
        _layoutsContainer.push(layout);
    };
    
    var _findLayoutById = function(IdToFind){
        var result = false;
        bb.jquery.each(_layoutsContainer,function(i,layout){
            if(layout.getId() == IdToFind){
                result =layout;
                return false;
            }
        });
        return result;
    };
    
    var _setGridSize = function(newGridSize){
        _settings.gridSize = newGridSize;
        _currentTemplate.gridSize = newGridSize;
    /*update Layout*/
    };
    
    var _getGridSize = function(){
        return _settings.gridSize; 
    };
    
    var _loadFromDom = function(template){
        var template = ((template) && template.length > 0) ? template : "<p style='color:red; font-weight:bold'>"+bb.i18n.__('layoutmanager.dialog.select_valid_template')+"</p>";
        /*retrieve data from dom*/
        var layoutData = _walkThrough(template,true);
        /*draw zones*/
        _loadLayoutStructure(layoutData);
       
          
    };
  
    var _enableMng = function(){
        //bb.jquery(_settings.contentId).find('object').remove();
        _cache = bb.jquery(_settings.contentId).get(0).innerHTML; //fix a strange jQuery html() bug
        bb.jquery(_settings.contentId).empty().html('<div class="container"><div id="bb5-templateHeader" class="bb5-lockedLayout"></div><div class="row bb5-layout" id="bb5-mainLayoutRow" style="height:800px;"></div><div id="bb5-templateFooter" class="bb5-lockedLayout"></div></div>');
        if(_context.enableGrid){
            bb.jquery(_settings.contentId+' > div.container').css('background-image', 'url('+bb.baseurl+bb.resourcesdir+'img/grid.png)');
            bb.jquery(_settings.contentId+' > div.container').css('background-size', '100% 100%');
            bb.ToolsbarManager.getTbInstance("layouttb").selectGridBtn();
        }
        
        /*header and footer*/
        bb.jquery(_settings.tplHeaderId).addClass("col-md-"+_settings.gridSize);
        bb.jquery(_settings.tplFooterId).addClass("col-md-"+_settings.gridSize);
		
        if (0 == _layoutsContainer.length) {
            var playGroundLayoutL = new BB4.LayoutItem({
                title:"root",
                id:"rootLayout",
                target:"#bb5-mainLayoutRow",
                gridSize : _settings.gridSize,
                layoutManager: publicApi,
                selectedClass: _settings.selectedLayoutClass
            });
			
            _addLayout(playGroundLayoutL);
        }	
        _drawAll();
        _enable = true;
		
        return publicApi;
    };
    
    var _disableMng = function(){
        _reset(); 
        
        bb.jquery(_settings.contentId).get(0).innerHTML = _cache;
        //bb.jquery(_settings.contentId).css('background', '');
        
        bb.jquery.each(bb.jquery(_settings.layoutClass),function(i,layout){
            bb.jquery(layout).removeClass(_settings.selectedLayoutClass);
            bb.jquery(layout).resizable("destroy");
        });
        
        /*disable click on layout*/
        _enable = false;
    };
    
    var _confirmLayoutSave = function(callback, arg) {
        if (_currentTemplate && _currentTemplate.isModified) {
            var popupDialog = bb.PopupManager.init({
                dialogSettings:{
                    modal: true
                }
            });
            popupDialog = popupDialog.create("confirmDialog",{
                title: bb.i18n.__('layoutmanager.dialog.modified_template'),
                buttons:{
                    "Save" : {
                        text: bb.i18n.__('popupmanager.button.save'),
                        click : function(){
                            _notify('saveTemplate');
                            bb.jquery(this).dialog("close");
                            return callback(arg);
                        }
                    },
                    "Cancel": {
                        text: bb.i18n.__('popupmanager.button.cancel'),
                        click: function(a){
                            if (-1 != _currentTemplate.uid.indexOf('Layout_')) {
                                /* remove unsaved added template from carousel */
                                _notify('removeTempTemplate');
                            }
                            bb.jquery(this).dialog("close");						
                            return callback(arg);
                        }
                    }
                }
            });
            bb.jquery(popupDialog.dialog).html(bb.i18n.__('layoutmanager.dialog.save_modification'));
            popupDialog.show();
        } else {
            callback(arg);
        }
    };
	
    var _editZoneProperties = function() {
        _propDialog.show();
    };
	
    var _cloneTemplate = function(template) {
        var _newTemplate = {
            uid : _generateId(),
            templateTitle: bb.i18n.__('layoutmanager.untitled_template'), 
            templateLayouts:[],
            gridSize : _settings.gridSize,
            picpath : template.picpath,
            site : _settings.site
        };
		
        bb.jquery.each(template.templateLayouts, function(index, layoutItem) {
            var _newLayoutItem = {};
            for(var i in layoutItem) {
                _newLayoutItem[i] = layoutItem[i];
            }
            _newTemplate.templateLayouts.push(_newLayoutItem);
        });
		
        return _newTemplate;
    };
	
    var publicApi = {
        createLayout : _createLayout,
        reset: _reset,
        setCurrentTemplateTitle : _setTemplateTitle,
        saveTemplate : _saveLayoutStructure,
        getCurrentTemplate : function() {
            return _currentTemplate;
        },
        getCurrentTemplateTitle : function(){
            return _currentTemplate.templateTitle;
        },
        getLayoutById : _findLayoutById,
        drawAll : _drawAll,
        disable : function() {
            _confirmLayoutSave(_disableMng);
        },
        enable : _enableMng,
        getSelectedLayout : function(){
            return _currentLayout;
        },
        selectLayout : _selectLayout,
        removeLayout : function(layout){
            layout = layout || this.getSelectedLayout();
            if (null == layout) return false;
            if(layout.isRemovable()){
                this.unSelectAll();
                /*effacer le Layout*/
                layout.remove();
                _notify("currentTemplateChange");
            }
        },
        editZoneProperties : _editZoneProperties,
        addLayout : _addLayout,
        unSelectAll : function(){
            bb.jquery(_settings.layoutClass).removeClass(_settings.selectedLayoutClass);
            bb.jquery('.bbLayoutButtons').hide();
            _currentLayout = null;
            _notify("noneItemSelected");
        },
        clone: _cloneTemplate,
        splitLayout: function(layout,splitType){
            if(!layout) return false;
            
            /*Split si seulement la taille est inférieure*/
            if(layout._gridSize==1) return false;
            if(splitType=="splitH"){
                this.splitH(layout);
            }
            else{
                this.splitV(layout); 
            }
        },
        trigger: _notify,
		
        splitH : function(layout){
            
            var hasChildren = layout.getChildren();
            if(hasChildren.length != 0) return false; 
            var nbItems = [1,1];
            var itemSize = layout._gridSize;
            var self = this;
            var layoutItems = [];
            bb.jquery.each(nbItems, function(){
                var newLayoutId = _generateId();
                var newlayouItemConfig = {
                    id:newLayoutId,
                    target:"#"+layout.getId(),
                    gridSize:itemSize,
                    alphaClass : "alpha",
                    omegaClass : "omega",
                    typeClass: "hChild",
                    title : "",
                    clearAfter:1,
                    resizable : false,
                    height :  bb.jquery(layout._layout).height()/2 //half its parent's width  
                };
                /*layoutChildrenInfos.childrenIDs.push("#"+newLayoutId);
                layout._layoutChildrenInfos = layoutChildrenInfos;*/
                layoutItems.push(self.createLayout(newlayouItemConfig));
            });
            var firstH = layoutItems[0];
            var secondH = layoutItems[1];
            firstH._hSibling = secondH;
            secondH._hSibling = firstH;
            this.drawAll();
			
            _notify("currentTemplateChange");
        },
        
        splitV : function(layout){
           
            var hasChildren = layout.getChildren();
            if(hasChildren.length != 0) return false; 
            var gridSizes = [];  
            var gridSizeType = ((layout._gridSize % 2) ==0)?"even":"odd"; 
            var itemSize = layout._gridSize /2;
          
            if(gridSizeType=="even"){
                gridSizes.push(itemSize);
                gridSizes.push(itemSize);
            }else{
                gridSizes.push(Math.floor(itemSize));
                gridSizes.push(Math.ceil(itemSize));
            }
            var self = this;
            bb.jquery.each(gridSizes, function(i,layoutSize){
                var newLayoutId = _generateId();
                var newlayouItemConfig = {
                    id:newLayoutId,
                    target:"#"+layout.getId(),
                    gridSize:layoutSize,
                    position : "after",
                    height :  bb.jquery(layout._layout).height()
                };
                var layoutParent = layout.getParent();
                if(layoutParent){ /*si l'item a un parent --> fix addVertical by group*/
                   
                    if(i==0){
                        newlayouItemConfig.alphaClass = "omega";
                    } 
                    if(i!=0) newlayouItemConfig.omegaClass = "alpha"; 
                  
                    /*si parent alors vChild*/
                    newlayouItemConfig.typeClass="vChild";
                }
               
                var vLayoutItem = self.createLayout(newlayouItemConfig);
            });
            layout._isDeleted = true; // fixme
            this.drawAll();
            layout.destroy();
            _initLayoutResizable();
			
            _notify("currentTemplateChange");
        },
        getLayoutById : _findLayoutById,
        
        toggleGridbackground : function(btn){
            if (bb.jquery(btn).is(":checked")) {
                bb.jquery(_settings.contentId+' > div.container').css('background-image', 'url('+bb.baseurl+bb.resourcesdir+'img/grid.png)');
                bb.jquery(_settings.contentId+' > div.container').css('background-size', '100% 100%');
                bb.jquery(_settings.contentId+' > div.container').css('background-color', '#fff');
            } else {
                bb.jquery(_settings.contentId+' > div.container').css('background', '');
            }
        }
        
    };
    
    return {
        init:_init,
        getLayoutById : _findLayoutById,
        setGridSize : _setGridSize,
        getGridSize: _getGridSize,
        saveLayout : _saveLayoutStructure,
        loadLayout : _loadLayoutStructure,
        loadTemplateFromDom : _loadFromDom,
        setTemplate : function(templateItem) {
        
            if (!_currentTemplate || templateItem.uid != _currentTemplate.uid) {
                _confirmLayoutSave(_setTemplate, templateItem);
            }
        }
    };   
})();


/*
 * Une grille est resizable uniquement à gauche ou à droite
 * déplace son voisin d'autant qu'il est déplacé dans la direction opposée de son propre
 * L'ajout d'une grille est toujours
 **/

LayoutItem = function(userConfig){
    var _gridManager = null;
    var _content = null;
    var _layoutTpl = '<div><p class="layoutTitle"></p></div>';
    var _resizeDirection = null;
    
    this._gridSize = null;
    this._isRendered = false;
    this._title = "";
    this._mainZone = false;
    this._accept = [];
    this._maxentry = 0;
    this._defaultClassContent = null;
    
    this._settings = {
        title : "",
        layoutSize : {
            height:300, 
            width:false
        },
        gridSizeInfos : {
            colWidth:60, 
            gutterWidth:20
        },
        id : null, //un élément qui existe déja ou un Id généré
        layoutClass : "bb5-resizableLayout",
        animateResize : false,
        showTitle : false, 
        target : null,
        resizable : true,
        useGridSize : true,
        gridSize : null,
        gridStep :80,
        gridClassPrefix : "col-md-",
        selectedClass : null
    };
    
    /*Dom element représentant le layout*/
    this._layout = null;
    
    /*Layout Manager*/
    this.layoutManager = null;
    
    if(typeof this._init!=="function"){
        this._init = function(userConfig){
            this._settings = bb.jquery.extend(true,this._settings,userConfig);
            this._layout = _buildItem.call(this,bb.jquery(_layoutTpl).clone());
            this._updateGrideSize();
			
            this.layoutManager = this._settings.layoutManager;
			
            /*mandatory field gridSize Target*/
            if(typeof this._settings.gridSize!=="number") throw("gridSize type error");
            
            this.setGridSize(this._settings.gridSize);
            var layoutTitle = (this._settings.title.length != 0) ? this._settings.title : bb.i18n.__('layoutmanager.untitled_area');
            this.setTitle(layoutTitle);
			
            this._mainZone = (this._settings.mainZone) ? this._settings.mainZone :this._mainZone;
            this._accept = (this._settings.accept) ? this._settings.accept : this._accept;
            this._maxentry = (this._settings.maxentry) ? this._settings.maxentry : this._maxentry;
            this._defaultClassContent = (this._settings.defaultClassContent) ? this._settings.defaultClassContent : this._defaultClassContent;
        }
    }

    this._updateGrideSize = function(){
        var currentGridSize = BB4.LayoutManager.getGridSize();
        /*console.log(currentGridSize);
        this._settings.gridSizeInfos = currentGridSize;*/
        this._settings.gridStep = parseInt(this._settings.gridSizeInfos.colWidth) + parseInt(this._settings.gridSizeInfos.gutterWidth);
        this._settings.gridStep = 100;
    }
  
    /*Déplacement de 80px --> */
    var _initResizable = function(userConfig){
        
        this._updateGrideSize(); //swith to even
        if(this._settings.resizable){
            var config = {
                helper : "bb4-ui-resizable-helper",
                grid :  this._settings.gridStep,
                start: bb.jquery.proxy(_onStart,this),
                resize: bb.jquery.proxy(_onResize,this),
                stop : bb.jquery.proxy(_onStop,this),
                minWidth : this._settings.gridSizeInfos.colWidth//taille d'une colonne,
            };
            
            if(this._settings.resizeContainment!="undefined"){
                config.containment = this._settings.resizeContainment;
            }
            bb.jquery(this._layout).resizable("destroy");
            config = bb.jquery.extend(true, config,userConfig);             
            bb.jquery(this._layout).resizable(config);      
        }
    } 
    
    var _currentmousePosition = null;
    
    /*onStart*/
    var _onStart = function(event,ui){
        _currentmousePosition = event.clientX;
        _resizeDirection = null;   
    }
    
    /*onResize*/
    var _onResize = function(event, ui){  
        
    //_currentmousePosition = event.clientX; //update position
    /*setMaxSize en fonction de la direction*/
    }
    
    /*onStop*/
    var _onStop = function(event,ui){
        _resizeDirection = (event.clientX < _currentmousePosition ) ? "left" : "right";
        var width = bb.jquery(event.target).width();
        var delta = ui.originalSize.width - width; //ne pas dépasser la taille d'une colonne
        var step = delta /this._settings.gridStep;
        
        /*Si step négatif --> augmentation de la taille*/       
        
        /*vers la gauche : diminution de la taille*/
        if((_resizeDirection=="left" && step > 0)){
            /*decrement current*/
            this.decrementGridSize(step);
            /*increment suivant*/
            var nextSibling = this.getSibling("next");
            if(nextSibling){
                nextSibling.incrementGridSize(step);   
            }
        }
        
        /*vers la droite : augmentation de la taille*/
        if(_resizeDirection=="right" && step < 0 ){
            /*decrement current*/
            this.incrementGridSize(Math.abs(step));
            /*increment suivant*/
            var nextSibling = this.getSibling("next");
            if(nextSibling){
                nextSibling.decrementGridSize(Math.abs(step));   
            }  
        }
        
        
        /*vers la droite : diminution de la taille*/
        if(_resizeDirection=="right" && step >0){
            /*decrement current*/
            this.decrementGridSize(Math.abs(step));
            /*increment prev*/
            var prevSibling = this.getSibling("prev");
            if(prevSibling){
                prevSibling.incrementGridSize(step);
            }    
        }
        
        
        /*vers la gauche : augementation de la taille*/
        if((_resizeDirection=="left" && step < 0)){
            this.incrementGridSize(Math.abs(step));
            var prevSibling = this.getSibling("prev");
            if(prevSibling){
                prevSibling.decrementGridSize(Math.abs(step));   
            }
        }
        
        /*reset*/
        _resizeDirection = null;
        _currentmousePosition = null;
        bb.jquery(ui.element).css({
            width:"",
            left:""
        });
        
        /*update::notify change || update maxWidth*/
        bb.jquery(document).trigger("layout:sizeChanged");
    };
    
    
    var _buildItem = function(layoutTpl){  
        bb.jquery(layoutTpl).addClass(this._settings.layoutClass);
        bb.jquery(layoutTpl).addClass(this._settings.alphaClass);
        bb.jquery(layoutTpl).addClass(this._settings.omegaClass);
        bb.jquery(layoutTpl).addClass(this._settings.typeClass);
        bb.jquery(layoutTpl).attr("id",this._settings.id);        
        bb.jquery(layoutTpl).css({
            //minHeight: "200px",
            height : this._settings.height
        
        });
        return layoutTpl; 
    };
    
    /*liste des événements Modele événementiel global?*/
    var _bindEvents = function(){};
    
    /*on settings update
     *Vérifier le type des options
     *
     **/
    this._onOptionsChanged = function(option, value){};
   
   
    /*Public API*/
    LayoutItem.prototype.isRemovable = function() {
        return (false != this.getParent() || 0 < bb.jquery(this._layout).siblings().length);
    };
	
    LayoutItem.prototype.getId = function(){
        return bb.jquery(this._layout).attr("id");
    };
	
    LayoutItem.prototype.setGridSize = function(gridSize){
        if(gridSize){
            /*remove previous class*/
            if(this._gridSize){
                bb.jquery(this._layout).removeClass(this._settings.gridClassPrefix+parseInt(this._gridSize));
            }
            /*add new*/
            bb.jquery(this._layout).addClass(this._settings.gridClassPrefix+parseInt(gridSize));
            this._gridSize = gridSize;
        }  
    };
    
    LayoutItem.prototype.decrementGridSize = function(step,onlyChild){
        var step = (parseInt(step)!=0) ? parseInt(step)  : false;
        if(!step) return false;
        var onlyChild = onlyChild || false;
        var childType = this.getChildType();
        var layoutHasParent = this.getParent() || false;
        var hChildren = this.getChildrenByType("hChild");
        var vChildren = this.getChildrenByType("vChild");
        var allChildren = bb.jquery.merge(hChildren,vChildren);
        
        
        /*cas layout de premier niveau*/
        //if(childType=="root"){
        if(!onlyChild){
            var newGridSize = this._gridSize - step;
            this.setGridSize(newGridSize);
        }
            
        //}
        
        /*S'il s'agit d'un enfant*/
        if(onlyChild){
            /*horizontal*/
            var layoutParent = layoutHasParent;
            /*Si je suis l'enfant résultant d'un Split H --> prendre la taille de mon parent*/
            if(childType=="hChild"){
                var newGridSize = layoutParent._gridSize;
                this.setGridSize(newGridSize);
                if(allChildren){
                    bb.jquery.each(allChildren,function(i,child){
                        child.decrementGridSize(step,true);
                    });
                }
            }
            /*Si je suis l'enfant résultant d'un Split V --> enlever [step] si je suis le dernier enfant
             * fix limit
             **/
            if(childType=="vChild"){
                if(bb.jquery(this._layout).hasClass("omega")){
                    var newGridSize = this._gridSize - step;
                    this.setGridSize(newGridSize);
                    /*Décrément limit si après resize l'item omega a une taille < à une colonne*/
                    /*ne traiter que les enfants*/
                    if(allChildren){
                        bb.jquery.each(allChildren,function(i,child){
                            child.decrementGridSize(step,true);
                        });
                    }
                } 
            }     
        }
        
        /* var hChildren = this.getChildrenByType("hChild");
        var vChildren = this.getChildrenByType("vChild");
        var allChildren = bb.jquery.merge(hChildren,vChildren);*/
        
        if(allChildren && onlyChild==false){
            bb.jquery.each(allChildren,function(i,child){
                child.decrementGridSize(step,true);
            });
        }
       
    }
     
    LayoutItem.prototype.incrementGridSize = function(step,onlyChild){ 
        var step = (parseInt(step)!=0) ? parseInt(step)  : false;
        if(!step) return false;
        var childType = this.getChildType();
        var layoutHasParent = this.getParent() || false;
        var onlyChild = onlyChild || false;
        
        
        /*current Item immediate children*/
        var hChildren = this.getChildrenByType("hChild");
        var vChildren = this.getChildrenByType("vChild");
        var allChildren = bb.jquery.merge(hChildren,vChildren);
        
        if(!onlyChild){
            var newGridSize = this._gridSize + step;
            this.setGridSize(newGridSize);
        }
        
        /*s'il s'agit d'un enfant*/
        if(onlyChild){     
            var layoutParent = layoutHasParent;
            
            /*Si je suis l'enfant résultant d'un Split H --> prendre la taille de mon parent*/
            if(childType=="hChild"){
                var newGridSize = layoutParent._gridSize;
                this.setGridSize(newGridSize);
                if(allChildren){
                    bb.jquery.each(allChildren,function(i,child){
                        child.incrementGridSize(step,true);
                    });
                }
            }
            
            /*
             *Si je suis l'enfant résultant d'un Split V --> ajouter [step] si je suis le dernier enfant:no next sibling
             * fix limit
             **/
            if(childType=="vChild"){
                if(bb.jquery(this._layout).hasClass("omega")){
                    var newGridSize = this._gridSize + step;
                    this.setGridSize(newGridSize);
                    /*ne traiter que les enfants*/
                    if(allChildren){
                        bb.jquery.each(allChildren,function(i,child){
                            child.incrementGridSize(step,true);
                        });
                    }
                }
            }
        }
        
        if(allChildren && onlyChild==false){
            bb.jquery.each(allChildren,function(i,child){
                child.incrementGridSize(step,true);
            });
        }
    };
    
    LayoutItem.prototype.getChildType = function(){
        /*available type : root->sansParent hChild vChild */
        var childType = "root";
        if(bb.jquery(this._layout).hasClass("hChild")){
            childType = "hChild";  
        }
        if(bb.jquery(this._layout).hasClass("vChild")){
            childType = "vChild";
        }
        return childType;   
    };
    
    LayoutItem.prototype.set = function(option,newValue){
        if(option && newValue){
            this._settings[option] = newValue;
            /*trigger optionChanged*/
            this._onOptionChanged(option,newValue);
        }
    };
    
    LayoutItem.prototype.setTitle = function(myTitle){
        if(myTitle){
            this._title = myTitle;
            
            this._settings.title = this._title;
            if(this._settings.showTitle) bb.jquery(this._layout).find(".layoutTitle").eq(0).text(myTitle);
        }
    };
    
    LayoutItem.prototype.toJson = function(){
        var layoutInfo = {};
        layoutInfo.layoutId = "#"+this.getId();
        layoutInfo.gridSize = this._gridSize;
        layoutInfo.layoutTitle = this.getTitle();
        layoutInfo.visible = (this._isDeleted)? 0 : 1;
        return layoutInfo;
    };
    
    LayoutItem.prototype.serialize = function(){
        var settings = bb.jquery.extend(true, {}, this._settings);    
        settings = bb.jquery.extend(true, settings, {
            mainZone: this._mainZone,
            accept: this._accept,
            maxentry: this._maxentry,
            defaultClassContent: this._defaultClassContent
        });
		
        if(this.getChildType()=="hChild"){
            var hSibling = this.getHSibling();
            if(hSibling){
                settings.hSibling = hSibling.getId();  
            }
        }
        
        if(this.getChildType()=="vChild"){
            
            if(bb.jquery(this._layout).hasClass("omega")){
                settings.clearAfter = 1;
                settings.omegaClass = "omega";
            }
            
            if(bb.jquery(this._layout).hasClass("alpha")){
                settings.alphaClass = "alpha"; 
            }      
        }
        
        if((settings.position) && (settings.position=="after")){
            settings.position = "none";
        }
        settings.gridSize = this._gridSize;
        return settings;
    }
    
    
    LayoutItem.prototype.setHeight = function(height){
        this._settings.layoutSize.height = height;
        this._settings.height = height;
        var childrenSize = this._settings.height / 2; //half parent size
        
        /*si hChild*/
        var hChildren = this.getChildrenByType("hChild");
        if(hChildren.length > 0){
            bb.jquery.each(hChildren,function(i, hChild){
                hChild.setHeight(childrenSize);
            }); 
        }
        this.update();
    }
    
    LayoutItem.prototype.setWidth = function(width){
        this._settings.layoutSize.width = width;
        this._settings.width = width;
        this.update();
    }
    
    LayoutItem.prototype.getHeight = function(computedSize){
        var computedSize = computedSize || false;
        var layoutHeight = (computedSize) ? bb.jquery(this._layout).outerHeight() : bb.jquery(this._layout).height(); 
        return layoutHeight;
    }
    
    LayoutItem.prototype.getWidth = function(computedSize){
        var layoutWidth = (computedSize) ? bb.jquery(this._layout).outerWidth() : bb.jquery(this._layout).width(); 
        return layoutWidth;   
    }
   
    LayoutItem.prototype.resizeTo = function(){}
    
    LayoutItem.prototype.getContent = function(){
        return this._layout;
    }
    
    LayoutItem.prototype.update = function(){
        /*update size*/
        bb.jquery(this._layout).css({
            height : this._settings.layoutSize.height+"px",
            width : this._settings.layoutSize.width+"px"
        });
    }
    
    LayoutItem.prototype.getTarget = function(){
        var layoutTarget = this._settings.target || null;
        layoutTarget = bb.jquery(layoutTarget) || false;
        return layoutTarget;
    }
    
    LayoutItem.prototype.getTitle = function(){
        return this._title; 
    }
    
    LayoutItem.prototype.select = function(){
        bb.jquery(this._layout).addClass(this._settings.selectedClass);
        bb.jquery(this._layout).children('.bbLayoutButtons').show();
    }
    
    LayoutItem.prototype.unSelect = function(){
        this.layoutManager.unSelectAll();
    }
   
    LayoutItem.prototype.destroy = function(){
        this._isDeleted = true;
        bb.jquery(this._layout).remove();
    }
    
    LayoutItem.prototype.create = function(){
        /*si layout existe déjà ne rien faire*/
        if(this._isRendered || this._isDeleted) return;
        var target = this.getTarget();
        if(this._settings.position=="after"){
            bb.jquery(this._layout).insertAfter(bb.jquery(target));
        }else{
            bb.jquery(target).append(this._layout);
        }
        if(this._settings.clearAfter){
            bb.jquery(this._layout).after('<div class="clear"></div>');
        }
        
        /*update height -> main parent is root*/
        var itemType = this.getChildType();
        if(itemType=="root"){
            var parentHeight = target.height();
            bb.jquery(this._layout).css({
                height:parentHeight
            });
        }
        
        /*update height -> hChild*/
        if(this._settings.height=="computed"){
            if(this._settings.typeClass=="hChild"){
                var parentHeight = bb.jquery(this._settings.target).height()/2;
                this._settings.height = parentHeight;  
                bb.jquery(this._layout).css({
                    height:parentHeight
                });
            }
        }
        
        this._isRendered = true;
        this._isDeleted = false;
    }
    
    LayoutItem.prototype.setResizableHandlesRegion = function(handlesRegion){
        this._handlesRegion = handlesRegion; 
        /*Taille max si enfant*/
        var maxWidth = this.getWidth(); 
       
        if(bb.jquery.isArray(handlesRegion)){
     
            if(handlesRegion[0]=="e"){
                var nextLayout = this.getSibling("next");
                if(nextLayout){
                    /*si nextLayout width==setting*/
                    if(nextLayout.getWidth() > this._settings.gridStep){
                        var nextChildren = nextLayout.getChildrenByType("vChild",true);
                        if(nextChildren.length==0){
                            maxWidth = parseInt(this.getWidth()) + parseInt(nextLayout.getWidth()) - this._settings.gridStep;
                        }
                        else{
                            /*si vChild*/
                            var smallestWidth = nextLayout.getSmallestVChildWidth();
                            maxWidth = parseInt(this.getWidth()) + smallestWidth - this._settings.gridStep;
                        }
                    } 
                }
                /*Cas Split H zero sibling*/
                else{
                    var layoutParent = this.getParent();
                    maxWidth = (layoutParent) ? layoutParent.getWidth() : maxWidth;   
                }
            }
       
            if(handlesRegion[0]=="w"){
                var prevLayout = this.getSibling("prev");
                if(prevLayout){
                    if(prevLayout.getWidth() >this._settings.gridStep){  
                        var prevLayoutChildren = prevLayout.getChildrenByType("vChild",true);
                        if(prevLayoutChildren.length==0){
                            maxWidth = parseInt(this.getWidth()) + parseInt(prevLayout.getWidth()) - this._settings.gridStep;    
                        }
                        else{
                            var smallestWidth = prevLayout.getSmallestVChildWidth();
                            var maxWidth = parseInt(this.getWidth()) + smallestWidth - this._settings.gridStep;
                        }
                        
                    }
                }
            }
            
            /*Déterminer la taille minimale si l'item a des enfants*/
            var minWidth = false;
            var hasChildren = this.getChildrenByType("vChild",true);
            if(hasChildren.length){
                /*Enfants verticaux omega*/
                var allOmegaChildren = [];
                var smallestChild = 0;
                bb.jquery.each(hasChildren,function(i,child){
                    if(bb.jquery(child._layout).hasClass("omega")){
                        allOmegaChildren.push(child);
                    }
                });
                 
                /*Si enfants verticaux*/
                if(allOmegaChildren){
                    var smallestChildWidth = allOmegaChildren[0].getWidth();
                    bb.jquery.each(allOmegaChildren,function(i,child){
                        smallestChildWidth = Math.min(smallestChildWidth,child.getWidth());
                    });
                    
                    /*parent minWidth - taille plus petit enfant + une colonne */
                    var minWidth = parseInt(this.getWidth()) -  smallestChildWidth + this._settings.gridStep;   
                }
                    
            }
          
            /*fix classes for Vertical margin here*/
            var handlesRegion = handlesRegion.join(", ");
            //var minWidth = this.getMaxHChildWidth();
            bb.jquery(this._layout).resizable("destroy"); 
            _initResizable.call(this,{
                handles:handlesRegion,
                maxWidth : maxWidth,
                minWidth : minWidth
            });
        }
    }
    
    LayoutItem.prototype.getSmallestVChildWidth = function(){
        var minWidth = false;
        var smallestChildWidth = 0;
        var hasChildren = this.getChildrenByType("vChild",true);
        if(hasChildren.length){
            /*Enfants verticaux omega*/
            var allOmegaChildren = [];
            var smallestChild = 0;
            bb.jquery.each(hasChildren,function(i,child){
                if(bb.jquery(child._layout).hasClass("omega")){
                    allOmegaChildren.push(child);
                }
            });
                 
            /*Si enfants verticaux*/
            if(allOmegaChildren){
                var smallestChildWidth = allOmegaChildren[0].getWidth();
                bb.jquery.each(allOmegaChildren,function(i,child){
                    smallestChildWidth = Math.min(smallestChildWidth,child.getWidth());
                });
            }
        }
        return smallestChildWidth;
    }
    
    
    LayoutItem.prototype.getHSibling = function(){
        var sibling = false;
        if(this.getChildType()=="hChild"){
            sibling = this._hSibling;   
        }
        return sibling;
    }
    
    /*Récupérer le voisin immédiat dans le sens du déplacement*/
    LayoutItem.prototype.getSibling = function(direction){
        var sibling = (direction=="next") ? bb.jquery(this._layout).next("."+this._settings.layoutClass) : bb.jquery(this._layout).prev("."+this._settings.layoutClass); 
        sibling = BB4.LayoutManager.getLayoutById(sibling.attr("id"));
        return sibling;
    }
    
    LayoutItem.prototype.hasSiblings = function(){
        var siblings = {};
        var leftSibling = (bb.jquery(this._layout).prev().hasClass(this._settings.layoutClass))? 1 : 0;
        var rightSibling = (bb.jquery(this._layout).next().hasClass(this._settings.layoutClass))? 1 : 0;
        
        /*test*/ 
        siblings.hasLeftSibling = leftSibling;
        siblings.hasRightSibling = rightSibling;
        return siblings;
    }
    
    LayoutItem.prototype.getParent = function(){
        var parentId = bb.jquery(this._layout).parent("."+this._settings.layoutClass).attr("id");
        var parent = BB4.LayoutManager.getLayoutById(parentId);
        return parent;
    }
    
    /*useful to set Parent minSWidth*/
    LayoutItem.prototype.getMaxHChildWidth = function(){
        var result = false;
        var childWidthMax = 0;
        if(this._layoutChildrenInfos && this._layoutChildrenInfos.childrenIDs){
            bb.jquery.each(this._layoutChildrenInfos.childrenIDs,function(i,id){
                childWidthMax = Math.max(childWidthMax, bb.jquery(id).width());
            });
            result = childWidthMax;
        }
        return result;    
    }
    
    LayoutItem.prototype.getChildren = function(){
        var children = [];
        var hChildren = this.getChildrenByType("hChild");
        var vChildren = this.getChildrenByType("vChild");
        children = bb.jquery.merge(hChildren,vChildren);
        return children;
    } 
    
    /* allChildren option permet de prendre en compte tous les options*/
    LayoutItem.prototype.getChildrenByType = function(type,allChildren){
        var type = type || false;
        var allChildren = (allChildren==true) ? allChildren : false;
        if(!type) return false;
        var children = []; 
        var layoutChildren = []; 
        
        var selectorType = (!allChildren) ? " > " : " ";
        if(type=="vChild"){
            var selector = "#"+this.getId() + selectorType + ".vChild"; // fixme ne prendre que le dernier
            children = bb.jquery(selector); 
        }
        
        if(type=="hChild"){
            var selector = "#"+this.getId()+selectorType+".hChild"; // fixme ne prendre que le dernier
            children = bb.jquery(selector); 
        } 
       
        bb.jquery.each(children,function(i,layoutNode){
            var child = BB4.LayoutManager.getLayoutById(bb.jquery(layoutNode).attr("id"));
            if(child){
                layoutChildren.push(child); 
            }
        });
        return layoutChildren;
    }
    
    LayoutItem.prototype.highlight = function(delay){
        var delay = parseInt(delay) || 5;
        bb.jquery(this._layout).html("selected");
        var self = this;
        setTimeout(function(){
            bb.jquery(self._layout).html("");
        },delay*1000); 
    }

    LayoutItem.prototype.remove = function(){
        
        this._deleted = true;
        /*remove algo here*/
        /*
         *H Child V Child
         * 1. Effacer enfants
         * 2. Ensuite effacer item
         * 
         *   
         * H Child 
         * 1. Repartir la taille sur le premier voisin
         * si voisin --> de type H :Remplacer
         * si voisin --> de type V répartir la taille
         * 
         * V Child
         * 1. Répartir la taille sur le premier voisin
         * 
         * 
        **/
        var currentLayoutType = this.getChildType(); 
        var layoutHasParent = this.getParent() || false;
        var children = this.getChildren()|| false;
        var sibling = (this._handlesRegion[0]=="w") ? this.getSibling("prev") : this.getSibling("next");
        
        if(currentLayoutType=="root"){
            bb.jquery(this._layout).remove(); //effacer du Dom
            sibling.incrementGridSize(this._gridSize);
        }
        
        /*s'il s'agit d'un enfant*/
        if(layoutHasParent){
            /*vChild*/
            if(currentLayoutType=="vChild"){
                bb.jquery(this._layout).remove();
                var hasSiblings = sibling.hasSiblings();
                if(hasSiblings.hasLeftSibling==0 && hasSiblings.hasRightSibling==0){
                    /*last vertical child-->change to hChild*/
                    sibling  = sibling.convertVToH(); 
                    
                }
                sibling.incrementGridSize(this._gridSize);
            }
            
            /*hChild*/
            if(currentLayoutType=="hChild"){
                bb.jquery(this._layout).remove(); 
                var parentHeight = layoutHasParent.getHeight(); 
                var currentHSibling = this.getHSibling();
                if(currentHSibling.exists()){
                    /*Le frère se transforme*/
                    currentHSibling.setHeight(parentHeight);
                    layoutHasParent.replaceBy(currentHSibling);
                    
                    
                /*Mettre à jour la taille des enfants h or v*/
                }
                
                /* Sinon le voisin a été transformé en vChild
                     * Remplacer le parent par les enfants verticaux
                     **/
                if(!currentHSibling.exists()){
                    var otherChildren = layoutHasParent.getChildren();
                    if(otherChildren.length){
                        layoutHasParent.replaceBy(otherChildren, "vChild");      
                    }
                }
            }
        } 
        bb.jquery(document).trigger("layout:ItemDeleted");
    }
    
    
    /*Chemin jusqu'au container*/  
    LayoutItem.prototype.getPath = function(){
        var path = bb.jquery(this._layout).parentsUntil(this._settings.defaultContainer);
        return path;
    }
    
    LayoutItem.prototype.replaceBy = function(layoutItem,childType){
        var childType = childType || "hChild";
        var content = null;
        if(childType=="vChild"){
            if (bb.jquery.isArray(layoutItem)){
                content = document.createDocumentFragment();
                var self = this;
                
                bb.jquery.each(layoutItem,function(i,layout){
                    layout.setHeight(self.getHeight());
                    var cloneLayout = bb.jquery(layout._layout).clone();
                    layout._layout = cloneLayout;
                    content.appendChild(bb.jquery(cloneLayout).get(0));
                });
            }
        }
        
        if(childType=="hChild"){
            
            content = layoutItem._layout.removeClass("omega").removeClass("alpha");
            /*changer hChild en vChild*/
            if(this.getParent()){
                bb.jquery(content).removeClass("hChild");
                layoutItem._settings.typeClass = "vChild";
                bb.jquery(content).addClass("vChild");
                
                if(bb.jquery(this._layout).hasClass("omega")){
                    bb.jquery(content).addClass("omega"); 
                }
                if(bb.jquery(this._layout).hasClass("alpha")){
                    bb.jquery(content).addClass("alpha");   
                }
            }else{
                bb.jquery(content).removeClass("hChild");  
                bb.jquery(content).removeClass("alpha").removeClass("omega");
            }
            
            layoutItem._settings.resizable = true;
            layoutItem.setHeight(this.getHeight());
        }
        
        /*transformer le parent
         *En fonction du type de layoutItem
         **/
        bb.jquery(this._layout).replaceWith(bb.jquery(content));
        this.destroy();
    }
    
    
    LayoutItem.prototype.convertHToV = function(){}
    
    
    LayoutItem.prototype.convertVToH = function(){
        var parent = this.getParent()||false;
        if(!parent) return false;
        
        var hChildType = parent.getChildrenByType("hChild");
        var sibling = null;
        /*changer sibling*/
        if(hChildType.length > 0){
            sibling = hChildType[0];
            sibling._hSibling = this;
        }
        bb.jquery(this._layout).removeClass("vChild").addClass("hChild").removeClass("alpha").removeClass("omega").addClass("alpha omega");
        this._settings.typeClass = "hChild";
        this._settings.resizable = false;
        this._hSibling = sibling;
        this.disableResize();
        return this;
      
       
    };
    
    /*still in the DOM*/
    LayoutItem.prototype.exists = function(){
        var exist = ( bb.jquery("#"+this.getId()).length ==0 ) ? false : true;
        return exist;
    };
    
    /**/
    LayoutItem.prototype.disableResize = function(){
        bb.jquery(this._layout).resizable("destroy"); 
    };
	
    this._init(userConfig);    
}

}) (bb.jquery);

BB4.LayoutItem = LayoutItem;


/*Add implement method to extend object*/

