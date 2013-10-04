bb.contentPluginsManager.registerPlugins("contentsetEdit",{
    settings:{
        title: "",
        dialogWidth: "50px",
        dialogHeight: "50px"
    },
    
    init: function(){
        this.optionDialog = null;
        this.formTemplate = $('<form class="row-fluid"><div class="span12">'
            +'<p class="row-fluid"><span class="span4"><label class="fieldLabel">Type de contenu <label></span><span class="span8"><select class="contentType" name="mode"></select></span></p>'
            +'<p class="row-fluid"><span class="span4"><label class="fieldLabel">Nombre d\'élements <label></span><span class="span8"><select class="maxentry" name="mode"></select></span></p>'
            +'</form>').clone();   
    /* si maxentry atteint grisé le bouton */
    //this.bindEvents();
    },

    showPluginOptions : function(acceptList){
        var maxentry = this.node.getMaxEntry();
        var self = this;
        var options = document.createDocumentFragment();
        
        $.each(acceptList,function(i,contentType){
            var option = $("<option/>");
            $(option).attr("value",contentType);
            $(option).text(contentType);
            options.appendChild($(option).get(0));
        });
        
        /*handle maxentry
         * si maxentry == 999
         * proposer 1 à 5
         *
         **/
        var maxentryOptions = document.createDocumentFragment();
        var max = (maxentry==9999) ? 10 : maxentry;
        for(var i=1; i <= max; i++){
            var option = $("<option/>");
            $(option).attr("value",i);
            $(option).text(i);
            maxentryOptions.appendChild($(option).get(0));
        }
        
        /*add contenttype options*/
        self.formTemplate.find(".contentType").append(options);
        /*add maxentry options*/
        self.formTemplate.find(".maxentry").append(maxentryOptions);
        
        var popupMng = bb.PopupManager.init({});
        var content = $("<div/>");
        $(content).append(this.formTemplate);
        popupMng.registerDialogType("content","<div class='bb5-ui bb5-dialog-content-plugin'></div>");
        this.optionDialog = popupMng.create("contentPlugin",{
            title: this.settings.title,
            height: this.settings.height,
            width: this.settings.width,
            position: ["center","center"],
            dialogEl: content
        }); 
        this.optionDialog.setOption("buttons",this.buildDialogActions());
        
        //this.mainDialog.setContent(content);
        this.optionDialog.show();
    },
    
    buildDialogActions : function(){
      
        var buttons = [];
        var self = this;
        var cancelButton = {
            text: "Cancel",
            click : function(){
                self.optionDialog.close();
            }
        };
      
        var confirmButton = {
            text: "Ajouter",
            click: function(){
                var selectedType = self.formTemplate.find(".contentType").eq(0).val();
                var maxentry = self.formTemplate.find(".maxentry").eq(0).val();
                if(selectedType){
                    var contents = self.createContent(selectedType,maxentry);
                    self.node.append(contents,parseInt(maxentry));
                    self.optionDialog.close();
                }
            }
        };
        
        buttons.push(confirmButton);
        buttons.push(cancelButton);
        return buttons;
    },
    
    createContent: function(contentType,nb){
        if(!contentType) return false;
        var nd = (parseInt(nb)!=0 )? nb : 1;
        var params = {
            content : []
        };
        for(var i=0; i < nd; i++){
            var contentUid = $.md5(new Date().toString() + i);
            var content = {
                uid : contentUid, 
                type:contentType
            };
            params.content.push(content);
        }
        
        return params;
    },
    
    cmdAdd: function(){      
        var accept = this.node.getAccept();
        var maxentry = this.node.getMaxEntry();
        accept = accept.split(',');
        accept.push("article");
        
        if(accept.length > 1){
            this.showPluginOptions(accept);
        }else{
            var contentInfos = this.createContent($.trim(accept));
            this.node.append(contentInfos);
        }
    },
              
    canApplyOn: function(){
        return true;
    },
              
    exposeActions : function(){
        this.createAction({
            label:"add a new item to this container", 
            icoCls : "bb5-ico-add", 
            command : this.cmdAdd
        });
    }
});  