/*Aloha Settings here*/
(function(global,jq) {
    
    if (global.Aloha === undefined || global.Aloha === null) {
        var Aloha = global.Aloha = {};
    }
    
    global.Aloha.settings = {
        baseUrl : bb.baseurl+bb.resourcesdir+"js/libs/alohaeditor/aloha/lib",
        toolbar: {
            floating : false,
            pin: false,
            draggable: false
        },
        bundles: {
            // Path for custom bundle relative from Aloha.settings.baseUrl usually path of aloha.js
            bbplugin: bb.baseurl+bb.resourcesdir+"js/bb5rteadapters/plugins/aloha"
        },
        
        plugins : {
            "format": {
                config : [],
                editables: {}
            }
        }
    }
})(window,$);

/* loadScript */
function loadScript(url, callback, config){
    var script = document.createElement("script");
    script.type = "text/javascript";
    if(typeof config =="object"){
        for (key in config){
            var value = config[key];
            script.setAttribute(key, value);
        }
    }
    if (script.readyState){  //IE
        script.onreadystatechange = function(){
            if (script.readyState == "loaded" ||
                script.readyState == "complete"){
                script.onreadystatechange = null;
                callback();
            }
        };
    } else {  //Others
        script.onload = function(){
            callback();
        };
    }
    script.src = url;
    document.getElementsByTagName("head")[0].appendChild(script);
}

/* declaring aloha adapter */
bb.RteManager.registerAdapter("aloha",{
    
    onInit : function(rteConfig){
        var self = this;
        this.contentNode = null;
        this.mode = null;
        this.editables = [];
        this.rteConfig = rteConfig;
        this.confMap = (("customconf" in rteConfig) && rteConfig.customconf) ? rteConfig.customconf : {};
        /*handle plugins conf here*/
        var availablePlugins = this._getAvailablePlugins(rteConfig);
        bb.jquery.extend(true,Aloha.settings,rteConfig.settings);
        loadScript(bb.baseurl+bb.resourcesdir+"js/libs/alohaeditor-0.25.3/aloha/lib/aloha-full.js", function(){
            Aloha.ready(function(){
                self.trigger("onReady");
                /*show toolbar*/
                Aloha.bind("aloha-editable-activated",bb.jquery.proxy(self.onShowToolbar,self));
                /* update bbcontent only if content has changed */
                Aloha.bind("aloha-editable-deactivated",bb.jquery.proxy(self.handleContentEdition,self));
            });
        },{
            "data-aloha-plugins" : availablePlugins
        });        
    },
    
    _getAvailablePlugins: function(rteConf){
        var customConf = rteConf.customconf;
        if(!customConf) return false;
        var pluginsInfos = "";
        var plugins = ["common/ui"];
        bb.jquery.each(customConf,function(confName,conf){
            if(conf.plugins && bb.jquery.isArray(conf.plugins)){
                bb.jquery.merge(plugins,conf.plugins);
            }
        });
        
        if(bb.jquery.isArray(plugins)){
            var pluginsList = plugins.filter( function(pluginName){
                if(!this[pluginName]){
                    this[pluginName] = true;
                    return pluginName;
                }
            });
            pluginsInfos =  pluginsList.join(", ");
        }
        return pluginsInfos;
    },
    
    handleContentEdition: function(e,alohaParams){
        /*filter events*/
        var editable = alohaParams.editable;
        if(!editable.isModified()) return false;
        /*trigger change only id content has been modified*/
        var editedContent = editable.getContents();
        var originalContent = editable.originalContent;
        /* notify content edition */
        var editedData = {
            node: alohaParams.editable.obj,
            newValue: editedContent,
            oldValue: originalContent
        }; 
        this.trigger("onEdit",editedData);
    },
    
    applyInlineTo : function(node){
        this.mode = "inline"; 
        this.mainNode = node;
        /*find all rte enabled contents*/
        var self = this;
        
        /*load Content params via the mainnode. this should not be needed.*/
       
        //var editables = this.loadNodesRteParams(bb.jquery(this.mainNode).attr("data-type"));//sync call
        var editables = node.getSubContents(); 
        var fieldPrefix = this._settings.fieldPrefix;
        if(!fieldPrefix || typeof fieldPrefix!="string") throw "aloha.adapter fieldPrefix must be a string";
        /* apply aloha to all the fields in the selected block*/
        if(bb.jquery.isArray(editables)){
            bb.jquery.each(editables, function(i,contentEl){
                var editableNode = bb.jquery(contentEl).get(0);
                if(Aloha.isEditable(editableNode)) return true;           
                var elementname = (typeof $bb(contentEl).get("element") == "string") ? $bb(contentEl).get("element") : false;
                var rteConf = ($bb(contentEl).get("rteconf")!=-1) ? $bb(contentEl).get("rteconf") : false;
                if( !elementname || !rteConf ) return true;
                var editableConf = (("customconf" in self.rteConfig) && self.rteConfig.customconf) ? self.rteConfig.customconf[rteConf]: false;
                if(!bb.jquery.isPlainObject(editableConf)) return true;
                /* extend default conf with editableConf*/
                var pluginsSettings = bb.jquery.extend(true,{},Aloha.settings.plugins);
                editableConf = bb.jquery.extend({},pluginsSettings,editableConf.pluginsconf); 
                /*prendre en compte les plugins desactivés ??*/
                Aloha.jQuery(editableNode).aloha();
                self.editables.push(editableNode);
                self._handleEditablePluginsconf(contentEl,editableConf);
            });
        }
    },
       
    /* Apply the plugin */
    _handleEditablePluginsconf : function(editable, nodeConfig){
        /*pour chaque plugin associer un editable à la conf*/
        var pluginsconf = (nodeConfig) ? nodeConfig : false;
        if(!pluginsconf) return;
        bb.jquery.each(pluginsconf, function(pluginName,pluginConf){
            /* make sure plugin is in aloha plugins list */
            
            if(!(pluginName in Aloha.settings.plugins)){
                Aloha.settings.plugins[pluginName] = {
                    "config" : [], 
                    "editables" : {}
                };
            }
            
            /* add config key if needed */
            if(!("config" in Aloha.settings.plugins[pluginName])){
                Aloha.settings.plugins[pluginName]["config"] = [];
            }
       
            /* add editables key if needed */
            if(!("editables" in Aloha.settings.plugins[pluginName]) || !bb.jquery.isPlainObject(Aloha.settings.plugins[pluginName]["editables"])){
                Aloha.settings.plugins[pluginName]["editables"] = {};
            }
            
            if(editable){
                var id = "#"+bb.jquery(editable).attr("id");
                if(!("config" in pluginConf)){
                    console.warn("'config' key can't be found for plugin"+pluginName);
                    return true;
                }
                var editablesInfos = Aloha.settings.plugins[pluginName]["editables"];
                editablesInfos[id] =  (pluginConf.config) ? pluginConf.config : [];
                Aloha.settings.plugins[pluginName]["editables"] = editablesInfos;
            }
        });        
    },
    
    /* apply param to node */
    _handleEditableStyles: function(node,nodeConfig){
        var nodeStyle = (nodeConfig.styles) ? nodeConfig.styles : [];
        if(node && bb.jquery.isArray(nodeStyle)){
            var id = "#"+bb.jquery(node).attr("id");
            Aloha.settings.plugins.format.editables[id] = nodeStyle;
        }
    },
    
    applyToTextarea : function(){
        this.mode = "textarea";
    },
      
    /* prendre en compte le mode*/
    onShowToolbar : function(){        
        if(this.mode != "inline") return;
        bb.jquery("#aloha").css({
            position:"relative"
        });
        bb.jquery(".aloha-ui.aloha-ui-toolbar").css({
            width: "490px",
            position: "absolute",
            top: "0px"
        });  
        bb.jquery(".aloha-multisplit-content").css("zIndex",1000);
        bb.jquery(".aloha-toolbar").appendTo("#aloha"); 
        
        /*... fixe size ... */
        $("#aloha").find("#tab-ui-container-3")
    },
    
    enable: function(){
        this.callSuper();
    },
    
    disable: function(){
        this.callSuper();
        if(!this.editables.length) return;
        $(".aloha-editable-highlight").removeClass("aloha-editable-highlight");
        Aloha.jQuery(this.editables).mahalo();
        this.editables = []; 
    }
      
});