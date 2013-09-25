/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/*
 *Simple container for Managers
 *
 **/
/**
 * init->
 *
 */
bb.ManagersContainer = (function(){
    
    var _instance = null;  
    
    var MngContainer = function(){
        this._settings = {};
        this.container = null
        if(typeof this._init !="function"){
            MngContainer.prototype._init = function(){
                this.container = new SmartList();
                this._init = null;
            } 
        }
      
        MngContainer.prototype.register = function(mngKey,manager){
            if(typeof mngKey!="string") throw new Exception("mngKey can't be null");
            if(typeof manager!= 'object') throw new Exception("manager");
            this.container.set(mngKey,manager); 
        } 
      
      
        MngContainer.prototype.getManager = function(mngKey){
            var mngKey = mngKey || "none";
            return this.container.get(mngKey);
        } 
     this._init();  
    }
    
     
       var _init = function(){
           
           if(!_instance){
              _instance = new MngContainer();  
           }
          return _instance;
        }
   
    
    var publicApi = {
        getInstance : _init 
    };
    
    return publicApi;
})();
