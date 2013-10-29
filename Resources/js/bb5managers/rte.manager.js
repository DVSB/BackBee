/**
 * Content Edition
 * 
 **/
(function($){
    
    /*dire à l'avance les événements qui nous intéressent */
    bb.core.registerManager("rte", {
        
        moduleConfig: {
            alias : "rte",
            dependencies: ["manager1", "adapter1"] //les dépendances seront chargées et disponibles dans bb.core
        },
        
        initDefaultSettings : function(){     
            this.managerSettings = {
                rte: "aloha",
                contentClass: ".mainContent"
            }
        },
        
        /*pour avoir les params utiliser*/
        init: function(userSettings){
            var userSettings = userSettings || {};
            this._settings = $.extend({},this._settings,userSettings);
            this.rte = null;
            bb.require(["RteManager"],$.proxy(this.applyRte,this));
            return this;
        },
        
        applyRte: function(RTEManager){
            // if(!this.isEnabled) return false;
            var rteSettings = {
                mainNodeClass: ".mainContent", 
                inlineContentClass: ".contentAloha", 
                editContentClass: "", 
                fieldPrefix: "data-aloha"
            };
            try{
                this.rte = RTEManager.use(this.managerSettings.rte,rteSettings);
                this.rte.onReady(this.handleRte,this);
                this.rte.onEdit(this.handleRteEdit,this); 
                this.rte.init();
            }catch(e){
                console.log(e);
            }
           
        },
        
        handleRte: function(){  
            var self = this;
            $(document).bind("content:ItemClicked",function(e, path, bbContent){
                if(self.isDisabled()) return;
                if(!bbContent) throw "rte.manager bbContent can't be found"; 
                self.rte.applyInlineTo(bbContent.contentEl);
                return true;
            });                
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
            if(this.rte) this.rte.enable();
        },
        
        disable: function(){
           this.callSuper();
           this.rte.disable(); 
        }
        
    });
        
})(jQuery);

/* usage losqu'on clique sur contenu 
 * Comment utilser un mediator
 * */
