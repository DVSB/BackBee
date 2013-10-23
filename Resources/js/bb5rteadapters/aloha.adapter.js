/*Aloha Settings here*/
(function(window,jq) {

    if (window.Aloha === undefined || window.Aloha === null) {
        var Aloha = window.Aloha = {};
    }
    
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


bb.rteManager.registerAdapter("aloha",{
    
    onInit : function(){
        var self = this;
        this.contentNode = null;
        this.mode = null;
        this._settings = {
            plugins:"common/ui,common/format,common/table,common/list,common/link," 
        +"common/highlighteditables,common/block,common/undo,common/commands,common/paste,common/abbr,"
        +"common/image,common/contenthandler"
        };
        loadScript("bower_components/alohaeditor/aloha/lib/aloha.js", function(){     
            Aloha.ready(function(){
                self.trigger("onReady");
            });
            Aloha.bind("aloha-selection-changed",jQuery.proxy(self.onShowToolbar,self));
        },{
            "data-aloha-plugins" : self._settings.plugins
        });        
    },
    
    applyInlineTo : function(node){
        this.mode = "inline"; 
        this.mainNode = node;
        Aloha.jQuery(node).aloha();
    },
    
      applyToTextarea : function(){
        this.mode = "textarea";
    },
    
    /* prendre en compte le mode*/
    onShowToolbar : function(){
        if(this.mode != "inline") return;
        $(".aloha-ui.aloha-ui-toolbar").css({
            "position":"absolute",
            "top": "0px",
            "left":"0px"
        });
        
        $(".aloha-toolbar").appendTo("#menu-container"); 
    }
   
    
});
