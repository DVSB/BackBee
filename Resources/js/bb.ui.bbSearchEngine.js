(function(jQuery,global){
    $.widget('ui.bbSearchEngine', {
         
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
            defaultFilterTypes : [{value:"",label:""}],
            searchWebserviceParams : {
                ws:null,
                method:"searchContent"
            }
        },
        
        _create: function(){  
            this._templates.main = $("#searchEngine-tpl").clone();
            this._templates.main.attr("id",bb.Utils.generateId("bbSearchEngine"));
        },
         
        _templates : {
            main : null
        },
         
        _init : function(){
            this.options.appendToContainer = (typeof this.options.appendToContainer=="boolean") ? this.options.appendToContainer : true; //default append
            this._context.searchCriteria = {};
            if(this.options.appendToContainer){
                $(this.element).html($(this._templates.main).get(0).innerHTML);  
            }else{
                $(this.element).html($(this._templates.main));  
            }
            this._bindEvents();
            this.typeField = ($(this.element).find(this.options.formFields.typeField).length) ? $(this.element).find(this.options.formFields.typeField).eq(0) : null;
            this.pubBeforeField = ($(this.element).find(this.options.formFields.pubBeforeField).length) ? $(this.element).find(this.options.formFields.pubBeforeField).eq(0) : null;
            this.pubAfterField = ($(this.element).find(this.options.formFields.pubAfterField).length) ? $(this.element).find(this.options.formFields.pubAfterField).eq(0) : null;
            this.formFields = $(this.element).find(this.options.formFieldsClass);
            this.selectedpageField = ($(this.element).find(this.options.formFields.selectedpageField).length) ? $(this.element).find(this.options.formFields.selectedpageField).eq(0) : null;
            this._setFilterTypes(this.options.defaultFilterTypes);
            this._initDateWidgets();
        },
         
        _bindEvents: function(){
            var self = this;
            $(this.element).find(this.options.formFields.searchBtn).bind("click",function(e){
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
            $(dpInfos.dpDiv).css('z-index', 601021);
            $(dpInfos.dpDiv).addClass('bb5-ui bb5-dialog-wrapper');
        },
        
        _updateDateField : function(dp){
            var selectedDate = (typeof $(this).val() == "string" ) ? $(this).val().length : false;
            var timestamp = "";
            if(selectedDate){
                var date = new Date(dp.selectedYear,dp.selectedMonth,parseInt(dp.selectedDay));
                timestamp = date.getTime()/1000; //javascript's timestamps are in ms 
            }
            $(this).attr("data-value",timestamp);
            $(this).trigger("change");
        }, 
        
        /*extendsDefaultParams*/
        _setUserParams : function(userParams){
            var userParams = ($.isPlainObject(userParams)) ? userParams : {};
            $.extend(this._context.searchCriteria,userParams); 
        }, 
        
        _initDateWidgets : function(){
            var self = this;
            /*modifierdaterange*/
            $(this.pubBeforeField).datepicker({
                dateFormat:"dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                beforeShow : $.proxy(this._beforeShowDatepicker,this),
                onClose : function(selectedDate,dp){
                    self._updateDateField.call(this,dp);
                }
                
            });
            $(this.pubAfterField).datepicker({
                dateFormat:"dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                onClose : function(selectedDate,dp){
                    self._updateDateField.call(this,dp);
                },
                beforeShow : $.proxy(this._beforeShowDatepicker,this)
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
            $(this.element).find(this.options.formFieldsClass).live("change",$.proxy(this._updateSearchCriteria,this)); 
        },
         
        _getSearchCriteria : function(){
            return this._context.searchCriteria; 
        },
         
        _updateSearchCriteria : function(e){
            /*apply validatation here*/
           
            var currentField = $(e.currentTarget);
            var fieldName = $(currentField).attr("data-field-name");
            switch(fieldName){
                case "searchField" :
                    this._context.searchCriteria.searchField = $(currentField).val();
                    break;
                    
                case "typeField":
                    this._context.searchCriteria.typeField = $(currentField).val();
                    break;
                    
                case "beforePubdateField":
                    this._context.searchCriteria.beforePubdateField = $(currentField).attr("data-value");
                    break;
                    
                case "afterPubdateField":
                    this._context.searchCriteria.afterPubdateField = $(currentField).attr("data-value");
                    break;
                    
               case "selectedpageField":
                   this._context.searchCriteria.selectedpageField = $(currentField).val();
                   break;
            }

        },
        _setFilterTypes : function(types){
            var types = ($.isArray(types)) ? types : [];
            var options = document.createDocumentFragment();
            $.each(types,function(i,typeInfos){
                var option = $("<option></option>").clone();
                $(option).attr("value",typeInfos.value);
                $(option).html(typeInfos.label);
                options.appendChild(option.get(0));
            });
            /*update Select here*/
            $(this.typeField).html($(options));
            $(this.typeField).trigger("change");
        },
        
        _setSelectedpageField :function(pageId){
            var pageId = (typeof pageId=="string") ? pageId : "";
            $(this.selectedpageField).val(pageId);
            $(this.selectedpageField).trigger("change");
        },  
        
        _reset : function(){
            this._context.searchCriteria = {};
            $(this.formFields).each(function(i,field){
                $(field).val("");
            });
            this._setFilterTypes(this.options.defaultFilterTypes);
        },
         

        _context: {},
        getWidgetApi: function(){
            var self = this;
            return{
                setFilterTypes : $.proxy(self._setFilterTypes,self), 
                setSelectedPage : $.proxy(self._setSelectedpageField,self), 
                getSearchCriteria : $.proxy(self._getSearchCriteria,self),
                setUserParams : $.proxy(self._setUserParams,self),
                reset : $.proxy(self._reset,self)
            }
             
             
        }

    });
    
    
    
    
    
})(jQuery,document);