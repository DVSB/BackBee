bb.contentPluginsManager.registerPlugins("contentsetEdit",{
    init: function(){
        console.log("plugin loaded",this.name);
    },
              
    cmdAdd: function(){
        alert("add is executed ");
    },
              
    canApplyOn: function(){
        return true;
    },
              
    exposeActions : function(){
        this.createAction({ label:"add", icoCls:"ranse", command:this.cmdAdd });
    }
});  