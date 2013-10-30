/*Aloha Settings here*/
(function(global,jq,undefined) {
    
    if (global.Aloha === undefined || global.Aloha === null) {
        var Aloha = global.Aloha = {};
    }
    
    global.Aloha.settings = {
        baseUrl : "js/libs/alohaeditor/aloha/lib",
        toolbar: {
            floating : false,
            pin: false,
            draggable: false
        },
        bundles: {
            // Path for custom bundle relative from Aloha.settings.baseUrl usually path of aloha.js
            cmsplugin: '../../../aloha-plugins'
        },
        plugins :{
            "format": {
                // all elements with no specific configuration get this configuration
                config : [ 'b', 'i','sub','sup'],
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

bb.RteManager.registerAdapter("aloha",{
    
    onInit : function(){
        var self = this;
        this.contentNode = null;
        this.mode = null;
        this.editables = [];
        this._settings = {
            plugins: "common/ui,common/format,common/table,common/list,common/link," 
        +"common/highlighteditables,common/block,common/undo,common/commands,common/paste,common/abbr,"
        +"common/image,common/contenthandler"
        };
        
        loadScript("js/libs/alohaeditor/aloha/lib/aloha-full.js", function(){
            Aloha.ready(function(){
                self.trigger("onReady");
                /*show toolbar*/
                Aloha.bind("aloha-editable-activated",jQuery.proxy(self.onShowToolbar,self));
                /* update bbcontent only if content has changed */
                Aloha.bind("aloha-editable-deactivated",jQuery.proxy(self.handleContentEdition,self));
            });
        },{
            "data-aloha-plugins" : self._settings.plugins
        });        
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
    
    handleContentChange : function(){
    //console.log(arguments);
    },
    
    applyInlineTo : function(node){
        this.mode = "inline"; 
        this.mainNode = node;
        /*find all rte enabled contents*/
        var self = this;
        /*load Content params via the mainnode. keep track of the*/
        var editables = this.loadNodeRteParams($(this.mainNode).attr("data-type"));//sync call
        /* apply aloha to all the the fields*/
        if(jQuery.isArray(editables)){
            jQuery.each(editables, function(i,configObject){
                jQuery.each(configObject,function(fieldname,nodeConfig){
                    var node = self.mainNode.find('[data-aloha="' + fieldname + '"]').eq(0); 
                    var editableNode = $(node).get(0); 
                    if(editableNode && !Aloha.isEditable(editableNode)){
                        Aloha.jQuery(editableNode).aloha();
                        self.editables.push(editableNode);
                        self._setNodeParams(node,nodeConfig); //save params for node
                    }
                });
            });
        }
    },
    
    _setNodeParams: function(node,params){
        if(node && typeof jQuery.isArray(params)){
            var id = "#"+$(node).attr("id");
            Aloha.settings.plugins.format.editables[id] = params;  
        }
    },
    
    applyToTextarea : function(){
        this.mode = "textarea";
    },
      
    /* prendre en compte le mode*/
    onShowToolbar : function(){
        $("#aloha").css({
            position:"relative"
        });
        if(this.mode != "inline") return;
        $(".aloha-ui.aloha-ui-toolbar").css({
            width: "490px",
            position: "absolute",
            top: "0px"
        });
        $(".aloha-toolbar").appendTo("#aloha"); 
    },
    
    _loadContentParams : function(contentType){
        if(!contentType) throw "_loadContentparams:rteAdapter";
        var config = [{
            "name":"text",
            uid:"",
            editables:[
            {
                title:["b","i"]
            },

            {
                content:["b","i"]
            },

            {
                headline:["b","i"]
            },

            {
                footer:["b","i"]
            }
            ]
        },                      
        ];
        return config;
    },
    
    enable: function(){
        this.callSuper();
    },
    
    disable: function(){
        this.callSuper();
        if(!this.editables.length) return;
        Aloha.jQuery(this.editables).mahalo();
    /*jQuery.each(this.editables,function(i,node){
            console.log(Aloha.jQuery(node).attr("id"));
            console.log(Aloha.getEditableById(Aloha.jQuery(node).attr("id")));
        });*/
    }
      
});