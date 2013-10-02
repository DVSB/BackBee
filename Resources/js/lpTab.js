var LpTabs = function(userConfig){
    
    this.tab = null;
    this.prefix = null;
    var CONTENT_KEY = "data-tab-content";
    this._settings = {
        mainContainer : null, 
        selected : 0,
        tabLinksContainerClass :".bb5-tabs",
        tabLinksEl : "a",
        tabContentClass :"",
        activeTabCls :"bb5-tabs-active",
        spinner : "Loading...",
        disabled : false,
        keepAnchor : false, // Keep the link anchor. Can be useful for navigation
        cloneContainer: false // Clone the html so that the tab can be added anywhere
    };
    
    this.selectedTab = null;
    this.tabLinksContainer = null;
    this.onSelect = function(){}
    this.onShow = function(){}
    this.onCreate = function(){}
    
    if(typeof this._init !=="function" ){
        LpTabs.prototype._init = function(userConfig){
            var userConfig = userConfig || {};
            this._settings = $.extend(true,this._settings,userConfig);
            // $(this._settings.mainContainer).mask(bb.i18n.loading); //add loader here
            this.tab = $(this._settings.mainContainer) || null;
            
            if(!this.tab.length) throw "mainContainer can't be null";
            this.prefix = bb.Utils.generateId("tab");

            /*clone container */
            if(this._settings.cloneContainer){
                this.mainContainer = "#"+this.prefix;
                this.tab = $(this.tab).clone();
                this.tab.attr("id",this.mainContainer);
            }
            
            this.tabLinksContainer = $(this.tab).find(" > "+this._settings.tabLinksContainerClass);
            this.tabLinks = $(this.tabLinksContainer).find(this._settings.tabLinksEl);
            if(!this.tabLinks.length) throw "tablinks can't be found";
            
            if(typeof this._settings.onSelect=="function"){
                this.onSelect = this._settings.onSelect;
            }
            
            if(typeof this._settings.onCreate=="function"){
                this.onCreate = this._settings.onCreate;
            }
            
            if(typeof this._settings.onShow=="function"){
                this.onShow = this._settings.onShow;
            }
            
            this._beforeCreate();
            this._createTabs();
            this._bindEvents();
        }      
    }
    
    LpTabs.prototype._createTabs = function(){
        var self = this;
       
        $.each(this.tabLinks,function(i,tab){
            $(tab).attr("data-tabno",i);
            /*disable tabs here*/
            if($.isArray(self._settings.disabled)){
                if($.inArray(i,self._settings.disabled)!=-1){
                    $(tab).addClass("disabled");
                    $(tab).hide();
                } 
            }
            
            var content = $(self.tab).find($(tab).attr(CONTENT_KEY));
            if(!content.length) throw "content for tab "+$(tab).attr(CONTENT_KEY).replace("#","")+" can't be found";
            if((($.isArray(self._settings.disabled)) && ($.inArray(i,self._settings.disabled)!=-1)) || self._settings.selected!=i){ 
                $(content).hide();
            }
            else{
                if($.inArray(self._settings.selected,self._settings.disabled)==-1){
                    if(self._settings.selected == i){
                        self.selectedTab = $(tab);
                        $(tab).addClass(self._settings.activeTabCls);
                        $(content).show();
                        var ui = {
                            panel : $(content)
                        }; 
                        self.onShow.call(self,{
                            type:"tabShow"
                        },ui);
                    }    
                }
            } 
        });
        var availableTabs = $(this.tabLinksContainer).find(this._settings.tabLinksEl).not(".disabled");
        /*select the first tabs*/
        if(!this.selectedTab){
            /*first available tab*/
            if(availableTabs.length){
                this.selectedTab = availableTabs[0];
                $(this.selectedTab).addClass(this._settings.activeTabCls);
                var content = $(this.tab).find($(this.selectedTab).attr(CONTENT_KEY));
                if(!content.length) throw "content for tab "+$(this.selectedTab).attr(CONTENT_KEY).replace("#","")+" can't be found";
                $(content).show();
                var ui = {
                    panel : $(content)
                }; 
                var text = $(this.selectedTab).get(0).text;
                self.onShow.call(this,{
                    type:"tabShow",
                    tabLabel:text
                },ui);
            } 
        }
        /* hide header if asked*/
        if(typeof this._settings.hideHeaderIfonlyOne=="boolean" && this._settings.hideHeaderIfonlyOne && availableTabs.length==1){
            var selected = this.getSelectedTab(); 
            $(selected.el).hide();
        }
    }
    
    LpTabs.prototype._beforeCreate = function(){
      
        var self = this;
        $(this.tabLinks).each(function(i,tabItem){
            if(self._settings.cloneContainer){
                var tabContentId = $(tabItem).attr("href");
                var content = $(self.tab).find(tabContentId);
                var newContentId = "#"+self.prefix+'-'+$(tabItem).attr("href").replace("#","");
                if(!content.length) throw "content for tab "+$(self).attr("href").replace("#","")+" can't be found";
                $(tabItem).attr("href",newContentId);
                $(content).attr("id",newContentId.replace("#",""));
            }
         
            if(!self._settings.keepAnchor){
                $(tabItem).attr(CONTENT_KEY,$(tabItem).attr("href"));
                $(tabItem).attr("href","javascript:;");
            }    
        });
        
    /*selectionner le premier non masqué*/
    //if(!this.selectedTab){}  
    }
   

    
    
    LpTabs.prototype._bindEvents = function(){
        var self = this;
        $(this.tabLinks).bind("click",function(e){
            var currentTab = $(this).attr("data-tabno") || null;
            if(currentTab){
                self.selectTab(currentTab);
            }      
        });    
    }
   
    LpTabs.prototype.destroy = function(){
        $(this.tabLinks).unbind();
        this.tab = null;      
      
    }
    
    LpTabs.prototype.selectTab = function(tabToselect){
        var previous = $(this.selectedTab).attr("data-tabno");
        if(tabToselect==previous) return false;
       
        /*hideCurrent*/
        var currentTab = tabToselect;
        $(this.selectedTab).removeClass(this._settings.activeTabCls);
        var content =  $(this.tab).find($(this.selectedTab).attr(CONTENT_KEY)) || null;
        if(content){
            $(content).hide();
        }
        /*show new*/
        var tabToSelect = $(this.tab).find("[data-tabno='"+tabToselect+"']") || null;
        if(!tabToSelect.length) throw " can't find tab no "+tabToSelect;
        tabToSelect = tabToSelect[0];
        this.selectedTab = tabToSelect;
        this._settings.selected = previous;
       
       
        $(this.selectedTab).addClass(this._settings.activeTabCls);
        var content = $(this.tab).find($(this.selectedTab).attr(CONTENT_KEY)) || null;
        if(content){
            $(content).show();
        }
       
        /*callback:selected*/
        var ui= {
            panel:content
        };  
        this.onSelect.call(this,{
            type:"tabSelected"
        },ui);
        
        this.onShow.call(this,{
            type:"tabShow"
        },ui);
    }
    
    LpTabs.prototype.getSelectedTab = function(){
        return {
            el:$(this.selectedTab), 
            tabNo :  this._settings.selected,
            tabPanel : $($(this.selectedTab).attr(CONTENT_KEY)) 
        }; 
    }
    LpTabs.prototype.getTab = function(){
        return this.tab;
    }  
    
    LpTabs.prototype.addTab = function(){ } 
    
    this._init(userConfig);

}

bb.LpTabs = LpTabs;
