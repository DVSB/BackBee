
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 *
 * Usage :
 *  addContent({id:"",content:$("#reset"),onload:function(){}});
 *
 *
 */

var ViewManager = function(elementId,userSettings){
       
    this._viewsList = [
    /*{id:"home",title:"Editeur", viewSize:{height:500,width:500}, render:function(){},availableAction:{}, afterRender : function(){}},
            {id:"edit",title:"Editeur", viewSize:{height:500,width:500}, render:function(){},availableAction:{}, afterRender:  function(){}}*/
    ];
    this._currentView = "";
    this._viewPositionInfos = {}; //à contruire en fonction de l'orientation
    this._searchManager = null;
    this._settings = {
        mainContainerSize : {
            height:850,
            width:900
        },
        defaultViewSize : {
            height:700,
            width:850
        },
        showTitle : false,
        scrollable : false,//true,
        animation : "slideLeft", //or animation function,
        slideMode :"seq", //alt,
        viewTitleClass : ".viewTitle",
        viewMaskClass : ".viewMask",
        viewItemWrapperClass : ".viewItemWrapper",
        viewItemClass : ".viewItem",
        actionsContainerClass: ".actionsContainer",
        currentViewId : ""
    };
    this._animInfos = {
        direction:"top",
        nextDirection : null
    };
    
    this._generateId = (function(){
        var genPrefix = "view_";
        var current = 0;
        return function(prefix){
            genPrefix = prefix || genPrefix;
            return genPrefix+current++;
        }
    })();
       
    this._currentId = null;
       
    this._widgetTmpl = '<div class="viewManager">'
    +'<p class="ViewTitle">This is my title</p>'
    +'<div class="viewMask">'
    +'<ul class="viewItemsList"></ul>'
    +'</div>'
    +'<div class="actionsContainer">This is my action container</div>'
    +'</div>'; 
       
    this._itemTmpl = '<li class="viewItemWrapper"><div class="viewItem">flashBack</div></li>';
       
    this._init = function(elementId,userSettings){
        this._settings = $.extend(this._settings,userSettings);
        this._currentId = this._generateId(elementId);
        this._mainContainer = $("#"+elementId) || null;
        this._mainContainer.hide();
        this._widgetHtml = $(this._widgetTmpl).clone();
        $(this._widgetHtml).data("widgetId",this._currentId);
        this._viewItems = $(this._settings.viewItemClass) || [];
        this._viewItemsList = $(this._widgetHtml).find(".viewItemsList");
        this._viewMask = $(this._widgetHtml).find(".viewMask");
        this._viewTitle = $(this._widgetHtml).find(".viewTitle");
        this._viewActionBar = $(this._widgetHtml).find(".actionsContainer");        
        this._buildWidget();
        this._bindEvents();
        this._handleMakup();

        if(this._settings.animation=="slideUp"){
            this._animInfos = {
                direction:"top",
                nextDirection : "down"
            };
        }else{
            this._animInfos = {
                direction:"left",
                nextDirection : "right"
            };
        }
        this._setWidgetMakeupStyle(this._animInfos.direction);
        return this;
    }
    
    this._handleMakup = function(){
        if(!this._settings.showTitle) this._viewTitle.hide();
        if(!this._settings.showActionBar) this._viewActionBar.hide();
    }
    
    this._setWidgetMakeupStyle = function(orientation){
        var availableOrientations = ["top","left"];
        var containerStyle = {}; 
        var maskStyle = {};
        containerStyle.height = this._settings.mainContainerSize.height+"px"; 
        containerStyle.width = this._settings.mainContainerSize.width+"px";
        containerStyle.border = "1px solid red";
        containerStyle.padding = "10px";
        
        
        /*Mask Style*/
        $(this._viewMask).css({
            position:"relative",
            overflow:"hidden",
            height:this._settings.defaultViewSize.height+"px", //gerer les ajustements en fonction de la taille de l'élement
            width:this._settings.defaultViewSize.width+"px",
            margin:"auto"
        });
        
        /*la taille des views ne doivent pas dépasser la taille des masques*/
        if($.inArray(orientation, availableOrientations)!=-1){
            if("top"==orientation){
                /*ViewList style*/
                this._viewItemsList.css({
                    width:'auto',
                    position:"absolute",
                    margin:"auto", 
                    padding:0
                })
            }
            
            if("left"==orientation){
                this._viewItemsList.css({
                    hieght:'auto',
                    position:"absolute",
                    margin:"auto", 
                    padding:0
                })
            }
           
        }
        this._widgetHtml.css(containerStyle);
   
    }
    
    
    this._bindEvents = function(){
        
        this._handlerHistoryEvents();
    }
    /**/
    this._buildWidget = function(){
        if(this._mainContainer){
            if(this._viewItems.length!=0) this._viewItemsList.append($(this._viewItems));
            $(this._mainContainer).replaceWith(this._widgetHtml);
        } 
    }
       
    this._buildViewItem = function(viewItem){
        /*
         *Constuire ici le panel
         * Si ID dans la page récupérer le contenu
         * Si url  non-vide récupérer le contenu de la page en ajax
         * Forcer la taille du viewItem
         * Après l'ajouter executer
         **/
            
        var itemPanel = $(this._itemTmpl).clone();
        $(itemPanel).find('.viewItem').html($(viewItem.id).show());
        $(this._widgetHtml).find(".viewItemsList").append(viewItem.getContent());
        /*Revoir cette fonction et le scope*/
        if(typeof viewItem.afterRender==="function"){
            viewItem.afterRender.call(viewItem,viewItem);
            
        }
    }
       
    this._saveViewPosition = function(){}
       
    if(typeof this.addView !=="function"){
        ViewManager.prototype.addView = function(elId, viewConfig){
            var viewConfig = viewConfig || {};
            viewConfig.size = this._settings.defaultViewSize;
            viewConfig.contentEl = elId;
            var viewItem = new ViewItem(viewConfig);
            viewItem._viewContainer = this;
            this._viewsList.push(viewItem);
            /*add view's position*/
            var nbView = this._viewsList.length - 1;
            if(this._settings.animation=="slideUp"){
                var viewId = "#"+viewItem.getViewId();
                this._viewPositionInfos[viewId] = nbView * viewItem.getHeight(true);
            }
            else{
                var viewId = "#"+viewItem.getViewId();
                this._viewPositionInfos[viewId] = nbView * viewItem.getWidth(true);
                $(viewId).css({
                    "float":"left"
                });
            }
            return this;
        }
    } 
     
    /*adjust container size when a new view is added--> useACallback*/
       
    /*
     *viewKey
     *viewId
     *viewno
     *usign default transition or custom transition
     **/
    ViewManager.prototype.gotoView = function(viewKey,useHistory){
        var useHistory = (useHistory==false) ? false : true;
        var searchManager =  new FilterManager(this._viewsList);
        var viewItem = searchManager.where("id","=",viewKey).execute();
        if(viewItem.length==0) return false;
        if(this._currentView.getViewId()==viewKey) return false;
        viewItem = viewItem[0];
        var viewKey = "#"+viewKey;
        
        if(this._settings.slideMode !="alt"){
            var nextPosition = this._viewPositionInfos[viewKey];
            if(nextPosition=="undefined") return false;
            var directionKey = this._animInfos.direction;
            var animParams = {};
            animParams[directionKey] = -nextPosition;
            this._viewItemsList.animate(animParams);
        /*Mettre à jour le titre du manager o-->o placer dans un post traitement*/   
        }else{
            this._swapView(viewItem);
        }
        this._currentView = viewItem;
        $(this._widgetHtml).find(".slideTitle").text(viewItem.title);
        /*history*/
        if(useHistory) history.pushState({
            controller:"viewMananger", 
            view:viewKey
        },"","#viewManager:"+viewKey.replace("#",""));
        /*Event*/
        if(typeof viewItem.onShow == "function"){
            viewItem.onShow.call(viewItem);
        }
    }
    
    this._swapView = function(nextView){
        
        /*Déterminer en fonction du sens du prochain déplacement 
         * un déplacement d'un pas = à la taille d'une vue
         * Dans tous les cas current+step || current-step
         *
         **/
        var nextViewId = nextView.getViewId();
        var viewsContainerLeft = parseInt($(this._viewItemsList).css("left").replace("px",""));
        /*determiner la nouvelle position de l'élément afin de déterminer le bon déplacement*/
        
        if(this._animInfos.nextDirection=="left"){
            var afterCurrentView = this._currentView.getNext();
            if(!afterCurrentView){
                var item = $("#"+this._currentView.getViewId()).insertAfter($("#"+nextView.getViewId()));
            }else{
                if(nextViewId == $(afterCurrentView).attr("id")){
                    item = afterCurrentView;
                }
                else{
                    var cloneAfterCurrent = afterCurrentView.clone(true);
                    var cloneNext = $("#"+nextView.getViewId()).clone(true);
                    /*mettre cloneAfterCurrent à la place de nextview*/
                    $("#"+nextView.getViewId()).replaceWith(cloneAfterCurrent);
                    /*mettre cloneNext à la place de afterCurrentView*/
                    item = $(afterCurrentView).replaceWith(cloneNext);
                } 
            }
            if(item){
                /*animate to next view*/
                var nextPosition =  viewsContainerLeft - 852;
                this._viewItemsList.animate({
                    left:nextPosition
                });
                this._animInfos.nextDirection = "right";  
                return;
            }
            
        }
         
        if(this._animInfos.nextDirection=="right"){
            
            var beforeView = this._currentView.getPrev();
            if(!beforeView){
                var item = $("#"+this._currentView.getViewId()).before($("#"+nextView.getViewId()));
            }
            else{
                
                if(nextViewId == $(beforeView).attr("id")){
                    var item = beforeView;  
                }
                else{
                    var clonePrev = beforeView.clone(true);
                    var cloneNext = $("#"+nextView.getViewId()).clone(true);
                    /*mettre clonePrev a la place de nextview*/
                    $("#"+nextView.getViewId()).replaceWith(clonePrev);
                    /*mettre cloneNext a la place de beforeView*/
                    var item = $(beforeView).replaceWith(cloneNext);
                } 
            }
            if(item){  
                var prevPosition = viewsContainerLeft + 852;
                this._viewItemsList.animate({
                    left:prevPosition
                });
                this._animInfos.nextDirection = "left"; 
            }
            /*animate to next view*/
            
            return;
        }
    
    }
    
    
    
    this._handlerHistoryEvents = function(){
        var self = this;
        window.onpopstate = function(e){
            var state = e.state;
            if(state) self.gotoView(state.view.replace("#",""),false);
        }
        
    }
      
    ViewManager.prototype.getView = function(viewKey){
        return this._viewsList[viewKey];
    }
      
    ViewManager.prototype.render = function(){
        var self = this;
        $.each(this._viewsList,function(i,viewItem){
            self._buildViewItem(viewItem);
        });
        
        /*ajust the view list size*/
        this._adjustViewMaskSize();
        this._ajustViewContainerSize();
        
        /*Select the current view*/
        var firstView = this._viewsList[0];
        var viewIdToSelect = (this._settings.currentViewId != "") ? this._settings.currentViewId : firstView.getViewId();
        this._initFirstView(viewIdToSelect.replace('#',''));
        
        /*show the widget*/
        $(this._mainContainer).show();
        
    }
    
    this._initFirstView = function(viewKey){
        
        var searchManager =  new FilterManager(this._viewsList);
        var viewItem = searchManager.where("id","=",viewKey).execute();
        if(viewItem.length==0) return false;
        viewItem = viewItem[0];
        var viewKey = "#"+viewKey;
        
        var nextPosition = this._viewPositionInfos[viewKey];
        if(nextPosition=="undefined") return false;
        var directionKey = this._animInfos.direction;
        var animParams = {};
        animParams[directionKey] = -nextPosition;
        this._viewItemsList.animate(animParams);
        this._currentView = viewItem;
    }
    
    this._adjustViewMaskSize = function(){
        var firstView = this._viewsList[0]||false;
        if(!firstView) return false;
        var viewHeight = firstView.getHeight(true);
        var viewWidth = firstView.getWidth(true);
        this._viewMask.css({
            height : viewHeight+"px",
            width : viewWidth +"px"
        });
    }
    
    this._ajustViewContainerSize = function(){
        var totalHeight = 0;
        var totalWidth = 0;
        var self = this;
        $.each(this._viewsList,function(i,item){
            totalHeight = totalHeight + item.getHeight(true);
            totalWidth = totalWidth + item.getWidth(true);
        });
   
        if(this._settings.animation=="slideUp"){
            $(this._viewItemsList).css({
                height:totalHeight
            });
        }
        else{
            $(this._viewItemsList).css({
                width:totalWidth
            });            
        } 
        
      
        
    }
    
    /*Get Max item size*/
    
      
      
    return this._init(elementId, userSettings);
       
}




/*View Factory
*
*Cette Classe permet de créer une vue
* view = {init:function(){}, }
     
**/

ViewItem = function(userConfig){
         
    this._settings = {
        size : {
            height:500, 
            width:500
        },
        title: "this is my view",
        contentEl :"",
        data :"",
        tpl : null,
        remoteUrl : "",
        viewItemClass : ".viewItem"
    };
    
    this.id = null;
    this._viewContainer = null;
    /* ViewItem : Events */
    this.afterRender = $.noop();
    this.onShow = $.noop();
    
    if(typeof this._init!="function"){
        this._init = function(userConfig){
            this._settings = $.extend(this._settings,userConfig);
            this._view = $(this._settings.contentEl)|| null;
            this._applyStyle();
            this.id = $(this._settings.contentEl).attr("id");   
            
            /*handle callbacks onRender*/
            if(typeof this._settings.afterRender==="function"){
                this.afterRender = this._settings.afterRender;
            }
            
            /*handle callbacks onShow*/
            if(typeof this._settings.onShow==="function"){
                this.onShow = this._settings.onShow;
            }
            
        }      
    }
    
    
    this._generateId = (function(){
        var genPrefix = "view_";
        var current = 0;
        return function(prefix){
            genPrefix = prefix || genPrefix;
            return genPrefix+current++;
        }
    })();
    
    this._applyStyle = function(){
        var cssObj = {
            height:this._settings.size.height+"px", 
            width:this._settings.size.width+"px"
        }; 
        cssObj.border = "1px solid blue";
        this._view.css(cssObj);
    }
        
        
    ViewItem.prototype.getContent = function(){
        var viewContent = null;
        if(this._settings.remoteUrl.length!=0){
        /*remotely load content*/
            
        }
        if(this._settings.remoteUrl.length==0){
            viewContent = this._view;
        }
        return viewContent;
    }
    
    ViewItem.prototype.set = function(optionKey,value){
        this._settings[optionKey] = value;
    }
    
    ViewItem.prototype.getHeight = function(computedSize){
        var computedSize = computedSize||false;
        var viewHeight = (computedSize) ? $(this._view).outerHeight() : this._settings.size.height;
        return viewHeight;
    }
    
    ViewItem.prototype.getWidth = function(computedSize){
        var computedSize = computedSize||false;
        var viewWidth = (computedSize) ? $(this._view).outerWidth() : this._settings.size.width;
        return viewWidth;
    }
    
    ViewItem.prototype.getViewId = function(){
        var contentEl = this._settings.contentEl;
        return $(contentEl).attr("id");
    }
    
    /*Renvoie le conteneur de la vue*/
    ViewItem.prototype.getViewContainer = function(){
        return this._viewContainer;
    } 
         
    ViewItem.prototype.getNext = function(){
        var nextItem = $("#"+this.getViewId()).next(this._settings.viewItemClass);
        if(!nextItem) return false;
        return nextItem;
    }   
    
    ViewItem.prototype.getPrev = function(){
        var nextItem = $("#"+this.getViewId()).prev(this._settings.viewItemClass);
        if(!nextItem) return false;
        return nextItem;  
    }
    
    return this._init(userConfig);
         
         
         
         
}

