bb.contentPluginsManager.registerPlugins("CutAndPastePlugin",{
                
    init: function(){
        console.log("plugin loaded",this.name);
    },
                
    canApplyOn: function(){
        return this.node.type=="contentSet";
    },
   
    cmdCut: function(){
        alert("cut is executed");
    },
                
    cmdPaste: function(){
        bb.jquery(this.node).append("radical blaze");
        alert("paste is executed");
    },
   
    exposeActions : function(){
        this.createAction({
            label:"cut", 
            icoCls:"tara", 
            command: this.cmdCut
        });
        this.createAction({
            label:"paste", 
            icoCls:"strage", 
            command: this.cmdPaste
        });
    }
});