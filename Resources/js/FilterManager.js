var FilterManager = function(data,onCompleteCallback){
                
                this._queriesInfos = {criteria:[], orderCriterium:null};
                this._data = data || [];
                this._operator = {}; /*keep operator*/
                
                this.onsearchComplete = (typeof onCompleteCallback=="function")? onCompleteCallback : function(){};
    
                /*permet de construire une fonction qui sera ensuite exécutée*/
                this._buildCriterion = function(field, operator, value, link){
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
                            var value = (Utils.isArray(value)) ? value: [value]; 
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
                   
                   
                if(typeof this.where !="function"){
                    FilterManager.prototype.where = function(field,operator,value){
                        this._buildCriterion(field, operator, value,"none");   
                        return this;
                    }
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
               
               
                this._orderResult = function(dataToSort, sortFunction){
                    if((dataToSort) && typeof sortFunction==="function"){
                        return  dataToSort.sort(sortFunction);
                    }
                    return dataToSort;
                }
                
                FilterManager.prototype.groupBy = function(){}
                
                
                
                
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
                    /*order if useful*/
                    tempResult = (this._queriesInfos.orderCriterion!=null)?this._orderResult(tempResult,this._queriesInfos.orderCriterion.filter):tempResult;
                    this.onsearchComplete(tempResult);
                    return tempResult;
                }
                
                
            } 