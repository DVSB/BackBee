var bb = bb || {};  

(function(global){
    
    bb.RteAbstractAdapter = new JS.Class({  
        
        initialize: function(){
            this._callbacks = {
                "onReady" : function(){}
            };
            this.settings = {};
            this.isEnabled = false;
            this.isLoaded = false;
            this.onCreate();
        },
            
        /* this method should be load automatically*/
        onCreate : function(){
            this.loadPlugins();
            console.log("onCreate is called");
        }, 
                   
        onInit: function(){
            console.log("execute onInit");
        },
        
        init : function(){
            this.onInit();  
        },
        
        trigger : function(stage){
            if(typeof this._callbacks[stage]=="function"){
                this._callbacks[stage]();
                if(stage=="onReady") this.isLoaded = false;
            }
        },
        
        onReady : function(callback,context){
            if(typeof callback!="function"){
                throw new Error("bb.RteAbstractAdapter onReady must be a function");
            }
            this._callbacks["onReady"] = (typeof context=="object") ? jQuery.proxy(callback,context) : jQuery.proxy(callback,this);
        },
        
        loadPlugins: function(config){
            console.log("execute loadPlugins");
        },
        
        enable: function(){
            this.isEnabled = true 
        },
        
        disable: function(){
            this.isDisabled = false;
        },
        
        applyTo: function(){},
        getValue: function(){}
    });
    
    /* use an AbstractClass for every Manager */
    bb.rteManager = (function(){
        /* adapter hash */
        var _adapters = {};
                
                
        /* clean class Definition */
        var _cleanClassDefinition = function(definition){
            /* remove protected methods */
            var  protectedMethods = ["initialize","onCreate","init"];
            for(var i in protectedMethods){
                var methodName = protectedMethods[i];
                if(typeof definition[methodName] == "function"){
                    delete(definition[methodName]);
                }
            }
            return definition;
        }
                
        /* create adapter */
        var _createAdapter = function(adapterDefinition){
            var adapterDefinition =  _cleanClassDefinition(adapterDefinition);
            var RteAdapter = new JS.Class(bb.RteAbstractAdapter,adapterDefinition);
            return RteAdapter;
        }
                
        this.init = function(){
            return  new PublicApi;
        } 
        
        /*public API */
        var PublicApi = function(){
            

            /* load rte def then create the instance */
            this.use = function(key){
                if(key in _adapters){
                    var adapter = new _adapters[key];
                    return adapter;
                }else{
                    throw "NotFoundAdapter ["+key+"]";
                }
            }
                       
            this.registerAdapter = function(key,definition){
                _adapters[key] = _createAdapter(definition); //create a constructor
            }
        }
                
        return this.init();
    })(window);             
          
})(window);

