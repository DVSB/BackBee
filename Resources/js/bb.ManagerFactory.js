var bb = bb || {};
bb.core = bb.core || {};


/* bb Module factory don't load jsCore */
var ManagerFactory = null;
define(["jscore"], function(){
    
    ManagerFactory = (function(){
        var _managers = {};
        var _managerInstances = {};
        var _instance = null;
        /* module config is used to handle dependencies
         * and others stuffs
         * put in a global config
    /**/
        var _settings = {
            managerPath: "js/bb5managers/"
        };
        
        var _handleModuleConfig = function(){
        
        }
    
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
            initialize: function(params){
                console.log("inside abstract");
            },
            getExposedApi: function(){},
            enable: function(){},
            disable: function(){},
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
                        var initFunc = {
                            init : definition["init"]
                        };
                    
                        var Manager = new JS.Class(AbstactManager, cleanedDefinition);
                    
                        /* override getExposed */
                        Manager.define('getPublicApi', function(){
                            var api = this.getExposedApi();
                            jQuery.extend(api,initFunc);
                            console.log("ap",api);
                            return api;
                        });
                        /* save Manager */
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
                var newInstance = (typeof newInstance =="boolean")? newInstance : false;
                try{
                    if(typeof _managers[name] == "function"){
                        var bbManager = new _managers[name];
                        _managerInstances[name] = bbManager.getPublicApi();
                        /* new instance */
                        if(newInstance){
                            return bbManager.getPublicApi();
                        }
                        /* singleton -> oldInstance */
                        return _managerInstances[name];
                    } 
                    else{
                        var adapterPath = _settings.managerPath+name+".manager.js";
                        /*sync */
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
