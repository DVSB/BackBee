bb.contentPluginsManager.registerPlugins("contentsetEdit",{
    init: function(){
        console.log("plugin loaded",this.name);
    /* si maxentry atteint grisé le bouton */
    },
        
    showPluginOptions : function(){
        alert("strange");
        /*display form and a select form*/
    },
    
    cmdAdd: function(){      
        var accept = this.node.getAccept();
        var maxentry = this.node.getMaxEntry();
        accept = accept.split(',');
        var contentUid = $.md5(new Date().toString());
        if(accept.length > 1){
            this.showPluginOptions();
        }
        else{
            var params = {
                content : [{
                    uid:contentUid, 
                    type: $.trim(accept[0])
                }]
            }; 
        }
        this.node.append(params);

    },
              
    canApplyOn: function(){
         return true;
    },
              
    exposeActions : function(){
        this.createAction({
            label:"add a new item to this container", 
            icoCls : "bb5-ico-add", 
            command : this.cmdAdd
        });
    }
});  