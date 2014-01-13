(function($){
    bb.jquery.widget("ui.bbSelector", {
        options: {
            popup: false,
            autoOpen: false,
            selected: 0,
            pageSelector: true,
            linkSelector: true,
            mediaSelector: true,
            contentSelector:true,
            draggable: true,
            resizable: true,
            callback: null,
            beforeWidgetInit:null,
            selectorTitle :"",
            resizeStop : null,
            resize : null,
            resizeStart :null,
            site:null
        //            popup: {
        //                width: bb.jquery(window).innerWidth(),
        //                height: bb.jquery(window).innerHeight()
        //            }
        },
        i18n: {
            defaultLabel:"Sélecteur"
        },
        _templates: {
            tabs: '#bb-ui-bbselector-tabs-tpl'
        },
        
        _onInitCallbacks: {},
        
        _context: {
            panels: {},
            selected: 0,
            callback: null,
            isOpen : false
        },
        _panel :{
            INTERNAL_LINK:"bbLinkInternalContainer",
            EXTERNAL_LINK:"bbLinkInExternalContainer",
            MEDIA_LINK:"bbMediaContainer",
            CONTENT_LINK:"bbContentsSelectorContainer"
        },
        widgetClasses : {
            bbLinkInternalContainer:"bbPageSelector",
            bbLinkInExternalContainer:"bbLinkSelector",
            bbMediaContainer:"bbMediaSelector",
            bbContentsSelectorContainer:"bbContentSelector"
        },
        toolsbarsCtn :{},
        
        resizeInfos :{
            originalContainerHeight : null,
            resizeStep : 0
        },
        
        _create: function() {
            var myself = this;
            this.options.selectorTitle = ( typeof this.options.selectorTitle=="string" ) ? this.options.selectorTitle : this.i18n.defaultLabel;
            this.setContext({
                panels: {},
                selected: this.options.selected
            });
            
            /*creation des tabs */
            var tabsInfos = {
                "bbLinkInternal":{
                    index:0
                },
                "bbLinkExternal":{
                    index:1
                },
                "bbMedia":{
                    index:2
                },
                "bbContentsSelector":{
                    index:3
                }
            };
            
            var tabsToDisable = [];
            if(!this.options.pageSelector)  tabsToDisable.push(tabsInfos["bbLinkInternal"].index);
            if(!this.options.linkSelector)  tabsToDisable.push(tabsInfos["bbLinkExternal"].index);
            if(!this.options.mediaSelector) tabsToDisable.push(tabsInfos["bbMedia"].index);
            if(!this.options.contentSelector) tabsToDisable.push(tabsInfos["bbContentsSelector"].index);
           
            /*handle default selected*/
            //
            if (typeof this.options.beforeWidgetInit == "function")
                this.options.beforeWidgetInit.call(this);
            
            this._context.selectorTabs = new bb.LpTabs({
                mainContainer :"#bb5-selectortabs-tpl",
                disabled: tabsToDisable,
                cloneContainer:true,
                onShow : bb.jquery.proxy(this._renderSelector,this),
                hideHeaderIfonlyOne : true
            });
           
            this._trigger('ready');
            /*add tab*/
            bb.jquery(this.element).html(this._context.selectorTabs.getTab());
        },
            
        /*add Some useful functions to selector's Children*/
        extendChildWidget: function(selectorWidget){
            if(typeof selectorWidget != "function") return false;
            var self = this;
            var api = {
                getParent : (function(parent){
                    var mainContainer = parent; 
                    return function(){
                        return mainContainer;
                    }
                })(self),
                getTest: function(){
                    return "test";
                }
            };
            if(typeof selectorWidget=="function"){
                bb.jquery.extend(true,selectorWidget.prototype,api);  
            } 
        },
        
        onWidgetInit : function(widgetName,callback){
            var widgetName = widgetName || false;
            if(!widgetName) return;
            this._onInitCallbacks[widgetName] = (typeof callback =="function") ? callback : bb.jquery.noop;
        }, 
        
        _renderSelector : function(e,ui){
            var myself = this;
            var context = myself.getContext();
            var widgetName = null;
            switch(bb.jquery(ui.panel).attr("class")){
                case this._panel.INTERNAL_LINK:
                    if (!context.panels[this._panel.INTERNAL_LINK]){ 
                        this.extendChildWidget(bb.jquery.ui.bbPageSelector); 
                        context.panels[this._panel.INTERNAL_LINK] = bb.jquery(ui.panel).bbPageSelector({
                            site: myself.options.site,
                            callback: context.callback,
                            ready: function() {
                                //console.log('test', myself._onInitCallbacks);
                                if (typeof myself._onInitCallbacks[myself._panel.INTERNAL_LINK] == "function") {
                                    myself._onInitCallbacks[myself._panel.INTERNAL_LINK].call(this);
                                //console.log('INTERNAL_LINK');
                                }
                            }
                        });
                    }
                   
                    break;
                case this._panel.EXTERNAL_LINK:
                    if (!context.panels[this._panel.EXTERNAL_LINK]){
                        this.extendChildWidget(bb.jquery.ui.bbLinkSelector);                        
                        context.panels[this._panel.EXTERNAL_LINK] = bb.jquery(ui.panel).bbLinkSelector({
                            callback: context.callback,
                            ready: function() {
                                if (typeof myself._onInitCallbacks[myself._panel.EXTERNAL_LINK] == "function") {
                                    myself._onInitCallbacks[myself._panel.EXTERNAL_LINK].call(this);
                                //console.log('EXTERNAL_LINK');
                                }
                            }
                        });
                    }
                    break;
                case this._panel.MEDIA_LINK:
                    if (!context.panels[this._panel.MEDIA_LINK]){
                        this.extendChildWidget(bb.jquery.ui.bbMediaSelector);                        
                        context.panels[this._panel.MEDIA_LINK] = bb.jquery(ui.panel).bbMediaSelector({
                            callback: context.callback,
                            editMode: true,
                            ready: function() {
                                if (typeof myself._onInitCallbacks[myself._panel.MEDIA_LINK] == "function") {
                                    myself._onInitCallbacks[myself._panel.MEDIA_LINK].call(this);
                                }
                            }
                        });
                        
                    }
                    break;
                    
                case this._panel.CONTENT_LINK:
                    if(!context.panels[this._panel.CONTENT_LINK]){
                        this.extendChildWidget(bb.jquery.ui.bbContentSelector);                        
                        context.panels[this._panel.CONTENT_LINK] = bb.jquery(ui.panel).bbContentSelector({
                            ready: function(){
                                if (typeof myself._onInitCallbacks[myself._panel.CONTENT_LINK] == "function") {
                                    myself._onInitCallbacks[myself._panel.CONTENT_LINK].call(this);
                                //console.log('EXTERNAL_LINK');
                                }
                            },
                            close : function(){
                                myself.close();
                            }
                        });  
                    }
                    break;
            }
            
            this.setContext(context);
        },
        
        getWidget : function(selectorName){
            var selectorName = selectorName || "none";  
            var context = this.getContext();
            return context.panels[selectorName];
        }, 
        
        
        _handleSelectedTabActions : function(){
            
            /*handle toolsbar here*/
            if(!this.options.popup) return false;
            var selectedTab =  this._context.selectorTabs.getSelectedTab();
            var selectedWidget = this.getWidget(bb.jquery(selectedTab.tabPanel).attr("class"));
            var dialogUi = this.mainDialog.dialogUi;
            bb.jquery(dialogUi).find(".bb5-dialog-title-tools").remove();
            if(selectedWidget){
                var tbMenu = this.toolsbarsCtn[bb.jquery(selectedTab.tabPanel).attr("class")] || false ;
                if(!tbMenu){
                    var selectedWidget = bb.jquery(selectedWidget).data(this.widgetClasses[bb.jquery(selectedTab.tabPanel).attr("class")]);
                    if(typeof selectedWidget.initToolsbarMenu=="function"){
                        var tbMenu = selectedWidget.initToolsbarMenu();
                        this.toolsbarsCtn[bb.jquery(selectedTab.tabPanel).attr("class")] = tbMenu;
                    }
                }
                if(tbMenu.length){
                    bb.jquery(dialogUi).find('.ui-dialog-titlebar .ui-dialog-titlebar-close').before(bb.jquery(tbMenu).clone(true)); 
                }
            }
        },
       
        _init: function() {
            var myself = this,
            context = myself.getContext();
            this.options.resize = (typeof this.options.resize =="function")? this.options.resize : bb.jquery.noop;
           
            if (this.options.popup) {
                var popupManager = bb.PopupManager.init();
                this.mainDialog = popupManager.create("selectorDialog",{
                    dialogType: "bb5-dialog-selector",
                    resizable: this.options.resizable,
                    draggable:this.options.draggable,
                    autoOpen : false,
                    closeOnEscape: false,
                    modal: true,
                    zIndex: 500001,
                    position: ["center","center"],
                    minWidth: 990,
                    minHeight: 560,
                    width: (bb.jquery(window).innerWidth() - 100),
                    height: (bb.jquery(window).innerHeight() - 50),
                    close : bb.jquery.proxy(this.onMainDialogClose,this),
                    open : bb.jquery.proxy(this._handleSelectedTabActions,this),
                    resizeStart: function(e,ui){
                        myself.resizeInfos.originalContainerHeight = bb.jquery(e.target).height();
                        myself.resizeInfos.resizeStep = 0;
                        myself._trigger("resizeStart");
                        if(typeof myself.options.resizeStart=="function"){
                            myself.options.resizeStart.call(myself,e,ui); 
                        }
                    },
                    resize :function(e,ui){
                        var delta =  bb.jquery(e.target).height() - myself.resizeInfos.originalContainerHeight;
                        var deltaStep = delta - myself.resizeInfos.resizeStep;
                        myself.resizeInfos.resizeStep = delta;
                        myself._trigger("resize",0,{
                            delta : deltaStep
                        });
                        if(typeof myself.options.resize =="function"){
                            myself.options.resize.call(myself,e,ui);

                        }
                    },
                    resizeStop: function(){
                        myself.resizeInfos.originalContainerHeight = null;
                        myself.resizeInfos.resizeStep = 0;
                        myself._trigger("resizeStop");
                        /*explicit call*/
                        if(typeof myself.options.resize=="function"){
                            bb.jquery.proxy(myself.options.resizeStop,myself);
                        }
                        
                    }
                }); 
                
                this.mainDialog.setOption("title",this.options.selectorTitle);
                /*bb.jquery(this.element).dialog({
                    dialogClass: 'bb5-ui bb5-dialog-wrapper bb5-dialog-selector',
                    resizable: this.options.resizable,
                    draggable:this.options.draggable,
                    autoOpen : false,
                    closeOnEscape: false,
                    modal: true,
                    zIndex: 500001,
                    position: ["center","center"],
                    minWidth: 990,
                    minHeight: 580,
                    close : bb.jquery.proxy(this.onMainDialogClose,this),
                    open : bb.jquery.proxy(this._handleSelectedTabActions,this)
                });*/
                
                this.mainDialog.setContent(bb.jquery(this.element));
                if (this.options.autoOpen){
                    this.mainDialog.open();
                //bb.jquery(this.element).dialog('open');
                }
                    
            } else {
                if (this.options.autoOpen)
                    bb.jquery(this.element).show();
            }
            
            context.callback = this.options.callback;
            // var context = myself.getContext();
            /*populate content here*/
            this.setContext(context);
            
            this._bindEvents();
        },
        
        open: function() {
            if (this.options.popup) {
                this.mainDialog.open();
            //bb.jquery(this.element).dialog('open');
            } else {
                bb.jquery(this.element).show();
            }
            this._context.isOpen = true;
            this._trigger('open');
        },
        
        close: function() {
            if (this.options.popup) {
                this.mainDialog.close();
            //bb.jquery(this.element).dialog('close');
            } else {
                bb.jquery(this.element).hide();
            }
             this._context.isOpen = false;
            this._trigger('close');
        },
        
        on : function(eventName,callback){
            var availableEvents = ["open","close","resizeStart","resize","resizeStop"];
            var callback = (typeof callback=="function") ? callback : function(){
                return "Callback should be a function";
            };
            var eventName = (typeof eventName =="string") ? eventName : false;
            if(!eventName || bb.jquery.inArray(eventName,availableEvents)==-1) return;
            var eventName = "bbselector"+eventName.toLowerCase();
            bb.jquery(this.element).bind(eventName,callback);
        },
        
        onMainDialogClose :function(){
            this._trigger("close"); 
        },
        
        _bindEvents :function(){
            var self = this;
            var context = this.getContext(); 
            bb.jquery(window).resize(function(e){
            if(("delay" in context) && context.delay != false) clearTimeout(context.delay);
            var onResize = function(){
                if(!self._context.isOpen) return;
                 self.mainDialog.setOption("width",bb.jquery(window).innerWidth() - 100);
                 self.mainDialog.setOption("height",bb.jquery(window).innerHeight() - 100);
                 //bb.jquery(self.element).dialog("options","position",);
            }
            context.delay = setTimeout(onResize,100);
            self.setContext(context);
        });
        },
       
        setCallback: function(callback) {
            var context = this.getContext();
            context.callback = callback;
            
            //panels
            if (context.panels[this._panel.INTERNAL_LINK])
                context.panels[this._panel.INTERNAL_LINK].bbPageSelector('setCallback', context.callback);
            
            if (context.panels[this._panel.EXTERNAL_LINK])
                context.panels[this._panel.EXTERNAL_LINK].bbLinkSelector('setCallback', context.callback);
            
            if (context.panels[this._panel.MEDIA_LINK])
                context.panels[this._panel.MEDIA_LINK].bbMediaSelector('setCallback', context.callback);
            
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
            
            //panels
            if (context.panels['bbLinkInternal'])
                context.panels['bbLinkInternal'].bbPageSelector('destroy');
            
            if (context.panels['bbLinkExternal'])
                context.panels['bbLinkExternal'].bbLinkSelector('destroy');
            
            if (context.panels['bbMedia'])
                context.panels['bbMedia'].bbMediaSelector('destroy');
            
            //tabs
            this._context.selectorTabs.destroy();
            
            //popin
            if (this.options.popup) {
                this.mainDialog.destroy();
            //bb.jquery(this.element).dialog('destroy');
            } else {
                bb.jquery(this.element).hide();
            }
            
            bb.jquery.Widget.prototype.destroy.apply(this, arguments);
            
            this.setContext(context);
        }
    })
})(bb.jquery);