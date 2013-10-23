(function($){
    
    bb.core.registerManager("content",{
        
        defaultParams: { },
        /*init must expose the api*/
        init : function(settings){
            console.log(" thisisi ");
            var settings = settings || {};
            $.extend({},this.defaultParams,settings);
            this.title = "parents";
            this.hour = " down south"; 
            console.log("this is init");
        },
    
        getHour: function(){
            console.log(new Date().getTime());
        },
    
        setHour: function(){
            console.log("setHour");
        },
        
        enable: function(){
            this.isEnable = true; 
        },
        
        disable: function(){
            this.isEnable = true;
        },
        
        getExposedApi: function(){
            return{
                getTitle: function(){
                    console.log(this.title);
                },
                getHour: function(){},
                disable: this.disable,
                enable: this.enable
            }  
        }
    
    
    });
    
})(jQuery)
