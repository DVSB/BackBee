/*LayoutToolsbar*/

(function($) {

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
            var selectedTemplateId = bb.jquery(e.currentTarget).attr("id").replace("template_","");
            this._selectTemplate(selectedTemplateId);
        },
        
        "modelTemplateClick" : function(e){
            bb.jquery(this.layoutEditorInfos.defaultModelCtnId).hide();
            var selectedModelId = bb.jquery(e.currentTarget).attr("id").replace("template_","");
            var template = this.templateModelsContainer.get(selectedModelId);
            this._callbacks["modelTemplateClick_action"].call(this,template);
        },
        
        "deleteLayout" : function(e){
            if(!this._selectedTemplate) return;
            bb.jquery(this.confirmSupprDialog.dialog).html("Are you sure you wan to delete `"+this._selectedTemplate.templateTitle+"` layout.");
            this.confirmSupprDialog.show();
        }
    },
    
    _init : function(){
        this.layoutEditorInfos = this._settings.layoutEditorInfos;
        this.gridEditorInfos = this._settings.gridEditorInfos;
        this.toolsbar = bb.jquery(this._settings.mainContainer);
        this._bindPrivateEvents();
        this.availableModelFormats = ["2-1","1-2","2-2-V","2-2-H","1-1-1"];
        
        this.templateContainer = new SmartList({
            idKey:"uid", 
            onAdd : bb.jquery.proxy(this._addUserTpl,this),
            onInit : bb.jquery.proxy(this._buildTemplateSlide,this),
            onReplace : bb.jquery.proxy(this._onReplaceTemplate,this),
            onDelete : bb.jquery.proxy(this._onDelete,this)
        });
        
        this.templateModelsContainer = new SmartList({
            idKey:"uid", 
            onInit:bb.jquery.proxy(this._onModelTplChanged,this)
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
        bb.jquery("#template_"+item.uid).attr("title",item.templateTitle);
        bb.jquery("#template_"+item.uid).find("i").attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+item.picpath+"?"+(new Date()).getTime()+");");
    },
     
    _onDelete : function(data,name,item) {
        //        bb.jquery("#template_"+item.uid).parents(".pager").remove();
        this._selectedTemplate = null;
        this._buildTemplateSlide(this.templateContainer.dataContainer);
    },

    _buildTemplateItem : function(item){
        var templateItem = bb.jquery('<li></li>').clone();    
        var btn = bb.jquery("<button class='bbBtn_square templateBtn'><i></i></button>").clone();
        bb.jquery(btn).attr("id","template_"+item.uid);
        bb.jquery(btn).attr("title",item.templateTitle);
        bb.jquery(templateItem).append(btn);
        return templateItem;
    },
     
    _buildTemplateSlide : function(templates){
        this._resetSlider();
        var self = this; 
        bb.jquery.each(templates,function(key,template){
            self._onUserTplChanged(template);
        });
        
        var slider = bb.jquery(this.layoutEditorInfos.bbGridSlideId).bxSlider({
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
                    bb.jquery(this).dialog("close");
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
                    bb.jquery(this).dialog("close");
                    return false;
                }       
            }
        });
		
        this.confirmSupprDialog = popupDialog.create("confirmDialog",{
            title:"Deleting layout",
            buttons:{
                "Delete" : function(){
                    self._callbacks["deleteLayout_action"].call(self,self._selectedTemplate);
                    bb.jquery(this).dialog("close");
                    return;
                },
                
                "Cancel" :function(){
                    bb.jquery(this).dialog("close");
                    return false;
                }       
            }
        });
    },
    
    _initGridManager : function(){
        var editorInfos = this._settings.gridEditorInfos;
        var self = this;
        
        bb.jquery(editorInfos.showBtnClass).bind("click",function(e){
            bb.jquery(editorInfos.editorClass).show();
            e.stopPropagation();
        });
        
        bb.jquery(editorInfos.validBtnClass).bind("click",function(e){
            bb.jquery(editorInfos.editorClass).hide();
            
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
        bb.jquery(editorInfos.gutterSizeBtnClass).bind("click",function(e){
            var action = (bb.jquery(e.currentTarget).hasClass("upAction")) ? "up" : "down";
            
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
        bb.jquery(editorInfos.colBSizeBtnClass).bind("click",function(e){
            var action = (bb.jquery(e.currentTarget).hasClass("upAction")) ? "up" : "down";
            
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
        bb.jquery(editorInfos.editorClass).bind("click",function(e){
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
            bb.jquery(editorInfos.colSizeFieldId).val(newVal);
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
            bb.jquery(editorInfos.gutterSizeFieldId).val(newVal);
            editorInfos.gridSizeInfos.gutterWidth = newVal;
            result = newVal; 
        }
        return result;
    },
    
   
    _bindPrivateEvents : function() {
        var self = this;
        this._callbacks["_showTplModel_action"] = this._showTplModel;
        bb.jquery(this._settings.pathInfos.pathCtnId).delegate(this._settings.pathInfos.pathItemClass,"click",function(e){
            //var nodeId = (bb.jquery(e.currentTarget).attr("path-data-uid"))? bb.jquery(e.currentTarget).attr("path-data-uid") : null;
            self._callbacks["selectPath_action"].call(self,e);
        });
        
        bb.jquery(document).bind('click', function(e) {
            bb.jquery('.bb5-grid-extras').hide();
        });
        
    },
    
    _selectTemplate : function(templateId){
        var template = null;
        if (template = this.templateContainer.get(templateId)) {
            this._callbacks["templateClick_action"].call(this,template);
            this._selectedTemplate = template;
            this.updateCurrentTemplateTitle(template.templateTitle);
            bb.jquery(this._settings.layoutEditorInfos.bbGridSlideId+' button').removeClass('bb5-layout-selected');
            bb.jquery('li.pager #template_'+templateId).addClass('bb5-layout-selected');
        }
    },
	
    _selectLastTemplate: function() {
        bb.jquery(this._settings.layoutEditorInfos.bbGridSlideId).find('.bbBtn_square.templateBtn').last().trigger('click');
    },
	
    enableZoneProperties : function(data) {
        this.setZoneName(data.selectedLayout.getTitle());
    },
    
    disableZoneProperties : function() {
        this.setZoneName("");
    },
    
    setZoneName : function(zoneName){
        var zoneName = zoneName || "";
        bb.jquery(this._settings.zoneNameFieldId).val(zoneName);
    },
    
    getlayoutEditorDialog : function(){
        return this.layoutEditorDialog;
    },  
    
    getZoneEditorDialog : function(){
        return this.zoneEditorDialog;
    },
    
    updatePath : function(layoutItem){
        var pathToUpdate = "#"+this._settings.pathInfos.pathPrefix+layoutItem.getId();
        bb.jquery(pathToUpdate).html(layoutItem.getTitle());
        return false;
    },
    
    showLayoutPath : function(data){
        var data = data || [];
        if(data.length == 0) return false;
        var pathFragment = document.createDocumentFragment();
        var layoutClass = data[0]._settings.layoutClass;
        var self = this;
        bb.jquery.each(data,function(i,layoutItem){
            var pathHtml = bb.jquery("<a></a>").clone();
            bb.jquery(pathHtml).addClass(self._settings.pathInfos.pathItemClass.replace(".",''));
            bb.jquery(pathHtml).html(layoutItem.getTitle());
          
            /*last layout*/
            if(i+1 == data.length) {
                bb.jquery(pathHtml).addClass(self._settings.pathInfos.pathItemActive);
            }
            bb.jquery(pathHtml).attr("id",self._settings.pathInfos.pathPrefix+layoutItem.getId()); 
            pathFragment.appendChild(bb.jquery(pathHtml).get(0));
        /*append path*/
        });
        bb.jquery(this._settings.pathInfos.pathCtnId).html(bb.jquery(pathFragment));
        bb.jquery("#bbPathWrapper").slideDown();
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
        bb.jquery(this.layoutEditorInfos.defaultModelCtnId).show();
        return false;
    },
    
    
    /*user Template*/
    _onUserTplChanged : function(item){
        var templateItem = bb.jquery('<li></li>').clone();    
        var btn = bb.jquery("<button class='bb5-button bb5-button-bulky bb5-layout-choice-x templateBtn'><i></i></button>").clone();
        bb.jquery(btn).attr("id","template_"+item.uid);
        bb.jquery(btn).attr("title",item.templateTitle);
        bb.jquery('i', bb.jquery(btn)).attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+item.picpath+"?"+(new Date()).getTime()+");");
        bb.jquery(templateItem).append(btn);
        bb.jquery(this.layoutEditorInfos.bbGridSlideId).append(templateItem);
    },
    
    /*model template*/
    _onModelTplChanged : function(data){
        var modelTemplates = document.createDocumentFragment();
        bb.jquery.each(data,function(i,item){
            var templateItem = bb.jquery("<li></li>").clone();
            var btn = bb.jquery("<label><button class='bb5-button bb5-button-bulky bb5-layout-choice-x tplModel'><i></i></button></label>").clone();
            bb.jquery(btn).find("button").attr("id","template_"+item.uid);
            bb.jquery(btn).find("button").attr("title",item.templateTitle);
            bb.jquery('i', bb.jquery(btn)).attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+item.picpath+"?"+(new Date()).getTime()+");");
            var templateName = bb.jquery("<span>"+item.templateTitle+"</span>").clone();
            bb.jquery(templateName).appendTo(bb.jquery(btn).find("label"));
            bb.jquery(btn).find("button").after(templateName);
            
            bb.jquery(templateItem).append(btn);
            
            modelTemplates.appendChild(bb.jquery(templateItem).get(0));
        }); 
        bb.jquery(this.layoutEditorInfos.bbModelGridSlideId).append(modelTemplates);
    },
    
    
    /*contruire les templates disponibles*/
    setTemplatesUser : function(templates){
        this.templateContainer.setData(templates);
    },
    
    //class="bbGridSlide"
    _resetSlider : function(){
        var sliderTemplate = bb.jquery('<div id="bb5-use-template-wrapper" class="bb5-tabarea_content">'
            +'<div class="bb5-slider-layout-wrapper slider-fx">'
            +'<ul id="bb5-slider-layout"></ul>'
            +'</div>'
            +'</div>');
                    
        var slider = bb.jquery(sliderTemplate).clone();
        bb.jquery("#bb5-use-template-wrapper").replaceWith(slider);
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
        bb.jquery(this.layoutEditorInfos.bbGridSlideId).bxSlider({
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
        bb.jquery.extend(true,this.gridEditorInfos.gridSizeInfos,gridSize);
        this._colWidth(gridSize.colWidth);
        this._gutterWidth(gridSize.gutterWidth);
        /*update grid infos*/
        var widthSize = (gridSize.nbColumns * gridSize.colWidth) + (gridSize.gutterWidth * (gridSize.nbColumns - 1)) + (2*gridSize.gutterWidth);
        var widthSize = 960;
        bb.jquery(this.toolsbar).find(this.gridEditorInfos.gridSizeClass).html(bb.jquery("<strong>"+widthSize+"</strong>"));
    },
    
    updateCurrentTemplateTitle : function(newTitle){
        var layoutInfos = this._settings.layoutEditorInfos;
        var newTitle = newTitle || "";
        bb.jquery(layoutInfos.layoutNameFieldId).val(newTitle);
        return false;
    },
   
   
   
    selectGridBtn : function(){
        bb.jquery(this._settings.gridEditorInfos.showGridBtn).attr("checked","checked");
    }
    
});

}) (bb.jquery);
