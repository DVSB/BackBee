(function($){
    
    /*module exposes API*/
    bb.core.registerManager("content",{
        
        init : function(settings){
            settings = settings || {};
            this._settings = bb.jquery.extend({},this._settings, settings);
            this.ws = settings.ws; 
            this.hasButton = false;
            this._initContentDialog();
            this.onDeleteContent = (typeof this._settings.onDeleteContent=="function") ? this._settings.onDeleteContent : $.noop;
        },
       
        enable: function(){
            this.callSuper();  
        },
        
        
        disable: function(){
            this.callSuper();
        },
        
        _initContentDialog: function(){
            var popupMng = bb.PopupManager.init();
            if(!this.contentDialog){
                popupMng.registerDialogType("content","<div class='bb5-ui bb5-dialog-wrapper bb5-dialog-deletion'></div>");
                this.contentDialog = popupMng.create("contentpreview",{
                    title: "Delete this item?",
                    width: 278,
                    maxHeight: 360,
                    position: ["center","center"]
                });
                this.contentDialog.on("open",$.proxy(this._deleteContent,this));
            }          
        },
        
        _deleteContent : function(){
            var self = this;
            this.contentDialog.setContent("<p>Loading...</p>");
            this.ws.request("showContentsPage",{
                params: {
                    contentUid: self.content.uid,
                    contentType: self.content.type
                },
                
                success: function(response){
                    var infos = response.result; 
                    var orphanContent = $("<p>Orphaned content.</p>").clone();
                    var warning = $("<p><strong class='bb5-alert'>Warning,</strong></p><p class='bb5-alert'>are you sure you want to delete this item?</p>").clone();
                    var html = $("<div data-content-page=''><p><strong>This content is being used on the following pages :</strong></p><div class='bb5-dialog-overflow-y'><ul class='contents'></ul></div></div>").clone();
                    html = $(html).prepend(warning);
                    if(infos.pages.length){
                        $.each(infos.pages,function(i,page){
                            var title = "<li class='page-title'>"+page.title+"</li>";
                            $(html).find(".contents").append($(title));
                        });   
                    }else{
                        $(html).empty().html($(orphanContent));
                    }               
                    var deleteText = $("<p>If you delete this content, it will be erased from all the pages of this list.<br><br><strong>Do you really want to delete it ?</strong></p>").clone();
                    $(html).append(deleteText);
                    self._addButtons();
                    self.contentDialog.setContent($(html));
                },
                
                error: function(response){}
            });
        },
        
        _onDeleteContent: function(){
            this.onDeleteContent();
            this.contentDialog.close();
        },
        
        onError: function(){
            this.contentDialog.close();
        },
        
        _addButtons : function(){
            var self = this;
            if(this.hasButton) return;
            this.contentDialog.addButton({
                text: "Oui",
                click: function(e){
                    self.contentDialog.setContent("<p>Please wait while deleting the content...</p>")
                    self.ws.request("deleteContent",{
                        params:{
                            contentUid: self.content.uid,
                            contentType: self.content.type
                        },
                        success: $.proxy(self._onDeleteContent,self),
                        error: $.proxy(self.onError,self)
                    })
                }
            });
            this.contentDialog.addButton({
                text: "Non",
                click: function(){
                    self.contentDialog.close();
                }
                    
            });
            this.hasButton = true;
        }, 
        
        showDeleteDialog : function(content){
            this.content = content;
            this.contentDialog.setContent("<p>Le contenu se trouve sur la page</p>");
            this.contentDialog.open();
        },

        getExposedApi: function(){
            var self = this;
            return {
                showDeleteDialog: $.proxy(self.showDeleteDialog,self)
            }  
        }
    
    
    });
    
})(bb.jquery)
