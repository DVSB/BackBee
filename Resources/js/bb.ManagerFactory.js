var bb = bb || {};
/* bb Module factory don't load jsCore */
var ManagerFactory = null;
define(["jscore"], function(){
    
    ManagerFactory = (function(){
        var _managers = {};
        var _managerInstances = {};
        var _instance = null;
        var _hasError = false; 
        var _try = 0; 
        /* module config is used to handle dependencies
         * and others stuffs
         * put in a global config
    /**/
        var _settings = {
            managerPath: bb.baseurl+bb.resourcesdir+"js/bb5managers/"
        };
        
        var _handleModuleConfig = function(){
        
        };
   
        var _clearDefinition = function(definition){
            var forbiddenMethods = ["initialize"];
            var i;
            for(i in forbiddenMethods){
                var methodName = forbiddenMethods[i];
                if(typeof forbiddenMethods[methodName]=="function"){
                    delete(definition[methodName]);
                }
            }
            return definition;
        } 
    
        /* Abstract manager */
        var AbstactManager = new JS.Class({
            
            initDefaultSettings: function(){
                this._settings = {
                    "dead":"dyolè",
                    "test":"sdsd"
                };
            },
            
            initialize: function(){
                this.initDefaultSettings(); //init default
                this.managerIsEnabled = false;
            },
            
            getExposedApi: function(){
                return {};
            },
            
            getMedator: function(){},
         
          
            enable: function(){
                this.managerIsEnabled = true 
            },
            
            disable: function(){
                this.managerIsEnabled = false;
            },
        
            isEnabled: function(){
                return (this.managerIsEnabled==true) ? true : false;  
            },
            
            isDisabled: function(){
                return (this.managerIsEnabled==false) ? true : false;
            },
            
            saveState: function(){},
            
            restoreState: function(){}
        }); 
        
        /* Abstract Module */
        var PublicApi = new function(){
        
            /* register manager */
            this.registerManager = function(name,definition){
                try{
                    if(typeof definition=="object"){
                        var cleanedDefinition = _clearDefinition(definition);
                        
                        var Manager = new JS.Class(AbstactManager, cleanedDefinition);
                        /* override getExposed and and the init method the api */
                        Manager.define('getPublicApi', function(){
                            /*add init - enable - disable function to the public api */
                            var init = this.method("init");
                            var enable = this.method("enable");
                            var disable = this.method("disable");
                            var api = this.getExposedApi();
                            api.init = init;
                            api.enable = enable;
                            api.disable = disable;
                            return api;
                        });
                        _managers[name] = Manager;
                    }  
                }catch(e){
                    console.log("sddsd");
                    console.log(e);
                }
                
            }
        
            
            /**
             * @params newInstance return a new instance instead of a singleton (the first object that was created)
             */
            this.getManager = function(name,newInstance){   
                var self = this;
                try{
                    var newInstance = (typeof newInstance =="boolean")? newInstance : false;
                    if(typeof _managers[name] == "function"){
                        var bbManager = new _managers[name];
                        _managerInstances[name] = bbManager.getPublicApi();
                        /* new instance */
                        if(newInstance){
                            var manager =  new _managers[name];
                            return manager.getPublicApi();
                        }
                        /* singleton -> oldInstance */
                        return _managerInstances[name];
                    } 
                    else{
                        if(_try != 0){
                            _try = 0;
                            throw "getManager: manager ["+name+"] can't be loaded!"; 
                        }
                        var adapterPath = _settings.managerPath+name+".manager.js";
                        _try++;
                        bb.Utils.ScriptLoader.loadScript({
                            scriptname: adapterPath, 
                            async: true, 
                            onError: function(e){
                                throw "error while loading";
                            }
                        });
                        return self.getManager(name);
                    }
                }catch(e){
                    console.warn(e);
                    throw e;
                    
                }
            }
        }
        return PublicApi;
    })();
    
    /* 1. Extend the bb core */
    $.extend(bb.core,ManagerFactory);
    return ManagerFactory;
});
