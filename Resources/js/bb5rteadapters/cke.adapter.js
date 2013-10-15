/** declare a new cke adapter for bb5*/
bb.rteManager.registerAdapter("cke",{
                
    _settings: {
        inlineContentClass : ".contentAloha",
        rteClass:"",
        mainNodeClass:".mainContent"
    },
                
    onInit: function(){
        var self = this;
        this.currentContent = null;
        this.mainNodeContainer = null;
        this.mode = null;
        this.contentData = {};
        this.contentConfig = {};
        loadScript("bower_components/ckeditor-releases/ckeditor.js", function(){
            self.ckeMng = CKEDITOR;
            self.ckeMng.disableAutoInline = true;
            self.ckeMng.dtd.$editable.span = 1;
            self.ckeMng.dtd.$editable.a = 1;
            self.ckeMng.on("instanceCreated", function(){});
            self.ckeMng.on("instanceReady",function(){});
            self.trigger("onReady");  
        });
                    
        this.bindEvents();
    },
                
    loadPlugins: function(){
        console.log("inside loadPlugin");
    },
                
    _handleConfig : function(){        
        console.log("use it to hande config");
    },
                
    bindEvents: function(e){
        var self = this;
        jQuery(this._settings.contentClass).bind("click",jQuery.proxy(this.applyTo,this));
        jQuery(this._settings.contentClass).bind("blur",function(){
            console.log("please");
            console.log(self.getValue());
        });
    },
                
    applyInlineTo: function(node){
        var self = this;
        this.mainNode = $(node).parents(this._settings.mainNodeClass).eq(0); 
        var mainNodeUid = $(this.mainNode).attr("data-uid");
        console.log(mainNodeUid);
        /*load Content params via the mainnode*/
        var contentParams = this._loadContentParams($(this.mainNode).attr("data-type"));
        /* apply cke to all the the fields*/
        var params = contentParams[0];
        if(jQuery.isArray(params.editables)){
            jQuery.each(params.editables, function(i,configObject){
                jQuery.each(configObject,function(fielname,nodeConfig){
                    var node = self.mainNode.find('[data-aloha="' + fielname + '"]'); 
                    self._applyCkToNode(node,nodeConfig);
                });
            });
        }
    },
                
    _applyCkToNode: function(node,nodeConfig){
        if(!node) return;
        if($(node).hasClass("cke_editable")) return;
        $(node).attr("contenteditable",true);
        /* handle config here */
        this.ckeMng.inline($(node).get(0));
    },
               
    applyTo : function(node,mode){
        this.ckeMng.replace(node);
    }, 
                
    /* simulate content param */
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
                
    getValue : function(){
        var data = {};
        var contents = $(this.mainNode).find(this._settings.contentAloha);
    } 
});