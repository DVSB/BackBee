var bb = bb || {};  

var module = {
    dependencies: ["jscore","aintnolove"],
    moduleName: "RteManager",
    exports: {}
};

define(["jscore"], function(){
    
    return (function(global){
        //console.log(JS);
        bb.RteAbstractAdapter = new JS.Class({  
            /**
         * is called when ever The abstractClass is subclassed
         * not working
         * method de class
         **/
            initialize: function(){
                this._callbacks = {
                    "onReady" : function(){},
                    "onEdit": function(){}
                };
                this.settings = {};
                this.isEnabled = false;
                this.isLoaded = false;
                this.contentConfig = {}; //key - config 
                this.onCreate();
            },
            
            /* this method should be load automatically*/
            onCreate : function(){
                this.loadPlugins();
            }, 
                   
            onInit: function(){
                console.log("OnInit must be overrided");
            },
        
            /*called when the toolbar is shown */
            onShowToolbar : function(){},
        
            init : function(){
                this.onInit();  
            },
        
            trigger : function(stage,data){
                var data = (typeof data == "object") ? data : {};
                if(typeof this._callbacks[stage]=="function"){
                    this._callbacks[stage](data);
                    if(stage=="onReady") this.isLoaded = true; 
                }
            },
            
            /* ne proposer*/
            onReady : function(callback,context){
                if(typeof callback!="function"){
                    throw new Error("bb.RteAbstractAdapter onReady must be a function");
                }
                this._callbacks["onReady"] = (typeof context=="object") ? jQuery.proxy(callback,context) : jQuery.proxy(callback,this);
            },
            
            onEdit: function(callback,context){
                if(typeof callback!="function"){
                    throw new Error("bb.RteAbstractAdapter onReady must be a function");
                }
                this._callbacks["onEdit"] = (typeof context=="object") ? jQuery.proxy(callback,context) : jQuery.proxy(callback,this);
            },
        
            loadPlugins: function(config){
                console.log("execute loadPlugins");
            },
            
            /* load rte save content */
            loadContentParams : function(contentId,contentType){
                
            },

            enable: function(){
                this.isEnabled = true 
            },
            
            disable: function(){
                this.isDisabled = false;
            },
        
            applyInlineTo: function(){
                console.log("method:applyInlineTo must be overrided");
            },
        
            applyTo: function(node){
                console.log("method:applyTo must be overrided");
            }, //mode inline/textarea
        
            getValue: function(){
                console.log("method:getValue must be overrided");
            }
        });
 

        global.RteManager = (function(global){
            /* adapter hash */
            var _adapters = {};
            var _settings = {
                adapterPath: "js/bb5rteadapters/"
            };
     
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
                
            var PublicApi = function(){
                    
                this.use = function(adapterName){
                    var self = this;
                    if(adapterName in _adapters){
                        try{
                            var adapter = new _adapters[adapterName];
                            return adapter;  
                        }catch(e){
                            throw e;
                        }
                   
                    }else{
                        /* load adapter */
                        $.ajax({
                            url: _settings.adapterPath+adapterName+".adapter.js", 
                            async : false
                        })
                        .fail(function(e){
                            throw "NotFounRTEAdapter ["+adapterName+"]";
                        });
                        return self.use(adapterName);
                    }
                }
                       
                this.registerAdapter = function(key,definition){
                    _adapters[key] = _createAdapter(definition); //create a constructor
                }
            }
                
            return this.init();
        })(global);             
        return global.RteManager;   
    })(bb); 
});

