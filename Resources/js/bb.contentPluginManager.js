var bb = bb || {}; 
bb.contentPluginsManager = (function(){
    var pluginsContainer = {}; 
    var pluginsInstanceContainer = {}; 
    var _settings = {
        pluginPath: "/js/bb5contentplugins/",
        actionContainerClass: ".bb5-content-actions",
        pluginActionCls: "plugin-action"

    }; 
    var cache = {};
    var _selectedContent = null;
       
    /* move to an other class */
    var _watchContent = function(){
        $(document).bind("content:ItemClicked",function(e,data,bbContent){
            
            /* remove previous action here */
            if(_selectedContent == bbContent) return false;
            _selectedContent = bbContent;
            var contentActions = _getPluginActions(_selectedContent);
            if(jQuery.isArray(contentActions)){
                var cm = bb.ManagersContainer.getInstance().getManager("ContentEditionManager");
                if(cm){
                    cm.handlePluginsActions(contentActions); 
                }
                /*var actionBtn = _buildPluginsButtons(contentActions);
                console.log(actionBtn);
                $(".bb5-content-actions").prepend($(actionBtn)); //handle condition on btns*/
                _selectedContent = null;
            }
        }); 
    }
    
    var _cleanpluginsActions = function(){
        $(_settings.pluginActionCls).remove(); 
    } 
    
    var _buildPluginsButtons = function(actions){
        var actionsFrag = document.createDocumentFragment();
        jQuery.each(actions,function(i,actionInfos){
            var btnClass = "bb5-button #cls# bb5-button-square bb5-invert";
            btnClass = btnClass.replace("#cls#",actionInfos.icoCls);
            var btn = $("<button/>").clone();
            $(btn).attr("title",actionInfos.label);
            $(btn).bind('click',actionInfos.command.execute);
            $(btn).addClass(btnClass);
            $(btn).addClass(_settings.pluginActionCls);
            actionsFrag.appendChild($(btn).get(0)); 
        });
        return actionsFrag;
    }
    
    var _getPluginActions = function(content){
        var contentPlugins = content.get("contentplugins");
        if(!(typeof contentPlugins=="string")) return;
        contentPlugins = contentPlugins.split(",");
        if(!jQuery.isArray(contentPlugins))return;
        var actions = []; 
        jQuery.each(contentPlugins,function(i,pluginName){
            bb.Utils.ScriptLoader.loadScript({
                scriptname:_settings.pluginPath+pluginName+".plugin.js"
            });
            try{
                var plugin = bb.contentPluginsManager.getInstanceByName(pluginName); 
                if(typeof plugin =="object"){
                    var pluginsActions = plugin.setNode(content).getActions();
                    if($.isPlainObject(pluginsActions)){
                        jQuery.each(pluginsActions, function(i,pluginAction){
                            actions.push(pluginAction);
                        });
                    }
                }
            }catch(e){
                console.log(e);
            }
        });
        /* instance */
        return actions; 
        
    } 
    
    var _
    /* Abstract and default method */
    var _checkParameter = function(actionParams){
        var isValid = true;
        if(typeof actionParams.label   !== "string") isValid = false;
        if(typeof actionParams.icoCls  !== "string") isValid = false;
        if(typeof actionParams.command !== "function") isValid = false;
        return isValid;
    }
    
    /*apply plugins to contents here*/

    var Command = function Command(settings){
        this.init = function(){
            if(typeof settings.execute=="function"){
                this.execute = settings.execute;
            }
        } 
        this.init(settings); 
    }
    
    /* plugins abstract */
    var PluginAbstract = {
                   
        /*onCreate enable us to declare default property */
        onCreate: function(){
            this.name = null;
            this.state = {
                isEnabled: true
            };
            this.actions = {}; 
            this.node = null;
        },
                    
        init : function(){
            console.log("override this method");
        },
                    
        exposeActions: function(){
            return false;
        },
        
        canApplyOn:function(node){
            alert("in abstract");
            return true;
        },
                    
        isEnabled: function(){
            return  this.state.isEnabled; 
        },
                    
        setNode: function(node){
            this.node = node;
            return this;
        },
                    
        getActions: function(){
            if(!this.canApplyOn(this.node)) return;
            return this.actions;
        },
                    
        /* command condition: hided || disabled */
        createAction: function(actionParams){
            var isValid = _checkParameter(actionParams); 
            if(!isValid) return false;
            var actionLabel = this.name+"_"+actionParams.label; 
            actionParams.command = new Command({
                "execute":$.proxy(actionParams.command,this)
            });
            this.actions[actionLabel] = actionParams;
        },
                    
        enable: function(){
            this.state.isEnabled = true;
        },
                    
        disable: function(){
            this.state.isEnabled = false;
        } 
    };
                
                
    var _registerPlugins = function(pluginsName,config){
        /*create proxy for get Action */
        var getActionProxy = function(){
            if(!this.canApplyOn(this.node)) return;
            if(("getActions" in config) && typeof config["getActions"]=="function"){
                config.getActions.call(this);
            }
        }
        /* handle methods */
        var methods = {};
        var properties = {
            name:pluginsName
        };
        for(var prop in config){
            if(typeof config[prop]=="function"){
                /* use proxy */
                var method = config[prop];
                /* handle special methods */
                if( prop == "getActions"){
                    method = getActionProxy;    
                }
                methods[prop] = method;     
            }  
                        
            if(typeof config[prop] != "function"){
                properties[prop] = config[prop]; 
            }
        }
        /*  handle properties */
                  
                   
        /*constructor*/
        var _mock = function Plugin(){
            this.onCreate();
            $.extend(true,this,properties);
            this.init();
            this.exposeActions();
        }
        /* remove protectedMethod like[ getActions - onCreate ]*/
        $.extend(true,_mock.prototype,PluginAbstract,methods);
        /* save plugin ref */
        pluginsContainer[pluginsName] = _mock;
    }
        
    var _getPlugin = function(name){
        try{
            var pluginsInstance = pluginsInstanceContainer[name] || false;
            if(!pluginsInstance){
                if(typeof pluginsContainer[name]!= "undefined"){
                    pluginsInstance = new pluginsContainer[name];
                    pluginsInstanceContainer[name] = pluginsInstance;
                }
            }
            if(!pluginsInstance) throw new Error("NoPluginError : plugins "+name+" can't be found");
        }catch(e){
            console.log(e);
            throw e;
        }
        
        return pluginsInstance;
    }
                
    var _getAllPlugins = function(){
        return pluginsContainer;
    }
                
    /* API */
    return {
        registerPlugins : _registerPlugins,
        getInstanceByName: _getPlugin,
        getPlugins: _getAllPlugins,
        watchContents : _watchContent
    }
})(window);



/**
 *A simple object to apply 
 *
 **/
var ContentsManager = function(){
    this.init = function(){
        this.contentPlugins = {};  
        this.contents = [];
        this.bindEvents();
        this.selected = false;           
    }
                
    /*what to do when a content is selected*/
    this.bindEvents = function(){   
        var self = this;
        var contentClass = ".bb5-content";
        $(contentClass).bind("click", function(){
            if(self.selected == content) return;
            self.selected = content;
            var content = self.contents[0];                        
            var actions = self.initalizePlugins(content);
            var actionBtn = self.buildActionButton(actions); //éviter
            content.showActions(actionBtn);
        });
    }
                
    this.initalizePlugins = function(content){
        var actions = []; 
        var content = content || null;
        if(!content) return false;
        for(var pluginName in bb.contentPluginsManager.getPlugins()){
            var plugin = bb.contentPluginsManager.getInstanceByName(pluginName);
            var pluginActions = plugin.setNode(content).getActions();
            if((pluginActions) && $.isPlainObject(pluginActions)){
                for(var action in pluginActions){
                    actions.push(pluginActions[action]);
                };    
            }
                        
        }
        return actions;
    }
                 
    this.buildActionButton = function(actions){
        var actionsFrag = document.createDocumentFragment();
        for(var key in actions){ 
            var actionInfos = actions[key];
            var btn = $('<button class="action">action 1</button>').clone();
            $(btn).text(actionInfos.label);
            $(btn).bind('click',actionInfos.command.execute);
            actionsFrag.appendChild($(btn).get(0));
        }
        return actionsFrag;
    }
    this.init();
}
$(function(){
    bb.contentPluginsManager.watchContents();
});

/**/