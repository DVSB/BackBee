(function($){
    
    /*module exposes API. Api should be executed */
    bb.core.registerManager("content",{
        
        /*initDefaultSettings : function(){
            this._settings = {};
        },*/
        
        init : function(settings){
            var settings = settings || {};
            this._settings = bb.jquery.extend({},this._settings, settings);
            this.title = "parents";
            this.hour = " down south"; 
            console.log("init",this);
        },
        
        getHour: function(){
            console.log(new Date().getTime());
        },
    
        setHour: function(){
            console.log("this",this);
            console.log("setHour");
        },
        
        enable: function(){
            console.log("this",this);
            this.setHour();
            console.log(this._settings);
            console.log(this.title);
            this.isEnable = true; 
        },
        
        disable: function(){
            this.isEnable = true;
        },
        
        /*expose api context */
        getExposedApi: function(){
            var self = this;
            return{
                getTitle: function(){
                    console.log("self",self);
                    console.log("this",this);
                    console.log(self.hour);
                    console.log(self.title);
                    self.setHour();
                },
                getHour: function(){},             
                disable: bb.jquery.proxy(self.disable,self),
                enable: bb.jquery.proxy(self.enable,self)
            }  
        }
    
    
    });
    
})(bb.jquery)
