/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


(function($){
    
    bb.core.registerManager("contentpreview",{
        
        init: function(settings){
            this.ws = settings.ws;
            this.previewDialog = null;
            this._initPreviewDialog();
            this.isLoaded = true; 
        },
        
        _initPreviewDialog: function(){
            var popupMng = bb.PopupManager.init();
            if(!this.previewDialog){
                popupMng.registerDialogType("contentpreview","<div class='bb5-ui bb5-dialog-content-preview'></div>");
                this.previewDialog = popupMng.create("contentpreview",{
                    title: "Content's Preview",
                    width: (bb.jquery(window).innerWidth()/2),
                    height: (bb.jquery(window).innerHeight() - 50),
                    position: ["center","center"]
                });
                this.previewDialog.on("open",$.proxy(this._loadContentPreview,this));
            }
        },
        
        _loadContentPreview: function(){
            $(".bb5-dialog-content-preview").css("padding","5px");
            this.previewDialog.setContent("<p><strong>Loading...</strong></p>");
            var self = this;
            this.ws.request("showPreview", {
                params: {
                    contentUid : this.contentUid,
                    contentType: this.contentType
                },
                success : function(response){
                    var content = self._cleanContent(response.result);  
                    self.previewDialog.setContent(content);
                },
                error: function(){
                    self.previewDialog.setContent("<p><strong>Erreur while loadind preview...</strong></p>");
                }
            });
        },
        
        showPreview: function(contentUid,contentType){
            this.contentUid = contentUid;
            this.contentType = contentType;
            this.previewDialog.open();
        },
        
        _cleanContent : function(content){
            content = $(content)
            .find("*")
            .removeClass("contentAloha aloha-editable aloha-editable-active")
            .removeAttr("contenteditable");      
            return content;
        },
        
        enable: function(){
            this.callSuper();
        },
        
        disable: function(){
            this.callSuper();
        },
        
        getExposedApi: function(){
            var self = this;
            return {
                showPreview : $.proxy(self.showPreview, this)
            }
        }
        
    });
    
})(bb.jquery)