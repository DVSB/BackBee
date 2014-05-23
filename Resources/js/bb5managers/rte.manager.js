/**
 * Content Edition
 * 
 **/
(function($){
    
    /*dire à l'avance les événements qui nous intéressent */
    bb.core.registerManager("rte", {
        
        /* not implemented yet*/
        moduleConfig: {
            alias : "rte",
            dependencies: ["manager1", "adapter1"] //dependencies are other modules
        },
        
        initDefaultSettings : function(){     
            this.managerSettings = {
                adapter: "",
                contentClass: ".mainContent",
                wsName: "ws_local_config"
            }
        },
        
        /*avoir l'assurance que les dépendances seront chargées*/
        init: function(userSettings){
            userSettings = userSettings || {};
            this._settings = bb.jquery.extend({},this._settings,userSettings);
            this.rteAdapter = null;
            var self = this;
            this.rteConfig = {};
            bb.webserviceManager.getInstance(this.managerSettings.wsName).request("getRTEConfig",{
                async: false, 
                params: {},
                success: function(response){
                    var rteConfig = response.result;
                    if(!rteConfig) throw "rteconfig.yml can't be found.";
                    var adapter = ("adapter" in rteConfig.config) ? rteConfig.config.adapter : false;
                    if(!adapter) throw "rte.manager adapter can't be found";
                    self.managerSettings.adapter = adapter;
                    var adapterConfig = rteConfig[adapter];
                    if(adapterConfig == "undefined") throw "rte.manager config can't be found for "+adapter+" adapter";
                    bb.jquery.extend(self.rteConfig,rteConfig.config,adapterConfig);
                },
                error : function(e){
                    console.log("getRTEConfig sends and error");
                }
            });
            /* try to load rte config here */
            bb.require(["RteManager"], bb.jquery.proxy(this.applyRte,this));
            return this;
        },
        
        applyRte: function(RTEManager){
            try{
                this.rteAdapter = RTEManager.use(this.managerSettings.adapter);
                this.rteAdapter.onReady(this.handleRte,this);
                this.rteAdapter.onEdit(this.handleRteEdit,this); 
                this.rteAdapter.init(this.rteConfig);
            }catch(e){
                console.log(e);
            }  
        },
        
        handleRte: function(){  
            var self = this;
           
            bb.jquery(document).bind("content:ItemClicked",function(e, path, bbContent){
                if(self.isDisabled()) return;
                if(!bbContent) throw "rte.manager bbContent can't be found"; 
                self.rteAdapter.applyInlineTo(bbContent);
                return true;
            }); 
            bb.jquery(bb).trigger("bb:rteReady",{data: self.rteAdapter});
        },
        
        handleRteEdit: function(data){
            if( typeof data !=="object" ) throw "rte.manager.handleRteEdit data must be an object { node:'', newValue:'', oldValue:''}"; 
            var bbContent = $bb(data.node);
            if(bbContent.isASubContent){
                bbContent.set("value",data.newValue);
                bbContent.parentNode.updateData(); 
            } 
        },
        
        enable: function(){
            this.callSuper();
            //delay call if rte is not ready yet
            if(this.rteAdapter) this.rteAdapter.enable();
        },
        
        disable: function(){
            this.callSuper();
            if(this.rteAdapter) this.rteAdapter.disable(); 
        }
        
    });
        
})(bb.query);

/* usage losqu'on clique sur contenu 
 * Comment utilser un mediator
 * */
