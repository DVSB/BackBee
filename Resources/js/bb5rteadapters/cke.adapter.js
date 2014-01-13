/** declaring ck adapter*/
var compteur = (function(){
    var compteur = 0;
    return function(){
        return "compteur: "+compteur++;
    }
})()
bb.RteManager.registerAdapter("cke",{
                
    onInit: function(){
        var self = this;
        this.currentContent = null;
        this.mainNodeContainer = null;
        this.mode = null;
        this.editables = [];
        CKEDITOR_BASEPATH = bb.baseurl+bb.resourcesdir+"js/libs/ckeditor-releases/"; 
        bb.jquery.get(CKEDITOR_BASEPATH+"ckeditor.js", function(){
            self.ckeMng = CKEDITOR;
            self.ckeMng.disableAutoInline = true;
            self.ckeMng.dtd.$editable.span = 1;
            self.ckeMng.dtd.$editable.a = 1;
            
            self.ckeMng.on("currentInstance",function(){
                var editor = CKEDITOR.currentInstance;
                if(editor){
                    self.onShowToolbar({
                        editor:editor
                    })
                }
            });
            self.trigger("onReady");  
        });                    
    },
                
    loadPlugins: function(){
        console.log("inside loadPlugin");
    },
                
    _handleConfig : function(){        
        console.log("use it to hande config");
    },
    
    applyInlineTo: function(node){
        var self = this;
        this.mainNode = node; 
        this.mode = "inline"; 
        /*load Content params via the mainnode*/
        var editables = this.loadNodesRteParams(bb.jquery(this.mainNode).attr("data-type"));
        /* apply cke to all the the fields*/
        if(bb.jquery.isArray(editables)){
            bb.jquery.each(editables, function(i,configObject){
                bb.jquery.each(configObject,function(fieldname,nodeConfig){
                    var node = self.mainNode.find('[data-aloha="' + fieldname + '"]').eq(0);
                    if(bb.jquery(node).hasClass("cke_editable_inline")) return;
                    /* if node ha an editor */
                    /* handle config here */
                    var element = CKEDITOR.dom.element.get( bb.jquery(node).get(0) );
                    if(!element || element.getEditor()) return;
                    var editableNode = bb.jquery(node).get(0); 
                    self._applyCkToNode(editableNode,nodeConfig);
                });
            });
        }
    },
    onShowToolbar: function(e){
        var mode = this.mode;
        bb.jquery(".cke").hide();
        setTimeout(function(){
            var editor = e.editor;
            var name = editor.name;
            if(!editor) return;
            bb.jquery("#aloha").css({
                position:"relative"
            });
            /**reset**/
            bb.jquery("#cke_"+name).removeAttr('style')
            bb.jquery("#cke_"+name).removeClass("cke_float");
            bb.jquery("#cke_"+name).css({
                position: "absolute",
                width: "490px",
                top: "0px",
                left: "0px",
                height: "84px",
                border: "none"
            }).appendTo("#aloha");
            bb.jquery("#cke_"+name).css("visibility","visible"); 
        },150);       
    },
    
    _hideEditor: function(editor){
        var editorName = editor.name;
        if(editorName){
            bb.jquery("#cke_"+editorName).hide();
        }
    },
    
    handleContentEdition : function(event){
        this._hideEditor(event.editor);
        /* hide editor */
        var editor = event.editor;
        if(!editor || !editor.checkDirty()) return false;
        var editedContent = editor.getData();
        var editedData = {
            node : editor.element.$,
            newValue: editedContent,
            oldValue: ""
        };
        this.trigger("onEdit",editedData); 
    },
    
    _applyCkToNode: function(node,nodeConfig){
        if(!node) return;
        if(bb.jquery(node).hasClass("cke_editable")) return;
        bb.jquery(node).attr("contenteditable",true);
        var editor = this.ckeMng.inline(bb.jquery(node).get(0));
        if(editor){
            this.editables.push(editor);
            editor.on("blur",bb.jquery.proxy(this.handleContentEdition,this));
            editor.on("focus",bb.jquery.proxy(this.onShowToolbar,this));
        }
    },
               
    applyTo : function(node,mode){
        this.ckeMng.replace(node);
    },
    
    disable: function(){
        this.callSuper();
        if(this.editables.length){
            bb.jquery.each(this.editables,function(i,editor){
                editor.destroy();
            }); 
        }
        
    }
})