/*LayoutToolsbar*/
BB4.ToolsbarManager.register("layouttb",{
  
    _settings : {
        toolsbarContainer : "#bb5-grid",
        zoneNameFieldId : "#bb5-zoneNameField",
        mainContainer:"#bb5-grid",
        defaultTemplateUrl :"partials/templateModels/",
        layoutEditorInfos : {
            layoutNameFieldId :"#bb5-layoutNameField",
            bbGridSlideId :"#bb5-slider-layout",
            defaultModelCtnId : ".bb5-tabdrawer-data.bb5-tplModelContainer",
            bbModelGridSlideId: "#bb5-layout-extra"
        },
        gridEditorInfos:{
            showBtnClass : ".bb5-tabdrawer-wrapper a.bb5-gridConstant",
            editorClass : ".bb5-grid-extras",
            validBtnClass : ".bb5-gridConstant-validate",
            gutterSizeBtnClass :".gutterBtn",
            colBSizeBtnClass :".gridColBtn",
            gridSizeClass : ".gridSize",
            colSizeFieldId : "#colSizeField",
            gutterSizeFieldId :"#gutterSizeField",
            showGridBtn : "#bb5-showGridBtn",
            gridSizeInfos :{
                colWidth : 30,
                gutterWidth : 10,
                stepUnit : 10
            } 
        },
        pathInfos : {
            pathCtnId : "#bb5-exam-path",
            pathItemClass :".bb5-path-item",
            pathItemActive : "bb5-exam-path-current-item",
            pathPrefix : "bb5-path-"
        } 
    },
    
    _events: {
        ".bb5-ico-clone click" : "duplicateLayout", //good
        ".bb5-ico-save click" : "saveLayout",
        ".bb5-ico-del click" : "deleteLayout",
        ".bb5-button.bb5-ico-edit-layout click" : "editLayoutName",
        
        ".bb5-ico-splitzoneh click" : "splitH", //good
        ".bb5-ico-splitzonev click" : "splitV",
        ".bb5-ico-delzone click" : "deleteItem",
        ".bb5-button.bb5-ico-edit-zone click" : "editZoneName",
        
        ".bb5-button.templateBtn click" : "templateClick",
        ".bb5-tabdrawer-toggle click" :"_showTplModel",
        ".bb5-button.tplModel click" :"modelTemplateClick",
        "#bb5-showGridBtn change" :"showGrid",
        "global event" :"templateModelClick",
      
        ".bb5-path-item click" : "selectPath"
    },
    /*renvoie les params de callback*/
    _beforeCallbacks : {
        
        "templateClick" : function(e){
            var selectedTemplateId = $(e.currentTarget).attr("id").replace("template_","");
            this._selectTemplate(selectedTemplateId);
        },
        
        "modelTemplateClick" : function(e){
            $(this.layoutEditorInfos.defaultModelCtnId).hide();
            var selectedModelId = $(e.currentTarget).attr("id").replace("template_","");
            var template = this.templateModelsContainer.get(selectedModelId);
            this._callbacks["modelTemplateClick_action"].call(this,template);
        },
        
        "deleteLayout" : function(e){
            if(!this._selectedTemplate) return;
            $(this.confirmSupprDialog.dialog).html("Are you sure you wan to delete `"+this._selectedTemplate.templateTitle+"` layout.");
            this.confirmSupprDialog.show();
        }
    },
    
    _init : function(){
        this.layoutEditorInfos = this._settings.layoutEditorInfos;
        this.gridEditorInfos = this._settings.gridEditorInfos;
        this.toolsbar = $(this._settings.mainContainer);
        this._bindPrivateEvents();
        this.availableModelFormats = ["2-1","1-2","2-2-V","2-2-H","1-1-1"];
        
        this.templateContainer = new SmartList({
            idKey:"uid", 
            onAdd : $.proxy(this._addUserTpl,this),
            onInit : $.proxy(this._buildTemplateSlide,this),
            onReplace : $.proxy(this._onReplaceTemplate,this),
            onDelete : $.proxy(this._onDelete,this)
        });
        
        this.templateModelsContainer = new SmartList({
            idKey:"uid", 
            onInit:$.proxy(this._onModelTplChanged,this)
        });
        
        this.layoutEditorDialog = null;
        this.zoneEditorDialog = null;
        this.confirmSupprDialog = null,
        this._gridDialog = null,
        this._selectedTemplate = null;
        this._initGridManager();
        this._createDialogs();
        
    },
    
    _onReplaceTemplate : function(data,listName,item){
        $("#template_"+item.uid).attr("title",item.templateTitle);
        $("#template_"+item.uid).find("i").attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+item.picpath+"?"+(new Date()).getTime()+");");
    },
     
    _onDelete : function(data,name,item) {
        //        $("#template_"+item.uid).parents(".pager").remove();
        this._selectedTemplate = null;
        this._buildTemplateSlide(this.templateContainer.dataContainer);
    },

    _buildTemplateItem : function(item){
        var templateItem = $('<li></li>').clone();    
        var btn = $("<button class='bbBtn_square templateBtn'><i></i></button>").clone();
        $(btn).attr("id","template_"+item.uid);
        $(btn).attr("title",item.templateTitle);
        $(templateItem).append(btn);
        return templateItem;
    },
     
    _buildTemplateSlide : function(templates){
        this._resetSlider();
        var self = this; 
        $.each(templates,function(key,template){
            self._onUserTplChanged(template);
        });
        
        var slider = $(this.layoutEditorInfos.bbGridSlideId).bxSlider({
            nextText:'<span><i class="visuallyhidden focusable">'+bb.i18n.__('Next')+'</i></span>',
            prevText:'<span><i class="visuallyhidden focusable">'+bb.i18n.__('Previous')+'</i></span>',
            displaySlideQty:4,
            moveSlideQty:1,
            pager:false,
            infiniteLoop : false
        });
        
        return slider;
    },
    
    _addUserTpl : function(userTemplate){
        var data = this.templateContainer.getData();
        var slider = this._buildTemplateSlide(data);
        slider.goToLastSlide();
    },
    
    
    _createDialogs : function(){
        /*template info Dialog*/
        var popupDialog = bb.PopupManager.init({});
        var self = this;
        /*layoutEditorDialog*/
        this.layoutEditorDialog = popupDialog.create("layoutEditor",{
            title:"Editer le nom du gabarit",
            buttons:{
                        
                "Enregister" :function(){
                    self.layoutEditorDialog.callbacks["save"].call(self.layoutEditorDialog);
                    return;
                },
                        
                "Annuler":function(a){
                    $(this).dialog("close");
                    return false;
                }
            }
        });
      
        /*zoneEditorDialog*/
        this.zoneEditorDialog = popupDialog.create("zoneEditor",{
            title:"Editer le nom de la zone : ",
            buttons:{
                "Enregister" : function(){
                    self.zoneEditorDialog.callbacks["save"].call(self.zoneEditorDialog);
                    return;
                },
                    
                "Annuler" :function(){
                    $(this).dialog("close");
                    return false;
                }       
            }
        });
		
        this.confirmSupprDialog = popupDialog.create("confirmDialog",{
            title:"Deleting layout",
            buttons:{
                "Delete" : function(){
                    self._callbacks["deleteLayout_action"].call(self,self._selectedTemplate);
                    $(this).dialog("close");
                    return;
                },
                
                "Cancel" :function(){
                    $(this).dialog("close");
                    return false;
                }       
            }
        });
    },
    
    _initGridManager : function(){
        var editorInfos = this._settings.gridEditorInfos;
        var self = this;
        
        $(editorInfos.showBtnClass).bind("click",function(e){
            $(editorInfos.editorClass).show();
            e.stopPropagation();
        });
        
        $(editorInfos.validBtnClass).bind("click",function(e){
            $(editorInfos.editorClass).hide();
            
            var sizeO = {
                colWidth:"",
                gutterWidth:""
            };
            sizeO.colWidth = self._colWidth();
            sizeO.gutterWidth = self._gutterWidth();
            if(typeof self._callbacks["changeGridSize_action"] !== "function") return;
            self._callbacks["changeGridSize_action"].call(this,sizeO);
        });
        
        /*change col and gutter*/
        $(editorInfos.gutterSizeBtnClass).bind("click",function(e){
            var action = ($(e.currentTarget).hasClass("upAction")) ? "up" : "down";
            
            if(action=="up"){
                var newValue = self._gutterWidth() + editorInfos.gridSizeInfos.stepUnit;
                self._gutterWidth(newValue);
            }
           
            if(action=="down"){
                var newValue = self._gutterWidth() - editorInfos.gridSizeInfos.stepUnit;
                if(newValue < editorInfos.gridSizeInfos.stepUnit) return false;
                self._gutterWidth(newValue);
            }
            return false;
        });
          
        /*change col and gutter*/
        $(editorInfos.colBSizeBtnClass).bind("click",function(e){
            var action = ($(e.currentTarget).hasClass("upAction")) ? "up" : "down";
            
            if(action=="up"){
                var newValue = self._colWidth() + editorInfos.gridSizeInfos.stepUnit;
                self._colWidth(newValue);
            }
            
            if(action=="down"){
                var newValue = self._colWidth() - editorInfos.gridSizeInfos.stepUnit;
                if(newValue < editorInfos.gridSizeInfos.stepUnit) return false;
                self._colWidth(newValue);
            }
            return false;
        });
        
        /*delegate click on editor*/
        $(editorInfos.editorClass).bind("click",function(e){
            //e.stopPropagation();
            });    
    },
    
    
    /*Gutter size*/
    _colWidth : function(newVal) {
        var result = false;
        var editorInfos = this._settings.gridEditorInfos;
        var newVal = (!newVal) ? "none" : parseInt(newVal);
            
        if(newVal=="none") {
            result = editorInfos.gridSizeInfos.colWidth; 
        }else{
            $(editorInfos.colSizeFieldId).val(newVal);
            editorInfos.gridSizeInfos.colWidth = newVal;
            result = newVal;
        }  
        return result;
    },
        
    _gutterWidth : function(newVal){
        var result = false;
        var editorInfos = this._settings.gridEditorInfos;
        var newVal = (!newVal) ? "none" : parseInt(newVal); 
            
        if(newVal=="none"){
            result = editorInfos.gridSizeInfos.gutterWidth; 
        }else{
            $(editorInfos.gutterSizeFieldId).val(newVal);
            editorInfos.gridSizeInfos.gutterWidth = newVal;
            result = newVal; 
        }
        return result;
    },
    
   
    _bindPrivateEvents : function() {
        var self = this;
        this._callbacks["_showTplModel_action"] = this._showTplModel;
        $(this._settings.pathInfos.pathCtnId).delegate(this._settings.pathInfos.pathItemClass,"click",function(e){
            //var nodeId = ($(e.currentTarget).attr("path-data-uid"))? $(e.currentTarget).attr("path-data-uid") : null;
            self._callbacks["selectPath_action"].call(self,e);
        });
        
        $(document).bind('click', function(e) {
            $('.bb5-grid-extras').hide();
        });
        
    },
    
    _selectTemplate : function(templateId){
        var template = null;
        if (template = this.templateContainer.get(templateId)) {
            this._callbacks["templateClick_action"].call(this,template);
            this._selectedTemplate = template;
            this.updateCurrentTemplateTitle(template.templateTitle);
            $(this._settings.layoutEditorInfos.bbGridSlideId+' button').removeClass('bb5-layout-selected');
            $('li.pager #template_'+templateId).addClass('bb5-layout-selected');
        }
    },
	
    _selectLastTemplate: function() {
        $(this._settings.layoutEditorInfos.bbGridSlideId).find('.bbBtn_square.templateBtn').last().trigger('click');
    },
	
    enableZoneProperties : function(data) {
        this.setZoneName(data.selectedLayout.getTitle());
    },
    
    disableZoneProperties : function() {
        this.setZoneName("");
    },
    
    setZoneName : function(zoneName){
        var zoneName = zoneName || "";
        $(this._settings.zoneNameFieldId).val(zoneName);
    },
    
    getlayoutEditorDialog : function(){
        return this.layoutEditorDialog;
    },  
    
    getZoneEditorDialog : function(){
        return this.zoneEditorDialog;
    },
    
    updatePath : function(layoutItem){
        var pathToUpdate = "#"+this._settings.pathInfos.pathPrefix+layoutItem.getId();
        $(pathToUpdate).html(layoutItem.getTitle());
        return false;
    },
    
    showLayoutPath : function(data){
        var data = data || [];
        if(data.length == 0) return false;
        var pathFragment = document.createDocumentFragment();
        var layoutClass = data[0]._settings.layoutClass;
        var self = this;
        $.each(data,function(i,layoutItem){
            var pathHtml = $("<a></a>").clone();
            $(pathHtml).addClass(self._settings.pathInfos.pathItemClass.replace(".",''));
            $(pathHtml).html(layoutItem.getTitle());
          
            /*last layout*/
            if(i+1 == data.length) {
                $(pathHtml).addClass(self._settings.pathInfos.pathItemActive);
            }
            $(pathHtml).attr("id",self._settings.pathInfos.pathPrefix+layoutItem.getId()); 
            pathFragment.appendChild($(pathHtml).get(0));
        /*append path*/
        });
        $(this._settings.pathInfos.pathCtnId).html($(pathFragment));
        $("#bbPathWrapper").slideDown();
    },
	
    removeTempTemplate: function() {
        for(templateId in this.templateContainer.dataContainer) {
            if (-1 != templateId.indexOf('Layout_')) {
                this.templateContainer.deleteItem(this.templateContainer.get(templateId));
                this._buildTemplateSlide(this.templateContainer.dataContainer);
            }
        }
    },
	
    _showTplModel : function(e){
        $(this.layoutEditorInfos.defaultModelCtnId).show();
        return false;
    },
    
    
    /*user Template*/
    _onUserTplChanged : function(item){
        var templateItem = $('<li></li>').clone();    
        var btn = $("<button class='bb5-button bb5-button-bulky bb5-layout-choice-x templateBtn'><i></i></button>").clone();
        $(btn).attr("id","template_"+item.uid);
        $(btn).attr("title",item.templateTitle);
        $('i', $(btn)).attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+item.picpath+"?"+(new Date()).getTime()+");");
        $(templateItem).append(btn);
        $(this.layoutEditorInfos.bbGridSlideId).append(templateItem);
    },
    
    /*model template*/
    _onModelTplChanged : function(data){
        var modelTemplates = document.createDocumentFragment();
        $.each(data,function(i,item){
            var templateItem = $("<li></li>").clone();
            var btn = $("<label><button class='bb5-button bb5-button-bulky bb5-layout-choice-x tplModel'><i></i></button></label>").clone();
            $(btn).find("button").attr("id","template_"+item.uid);
            $(btn).find("button").attr("title",item.templateTitle);
            $('i', $(btn)).attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+item.picpath+"?"+(new Date()).getTime()+");");
            var templateName = $("<span>"+item.templateTitle+"</span>").clone();
            $(templateName).appendTo($(btn).find("label"));
            $(btn).find("button").after(templateName);
            
            $(templateItem).append(btn);
            
            modelTemplates.appendChild($(templateItem).get(0));
        }); 
        $(this.layoutEditorInfos.bbModelGridSlideId).append(modelTemplates);
    },
    
    
    /*contruire les templates disponibles*/
    setTemplatesUser : function(templates){
        this.templateContainer.setData(templates);
    },
    
    //class="bbGridSlide"
    _resetSlider : function(){
        var sliderTemplate = $('<div id="bb5-use-template-wrapper" class="bb5-tabarea_content">'
            +'<div class="bb5-slider-layout-wrapper slider-fx">'
            +'<ul id="bb5-slider-layout"></ul>'
            +'</div>'
            +'</div>');
                    
        var slider = $(sliderTemplate).clone();
        $("#bb5-use-template-wrapper").replaceWith(slider);
    },
    
    /*addUserTemplate*/
    addUserTemplate : function(addedTemplate){
        this.templateContainer.addData(addedTemplate);
    /*build slide again*/
    },

    handleUserTemplateAction :function(template,action){
        if(action=="creation"){
            this.removeTempTemplate();
            this.templateContainer.addData([template]);
            this._selectedTemplate = template;
        }
 
        if(action=="edit"){
            this.templateContainer.replaceItem(template);  
        }
        /* force template selection template */
        if("uid" in template){
          this._selectTemplate(template.uid); 
        }
    },
    
    deleteCurrentTemplate : function(){
        if(this._selectedTemplate){
            this.templateContainer.deleteItem(this._selectedTemplate);
            this._selectLastTemplate();
        }
    },
    
    setTemplateModels : function(data){
        this.templateModelsContainer.setData(data);
    },
    
    setAppTemplates : function(templateData, tmplType, currentTemplateId){
        var tmplType = tmplType || "userTpl";
        if("userTpl" == tmplType){
            this.setTemplatesUser(templateData);
            this._selectTemplate(currentTemplateId);
        }
        if("genTpl"==tmplType){
            this.setTemplatesUser([]); 
        }
		
        var data = this.templateContainer.getData();
        var slider = this._buildTemplateSlide(data);
		
    /*recréer le scroll*/
    /*
        $(this.layoutEditorInfos.bbGridSlideId).bxSlider({
            nextText:'<span><i class="visuallyhidden focusable">Suivant</i></span>',
            prevText:'<span><i class="visuallyhidden focusable">Précédent</i></span>',
            displaySlideQty:4,
            moveSlideQty:1,
            pager:false,
            infiniteLoop : false
        });
		*/
    },
    
    setGridSize : function(gridSize){
        $.extend(true,this.gridEditorInfos.gridSizeInfos,gridSize);
        this._colWidth(gridSize.colWidth);
        this._gutterWidth(gridSize.gutterWidth);
        /*update grid infos*/
        var widthSize = (gridSize.nbColumns * gridSize.colWidth) + (gridSize.gutterWidth * (gridSize.nbColumns - 1)) + (2*gridSize.gutterWidth);
        var widthSize = 960;
        $(this.toolsbar).find(this.gridEditorInfos.gridSizeClass).html($("<strong>"+widthSize+"</strong>"));
    },
    
    updateCurrentTemplateTitle : function(newTitle){
        var layoutInfos = this._settings.layoutEditorInfos;
        var newTitle = newTitle || "";
        $(layoutInfos.layoutNameFieldId).val(newTitle);
        return false;
    },
   
   
   
    selectGridBtn : function(){
        $(this._settings.gridEditorInfos.showGridBtn).attr("checked","checked");
    }
   
   
    
   
    
    
});