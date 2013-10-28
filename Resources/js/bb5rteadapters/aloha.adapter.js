/*Aloha Settings here*/
(function(global,jq) {
    
    if (global.Aloha === undefined || global.Aloha === null) {
        var Aloha = global.Aloha = {};
    }
    Aloha = {};
    Aloha.settings = {
        baseUrl : "bower_components/alohaeditor/aloha/lib",
        toolbar: {
            floating : false
        },
        bundles: {
            // Path for custom bundle relative from Aloha.settings.baseUrl usually path of aloha.js
            cmsplugin: '../../../aloha-plugins'
        }
    };
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
        this._settings = {
            plugins: "common/ui,common/format,common/table,common/list,common/link," 
        +"common/highlighteditables,common/block,common/undo,common/commands,common/paste,common/abbr,"
        +"common/image,common/contenthandler"
        };
        
        loadScript("js/libs/alohaeditor/aloha/lib/aloha-full.min.js", function(){
            Aloha.ready(function(){
                self.trigger("onReady");
            });
            
            /*show toolbar*/
            Aloha.bind("aloha-editable-activated",jQuery.proxy(self.onShowToolbar,self));
            /* update bbcontent only if content has changed */
            Aloha.bind("aloha-editable-deactivated",jQuery.proxy(self.handleContentEdition,self));
            /* save content when Aloha is deactivated */
            //Aloha.bind("aloha-editable-deactivated",jQuery.proxy(self.handleContentChange,self));
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
        var contentParams = this._loadContentParams($(this.mainNode).attr("data-type"));
        /* apply aloha to all the the fields*/
        var params = contentParams[0];
        if(jQuery.isArray(params.editables)){
            jQuery.each(params.editables, function(i,configObject){
                jQuery.each(configObject,function(fieldname,nodeConfig){
                    var node = self.mainNode.find('[data-aloha="' + fieldname + '"]').eq(0); 
                    if(node){
                        Aloha.jQuery(node).aloha();
                    }
                });
            });
        }
    },
    
    applyToTextarea : function(){
        this.mode = "textarea";
    },
    
    
    
    /* prendre en compte le mode*/
    onShowToolbar : function(){
        if(this.mode != "inline") return;
        $(".aloha-ui.aloha-ui-toolbar").css({
            width: "490px"
        });
        $(".aloha-toolbar").appendTo("#bb5-edit-tabs-data"); 
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
    }
});