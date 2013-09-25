var bb = (bb) ? bb : {};

/*Webservice cache Manager
 *
 *
 * Usage
 * var webServiceT = bb.webserviceManager.getInstance()
 * webServiceT.request("webservicenme.method",{
 * params : [],
 * success : function(){},
 * error: function(){} 
 * 
 * });
 *
 *
 *
 **/
var WsCacheManager = (function(){
    
    var _instance = null;
    var _availableCleaningMode = {
        CLEAN_ALL : 1,
        CLEAN_BY_TAGS:2,
        CLEAN_EXPIRE:3
    };
    
    var WsCacheHandler = function(){
        this.dbContainer = DbManager.init("BB5_WEBSERVICES");        
    } 
    /*Cache Constante*/
    $.extend(WsCacheHandler,_availableCleaningMode);
    
  /**
    * Useful function that allow retrieve cache content by tags
    *
    */
    var _findByTags = function(dataList,tag){
        var result = [];
        if(!$.isArray(dataList) && typeof tag !="string") return result;
        $.each(dataList,function(itemId,data){
            if(("tags" in data) && $.isArray(data.tags)){
                if($.inArray(tag,data.tags)!=-1){
                  result.push(itemId);
                }
            }
        });
        return result;
    }
     
    /**
    * handle expire
    */
    WsCacheHandler.prototype = {
        
        /**
         * @parameter key
         * @paramater data
         * @parameter tags
         * @parameter lifetime 
         */
        
        save:  function(key,data,tags,lifetime){
            var tags = (tags && $.isArray(tags)) ? tags : [];
            var secInOneYear = 60*60*24*365;
            var currentTime = new Date().getTime();  
            var lifetime = (typeof lifetime=="number") ? lifetime : secInOneYear + currentTime;
            var cacheObj = {
                data : data,
                tags: tags,
                lifetime: lifetime
            } 
            
            this.dbContainer.set(key,cacheObj);   
        },
        /**
         * prendre en compte la date d'expiration
         */
        load: function(key){
            var cacheObject = this.dbContainer.get(key);
            return cacheObject;
        },
        
        clean: function(cmode,params){
            var cmode = (typeof cmode=="number") ? cmode : false;
            var self = this;
            switch(cmode){
                case _availableCleaningMode.CLEAN_ALL:
                    this.dbContainer.reset();
                    break;
                case _availableCleaningMode.CLEAN_BY_TAGS:
                    var cDataIds = _findByTags(this.dbContainer.getData(),params);
                    $.each(cDataIds,function(i,cId){
                        self.dbContainer.deleteItemById(cId);
                    });
                    return true;
                    break;
                case _availableCleaningMode.CLEAN_EXPIRE:
                
                default:break
            }
            
        }
        
         
    }     
     
    var _publicApi = {
         
        getInstance :function(){
            if(_instance){
                return _instance; 
            }else{
                _instance = new WsCacheHandler();
            }
            return _instance;
        },
        
        cleanMode : _availableCleaningMode
         
    }
    return _publicApi;
     
})()

bb.wsCacheManager = WsCacheManager;

/***** bb.webservice ****/
bb.webservice = $.extend({}, {
    }, $.jsonRPC);

$.extend(bb.webservice, {
    token: null,
    
    setToken: function(token) {
        this.token = token;
    },
    
    setup: function(params) {
        this.token = params.token;
        this._validateConfigParams(params);
        this.endPoint = params.endPoint;
        this.namespace = params.namespace;
        this.wsCacheManager = bb.wsCacheManager.getInstance();
        return this;
    },
    
    _checkRequestCache: function(){},
   
    /**
    * the whole query string is used as key. Method name and params.
    *
    **/
    _handleCachedRequest : function(data,options){
        var result =  false;
        var jsonData = JSON.parse(data);
        if($.isPlainObject(jsonData)){
            result = this.wsCacheManager.load($.md5(data));   
        }
        return result;
    },
        
    
    _doRequest: function(data, options) {
        var _that = this;
        var useCache = (typeof options.useCache=="boolean") ? options.useCache : false;
        if(useCache){
            var cacheTags = ($.isArray(options.cacheTags)) ? options.cacheTags : [];   
            var cachedResult = this._handleCachedRequest(data,options);
            if(cachedResult){
                this._requestSuccess.call(_that, cachedResult.data, options.success, options.error);
                return;
            }
        }

        $.ajax({
            type: 'POST',
            async: false !== options.async,
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-BB-METHOD': 'JsonRpc',
                'X-BB-AUTH': bb.authmanager.getToken(),
                X_BB_TOKEN: this.token
            },
            url: (bb.baseurl ? bb.baseurl : '') + this._requestUrl(options.url),
            data: data,
            cache: false,
            processData: false,
            error: function(jqXHR) {
                if ('401' == jqXHR.status && 'undefined' != jqXHR.getResponseHeader('X-BB-AUTH')) {
                    $(bb.authmanager).trigger('bb-auth-required', [jqXHR.getResponseHeader('X-BB-AUTH'), this]);
                } else if ('403' == jqXHR.status && 'undefined' != jqXHR.getResponseHeader('X-BB-AUTH')) {
                    $(bb.authmanager).trigger('bb-auth-forbidden', [jqXHR.getResponseHeader('X-BB-AUTH'), this]);
                } else {
                    _that._requestError.call(_that, json, options.error);
                }
            },
            
            success: function(json) {
                
                if(useCache){
                    /*save only if the request return no error*/
                    if(!json.error){
                        _that.wsCacheManager.save($.md5(data),json,cacheTags);
                    }
                }
                _that._requestSuccess.call(_that, json, options.success, options.error);
            }
            
           
        });
    }
});

bb.webserviceManager = {};

$.extend(bb.webserviceManager, {
    webservices: {},
    
    setup: function(config) {
        var myself = this;
        
        $.each(config.webservices, function(index, service) {
            myself.webservices[service.name] = {};
            myself.webservices[service.name] = $.extend({}, {}, bb.webservice);

            myself.webservices[service.name]= myself.webservices[service.name].setup({
                token: config.token,
                endPoint: config.endPoint,
                namespace:  service.namespace
            });
        });
    },
    	
    setToken: function(token) {
        $.each(this.webservices, function(index, service) {
            service.setToken(token);
        });
    },
    
    getInstance: function(name) {
        return this.webservices[name];
    }
});


