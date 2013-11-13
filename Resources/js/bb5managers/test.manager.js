(function(){
    var _title = null;
    var _test = function(){
        console.log("title : "+_title);
    }
    
    bb.core.registerManager("test",{
        initialize: function(){
        },
        init: function(){
            _title = " strange ";
        },
        getExposedApi: function(){
            return {
                test: _test
            };
        }
    });
    
})()


/*bb.core.registerManager("test",{
    
    init: function(params){
    
    },
    
    sayHello: function(){
        console.log("sayHello is called");
    },
    
    getName: function(){
        console.log("getName is called"); 
    },
    
    getExposedApi: function(){
        return {
            init :  this.init,
            toto: function(){console.log("toto")},
            test1: this.getName
        }
    }
    
});*/