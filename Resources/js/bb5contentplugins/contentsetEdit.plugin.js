bb.contentPluginsManager.registerPlugins("contentsetEdit",{
    settings:{
        title: "Edit this content block",
        dialogWidth: "50px",
        dialogHeight: "50px"
    },
    
    init: function(){
        this.optionDialog = null;
        this.maxentryMsg = $("<p><strong>No more content can be added to this  container!</strong></p>");
        this.formTemplate = $('<form class="row-fluid"><div class="span12">'
            +'<p class="row-fluid"><span class="span4"><label class="fieldLabel">Type de contenu <label></span><span class="span8"><select class="contentType" name="mode"></select></span></p>'
            +'<p class="row-fluid"><span class="span4"><label class="fieldLabel">Nombre d\'élements <label></span><span class="span8"><select class="maxentry" name="mode"></select></span></p>'
            +'</form>').clone();   
    /* si maxentry atteint grisé le bouton */
    /* handle change on content too like change on content*/
    //this.bindEvents();
    },
    
    onContentChange : function(){
       console.log("");
    },
    
    _getAllowedNbItems: function(){
        var maxentry = 5;//this.node.getMaxEntry();
        var nbContent = this.node.getNbContent();
        var allowedMaxentry = ( maxentry == 9999 ) ? maxentry : maxentry - nbContent;
        return allowedMaxentry;

    },
    
    showPluginOptions : function(acceptList){
        
        this.allowedMaxentry = (this._getAllowedNbItems() == 9999) ? 10 : this._getAllowedNbItems();
        var self = this;
        /* clean options */
        $(this.formTemplate).find(".contentType").empty();
        $(this.formTemplate).find(".maxentry").empty();
        /* build */
        var options = document.createDocumentFragment();
        $.each(acceptList,function(i,contentType){
            var option = $("<option/>");
            $(option).attr("value",contentType);
            $(option).text(contentType);
            options.appendChild($(option).get(0));
        });
        
        var maxentryOptions = document.createDocumentFragment();
        
        for(var i=1; i <= this.allowedMaxentry; i++){
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
        this.content = content; 
        /* create dialog up */
        if(!this.optionDialog){
            popupMng.registerDialogType("content-plugin","<div class='bb5-ui bb5-dialog-content-plugin'></div>");
            this.optionDialog = popupMng.create("contentPlugin",{
                title: this.settings.title,
                height: this.settings.height,
                width: this.settings.width,
                position: ["center","center"]
            //dialogEl: content
            }); 
            this.optionDialog.on("open",$.proxy(this._showDialog,this));
        }
        this.optionDialog.show();
    },
    
    _showDialog : function(e){
        var dialogContent = ( this.allowedMaxentry != 0 ) ? this.content : this.maxentryMsg;
        this.optionDialog.setContent(dialogContent);
        var disableAdd = ( this.allowedMaxentry == 0 ) ? true : false;
        this.optionDialog.setOption("buttons",this.buildDialogActions(disableAdd));
    }, 
    
    buildDialogActions : function(disableAdd){
        var disableAdd = ( typeof disableAdd == "boolean" ) ? disableAdd : false;
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
        
        /* reset dialog actions*/
        if(disableAdd){
            buttons  = [];
            var okButton = {
                text:"Ok",
                click:function(){
                    self.optionDialog.close();
                }
            };
            buttons.push(okButton);
        }
        return buttons;
    },
    
    createContent: function(contentType,nb){
        if(!contentType) return false;
        var nb = nb || 1;
        var nb = ( parseInt(nb) !=0)? nb : 1;
        var params = {
            content : []
        };
        for(var i=0; i < nb; i++){
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
        var allowedMaxEntry = this._getAllowedNbItems();
        accept = accept.split(',');
        /*n'afficher le dialog des options que si accept > 1 */
        if(accept.length > 1){
            this.showPluginOptions(accept);
        }else{
            if(allowedMaxEntry){
                var contentInfos = this.createContent($.trim(accept[0]));
                this.node.append(contentInfos);
            }
            
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