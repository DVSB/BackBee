(function($,global){
    bb.jquery.widget('ui.bbSearchEngine', {
         
        options: {
            formFields : {
                searchBtn:".bb5-ico-search", 
                searchField:"", 
                typeField:".typeField", 
                pubBeforeField:".beforePubdateField",
                pubAfterField:".afterPubdateField",
                selectedpageField: ".selectedpageField"
            },
            formFieldsClass: ".searchengineField",
            appendToContainer : true, //or replace the container,
            defaultFilterTypes : [{
                value:"",
                label:""
            }],
            searchWebserviceParams : {
                ws:null,
                method:"searchContent"
            }
        },
        
        _create: function(){  
            this._templates.main = bb.jquery("#searchEngine-tpl").clone();
            this._templates.main.attr("id",bb.Utils.generateId("bbSearchEngine"));
        },
         
        _templates : {
            main : null
        },
         
        _init : function(){
            this.options.appendToContainer = (typeof this.options.appendToContainer=="boolean") ? this.options.appendToContainer : true; //default append
            this._context.searchCriteria = {};
            if(this.options.appendToContainer){
                bb.jquery(this.element).html(bb.jquery(this._templates.main).get(0).innerHTML);  
            }else{
                bb.jquery(this.element).html(bb.jquery(this._templates.main));  
            }
            this._bindEvents();
            this.typeField = (bb.jquery(this.element).find(this.options.formFields.typeField).length) ? bb.jquery(this.element).find(this.options.formFields.typeField).eq(0) : null;
            this.pubBeforeField = (bb.jquery(this.element).find(this.options.formFields.pubBeforeField).length) ? bb.jquery(this.element).find(this.options.formFields.pubBeforeField).eq(0) : null;
            this.pubAfterField = (bb.jquery(this.element).find(this.options.formFields.pubAfterField).length) ? bb.jquery(this.element).find(this.options.formFields.pubAfterField).eq(0) : null;
            this.formFields = bb.jquery(this.element).find(this.options.formFieldsClass);
            this.selectedpageField = (bb.jquery(this.element).find(this.options.formFields.selectedpageField).length) ? bb.jquery(this.element).find(this.options.formFields.selectedpageField).eq(0) : null;
            this._setFilterTypes(this.options.defaultFilterTypes);
            this._initDateWidgets();
        },
         
        _bindEvents: function(){
            var self = this;
            bb.jquery(this.element).find("input,select").bind("click", function(e){
                $(this).focus();
                return true;
            });
            
            bb.jquery(this.element).find(this.options.formFields.searchBtn).bind("click",function(e){
                e.preventDefault();
                self._trigger("beforeSearch", {
                    "type":"beforeEvents"
                }, {
                    widgetApi : self.getWidgetApi()
                });
                
                self._trigger("onSearch",{
                    "type":"beforeEvents"
                },self._getSearchCriteria())
            });
            this._initFormAutoBind();
        },
        
        _beforeShowDatepicker : function(dateField,dpInfos){
            bb.jquery(dpInfos.dpDiv).css('z-index', 601021);
            bb.jquery(dpInfos.dpDiv).addClass('bb5-ui bb5-dialog-wrapper');
        },
        
        _updateDateField : function(dp){
            var selectedDate = (typeof bb.jquery(this).val() == "string" ) ? bb.jquery(this).val().length : false;
            var timestamp = "";
            if(selectedDate){
                var date = new Date(dp.selectedYear,dp.selectedMonth,parseInt(dp.selectedDay));
                timestamp = date.getTime()/1000; //javascript's timestamps are in ms 
            }
            bb.jquery(this).attr("data-value",timestamp);
            bb.jquery(this).trigger("change");
        }, 
        
        /*extendsDefaultParams*/
        _setUserParams : function(userParams){
            var userParams = (bb.jquery.isPlainObject(userParams)) ? userParams : {};
            bb.jquery.extend(this._context.searchCriteria,userParams); 
        }, 
        
        _initDateWidgets : function(){
            var self = this;
            /*modifierdaterange*/
            bb.jquery(this.pubBeforeField).datepicker({
                dateFormat:"dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                beforeShow : bb.jquery.proxy(this._beforeShowDatepicker,this),
                onClose : function(selectedDate,dp){
                    self._updateDateField.call(this,dp);
                }
                
            });
            bb.jquery(this.pubAfterField).datepicker({
                dateFormat:"dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                onClose : function(selectedDate,dp){
                    self._updateDateField.call(this,dp);
                },
                beforeShow : bb.jquery.proxy(this._beforeShowDatepicker,this)
            });   
        },
         
         
        _doSearch : function(){
            var ws = this.options.searchWebserviceParams.ws;
            var method = this.options.searchWebserviceParams.method;
            var self = this;
            ws.request(method,{
                params : {
                    params : this._context.searchCriteria
                },
                async : false,
                success : function(response){
                    self._trigger("onResult",{
                        type:"searchEngine:onResult"
                    },response.result);  
                },
                error: function(){
                    new Error("searchEngine._doSearch");
                }
            });
        },
        
        
        _context: {
            searchCriteria:null
        },
        _initFormAutoBind : function(){
            bb.jquery(this.element).find(this.options.formFieldsClass).live("change",bb.jquery.proxy(this._updateSearchCriteria,this)); 
        },
         
        _getSearchCriteria : function(){
            return this._context.searchCriteria; 
        },
         
        _updateSearchCriteria : function(e){
            /*apply validatation here*/
           
            var currentField = bb.jquery(e.currentTarget);
            var fieldName = bb.jquery(currentField).attr("data-field-name");
            switch(fieldName){
                case "searchField" :
                    this._context.searchCriteria.searchField = bb.jquery(currentField).val();
                    break;
                    
                case "typeField":
                    this._context.searchCriteria.typeField = bb.jquery(currentField).val();
                    break;
                    
                case "beforePubdateField":
                    this._context.searchCriteria.beforePubdateField = bb.jquery(currentField).attr("data-value");
                    break;
                    
                case "afterPubdateField":
                    this._context.searchCriteria.afterPubdateField = bb.jquery(currentField).attr("data-value");
                    break;
                    
                case "selectedpageField":
                    this._context.searchCriteria.selectedpageField = bb.jquery(currentField).val();
                    break;
            }

        },
        _setFilterTypes : function(types){
            types = (bb.jquery.isArray(types)) ? types : [];
            var options = document.createDocumentFragment();
            bb.jquery.each(types,function(i,typeInfos){
                var option = bb.jquery("<option></option>").clone();
                bb.jquery(option).attr("value",typeInfos.value);
                bb.jquery(option).html(typeInfos.label);
                options.appendChild(option.get(0));
            });
            /*update Select here*/
            bb.jquery(this.typeField).html(bb.jquery(options));
            bb.jquery(this.typeField).trigger("change");
        },
        
        _setSelectedpageField :function(pageId){
            var pageId = (typeof pageId=="string") ? pageId : "";
            bb.jquery(this.selectedpageField).val(pageId);
            bb.jquery(this.selectedpageField).trigger("change");
        },  
        
        _reset : function(){
            this._context.searchCriteria = {};
            bb.jquery(this.formFields).each(function(i,field){
                bb.jquery(field).val("");
            });
            this._setFilterTypes(this.options.defaultFilterTypes);
        },
         

        _context: {},
        getWidgetApi: function(){
            var self = this;
            return{
                setFilterTypes : bb.jquery.proxy(self._setFilterTypes,self), 
                setSelectedPage : bb.jquery.proxy(self._setSelectedpageField,self), 
                getSearchCriteria : bb.jquery.proxy(self._getSearchCriteria,self),
                setUserParams : bb.jquery.proxy(self._setUserParams,self),
                reset : bb.jquery.proxy(self._reset,self)
            }
             
             
        }

    });
    
    
    
    
    
})(bb.jquery,document);