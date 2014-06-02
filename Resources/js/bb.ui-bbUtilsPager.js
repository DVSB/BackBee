(function($) {
	
    bb.jquery.widget("ui.bbUtilsPager", {
        options: {
            // Options principales
            pagerContainerClass: ".bb5-windowpane-main-toolbar-nav",
            maxItemSelectorCtnClass : ".bb5-windowpane-main-toolbar-sort-wrapper",
            selectPerPageCtnClass: ".maxPerPageSelector",
            maxPerPage: null,
            maxPerPageDefault: 50,
            start: 0,
            url: null,
            postParams: {},
            callback: null,
            onSelect: null,
            onRequest: null,
            maxPerPageSelector: true,
            border: true,
            border_color: 'none',
            text_color: 'none',
            background_color: 'none',	
            border_hover_color: 'none',
            text_hover_color: 'none',
            background_hover_color: 'none', 
            images: true,

            // Options d'état (il n'est pas recommandé de les définir à la création du widget)
            numResults: 0,
            numPages: 0,
            oldNumPage: 0,
            pager: null,
            dataWebserviceParams : null //retrieve data from webServices  
        },
                
        messages: {
        },

        tplMaxPerPageSelector: bb.jquery.template('\n\<select class="maxPerPageSelector">\n\
					<option value="5">5</option>\n\
					<option value="10">10</option>\n\
					<option value="20">20</option>\n\
					<option value="30">30</option>\n\
					<option value="50">50</option>\n\
					<option value="80">80</option>\n\
					<option value="100">100</option>\n\
				</select>\n\
		'),

        _create: function(){
            if(this.options.maxPerPage == null){
                if(bb.jquery(document).data('bbUtilsPager.maxPerPage')){
                    this.options.maxPerPage = bb.jquery(document).data('bbUtilsPager.maxPerPage');
                }else{
                    this.options.maxPerPage = this.options.maxPerPageDefault;
                }
            }
            this.getRecords(bb.jquery.proxy(this.displayRecords, this));
        },
		
        _getDataUsingWebService : function(callback){
                    
            var method = this.options.dataWebserviceParams.method; 
            var wbSuccessCallback = this.options.callback;
            var errorCallback = this.options.errorCallback;
            var wbParams =  this.options.postParams; 
            var self = this;
            
            /*success*/
            var successCallback = function(response){
                self.afterRequest(response.result.numResults);
                if(callback) callback(); //draw pager
                wbSuccessCallback(response);
                return;
            }
            
            /*error*/
            var errCallback = function(response){
                if(typeof errorCallback==="function"){
                    errorCallback(response);
                    return;
                }
            }
                    
            this.options.dataWebserviceParams.wb.request(method,{
                params : wbParams,
                success : successCallback,
                error: errCallback
            });
                  
        },   
        
        
        /*Affiche pagination et options*/
        displayRecords: function(datas){
            var self = this;
            bb.jquery(this.options.pagerContainerClass).html("");
            if(this.options.maxPerPageSelector){
                bb.jquery(this.options.selectPerPageCtnClass).remove();
                bb.jquery(this.element).find(this.options.maxItemSelectorCtnClass).eq(0).append(this.tplMaxPerPageSelector);                
                bb.jquery(this.element).find("select").val(this.options.maxPerPage);
                bb.jquery(this.element).find("select").unbind("change").change(function(){
                    self.options.maxPerPage = bb.jquery(this).val();
                    bb.jquery(this.element).data('bbUtilsPager.maxPerPage', self.options.maxPerPage);
                    self.options.start = 0;
                    var callback = bb.jquery.proxy(self.displayRecords, self);
                    self.getRecords(bb.jquery.proxy(self.displayRecords, self));
                //self.displayRecords();
                });
            }
            if(this.options.numResults > this.options.maxPerPage){
                bb.jquery(this.options.pagerContainerClass).html("");
                this.pager = bb.jquery(this.element).find(this.options.pagerContainerClass).eq(0);
                            
                bb.jquery(this.pager).paging(self.options.numResults, {
                    perpage: self.options.maxPerPage,
                    onSelect: function(numPage){
                        if(self.options.oldNumPage != 0){
                            if(self.options.onSelect) self.options.onSelect.call();
                            self.options.start = (numPage-1)*self.options.maxPerPage;
                            self.getRecords();
                        }
                        self.options.oldNumPage = numPage;
                    },
                    onFormat: function(type) {
                        switch (type) {
                            case 'block':
                                if (!this.active) return '';
                                else if (this.value != this.page)
                                    //return '<div><a href="#' + this.value + '">' + this.value + '</a></div>';
                                    return '<a class="bb5-link" href="#'+this.value+'">'+this.value+'</a>';
                                                                    
                                //return '<div class="selected">' + this.value + '</div>';
                                return '<span class="bb5-link">'+this.value+'</span>';

                            case 'next':
                                if (this.active) {
                                    //return '<div class="next"><a href="#' + this.value + '">&nbsp;</a></div>';
                                    return '<a class="bb5-button-link bb5-button bb5-button-square" href="#'+this.value+'">&#8250;</a>'
                                }
                                return '';

                            case 'prev':
                                if (this.active) {
                                    //return '<div class="previous"><a href="#' + this.value + '">&nbsp;</a></div>';
                                    return '<a class="bb5-button-link bb5-button bb5-button-square" href="#'+this.value+'">&#8249;</a>'
                                }
                                return '';

                            case 'first':
                                if (this.active) {
                                    //return '<div class="first"><a href="#' + this.value + '">&nbsp;</a></div>';
                                    return '<a class="bb5-button-link bb5-button bb5-button-square first" href="#'+this.value+'">&#171;</a>'
                                }
                                return '';

                            case 'last':
                                if (this.active) {
                                    //return '<div class="last"><a href="#' + this.value + '">&nbsp;</a></div>';
                                    return '<a class="bb5-button-link bb5-button bb5-button-square first" href="#'+this.value+'">&#187;</a>'

                                }
                                return '';

                            case 'fill':
                                if (this.active) {
                                    return "&nbsp;&hellip;";
                                }
                            default:
                                return "";
                        }
                    },
                    format: "[< (qq-) nnncnnn (-pp) >]",
                    lapping: 0
                });				
            }
        //this.options.callback(datas);
        },
		
        updatePostParams: function(postParams){
  
            this.options.postParams = bb.jquery.extend({},this.options.postParams, postParams);
           
           
            //this.displayRecords();
            this.getRecords(bb.jquery.proxy(this.displayRecords, this),true);
        },
         
      
                
        getRecords: function(callback,resetLimit){
            var self = this;
            if(typeof this.options.onRequest=="function") this.options.onRequest.call();
            
            if(typeof resetLimit=="boolean" && resetLimit==true){
                this.options.start = parseInt(this.options.postParams.start);
                this.options.maxPerPage = parseInt(this.options.postParams.limit);
            }else{
                this.options.postParams.start = parseInt(this.options.start);
                this.options.postParams.limit = parseInt(this.options.maxPerPage); 
            }

            if(this.options.dataWebserviceParams.wb){
                this._getDataUsingWebService(callback);
                return;
            }else{
                var postParams = bb.jquery.extend(this.options.postParams, {
                    start: parseInt(this.options.start), 
                    limit: parseInt(this.options.maxPerPage)
                });
                bb.jquery.post(this.options.url, postParams, function(datas){
                    if(!datas.error){
                        //self.options.numResults = parseInt(datas.numResults);
                        if(callback) callback(datas.datas);
                        else self.options.callback(datas.view);
                        /*var numPage = Math.ceil((self.options.start+1)/self.options.maxPerPage);
					bb.jquery(this.element).find(".pageBtn").css("font-weight", "normal");*/
                        self.afterRequest(datas.numResults);
                    }else{
                        bb.utils.dialog(datas.error);
                    }
                });
            }	
        },
               
        afterRequest: function(numResults){
            if(parseInt(numResults)==-1) throw "numResults must be provided"; 
            this.options.numResults = parseInt(numResults);
            bb.jquery(this.element).find(".pageBtn").css("font-weight", "normal");
        }, 
                
        destroy: function(){
            bb.jquery.Widget.prototype.destroy.apply(this, arguments);
        }
    })
})(bb.jquery);