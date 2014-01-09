/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var bb = (bb) ? bb : {};

bb.ContentManager =(function($,gExport){
        
    var _settings = {
        draggableClass: "",
        layoutDroppabableClass :".bb5-droppable-item",
        //mainDroppableZoneClass : ".bb4ResizableLayout",
        draggedContentCls : "bb5-draggedContent",  
        contentClass : ".bb5-content",
        acceptClass : ".contentBlock",
        mainBlockContentCls :"blockContentBtn",
        contentWebservice :null,
        draggableContentClass : ".bb5-draggable-item",
        mainContensetContainerClass : ".rootContentSet",
        droppableContentClass : ".bb5-droppable-item",
        resizableContentClass : ".bb5-resizable-item",
        droppableRenderFlag :   "droppableIgnore",
        draggableRenderFlag :   "draggableIgnore",
        resizableRenderFlag :   "resizableIgnore",
        contentSelectedClass : ".bb5-content-selected",
        contentTypeHelperClass : ".contentTypeHelper",
        invalidPlaceHolderCls : "bb5-droppable-place-invalid",
        emptyContentCls : "emptyContent",
        emptyFileCls: 'bb5-empty-file',
        gridSizeInfos : {},
        ACCEPTED_ITEM_KEY : "data-accept",
        CONTENT_TYPE_ITEM_KEY : "data-type",
        CONTENT_RENDER_TYPE : "data-rendermode",
        CONTENT_MAXENTRY_KEY :"data-maxentry",
        ITEM_UID_KEY : "data-uid",
        emptyFileDropImg: "/ressources/img/filedrop.png"
    };
    
    var _enableContentSelection = false;
    var _resizableItems = {};
    var _droppableItems = {};
    var _draggableItems = {};
    var _itemClone = null;
    var _currentSortable = null;
    var _inAllowedZone = true;
    var _mustCancel = false;
    var _draggedContent = null;
    var _outOfDroppableZone = false;
    var _paddingTopSaved = false;
    var _paddingBottomSaved = false;
    
    var _initDroppableImage = function(content) {
        content = (content) ? content : bb.jquery('body');
        
        var replaceDroppableImage = function(img) {
            if (!img.complete) {
                bb.jquery(img).error(function() {
                    bb.jquery(this).attr('src', bb.baseurl+_settings.emptyFileDropImg);
                    bb.jquery(this).addClass(_settings.emptyFileCls);
                    bb.jquery(this).parents('[data-type^="Media\\"]').addClass(_settings.emptyFileCls);
                });
            } else{ 
                if((typeof img.naturalWidth != 'undefined' && img.naturalWidth == 0 ) || img.readyState == 'uninitialized' ) {
                    bb.jquery(img).attr('src', bb.baseurl+_settings.emptyFileDropImg);
                    bb.jquery(img).addClass(_settings.emptyFileCls);
                    bb.jquery(img).parents('[data-type^="Media\\"]').addClass(_settings.emptyFileCls);
                }
            }
        }
        
        content.find('img[data-type="Element\\\\image"]').each(function() {
           
            replaceDroppableImage(this);
           
        });
        
        content.find('[data-type="Media\\\\image"] img').each(function() {
            replaceDroppableImage(this);
        });
    };
    
    var _toggleEditionMode = function(mode) {
        if (mode)
            bb.jquery('.'+_settings.emptyFileCls).show();
        else
            bb.jquery('.'+_settings.emptyFileCls).hide();
    };
	
    var _cleanSortableClonedItem = function(){
        if(_itemClone){
            bb.jquery(_itemClone).remove();
            _itemClone = null;
        }
    };
        
    var _init = function(userConfig){
        var userConfig = userConfig || {};
        bb.jquery.extend(true,_settings,userConfig);
        _settings.gridStep = _setResponsiveGridInfos();
        //console.log(_settings.gridStep);
        _extendSortable();
        _bindEvents();
        _initComponents();
        _initDroppableImage();       
     
        /*register Container*/
        var mc = bb.ManagersContainer.getInstance();
        mc.register("ContentManager",_publicApi);
        return _publicApi;
    }
        
    var _initComponents = function(){
        _initSortable();
        _initResizable();
        _enableContentSelection = true;
    }
    /* itemdrop event added to jquery*/
    var _extendSortable = function(){
        var _mouseStop = bb.jquery.extend(true,{},bb.jquery.ui.sortable.prototype)._mouseStop;
        var nwMouseStop = function(a,b){
            this._trigger("itemdrop",a,this._uiHash());
            _mouseStop.apply(this,arguments);
        };
        bb.jquery.ui.sortable.prototype._mouseStop = nwMouseStop; 
    } 
    
    var _enable = function() {
        _enableContentSelection = true;
        var nodeData = bb.jquery(_settings.layoutDroppabableClass).data();
        if(nodeData.sortable){ //useful cf LayoutManager _clone
           
            bb.jquery(_settings.layoutDroppabableClass).sortable("enable");
        }else{
            _initSortable();
        }
        _initResizable();
        bb.jquery(_settings.contentClass).css({
            //cursor:"move"
            });
    };
    
    var _disable = function(){
        _enableContentSelection = false;
        bb.jquery(_settings.contentClass).css({
            cursor:""
        });
        /*remove Sortable*/
        bb.jquery(_settings.layoutDroppabableClass).sortable("disable");
        bb.jquery(_settings.layoutDroppabableClass).removeClass(_settings.droppableRenderFlag);
        bb.jquery(_settings.layoutDroppabableClass).enableSelection(); 

        /*remove resizable*/
        bb.jquery.each(_resizableItems,function(key,resizableItem){
            resizableItem.destroy();
        });
        _resizableItems = {};
        _droppableItems = {};
        _draggableItems = {};
        if(typeof bb.ManagersContainer.getInstance().getManager("ToolsbarManager").hidePath=="function"){
            bb.ManagersContainer.getInstance().getManager("ToolsbarManager").hidePath();
        }  
    };
    
    //    var _handleBodyDrop = function(){
    //        bb.jquery("body").attr("data-bodydropzone","1");
    //        bb.jquery("body").sortable({
    //            connectWith : _settings.layoutDroppabableClass,
    //            over: function(e,ui){
    //                console.log('dire');
    //            },
    //            drop:function(e,ui){
    //                console.log('this');
    //            }
    //        }); 
    //    }
    
    var _unselectAllContents = function(){
        bb.jquery(_settings.contentSelectedClass).removeClass(_settings.contentSelectedClass.replace(".",""));
    }    
        
    var _bindEvents = function(){  
        /*Mouse over content*/
        //var selectableContent = _settings.draggableContentClass+","+_settings.resizableContentClass;
        var selectableContent = _settings.contentClass;
        /*bb.jquery(selectableContent).live("mouseenter",function(e){
            if(!_enableContentSelection) return false;
            _unselectAllContents(); //usefull for subcontents
            bb.jquery(e.currentTarget).addClass(_settings.contentSelectedClass.replace(".",""));
        });
        
        bb.jquery(selectableContent).live("mouseleave",function(e){
            if(!_enableContentSelection) return false;
            bb.jquery(e.currentTarget).removeClass(_settings.contentSelectedClass.replace(".",""));
        });
     */
        /*Click*/
        /*bb.jquery(selectableContent).bind("click",function(e){
           // e.stopPropagation();
            var path = _getContentPath(e.currentTarget);
            path.push(e.currentTarget);
            var pathInfos = {
                selectedNode : e.currentTarget, 
                items : path,
                itemTitleKey : "data-type",
                itemClass : ".contentNodeItem",
                itemIdKey : "data-uid"
            };
            bb.jquery(document).trigger("content:ItemClicked",[pathInfos]);
            return true;
        });*/
        
        var delay = false;
        /*hack When resize*/
        bb.jquery(window).resize(function(e){
            if(delay != false) clearTimeout(delay);
            var onResize = function(){
                var isWindow = (bb.jquery(e.target).hasClass(_settings.resizableContentClass.replace(".","")))?false:true;
                if(isWindow){
                    var resizeStep = _setResponsiveGridInfos();
                    bb.jquery.each(_resizableItems,function(key,resizableItem){
                        resizableItem.setGridStep(parseInt(resizeStep));
                    });
                }
            }
            delay = setTimeout(onResize,1000);
        });
     
        bb.jquery(_settings.mainContensetContainerClass).live("mouseenter",_insideDropZone);
        bb.jquery(_settings.mainContensetContainerClass).live("mouseleave",_outsideDropZone);
        bb.jquery(_settings.mainContensetContainerClass).bind("mousestop",_handleMousestop);
        bb.jquery(document).bind("bbcontent:contentSelected",function(e,data){
            bb.jquery(".bb5-droppable-place").show();
            _insideDropZone();
        });
    };
    
   
    var _insideDropZone = function(e){
        _inAllowedZone = true;
        _outOfDroppableZone = false;
        return true;
    }
    
    var _outsideDropZone = function(e){
        _inAllowedZone = false;
        _outOfDroppableZone = true;
        bb.jquery(".bb5-droppable-place").hide();
        return true;
    }
    
    var _handleMousestop = function(e){
        if(bb.jquery(e.target).hasClass(_settings.mainContensetContainerClass.replace(".",""))){
            _insideDropZone();
        }else{
            _outsideDropZone();
        }
        
    }
    
   
    /*filter autoblocs*/
    var _filterAutoBlocks = function(i,content){
        var result = true;
        var autoBlocPattern = /autobloc/gi;
        var isAnAutoBlock = autoBlocPattern.test(bb.jquery(content).attr("data-type"));
        if(isAnAutoBlock){
            /*enlever la class droppable pour le container*/
            bb.jquery(this).removeClass(_settings.droppableContentClass.replace(".",""));
            var contentChildren = bb.jquery(this).find(_settings.contentClass); 
            bb.jquery(this).find(_settings.contentClass).removeClass(_settings.draggableContentClass.replace(".",""));
            result = false;
        }
        return result;  
    }
   
    /*hack to get the layout grid step*/
    var _setResponsiveGridInfos = function(){
        var layoutStepTester = bb.jquery("<div id='sizeTester' style='visibility:hidden' class='row'><div id='firstSpan' class='span1'></div><div id='secondSpan' class='span2'></div></div>");
        bb.jquery("body").append(layoutStepTester);
        var resizeStep = bb.jquery("#secondSpan").width() - bb.jquery("#firstSpan").width();
        bb.jquery("#sizeTester").remove();
        return parseInt(resizeStep);
    }
    
    var _handleRootContainerSiPlaceHolderPlace = function(action){
        var action = (typeof action=="string") ? action : false;
        if(!action) throw "Action must be a string";
        if(action=="add"){
            bb.jquery(_settings.mainContensetContainerClass).css("paddingTop","50px"); 
        }
        if(action=="remove"){
            bb.jquery(_settings.mainContensetContainerClass).css("paddingTop",""); 
        }
        
    } 
    
    /*initSortable for content node*/
    var _initSortable = function(){
        var droppables = bb.jquery(_settings.layoutDroppabableClass).not(_settings.droppableRenderFlag);
        if(droppables.length==0) return false;
        droppables = droppables.filter(_filterAutoBlocks); //enlever les autoblocs
        var scripts = null;
        /*build sortable here*/        
        bb.jquery.each(droppables,function(id, droppable){
          
            try{
                bb.jquery(droppable).sortable({
                    connectWith :_settings.layoutDroppabableClass, //   connect to other sortable
                    placeholder : "bb5-droppable-place",
                    revert :0,
                    scroll: true,
                    scrollSensitivity : 20,
                    cancel :"script",
                    delay : 150,
                    tolerance :"pointer",
                    //handle: ".bb5-ui.bb5-content-actions",
                    helper : function(event,draggedContent){
                        var contentCls = _settings.contentClass.replace(".","");
                        _draggedContent = (bb.jquery(draggedContent).hasClass(contentCls)) ? draggedContent : bb.jquery(draggedContent).parent(_settings.contentClass);
                        var contentTypeParams = bb.jquery(_draggedContent).data();
                        var pictoName = bb.baseurl+'ressources/img/contents/'+contentTypeParams.type.replace('\\', '/')+'.png';
                        var contentHelper = bb.jquery("<div></div>").clone(); 
                        bb.jquery(contentHelper).addClass("bb5-content-type-helper");
                        bb.jquery(contentHelper).css({
                            height:"50px",
                            width:"50px"                          
                        });
                        bb.jquery(contentHelper).css({
                            backgroundImage:"url("+pictoName+")"
                        });
                        return contentHelper;
                    },
                    cursorAt : {
                        left:-10,
                        top:0
                    },
                    appendTo :"body",
                    //dropOnEmpty: false,

                   
                    /*placeholder:{
                        element : function(currentItem){
                            console.log("iocic");
                            return bb.jquery("<li><em>test</em></li>")[0];
                        },
                        update: function(container, p) {
                           container.refreshPositions();
                           return;
                        }
                    },*/
                    itemdrop : function(e,ui){
                        var prevPlaceHolder = bb.jquery(ui.placeholder.prev());
                        if(ui.item.get(0) == prevPlaceHolder.get(0)){
                            bb.jquery(".bb5-content-type-helper").hide();
                            _mustCancel = true;
                        }
                        if(!_inAllowedZone){ 
                            bb.jquery(ui.placeholder).hide();
                            bb.jquery(ui.helper).hide();
                            _mustCancel = true;
                        }
                        return true;
                    },
                    
                    sort: function(e,ui){
                        var hidePlaceHolder = false;
                        
                        /*pas de place holder avant l'item*/
                        if(ui.placeholder.prev().get(0)==ui.item.get(0)){
                            hidePlaceHolder = true;
                        }
                        /*pas de place holder après l'item*/
                        if(ui.placeholder.next().get(0)==ui.item.get(0)){
                            hidePlaceHolder = true;
                        }
                        
                        if(!_inAllowedZone){
                            hidePlaceHolder = true;  
                        }
                        if(hidePlaceHolder) bb.jquery(ui.placeholder).hide();
                        if(!hidePlaceHolder) bb.jquery(ui.placeholder).show();
                    },
                    
                    start : function(e,ui){
                        var contentCls = _settings.contentClass.replace(".","");
                        ui.item = (bb.jquery(ui.item).hasClass(contentCls) || bb.jquery(ui.item).hasClass())? ui.item : bb.jquery(ui.item).parent(_settings.contentClass);
                        var scriptsCopy = bb.jquery(ui.item).find("script").clone();
                        if(scriptsCopy.length){
                            scriptsCopy = scriptsCopy;
                            bb.jquery(ui.item).find("script").remove();
                        }
                        _enableContentSelection = false; 
                        
                        /*create new bbContent*/
                        var newContent = $bb(ui.item);
                        var currentItemClone = ui.item;
                        bb.jquery(ui.item).addClass(_settings.draggedContentCls).show();
                        _currentSortable = bb.jquery(this).data("sortable");
                        bb.jquery(document).trigger("content:startDrag");
                    },
                    
                    stop : function(e,ui){
                        if(_mustCancel){
                            _currentSortable.cancel();
                            _cleanSortableClonedItem(); 
                            _mustCancel = false;
                        }else{
                            bb.jquery(ui.placeholder).hide();
                            var sender = this;                       
                            _currentSortable = false;
                            $bb(sender).updateData();
                           
                        }
                        bb.jquery(document).trigger("content:stopDrag");
                        _enableContentSelection = true;
                        bb.jquery(this).find(_settings.draggedContentCls).removeClass(_settings.draggedContentCls); 
                    },
                    
                    over : function(event,ui){
                        _outOfDroppableZone = false;
                        bb.jquery(ui.placeholder).show();
                        var receiver = $bb(this); 
                        var itemDatas = bb.jquery(ui.item).data();
                        var itemType = ("contentTypeInfos" in itemDatas) ? itemDatas.contentTypeInfos.name : itemDatas.type;
                        //console.log("itemType",itemType);
                        var canAccept = receiver.checkAcceptMaxEntry(itemType);
                        if(!canAccept) bb.jquery(ui.placeholder).addClass(_settings.invalidPlaceHolderCls);
                        if(canAccept) bb.jquery(ui.placeholder).removeClass(_settings.invalidPlaceHolderCls); 
                        bb.jquery(document).trigger("content:overItem");
                        if (false == _paddingTopSaved) _paddingTopSaved = bb.jquery(this).css('padding-top');
                        if (false == _paddingBottomSaved) _paddingBottomSaved = bb.jquery(this).css('padding-bottom');
                        bb.jquery(this).css('padding-top', '50px');
                        bb.jquery(this).css('padding-bottom', '50px');
                        return true;
                    },
                    
                    out: function(event,ui){
                        _outOfDroppableZone = true;
                        bb.jquery(ui.placeholder).hide();
                        bb.jquery(this).css('padding-top', _paddingTopSaved);
                        bb.jquery(this).css('padding-bottom', _paddingBottomSaved);
                        _paddingTopSaved = false;
                        _paddingBottomSaved = false;
                    },
                   
                    receive : function(event,ui){
                        var fixContent = false;
                        var oldContent = ui.item;
                        
                        try{
                            var contentCls = _settings.contentClass.replace(".","");
                            var acceptCls = _settings.acceptClass.replace(".","");
                            var draggedItem = (bb.jquery(ui.item).hasClass(contentCls))? ui.item : bb.jquery(ui.item).parent(_settings.contentClass);
                            
                            /*fix strange bug*/
                            if(bb.jquery(_draggedContent).get(0) != bb.jquery(draggedItem).get(0)){
                                draggedItem = _draggedContent;
                                fixContent = true;
                                bb.jquery(oldContent).hide();
                            }
                            /*new Content has contentBlock Class so we must switch draggedItem to oldContent*/
                            draggedItem = ((!draggedItem || !draggedItem.length) || bb.jquery(draggedItem).hasClass(acceptCls))? oldContent : draggedItem;
                            
                            /*if a new content is dropped ouside of a droppable zone*/
                            if(bb.jquery(draggedItem).hasClass(acceptCls) && (_outOfDroppableZone || !_inAllowedZone)){
                                bb.jquery(draggedItem).show();
                                if(ui.sender){
                                    bb.jquery(ui.sender).sortable("cancel");
                                }
                                return false;
                            }
                            
                            if(draggedItem.parent()[0]==bb.jquery(this)[0] || fixContent){
                                if( _mustCancel ) return true;
                                var nodeInfos = bb.jquery(draggedItem).data();
                                var contentTypeInfos = (nodeInfos.contentTypeInfos) ? nodeInfos.contentTypeInfos : {}; 
                                var dropType = ("contentTypeInfos" in nodeInfos) ? "newContent" : "moveContent";
                                var newContentType = ""; 
                                var beforeRequest = bb.jquery.noop;
                                var afterAppend = bb.jquery.noop;
                                var placeHolder = null;
                                
                              
                                /*New content*/
                                if(dropType=="newContent"){
                                    var content = [{
                                        uid:nodeInfos.contentTypeInfos.uid,
                                        type:nodeInfos.contentTypeInfos.name
                                    }];
                                    placeHolder = ui.placeholder;
                                    newContentType = nodeInfos.contentTypeInfos.name;
                                    beforeRequest = function(){
                                        bb.jquery(draggedItem).hide();
                                        bb.jquery(draggedItem).before(ui.placeholder);
                                    }
                                    afterAppend = function(){
                                        ui.sender.find('.prevContent').remove();
                                        bb.jquery(draggedItem).data("contentTypeInfos",contentTypeInfos); 
                                        bb.jquery(draggedItem).show();
                                        bb.jquery(ui.sender).sortable("cancel");
                                    }
                                    
                                }else{
                                    /*Moved Content*/
                                    var content = $bb(draggedItem);
                                    newContentType = content.getType();
                                    var beforeRequest = function(){
                                        if(fixContent){
                                            bb.jquery(draggedItem).remove();  
                                        }else{
                                            bb.jquery(draggedItem).hide();  
                                        } 
                                    }
                                    var afterAppend = function(){
                                        if(bb.jquery(draggedItem).length) bb.jquery(draggedItem).show();
                                    }
                                    placeHolder = (!fixContent) ? draggedItem : oldContent; //replace content
                                }
                                var receiverContent = $bb(this); 
                                var canAccept = receiverContent.checkAcceptMaxEntry(newContentType);
                                
                                /*Si drop invalid*/
                                if(!canAccept && dropType=="newContent"){
                                    ui.sender.find('.prevContent').remove();
                                    bb.jquery(ui.sender).sortable("cancel");
                                    return false;
                                }
                                if(!canAccept && dropType=="moveContent"){
                                    bb.jquery(ui.sender).sortable("cancel");
                                    return false; 
                                }
                                var params = {
                                    content : content, 
                                    dropeType : dropType, 
                                    placeHolder : placeHolder,
                                    beforeRequest : beforeRequest,
                                    afterAppend : afterAppend,
                                    nodeScripts : scripts
                                    
                                };
                                receiverContent.append(params); //make request here cf bb.contentWrapper
                                
                                //sortableCallbacks.onReceive.call(this,event,ui); 
                                _cleanSortableClonedItem();
                            //return true;
                            }
                        }catch(e){
                            console.log(e); 
                        }
                    
                    }
                });
                
                /**/
                if(_enableContentSelection){
                    bb.jquery(droppable).disableSelection();
                }
                /*ignore:enable Once*/
                bb.jquery(droppable).addClass(_settings.droppableRenderFlag);
            }catch(err){}
            
           
        });
        
    };
    
    
    var _fixContent = function(){
        
    }
    var _isNewContentValid = function(event,ui){
        return !bb.jquery(ui.placeholder).hasClass("invalid");
    }
  
    
    var _initResizable = function(){
        var resizableItems = bb.jquery(_settings.resizableContentClass).not(_settings.resizableRenderFlag);
        if(resizableItems.length==0) return false;
        bb.jquery.each(resizableItems,function(id,resizableItem){
            var resizableItem = new bb.ResizableItem({
                el:resizableItem,
                gridStep : _settings.gridStep
            });
            bb.jquery(resizableItem.getElement()).addClass(_settings.resizableRenderFlag);
            var resizableId = resizableItem.getId();
            _resizableItems[resizableId] = resizableItem;
        });
        
    }
    
    
   
    /* return true
 * return false
 *
 *
 **/
    var _checkReceiverMaxEntry = function(reciever,newItem){
        var nbContent = bb.jquery(reciever).find(_settings.contentClass).length;
        var receiverMaxEntry  = (bb.jquery(reciever).attr(_settings.CONTENT_MAXENTRY_KEY)=="") ? 999 : bb.jquery(reciever).attr(_settings.CONTENT_MAXENTRY_KEY);
        return nbContent < receiverMaxEntry; 
    }
    
    var sortableCallbacks = {
        onStart : function(event,ui){},
        onSort  : function(event,ui){},
        onReceive : function(event,ui){
            /*this==reciever*/
            _enableContentSelection = true;
            var acceptedContentType = bb.jquery(this).attr(_settings.ACCEPTED_ITEM_KEY);
            var containerRenderType = bb.jquery(this).attr(_settings.CONTENT_RENDER_TYPE)||null;
            var selectedItemRenderType = bb.jquery(ui.item).attr(_settings.CONTENT_RENDER_TYPE)||"";
            var selectedItemType = bb.jquery(ui.item).attr(_settings.CONTENT_TYPE_ITEM_KEY)||"";
            var maxAccept = _checkReceiverMaxEntry(this,ui.item);
            var itemUid = bb.jquery(ui.item).attr(_settings.ITEM_UID_KEY)||null;
            
            /*mainContentTypeBlock*/
            if(ui.item.hasClass(_settings.acceptClass.replace(".",""))){
                var selectedContentBlockInfos = bb.jquery(ui.item).data("contentTypeInfos");               
                itemUid = selectedContentBlockInfos.uid;
                selectedItemType = selectedContentBlockInfos.name;
                var accept =_isNewContentValid(event,ui);
                
              
                if(accept && maxAccept){
                    ui.item.hide();
                    ui.item.before(ui.placeholder);
                    //bb.jquery(ui.placeholder).css("border","2px solid blue");
                    _loadContent.call(this,selectedContentBlockInfos,containerRenderType,event.target,ui.placeholder,function(){
                        ui.sender.find('.prevContent').remove();
                        ui.item.show();
                        bb.jquery(ui.sender).sortable("cancel");
                    }); 
                }else{
                    ui.sender.find('.prevContent').remove();
                    bb.jquery(ui.sender).sortable("cancel");
                }
                
                return;  
            }
            
            /*other content*/
            var currentItemConf = {};
            currentItemConf.name = selectedItemType;
            currentItemConf.uid = itemUid;
            
            /*if(acceptedContentType.length==0){
                return true;
            } */
            
            var acceptedContainer = acceptedContentType.split(",");
            bb.jquery.each(acceptedContainer,function(key,item){
                acceptedContainer[key] = bb.jquery.trim(item);
            });
            
            if((bb.jquery.inArray(selectedItemType,acceptedContainer)!=-1) || (maxAccept==true)){
                
                /*when we have a match
             *Reload the content according to the render type if necessary
             **/
                if(containerRenderType !=selectedItemRenderType){
                    bb.jquery(ui.item).hide();
                    _loadContent.call(this,currentItemConf,containerRenderType,event.target,ui.item,null,ui.sender);
                    return false;
                }else{
                    bb.jquery(document).trigger("content:newContentAdded",[event.target,ui.item,ui.sender]);
                }
                return true;
            }else{  
                bb.jquery(ui.sender).sortable("cancel");
                return false;
            }
        },
        
        onOver : function(event,ui){}
    };
 
    var _initContentResizable = function(){};
    
    /*use for contentType*/
    var _initDroppable = function(){
        bb.jquery(_settings.layoutDroppabableClass).droppable({
            accept : _checkContentType,
            greedy : true,
            hoverClass : "contentBlock-drophover",
            over: function(event,ui){
                bb.jquery(_settings.contentTypeHelperClass).css("outline","");
            },
            drop : function(event,ui){
                /*check target contentType*/
                var contentTypeParams = bb.jquery(ui.helper).data("contentTypeInfos");
                var renderType = bb.jquery(this).attr(_settings.CONTENT_RENDER_TYPE)||null;
                _loadContent.call(this,contentTypeParams,renderType,event.target);
            } 
        });
    }
    
    var _checkContentType = function(draggedContent){
        var isValid = false;
        var isAcceptable = bb.jquery(draggedContent).hasClass(_settings.acceptClass.replace(".",""));
        bb.jquery(_settings.contentTypeHelperClass).css("outline","2px solid red");
        
        if(isAcceptable){
            isValid = true;
            /*match accepted*/
            var contentTypeParams = bb.jquery(draggedContent).data("contentTypeInfos");
            var acceptedContentType = bb.jquery(this).attr(_settings.ACCEPTED_ITEM_KEY)||"";
            var acceptedContainer = acceptedContentType.split(","); 
            
            /*clean type*/
            bb.jquery.each(acceptedContainer,function(key,item){
                acceptedContainer[key] = bb.jquery.trim(item);
            });
            
            /*check match*/
            if(bb.jquery.inArray(bb.jquery.trim(contentTypeParams.name),acceptedContainer) == -1){
                isValid = false;
            }
            if(acceptedContentType.length==0){
                isValid = true;
            }
        }
       
        return isValid;
    }
    
    
    /*handle new Content*/
    var _handleNewContent = function(content){
        _initSortable();
        _initResizable(); //only for resizable item
        _initDroppableImage(content);
        bb.jquery(content).css({
            cursor:"move"
        });
    }
   
    var _readSizeFromClasses = function(classes){
        var sizePattern = /span\d/gi;
        var result = sizePattern.exec(classes);
        if(result.length){
            var currentSize = parseInt(result[0].replace("span",""));
        }
        return currentSize; 
    };
    
    var _findMainRowSize = function(item){
        var mainParent = bb.jquery(item).parents('div[class *="span"]').get(0);
        var containerSize = _readSizeFromClasses(bb.jquery(mainParent).attr("class"));
        return containerSize;
    };
    
    var _loadContent = function(contentTypeParams,renderType,receiver,placeHolder,callback,sender){
        var webservice = _settings.contentWebservice;
        
        webservice.request("getDataContentType",{
            params:{
                name:contentTypeParams.name,
                mode:renderType,
                uid:contentTypeParams.uid,
                receiverclass: bb.jquery(receiver).attr('data-type'),
                receiveruid: bb.jquery(receiver).attr('data-uid')
            },
            success:function(response){
                var content = _renderNodeContentTypeNode(contentTypeParams,response.result.render);
                //   bb.jquery(content).css("");
                bb.jquery(content).addClass(_settings.draggableContentClass.replace("."," "));
                if(placeHolder){
                    bb.jquery(placeHolder).replaceWith(content);
                }else{
                    bb.jquery(receiver).append(content);
                    bb.jquery(receiver).attr('data-draftuid', response.result.draftuid);
                }
                if(typeof callback=="function"){
                    callback();
                }
                /*si c'est un container*/
                if(bb.jquery(receiver).hasClass("row")){
                    var itemSize = _findMainRowSize(receiver); 
                    bb.jquery(content).addClass("span"+itemSize);
                    bb.jquery(content).addClass(_settings.resizableContentClass.replace(".",""));
                }
               
                bb.Utils.scrollToContent(bb.jquery(content));
                
                /*sortable - resizable for the new content*/
                _handleNewContent(content); 
                /*remove emptyCls*/
                bb.jquery(document).trigger("content:newContentAdded",[receiver,bb.jquery(content),sender]);
            },
            error:function(response){
                throw response.error;
            }
        });
        
        return false;
     
    };
    
    var _loadContentsFromLibrary = function(receiverInfos,contents){
        /*Append new contents to container - call load content once*/
        var webservice = _settings.contentWebservice;
        var receiver = receiverInfos.contentEl;
        webservice.request("getContentsData", {
            params : {
                renderMode : receiverInfos.rendermode, 
                contents : contents,
                receiverclass: bb.jquery(receiver).attr('data-type'),
                receiveruid: bb.jquery(receiver).attr('data-uid'),
                page_uid : bb.frontApplication.getPageId()
            },
            success : function(response){
                var result = response.result;
                var nbItem = result.length;
                bb.jquery.each(result,function(i,item){
                    var cp = i+1;
                    var content = _renderNodeContentTypeNode(item,item.render); //strange to remove
                    bb.jquery(content).addClass(_settings.draggableContentClass.replace("."," "));
                    bb.jquery(receiver).append(content);
                    if(bb.jquery(receiver).hasClass("row")){
                        var itemSize = _findMainRowSize(receiver); 
                        bb.jquery(content).addClass("span"+itemSize);
                        bb.jquery(content).addClass(_settings.resizableContentClass.replace(".",""));
                        _handleNewContent(content);
                    }
                    bb.jquery(document).trigger("content:newContentAdded",[receiver,bb.jquery(content),null]);
                    if(cp == nbItem) bb.Utils.scrollToContent(bb.jquery(content));     
                });
            },
            
            error : function(response){
                console.log("Error pendant le chargement");
            }
            
        });
    };
    
    
    
    var _renderNodeContentTypeNode = function(contentTypeParams,nodeHtml){
        /*build params*/
        //var availableParams = ["item_type","accepted_items","isDroppable","isDraggable","isResizable","disable"];
        //var nodeHtml = bb.jquery(nodeHtml);
        
        //       if(bb.jquery(nodeHtml).hasClass("draggable_item")){
        //bb.jquery(nodeHtml).addClass(_settings.resizableContentClass.replace(".",""));    
        //     }
        //   var itemClasses = bb.jquery(nodeHtml).attr("class");
        // var sizePattern = /span\d/gi;
        //var isResizable = sizePattern.test(itemClasses); 
        
        bb.jquery(nodeHtml).data("contentParams",contentTypeParams);
        return bb.jquery(nodeHtml);
    }
    
    /*Public*/
    var _publicApi = {
        disable : _disable,
        enable : _enable,
        initDroppableImage : _initDroppableImage,
        appendContentsFromLibrary : _loadContentsFromLibrary,
        handleNewContent : _handleNewContent,
        toggleEditionMode: _toggleEditionMode
    };
    
    return {
        init : _init
    };      
        
})(bb.jquery,window);



/*bb.DrabableItem*/
/*bb.droppableItem*/

(function($) {

/*resizableItem --> resizable zone*/
var ResizableItem = function(userSettings){
    
    this._settings = {
        handles : "e",
        containment : "parent",
        helper : "bb5-content-ui-resizable-helper",
        el :null,
        sizeKey : "span",
        gridStep : 0,
        minWidth : 0, //taille d'une colonne
        sizePrefix : "span",
        resizableRenderFlag : "resizableIgnore"
    };
    this._userCallbacks = {
        "onCreate": function(){},
        "onResize": function(){},
        "onResizeStop":  function(){},
        "onResizeStart": function(){}  
    };
    
    if(typeof this._init !== "function"){
        ResizableItem.prototype._init = function(userSettings){
            this._element = null;
            this.resizableId = null;
            this._currentmousePosition = null;
            this._resizeDirection = null;
            this._itemSize = 0; 
            this._parentSize = 0;
            this._maxContainerSize = 0;
            
            bb.jquery.extend(true,this._settings,userSettings); 
            var settings = this._settings;
            this._element = bb.jquery(this._settings.el);
            /*this.bbContent = $bb(this._element);
            console.log(this.bbContent);*/
            
            this._bbContent = $bb(this._element);
            if (this._bbContent.parentNode)
                settings.gridStep = this._bbContent.parentNode.getContentEl().width() / 12;
            
            /*create resize here*/
            var resizableconfig = {};
            resizableconfig.handles = settings.handles;
            resizableconfig.helper = settings.helper;
            resizableconfig.grid = settings.gridStep;
            resizableconfig.maxWidth = bb.jquery(this._element).parent().width();
            bb.jquery(this._element).resizable(resizableconfig);
            this.resizableId = bb.Utils.generateId("resizableContent");
            bb.jquery(this._element).attr("resizableId",this.resizableId);
            this._itemSize = this._readSize(this._element);
            if(!this._itemSize){
                this._itemSize = bb.jquery(this._element).outerWidth(true)/settings.gridStep;
            }
            
            this._bindResizableEvent(); 
        } 
    }
    ResizableItem.prototype._readSize = function(el){
        var currentSize = null
        var sizePattern = /span\d+/gi;
        var classes = bb.jquery(el).attr("class");
        var result = sizePattern.exec(classes);
        if(result && result.length){
            currentSize = parseInt(result[0].replace("span",""));  
        }
        return currentSize;
    } 
    ResizableItem.prototype.getElement = function(){
        return bb.jquery(this._element);
    }
   
   
    ResizableItem.prototype._bindResizableEvent = function(){
        bb.jquery(this._element).bind("resizecreate",bb.jquery.proxy(this._onCreate,this));
        bb.jquery(this._element).bind("resize",bb.jquery.proxy(this._onResize,this));  
        bb.jquery(this._element).bind("resizestop",bb.jquery.proxy(this._onResizeStop,this));  
        bb.jquery(this._element).bind("resizestart",bb.jquery.proxy(this._onResizeStart,this));  
    }
   
    ResizableItem.prototype._onCreate = function(){}       
   
  
    ResizableItem.prototype._onResize = function(event,ui){
        return true;
    }
    
    ResizableItem.prototype._onResizeStart = function(event,ui){
        this._currentmousePosition = event.clientX;
        this._resizeDirection = null; 
        
        this.notify("onResizeStart",{
            contentEl : this._element
        });
    }
    
    
    ResizableItem.prototype.setGridStep = function(gridStep){
        this._settings.gridStep = gridStep;
        bb.jquery(this._element).resizable("option","grid",this._settings.gridStep);
        var maxWidth = bb.jquery(this._element).parent().width();
        bb.jquery(this._element).resizable("option","maxWidth",maxWidth);
    }
    
    ResizableItem.prototype._onResizeStop = function(event,ui){
        this._resizeDirection = (event.clientX < this._currentmousePosition ) ? "left" : "right";
        var width = bb.jquery(event.target).outerWidth(true);
        var delta = ui.originalSize.width - width; //ne pas dépasser la taille d'une colonne
        
        var step = delta / this._settings.gridStep;
        /*Si step négatif --> augmentation de la taille*/       
        
        /*vers la gauche : diminution de la taille*/
        if((this._resizeDirection=="left" && step > 0)){
            /*decrement current*/
            this.decrementItemSize(step);
        }
        
        /*vers la droite : diminution de la taille*/
        if(this._resizeDirection=="right" && step >0){
            /*decrement current*/
            this.decrementItemSize(Math.abs(step));
        }
        
        
        /*vers la droite : augmentation de la taille*/
        if(this._resizeDirection=="right" && step < 0 ){
            /*decrement current*/

            this.incrementItemSize(Math.abs(step));
        }
        
        
      
        /*vers la gauche : augmentation de la taille*/
        if((this._resizeDirection=="left" && step < 0)){
            this.incrementItemSize(Math.abs(step));
        }
        
        /*reset*/
        this._resizeDirection = null;
        this._currentmousePosition = null;
        bb.jquery(ui.element).css({
            width:"",
            left:"",
            height:"auto"
        });
        /*set new Size */
        this._bbContent.setSize(this._itemSize);
        this.notify("onResizeStop",{
            contentEl : this._element
        });
    }
    
    ResizableItem.prototype.incrementItemSize = function(step,onlyChild){
        var step = (parseInt(step)!=0) ? parseInt(step)  : false;
        if(!step) return false;
        if(!onlyChild){
            var newSpanSize = this._itemSize + step;
            this.setItemSize(newSpanSize);
        }
    }
    
    ResizableItem.prototype.decrementItemSize = function(step,onlyChild){
        var step = (parseInt(step)!=0) ? parseInt(step)  : false;
        if(!step) return false;
        
        if(!onlyChild){
            var newSpanSize = this._itemSize - step;
            this.setItemSize(newSpanSize); 
        }
    }
    
    
    
    ResizableItem.prototype.setItemSize = function(itemSize){
        if(itemSize){
            /*remove previous class*/
            if(this._itemSize){
                bb.jquery(this._element).removeClass(this._settings.sizePrefix+parseInt(this._itemSize));
            }
            
            /*add new*/
            bb.jquery(this._element).addClass(this._settings.sizePrefix+Math.abs(parseInt(itemSize)));
            this._itemSize = itemSize;  
            if (this._bbContent.parentNode){}   
        }  
    }

    ResizableItem.prototype.getId = function(){
        return this.resizableId;
    }
    
    ResizableItem.prototype.destroy = function(){
        bb.jquery(this._element).resizable("destroy");
        bb.jquery(this._element).removeClass(this._settings.resizableRenderFlag);
        /*clean events*/
        bb.jquery(this._element).unbind("resizecreate");
        bb.jquery(this._element).unbind("resize");
        bb.jquery(this._element).unbind("resizestop"); 
        bb.jquery(this._element).unbind("resizestart");
        
    }
    
    ResizableItem.prototype.on = function(eventName,callback){
        if(typeof eventName =="string" && typeof callback=="function"){
            this._userCallbacks[eventName] = callback; 
        }
    }
    
    ResizableItem.prototype.notify = function(eventName,data){
        var eventName = (typeof eventName =="string") ? eventName: "none";
        var data = data || null;
        bb.jquery(document).trigger("ContentResized:"+eventName,data);
    } 

    return this._init(userSettings);
} 


bb.ResizableItem = ResizableItem;

}) (bb.jquery);
