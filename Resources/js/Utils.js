/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var bb = (bb) ? bb : {};
bb.Utils = {
    
    generateId : (function(){
        var genPrefix = "Item_";
        var current = 0;
        return function(prefix){
            var currentPrefix = prefix || genPrefix;
            var currentTime = new Date().getTime();
            return currentPrefix+'_'+currentTime+'_'+current++;
        }
    })(),
    /*Scroll to new content*/
    scrollToContent : function(contentEl,delay,topDistance){
        var documentBody = (($.browser.chrome)||($.browser.safari)) ? document.body : document.documentElement;
        var contentEl = contentEl || null;
        var delay = (delay && parseInt(delay)!=0) ? parseInt(delay) : 1000;
        if(!contentEl) return false;
        var topDistance = parseInt(topDistance)|| 220;  // [220 : taille Toolbar + taille ContentPath]
        if($(contentEl).offset()){
            $(documentBody).animate({
                scrollTop: $(contentEl).offset().top - topDistance
            }, delay,'easeInOutCubic',function(){
                //$(contentEl).effect("highlight",{},500); 
                });
        }
    },
    handleAppError : function(message,response){
        var message = (typeof message =="String" )? message : "An error occured";
        $(document).trigger("application:error", {
            title: "An error occured", 
            message: message, 
            error: response.error
        } );
        
    },
    readSizeFromClasses : function(classes){
        currentSize = 0;
        var sizePattern = /span\d/gi;
        var result = sizePattern.exec(classes);
        if(result && result.length){
            var currentSize = parseInt(result[0].replace("span",""));
        }
        return currentSize; 
    }
};

/*searchManager*/
var FilterManager = function(data,onCompleteCallback){
    this._queriesInfos = {
        criteria:[], 
        orderCriterium : null
    };
    this._data = data;
    this._operator = {}; /*keep operator*/
    this.onsearchComplete = (typeof onCompleteCallback=="function")? onCompleteCallback : function(){};
    this._orderResult = function(dataToSort, sortFunction){
        if((dataToSort) && typeof sortFunction==="function"){
            return  dataToSort.sort(sortFunction);
        }
        return dataToSort;
    }
         
}
    
    
/*permet de construire une fonction qui sera ensuite exécutée*/
FilterManager.prototype._buildCriterion = function(field, operator, value, link){
    var availableLinks = ["and","or","none"];
    var link = ($.inArray(link,availableLinks)) ? link : "and";
                     
    var operator = (operator=="=") ? "==" : operator;
                    
                     
    /* special filter IN and [Regexp [contains, endWith, beginWith]] */
    var specialOperators = ["IN","contains","beginsWith","endsWith"];
    var operatorIsSpecial = ($.inArray(operator,specialOperators)!=-1)? true : false;
    if(!operatorIsSpecial){
        var searchMethod = new Function("item","return item['"+field+"']"+operator+" '"+value+"';");
    }else{
        /*IN case*/
        if(operator.toUpperCase()==="IN"){
            var value = ($.isArray(value)) ? value: [value]; 
            var searchMethod = function(item){
                return ($.inArray(item[field],value)!= -1);
            }  
        }
                        
        /*regex operators*/
        var regexpOperator = ["CONTAINS","BEGINSWITH","ENDSWITH"];
        if($.inArray(operator.toUpperCase(),regexpOperator)!=-1){
            /*beginsWith*/
            var regexp = "";
            if(operator.toUpperCase()=="CONTAINS"){
                regexp = new RegExp(value,"gi"); 
            }
                        
            if(operator.toUpperCase()=="BEGINSWITH"){
                regexp = new RegExp("^"+value,"gi");
            }
                        
            if(operator.toUpperCase()=="ENDSWITH"){
                regexp = new RegExp(value+"$","gi"); 
            }
            searchMethod = function(item){
                return regexp.test(item[field]);
            /*add marker here*/
            }
        }
    }
                    
                    
    var criterion = {}; 
    criterion.searchMethod = searchMethod;
    criterion.link = link;
    this._queriesInfos.criteria.push(criterion);
}
                   
                   
   
FilterManager.prototype.where = function(field,operator,value){
    this._buildCriterion(field, operator, value,"none");   
    return this;
      
}
                
FilterManager.prototype.andWhere = function(field,operator,value){
    this._buildCriterion(field, operator, value,"and");
    return this;
}
                
FilterManager.prototype.orWhere = function(field,operator,value){
    this._buildCriterion(field, operator, value,"or");
    return this;
}
/*orderBy*/
FilterManager.prototype.orderBy = function(field,order){
    var orderCriterion = {};
    orderCriterion.field = field || "";
    var availableOrders = ["ASC","DESC"];
    orderCriterion.order = ($.inArray(order.toUpperCase(),availableOrders)) ? order.toUpperCase() : "ASC";
                 
    /*order fonction*/
    orderCriterion.filter = function(a,b){       
        if(orderCriterion.order=="ASC")  return (a[field] < b[field]) ? -1 : (a[field] > b[field]) ? 1 : 0;
        if(orderCriterion.order=="DESC") return (a[field] > b[field]) ? -1 : (a[field] < b[field]) ? 1 : 0;
    };
            
    /*save order in queue*/
    this._queriesInfos.orderCriterion = orderCriterion;
    return this;
}
                      
FilterManager.prototype.groupBy = function(){}
                
FilterManager.prototype.setData = function(data){
    this._data = data || [];
    return this;
}
                
FilterManager.prototype.execute = function(){
    /*execute all criteria*/
    var tempResult = [];
    var self = this;
    var searchData = this._data; 
                    
    /*criteria*/
    $.each(this._queriesInfos.criteria,function(i,criterion){
        var linkType = criterion.link;
                        
        if(i!=0){
            if(linkType=="and"||linkType=="none"){
                searchData =  tempResult;
                tempResult = [];  
            }
        }
        $.each(searchData,function(i,item){
            var checker = criterion.searchMethod;
            if(checker(item)) tempResult.push(item); 
        }); 

    });
                   
    this._queriesQueue = [];
    this._queriesInfos.criteria = [];
    /*order if useful*/
    tempResult = (this._queriesInfos.orderCriterion!=null)?this._orderResult(tempResult,this._queriesInfos.orderCriterion.filter):tempResult;
    this.onsearchComplete(tempResult);
    return tempResult;
}
    
  
bb.Utils.FilterManager = FilterManager;


/**
 * Usage
 * bb.Utils.ScriptLoader.load({scriptname : "scripts.js", appendtoBody:true, basename:"wdcsqdssdf"});
 */
var ScriptLoader = function(params){
    this._init = function(params){
        this.scriptInfos = {};
        var test = {
            "md5" : "sdsdsd", 
            callbacks:[],
            state:""
        }; //notready,loading,ready 
        this.basename = "";
        return this.getPublicApi();
    }
    
    this.getPublicApi = function(){
        var self = this;
        return {
            loadScript : $.proxy(self._buildScript,self)
        }
    }
    
    
    this._isLoaded = function(key){
        if(typeof this.scriptInfos[key]!="undefined"){
            return true;
        } 
        return false;
    } 
    /**
     *handle cal
     *script readyState == loaded
     *script readyState == complete
     */
    this._buildScript = function(params){
        if (typeof params != 'object') throw new Error("param must be an object");
        var script = document.createElement('script');
        var source = (params.basename)? params.basename+params.scriptname : this.basename+params.scriptname;
        script.type = "text/javascript";
        script.src = source;
        script.async = (typeof params.async=="boolean") ? params.async: true;
        var id = $.md5(source);
        /*do nothing if the script is already loaded*/
        if(this._isLoaded(id)){
            return;  
        }
        this.scriptInfos[id] = source;
        
        /* handle */
        if (script.readyState){  //IE
            script.onreadystatechange = function(){
                if (script.readyState == "loaded" ||
                    script.readyState == "complete"){
                    script.onreadystatechange = null;
                    if(typeof params.onSuccess=="function"){
                        params.onSuccess();
                    }
                }
            };
        } else {  //Others
            script.onload = function(){
                params.onSuccess();
            };
        }
        $("body").eq(0).append(script);
    } 
    
    return this._init(params);  
}

bb.Utils.ScriptLoader = new ScriptLoader;
/*test branche js*/