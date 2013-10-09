$(function(){
    
    /*scalar type*/
    FormBuilder.registerRenderTypePlugin("date",{
        name:"date",
        _init : function(){
            this.id = this._settings.formId+'-'+this.id;
            this.fieldWrapper = $("<p></p>");
            this.template = $("<label class='fieldLabel'></label><input class='bb5-plugin-form-field fieldDate' type='text' value=''></input>").clone();
            this.fiedId = this.id+"-"+this._settings.fieldInfos.fieldLabel; 
        },

        render : function(){
            $(this.fieldWrapper).append(this.template);
            $(this.fieldWrapper).find(".fieldLabel").html((typeof this._settings.fieldInfos.param.array.label=="string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel);
            var value = this._settings.fieldInfos.param.array.value;
            $(this.fieldWrapper).find(".fieldDate").attr("id",this.fieldId).datepicker({
                dateFormat: 'dd/mm/yy'
            });
            if (value)
                $(this.fieldWrapper).find(".fieldDate").datepicker('setDate', new Date(value*1000));
            return this.fieldWrapper;
        },

        parse : function(){
            var value = $(this.fieldWrapper).find(".fieldDate").datepicker('getDate');
            
            var result = {
                'array':{
                    'value': (value) ? value.getTime() / 1000 : ''
                }
            };
            return result;            
        }

    });
  
    /*scalar type*/
    FormBuilder.registerRenderTypePlugin("scalar",{
        _init : function(){
            this.id = this._settings.formId+'-'+this.id;
            this.fieldWrapper = $("<p></p>");
            this.template = $("<label class='fieldLabel'>test</label><input class='bb5-plugin-form-field fieldText' type='text' value=''></input>").clone();
            this.fiedId = this.id+"-"+this._settings.fieldInfos.fieldLabel; 
        },

        render : function(){
            $(this.fieldWrapper).append(this.template);
            $(this.fieldWrapper).find(".fieldLabel").html(this._settings.fieldInfos.fieldLabel);
            var value = this._settings.fieldInfos.param.scalar;
            $(this.fieldWrapper).find(".fieldText").attr("id",this.fieldId).val(value);
            return this.fieldWrapper;
        },

        parse : function(){
            var result = {
                scalar: $(this.fieldWrapper).find(".fieldText").val()
            };
            return result;            
        }

    });

    /*scalar type*/
    FormBuilder.registerRenderTypePlugin("text",{
        _init : function(){
            this.id = this._settings.formId+'-'+this.id;
            this.fieldWrapper = $("<p></p>");
            
            var pattern = (this._settings.fieldInfos.param.array.pattern) ? " pattern='"+this._settings.fieldInfos.param.array.pattern+"'" : '';
            
            this.template = $("<label class='fieldLabel'>test</label><input class='bb5-plugin-form-field fieldText' type='text' value=''"+pattern+"></input>").clone();
            this.fiedId = this.id+"-"+this._settings.fieldInfos.fieldLabel; 
        },

        render : function(){
            $(this.fieldWrapper).append(this.template);
            $(this.fieldWrapper).find('.fieldLabel').html(this._settings.fieldInfos.param.array.label);
            $(this.fieldWrapper).find('.fieldText').attr("id",this.fieldId).val(this._settings.fieldInfos.param.array.value);
            return this.fieldWrapper;
        },

        parse : function(){
            var result = {
                'array':{
                    'value': $(this.fieldWrapper).find(".fieldText").val()
                }
            };
            return result;            
        }

    });

    /*Radio type*/
    FormBuilder.registerRenderTypePlugin('radio', {
        _init: function(){
            this.id = this._settings.formId+'-'+this.id; 
            var template = $("<p><label>Provide a label for this field</label></p>").clone();
            $(template).attr("id",this.id).addClass('bb5-param-'+this._settings.fieldInfos.fieldLabel);
            var form = this._populateForm();
            var fieldLabel = (typeof this._settings.fieldInfos.param.array.label=="string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
            $(template).find("label").text(fieldLabel);
            $(template).append(form);
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onchange))
                $(template).find("input").attr('onchange', this._settings.fieldInfos.param.array.onchange);
            this.form = template;
        },
        
        _populateForm : function(){
            var self = this;
            var options = this._settings.fieldInfos.param.array.options;
            var selection = this._settings.fieldInfos.param.array.checked;
            var dFragment = document.createDocumentFragment();

            $.each(options,function(value,option){
                var optionTpl = $('<label class="bb5-form-label-chkbx"><input type="radio" name="radio'+self.id+'" /><span></span></label>').clone();
                $(optionTpl).find("input").attr("value",value);
                $(optionTpl).find("span").html(option);
                if(selection+''==value+'') $(optionTpl).find("input").attr("checked","checked");
                dFragment.appendChild($(optionTpl).get(0));
            });
            return dFragment;
        },
        
        render : function(){
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onrender))
                eval(this._settings.fieldInfos.param.array.onrender);
            
            return this.form;
        },
        
        parse: function(){
            var result = {
                'array':{
                    'checked': $(this.form).find('input:checked').val()
                }
            };
            return result;
        }
    });
    
    /*Checkbox type*/
    FormBuilder.registerRenderTypePlugin('checkbox', {
        _init: function(){
            this.id = this._settings.formId + '-' + this.id;
            this.fiedId = this.id + '-' + this._settings.fieldInfos.fieldLabel;
            
            if ('undefined' == typeof(this._settings.fieldInfos.param.array.options)) {
                var template = $('<p><label class="fieldLabel">test</label><input class="bb5-plugin-form-field fieldCheck" type="checkbox" value="1"></p>').clone();
                $(template).attr("id",this.id).addClass('bb5-param-'+this._settings.fieldInfos.fieldLabel);
                $(template).find('.fieldLabel').html(this._settings.fieldInfos.param.array.label);
                if (this._settings.fieldInfos.param.array.checked == true) {
                    $(template).find('.fieldCheck').attr('checked', 'checked');
                }
                var value = this._settings.fieldInfos.param.scalar;
                $(template).find('.fieldCheck').attr('id',this.fieldId).val(value);
            } else {
                var template = $("<p><label>Provide a label for this field</label></p>").clone();
                $(template).attr("id",this.id).addClass('bb5-param-'+this._settings.fieldInfos.fieldLabel);
                var form = this._populateForm();
                var fieldLabel = (typeof this._settings.fieldInfos.param.array.label=="string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
                $(template).find("label").text(fieldLabel);
                $(template).append(form);
                this.initvalues = this._settings.fieldInfos.param.array.checked;
                for(var i=0; i<this.initvalues.length; i++) this.initvalues[i] = null;
                if ('undefined' != typeof(this._settings.fieldInfos.param.array.onchange))
                    $(template).find("input").attr('onchange', this._settings.fieldInfos.param.array.onchange);
            }
            
            this.form = template;                
        },
        
        _populateForm : function(){
            var self = this;
            var options = this._settings.fieldInfos.param.array.options;
            var selection = this._settings.fieldInfos.param.array.checked;
            var dFragment = document.createDocumentFragment();

            $.each(options,function(value,option){
                var optionTpl = $('<label class="bb5-form-label-chkbx"><input type="checkbox" name="radio'+self.id+'" /><span></span></label>').clone();
                $(optionTpl).find("input").attr("value",value);
                $(optionTpl).find("span").html(option);
                for(var i=0; i < selection.length; i++) {
                    if(selection[i]+''==value+'') {
                        $(optionTpl).find("input").attr("checked","checked");
                        break;
                    }
                }
                dFragment.appendChild($(optionTpl).get(0));
            });
            return dFragment;
        },

        render : function(){
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onrender))
                eval(this._settings.fieldInfos.param.array.onrender);
            return this.form;
        },

        parse: function(){
            if ('undefined' == typeof(this._settings.fieldInfos.param.array.options)) {
                var result = {
                    'array':{
                        'checked': $(this.form).find('.fieldCheck').is(':checked')
                    }
                };
            } else {
                var checked = new Array();
                $.each($(this.form).find('input:checked'), function(i, input) { checked[checked.length] = $(input).val()});

                var result = {
                    'array':{
                        'checked': $.extend(this.initvalues, checked)
                    }
                };
            }
            
            return result;
        }
    });
    
    
    /*----------------link-list------------------------*/
    FormBuilder.registerRenderTypePlugin("link-list",{
       
        _settings: {
            formTplClass: ".link-list-from-tpl",
            itemTplClass: ".linkItem-tpl",
            itemCls: "bb5-link-item",
            itemContainerClass: ".linksContainer",
            highLightCls: "bb5-link-item-highlighted",
            actionContainerClass :".btnContainer",
            fieldErrorCls: "hasError"
        },
       
        _context:{
            linkSelector : null, 
            parsedData : [],
            selected: null
        },
        _template:{
            itemTpl : null
        },
       
        _init : function(){
            this.id = this._settings.formId+'-'+this.id; 
            this.form = $(this._settings.formTplClass).eq(0).clone();
            this._template.itemTpl = this.form.find(this._settings.itemTplClass).eq(0);
            this.linksContainer = $(this.form).find(this._settings.itemContainerClass).eq(0);
            this.linksContainer.empty();
            $(this.form).removeClass(this._settings.formTplClass.replace(".",""));
            var linksInfos = this._settings.fieldInfos.param.array.links;
            if(linksInfos.length){
                this._populate(JSON.parse(linksInfos));
            }
            this._bindEvents();
        },
       
        _populate : function(linksInfos){
            var self = this;
            if($.isArray(linksInfos)){
                $.each(linksInfos,function(i,linkData){
                    self._populateLink(linkData);
                });
            }
        },
        
        _bindEvents : function(){
            var self = this;
           
            $(this.form).find(".addLinkBtn").die().unbind().bind("click",function(e){ 
                self._showLinkSelector();
            }); 
            
            $(this.form).find("."+this._settings.itemCls).live("mouseenter",function(e){
                var currentLink = e.currentTarget;
                $("."+self._settings.itemCls).removeClass(self._settings.highLightCls);
                self._highlight(currentLink);
                return;
            });
            
            $(this.form).find("."+this._settings.itemCls).live("mouseleave",function(e){
                $("."+self._settings.itemCls).removeClass(self._settings.highLightCls);
                $("."+self._settings.itemCls).find(self._settings.actionContainerClass).hide();
                return;
            });
            
            /*bind delete action*/
            $(this.form).find(".bb5-ico-del").live("click",function(e){
                var target = e.target;
               var linknode = $(target).parents("."+self._settings.itemCls).eq(0);
                if(linknode){
                    $(linknode).remove();
                    return;
                }
            });
            
        },
        _handleError : function(fieldsWithErrors){
            var self = this;
            if($.isArray(fieldsWithErrors)){
                $.each(fieldsWithErrors,function(i,node){
                    $(node).addClass(self._settings.fieldErrorCls);
                    $(node).unbind("click").bind("click",function(e){
                        $(node).removeClass(self._settings.fieldErrorCls);
                    });
                });
            }
        },
        
        _highlight: function(node){
            if(!node) return;
            /*clean all*/
            $("."+this._settings.itemCls).removeClass(this._settings.highLightCls);
            $(node).addClass(this._settings.highLightCls);
            $(node).find(this._settings.actionContainerClass).show();
             
        },
        _showLinkSelector : function(){
            var self = this;
            if(this._context.linkSelector){
                /*afficher - selectionner noeud*/
                if(this._context.linkSelector.data("bbSelector")){
                    this._context.linkSelector.data("bbSelector").open();
                    return false;
                }
            }
      
            if (!this._context.linkSelector) {
                var selectorLink = bb.i18n.__('toolbar.editing.linkselectorLabel');
                var linkSelectorContainer = $("<div id='bb5-param-linksselector' class='bb5-selector-wrapper'></div>").clone();
                this._context.linkSelector = $(linkSelectorContainer).bbSelector({
                    popup: true,
                    pageSelector: true,
                    linkSelector: true,
                    mediaSelector: false,
                    contentSelector : false,
                    resizable: false,
                    selectorTitle : selectorLink,
                    //site: bb.frontApplication.getSiteUid(),
                    callback: function(item) {
                        $('#bb5-param-linksselector').bbSelector('close');
                    },
                   
                    beforeWidgetInit:function(){
                        var bbSelector = $(this.element).data('bbSelector');
                        bbSelector.onWidgetInit(bbSelector._panel.INTERNAL_LINK, function () { 
                            var bbPageSelector = $(this).data('bbPageSelector') || false;
                            if(bbPageSelector){
                                bbPageSelector.setCallback(function(params) {
                                    self._populateLink(params);
                                    bbSelector.close();
                                }); 
                            }
                        });
                        
                        /*for External link*/
                        bbSelector.onWidgetInit(bbSelector._panel.EXTERNAL_LINK, function () {
                            var bbLinkSelector = $(this).data('bbLinkSelector');
                            bbLinkSelector.setCallback(function (params) {
                                params.value = "http://"+params.value;
                                self._populateLink(params);
                                bbSelector.close();
                            });
                        });
                    }
                
                //open: $.proxy(self.bbSelectorHandlers.openHandler,self,"bbLinkInternalContainer"),
                //resizeStart: $.proxy(this.bbSelectorHandlers.resizeStartHandler,this,"bbLinkInternalContainer"),
                //resize : $.proxy(this.bbSelectorHandlers.resizeHandler,this,"bbLinkInternalContainer")
                });
            }
            this._context.linkSelector.data("bbSelector").open();
        },
        
        /*afficher le lien {url, title, target, pageuid}*/
        _populateLink: function(data){
            var itemTpl = $(this._template.itemTpl).clone().removeClass("linkItem-tpl").addClass(this._settings.itemCls);
            $(itemTpl).data("uid",data.uid);
            var linkId = bb.Utils.generateId("link");
            $(itemTpl).find(".link").attr("disabled",1).val(data.value);
            $(itemTpl).find(".title").val(data.title);
            if(data.target=="_blank"){
                $(itemTpl).find(".targetBlank").attr("checked",1);
            }
            if(data.target=="_self"){
                $(itemTpl).find(".targetSelf").attr("checked",1);
            }
            $(itemTpl).find(".targetBlank,.targetSelf").attr("name",linkId);
            this.linksContainer.append(itemTpl);
            this.linksContainer.stop().animate({
                scrollTop : $(this.linksContainer).height()
            },800);
            this._parseLinks();
        },
        
        _parseLinks: function(){
            var self = this;
            self._context.parsedData = [];
            $.each(this.form.find("."+this._settings.itemCls),function(i,link){
                var linksInfos = {};
                linksInfos.uid = $(link).data("uid")||false;
                linksInfos.target = $(link).find('.target:checked').val();
                linksInfos.title = $(link).find(".title").val();
                linksInfos.value = $(link).find(".link").val(); 
                self._context.parsedData.push(linksInfos);
            });
        },
       
        render : function(){
            return this.form;
        },
        
        
        onOpen : function(e,data){},
        
        /*validate form: This function is called before submit*/
        validate : function(){
            var isValid = true;
            var linksWithErrors = [];
            $.each(this.form.find("."+this._settings.itemCls),function(i,linkNode){
                var titleField = $(linkNode).find(".title").eq(0);
                var title = $(titleField).val();
                if(!$.trim(title).length){
                    linksWithErrors.push($(linkNode).find(".title"));
                }
            });
            if(linksWithErrors.length > 0){
                this._handleError(linksWithErrors);
                isValid = false;
            }
            return isValid;
        },
        
        parse : function(){
            this._parseLinks();
            var result = {
                "array":{
                    links : JSON.stringify(this._context.parsedData)
                }
            };
            return result;  
        } 
    });
    
    
    
    /*----------------node-selector------------------------*/
    FormBuilder.registerRenderTypePlugin("node-selector",{
        
        _settings : {
            addNodeBtnCls:".addParentNodeBtn",
            formTplClass : ".node-selector-form-tpl",
            formfielClass : ".bb5-plugin-form-field",
            fieldWrapperClass:".fieldWrapper",
            treeBtnClass :".bb5-ico-tree"
        },
        
        i18n : {
            pageBrowserTitle:"Selectionner une page"
        },
        _init : function(){
            this.id = this._settings.formId+'-'+this.id; 
            this.form = $(this._settings.formTplClass).clone();
            $(this.form).removeClass(this._settings.formTplClass.replace(".",""));
            $(this.form).attr("id",this.id);
            this.bindEvents();
            this.kwRenderer = this._initKeywordsRenderer();
            this._populateForm();
        },
        _context: {
            parsedData:{},
            pageBrowser:""
        },
            
        callbacks : {
            clickOnFieldHandler : function(e){
                var currentTarget = e.currentTarget;
                var fieldType = $(currentTarget).attr("data-key");
                if($(currentTarget).hasClass("parentnode-tree")){
                    this.showPageTree($(currentTarget).next());
                }
                if($(currentTarget).hasClass("classcontent-tree")){
                    this.initOrShowContentSelector($(currentTarget).next());
                }
            }
        },
        
        _populateForm : function(){
            var self = this;
            var parentnodeTitle = this._settings.fieldInfos.param.array["parentnodeTitle"]||"";
            $(this.form).find(this._settings.formfielClass).each(function(i,field){
                var key = $(field).attr("data-key");
                if($.inArray(key,self._settings.disabledFields) != -1){
                    $(field).parents(self._settings.fieldWrapperClass).hide();
                    return true;
                } 
                var fieldValue = (key=="limit") ? parseInt(self._settings.fieldInfos.param.array[key]) : self._settings.fieldInfos.param.array[key][0];
                $(field).val(fieldValue);
                if(key=="parentnode"){
                    $(field).val(parentnodeTitle);
                }
                if (key == 'classcontent') $(field).val($(field).val().replace('BackBuilder\\ClassContent\\',''));
                if(key != "limit") $(field).attr("data-fieldvalue",fieldValue);
                $(field).trigger("change");
                return true;
            });
            if(this.kwRenderer){
                $(this.form).append(this.kwRenderer.render());   
            }
           
        },
        /**
         * Keyword has it's own renderer, so we must use it.
         * Instance is created by calling bb.FormBuilder.createSubformRenderer
         * this function return a renderer
         **/
        _initKeywordsRenderer : function(){
            var keywordsRender = false;
            if(typeof this._settings.fieldInfos.param.array.keywordsselector !="undefined"){
                var keywordSelector = this._settings.fieldInfos.param.array.keywordsselector;
                keywordsRender = bb.FormBuilder.createSubformRenderer("keywordsselector",keywordSelector,this._settings.formId);
            } 
            return keywordsRender;
        },
        
        showPageTree : function(formField){
            var pageBrowser = $("<div id='bb5-form-pagebrowser'><div id='browser' class='filetree'></div></div>").clone();
            var self = this;
            if(this._context.pageBrowser){
                /*afficher - selectionner noeud*/
                if(this._context.pageBrowser.data("bbPageBrowser")){
                    this._context.pageBrowser.data("bbPageBrowser").open();
                    return false;
                }
                
            }else{
                this._context.pageBrowser = $(pageBrowser).bbPageBrowser({
                    title : this.i18n.pageBrowserTitle,
                    popup: {
                        width: 269,
                        height: 500,
                        position: [0, 60] //handle destroy on close
                    },
                    editMode: false,
                    site: bb.frontApplication.getSiteUid(),
                    breadcrumb:bb.frontApplication.getBreadcrumbIds(),
                    
                    select: function(e, data){
                        $(formField).attr("data-fieldValue",data.node_id);
                        $(formField).val($("#node_"+data.node_id).find("a").get(0).textContent);
                        $(formField).trigger("change");
                        $(this).bbPageBrowser("close");
                        self._context.pageBrowser = false;
                    }
                });
                this._context.pageBrowser.data("bbPageBrowser").open();
            }
        },
        initOrShowContentSelector : function(formField){
            var contentTypeSelector = $("<div id='bb5-form-contentTypeSelector'><div class='bb5-windowpane-treewrapper-inner' class='filetree'></div></div>").clone();
            var self = this;
            if(this._context.contentTypeSelector){
                if(this._context.contentTypeSelector.data("bbContentTypeBrowser")){
                    this._context.contentTypeSelector.data("bbContentTypeBrowser").open();
                    return false;
                }
                
            }
            this._context.contentTypeSelector = $(contentTypeSelector).bbContentTypeBrowser({
                popup: {
                    width: 200,
                    height: 500,
                    position: [0, 120]
                },
                site: bb.frontApplication.getSiteUid(),
                ready: function() {},
                select: function(e,nodeInfos){
                    if(nodeInfos.nodeType!="contentType") return false; //main category do nothing
                    var selectedNode = nodeInfos.node;
                    var selectedContentType = $(selectedNode).find("a").get(0).textContent;
                    var pattern = /contentType_(\w+)/i;
                    var fieldValue = selectedContentType.substr(1);
                    if(pattern.test(jQuery(selectedNode).attr('rel'))) {
                        var result = pattern.exec(jQuery(selectedNode).attr('rel'));
                        fieldValue = result[1];
                    }
                    if (selectedContentType.indexOf('BackBuilder\\ClassContent\\') === -1) {
                        fieldValue = 'BackBuilder\\ClassContent\\' + fieldValue;
                    }
                   
                    $(formField).attr("data-fieldValue", fieldValue);
                    $(formField).trigger("change");
                    $(formField).val(selectedContentType);
                    $(this).bbContentTypeBrowser("close");
                    self._context.contentTypeSelector = false;
                }
            });
            this._context.contentTypeSelector.data("bbContentTypeBrowser").open();
        },
        
        
        bindEvents : function(){
            $(this.form).delegate(this._settings.treeBtnClass,"click",$.proxy(this.callbacks["clickOnFieldHandler"],this));
            this.initFormAutoBind();
        },
        
        initFormAutoBind : function(){
            $(this.form).delegate(this._settings.formfielClass,"change",$.proxy(this.handleFieldsChange,this));
        },
        
        handleFieldsChange :function(e){
            var nodeType = $(e.currentTarget).attr("data-key") || "none";
            var nodeValue = $(e.currentTarget).attr("data-fieldValue") || $(e.currentTarget).val();
            if(nodeType=="limit"){
                this._context.parsedData[nodeType] = parseInt($.trim(nodeValue));
            }else{
                if(nodeType!="none"){
                    this._context.parsedData[nodeType] = [$.trim(nodeValue)];
                }
                if(nodeType=="parentnode"){
                    this._context.parsedData["parentnodeTitle"] = $(e.currentTarget).val();    
                }
            }
            
        },
        
        render : function(){
            return this.form;
        },
        
        parse : function(){
            if (this.kwRenderer) {
                var keywordsData = this.kwRenderer.parse();
                /*add keywords if not empty*/
                if(keywordsData.array.selected){
                    var selectedKeywords = keywordsData.array.selected;
                    this._context.parsedData["keywordsselector"] ={
                        selected : selectedKeywords
                    } 
                }
            }
            
            var result = {
                "array": this._context.parsedData
            };
            return result;  
        },
         
        onClose : function(){
            /*masquer tous les arbes*/
                if(this._context.contentTypeSelector){
                    if(this._context.contentTypeSelector.data("bbContentTypeBrowser")){
                        this._context.contentTypeSelector.data("bbContentTypeBrowser").destroy();  
                    }
                }
            
            if(this._context.pageBrowser){
                if(this._context.pageBrowser.data("bbPageBrowser")){
                    this._context.pageBrowser.data("bbPageBrowser").destroy();  
                }
                
            }
        }
        

    });
    
  
    /*--------------select renderType-------------------------*/  
    FormBuilder.registerRenderTypePlugin("select",{
      
        _settings : {
            emptyLabel: "Provide a label for this field"
        },
      
        _init : function(){
            this.id = this._settings.formId+'-'+this.id; 
            var template = $("<p><label>Provide a label for this field</label><select></select></p>").clone();
            $(template).attr("id",this.id);
            var form = this._populateForm();
            var fieldLabel = (typeof this._settings.fieldInfos.param.array.label=="string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
            $(template).find("label").text(fieldLabel);
            $(template).find("select").append(form);
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onchange))
                $(template).find("select").attr('onchange', this._settings.fieldInfos.param.array.onchange);
            this.form = template;
           
        },
        
        _populateForm : function(){
        
            var options = this._settings.fieldInfos.param.array.options;
            var selection = this._settings.fieldInfos.param.array.selected;
            var dFragment = document.createDocumentFragment();
        
            $.each(options,function(value,option){
                var optionTpl = $("<option></option>").clone();
                $(optionTpl).attr("value",value);
                $(optionTpl).html(option);
                if(selection==value) $(optionTpl).attr("selected","selected");
                dFragment.appendChild($(optionTpl).get(0));
            });
            return dFragment;
        },
      
        render : function(){
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onrender))
                eval(this._settings.fieldInfos.param.array.onrender);
            
            return this.form;
        },
      
        parse :function(){
            var result = {
                "array":{
                    selected: $(this.form).find("select").eq(0).val()
                }
            };
            return result; 
        }
     
    });  
    /*--------------select-multiple renderType-------------------------*/  
    FormBuilder.registerRenderTypePlugin("select-multiple",{
      
        _settings : {
            emptyLabel: "Provide a label for this field"
        },
      
        _init : function(){
            var self = this;
            
            this.id = this._settings.formId+'-'+this.id; 
            var template = $('<p><label>Provide a label for this field</label><select class="available" style="width:150px;"><option>'+bb.i18n.__('parameters.add_multiple')+'</option></select><br/><select class="selected" size="4" style="width:150px;height:65px;float:left;"></select><button style="display:block;" class="bb5-button bb5-ico-arrow_n bb5-button-square"></button><button style="display:block;" class="bb5-button bb5-ico-del bb5-button-square"></button><button style="display:block;" class="bb5-button bb5-ico-arrow_s bb5-button-square"></button></p>').clone();
            $(template).attr("id",this.id).addClass('bb5-param-'+this._settings.fieldInfos.fieldLabel);
            var available = this._populateAvailable();
            var form = this._populateForm();
            var fieldLabel = (typeof this._settings.fieldInfos.param.array.label=="string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
            $(template).find("label").text(fieldLabel);
            $(template).find("select.available").append(available);
            $(template).find("select.selected").append(form);
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onchange))
                $(template).find("select").attr('onchange', this._settings.fieldInfos.param.array.onchange);
            
            this.form = template;
            this.initvalues = this._settings.fieldInfos.param.array.selected;
            for(var i=0; i<this.initvalues.length; i++) this.initvalues[i] = null;
            
            $(self.form).find("select.available").bind('change', function() {
                $(self.form).find("select.selected").append($(this).find(':selected').remove());
            });
            $(self.form).find('button.bb5-ico-del').unbind('click').bind('click', function() {
                $(self.form).find('select.available').append($(self.form).find('select.selected option:selected').remove()).get(0).selectedIndex = 0;
            });
            $(self.form).find('button.bb5-ico-arrow_n').unbind('click').bind('click', function() {
                $(self.form).find('select.selected option:selected').after($(self.form).find('select.selected option:selected').prev().remove());
            });
            $(self.form).find('button.bb5-ico-arrow_s').unbind('click').bind('click', function() {
                $(self.form).find('select.selected option:selected').before($(self.form).find('select.selected option:selected').next().remove());
            });
        },
        
        _populateForm : function(){
        
            var options = this._settings.fieldInfos.param.array.options;
            var selection = this._settings.fieldInfos.param.array.selected;
            var dFragment = document.createDocumentFragment();

            $.each(selection, function(index, value) {
                if (options[value]) {
                    var optionTpl = $("<option></option>").clone();
                    $(optionTpl).attr("value",value);
                    $(optionTpl).html(options[value]);
                    dFragment.appendChild($(optionTpl).get(0));        
                }
            });

            return dFragment;
        },
        
        _populateAvailable : function(){
        
            var options = this._settings.fieldInfos.param.array.options;
            var selection = this._settings.fieldInfos.param.array.selected;
            var dFragment = document.createDocumentFragment();

            $.each(options,function(value,option){
                if(-1 == $.inArray(value+'', selection)) {
                    var optionTpl = $("<option></option>").clone();
                    $(optionTpl).attr("value",value);
                    $(optionTpl).html(option);
                    dFragment.appendChild($(optionTpl).get(0));
                }
            });
            return dFragment;
        },
      
        render : function(){
            if ('undefined' != typeof(this._settings.fieldInfos.param.array.onrender))
                eval(this._settings.fieldInfos.param.array.onrender);
            
            return this.form;
        },
      
        parse :function(){
            var values = [];
            $(this.form).find('select.selected option').each(function() {
                values[values.length] = $(this).val();
            })
            
            var result = {
                "array":{
                    selected: $.extend(this.initvalues, values)
                }
            };
            return result; 
        }
     
    });  
    
    /*--------------keyword-selector-autocomplete renderType-------------------------*/
    FormBuilder.registerRenderTypePlugin("keyword-selector-autocomplete",{
        
        _settings : {
            selectedCtnClass : ".selectedContainer",
            keywordContainerClass:".selectedKeywords"
        },
        _init : function(){
            this.id = this._settings.formId+'-'+this.id;
            this.fieldWrapper = $("<p></p>");
            var template = $("<label class='fieldLabel'>Choisir un mot clé</label>"
                +"<p class='selectedContainer'><strong>Sélection(s) :</strong><span class='selectedKeywords'></span></p>"
                +"<textarea class='bb5-plugin-form-field fieldKeyword bb5-form-inputtext' value=''></textarea>").clone();
            this.fiedId = this.id+"-"+this._settings.fieldInfos.fieldLabel;
            this.template = this.fieldWrapper.append(template);
            this.maxEntry = this._settings.fieldInfos.param.array.maxentry || 0;
            this.keywordField = $(this.template).find(".fieldKeyword").eq(0);
            this.keywordSelectedContainer = $(this.template).find(this._settings.selectedCtnClass).eq(0);
            this.selectedContainer = $(this.template).find(this._settings.keywordContainerClass).eq(0);
            this.keywordSelectedContainer.hide();
            this.selected = this._settings.fieldInfos.param.array.selected || [];
            this.selectedItems = {};
            this.keywordsList = this._getKeywordList();
 
            this._populateKeywords();
            this._initAutoComplete();
        },
        render : function(){
            return this.template;
        },
        /* Check mandatory params*/
        _checkParams : function(){
            
        },
        _hasManyEntries : function(){
            
        },
        
        _findKeywordByIds : function(keywordIds){ 
            var keywords = [];
            var self = this;
            var nbItem = keywordIds.length;
            $.each(this.keywordsList,function(i,keyword){
                if(nbItem==0) return true;
                if($.inArray(keyword.value, keywordIds)!=-1){
                    keywords.push(keyword.label);
                    self.selectedItems[ keyword.label] = keyword.value;
                    nbItem -=1; 
                } 
            });
            return keywords;
        },
        
        _cleanSelection: function(selected){
            var selected = ($.isPlainObject(selected)) ? selected : {};
            var keywordIds = []; 
            $.each(selected, function(key,label){
                keywordIds.push(key);
            });
            return keywordIds;
        }, 
        
        /*handle multiple selections*/
        _handleKeywordSelection : function(terms,selectedKeyword){
            var self = this;
            var selectedItems = [];  
            if(this.maxEntry > 0){
                var termArr = [];
                var cmpt;
                for( cmpt = 0 ; cmpt <= this.maxEntry; cmpt++ ){
                    termArr.push(terms.pop());   
                }
                terms = termArr.reverse();
            }
            
            $.each(terms,function(i,keyword){
                var kwId = self.selectedItems[keyword];
                if(typeof kwId=="string"){
                    selectedItems.push(self.selectedItems[keyword]);
                }
            });
            this.keywordField.val(terms.join(", "));
            this.selected = selectedItems;
        },
        
        _split : function(val){
            return val.split(/,\s*/);
        },
        
        _populateKeywords : function(){
            if(!this.selected.length) return false;
            var keywordsString = "";
            var keywords = this._findKeywordByIds(this.selected);
            if(keywords.length){
                keywordsString = keywords.join(", ");
            }
            /*handle multiple selection*/
            $(this.keywordField).val(keywordsString);
            this._updateFieldSpace();
        },
        
        _updateFieldSpace: function(){
        /*$(this.keywordField).animate({
                height : "16px"
            },"fast");*/
            
        },
        _getKeywordList : function(){
            var self = this;
            var list = null;
            bb.webserviceManager.getInstance('ws_local_keyword').request('getKeywordsList', {
                params: {},
                async : false,
                useCache : true,
                success: function(response) {
                    list = response.result;
                }
            });
            return list;           
        }, 
        
        _getLast : function(term){
            return this._split(term).pop();
        },
        
        _initAutoComplete : function(){
            var self = this;
            $(this.keywordField).autocomplete({
                minLength :2,
                source : function(request, response){
                    response($.ui.autocomplete.filter(self.keywordsList,self._getLast(request.term)));
                },
                focus : function(){
                    return false;
                },
                // fix empty list etc
                select : function(event, ui){
                    var terms = self._split(this.value);
                    terms.pop();//last
                    terms.push(ui.item.label);
                    terms.push("");
                    self.selectedItems[ui.item.label] = ui.item.value;
                    self._handleKeywordSelection(terms,ui.item);
                    return false;
                }
            });
        },  
        parse : function(){
            var result = {
                "array" :{
                    selected: this.selected
                } 
            };
            return result;
        }
        
    });

     
});
  