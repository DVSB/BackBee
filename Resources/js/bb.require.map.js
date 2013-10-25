var bb = bb || {};

bb.require = (function(require){
    return require;
})(require);

/* à éditer automatiquement */
bb.require({
    baseUrl: "js/",
    catchError:true,
    paths: {
        "RteManager" : "bb.RteManager",
        "ManagerFactory": "bb.ManagerFactory",
        "jscore" : "libs/jsclass/min/core",
        "aloha": "libs/alohaeditor/aloha/lib/aloha"
    }
});

/* expected Api */
/*bb.createManager("RteManager",{
    
    moduleConfig: {
        alias: "RteManager",
        dependencies:"jscore,radicalblae,tresn,sted",// other object
        exposedEvents: "onInit,onLoad,onClose, onDisable,onDisable"
    },
    
    init: function(userSettings){
        var jscore = this.get("jscore");
        console.log("this is it");
        this.triggerOnInit();
        this.trigger('onInit');
    },
    
    sayHello: function(){
        console.log("sayHello");
    },
    
    sayMyName: function(){
        console.log("I'm in say Hello");
    },
   

    exposeMethods: function(){
        return {
            test1: this.sayHello,
            test2: this.sayMyName
        } 
    },
   
    saveState : function(){},
   
    restoreState: function(){},
   
    enable: function(){},
   
    disable: function(){}
});


/**
* Lorsqu'un manager s'il n'a pas déja été appelé une nouvelle instance est créée
* Au prochain appel l'instance créé est renvoyé
* 
* 
*/

/*tranform */
//require(["a","b","c","d"],function(){});

/*
var serviceConfig = {
    layoutManager: {
        params: {test:"tesit", second:""}
    },
    
    lteManager: {
        params : { } 
    }
};


var rteManager = bb.ServiceManager.get("RteManager");
console.log(rteManager.test1());
console.log(rteManager.test2());
*/

