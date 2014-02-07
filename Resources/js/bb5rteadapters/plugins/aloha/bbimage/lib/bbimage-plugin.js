define(['aloha','aloha/plugin','aloha/pluginmanager','aloha/jquery','ui/scopes','ui/ui','PubSub','ui/button','ui/toggleButton'],function(Aloha,Plugin,PluginMng,jQuery,Scopes,Ui,PubSub,Button,ToggleButton){
    
    
    
    var Delegate = {
        mediaBrowser : null,
        imagePlugin: PluginMng.plugins["image"],
        editable: null,
        showBrowser: function(){
            var $ = bb.jquery;
            var self = this;
            if(!this.mediaBrowser){
                var mediaBrowser = jQuery("<div id='bb5-aloha-plugin-mediaBrowser'/>");
                $("body").append(mediaBrowser);
                $(mediaBrowser).bbSelector({
                    popup: true,
                    mediaSelector: true,
                    contentSelector: false,
                    pageSelector: false,
                    linkSelector: false,
                    resizable: false,
                    selectorTitle: "Choose an image",
                    /* callback: function(){
                         bb.jquery('#bb5-aloha-plugin-mediaBrowser').bbSelector('close');
                    },*/
                    beforeWidgetInit : function(){
                        var bbSelector = bb.jquery(this.element).data('bbSelector');
                        bbSelector.onWidgetInit(bbSelector._panel.MEDIA_LINK, function () {
                            var bbMediaSelector = bb.jquery(this).data('bbMediaSelector') || false;
                            if(bbMediaSelector){
                                bbMediaSelector.setCallback(function(params) {
                                    self.onMediaSelection(params);
                                    bbSelector.close();
                                });
                            }
                        }); 
                    }
                });
                this.mediaBrowser = $(mediaBrowser).data("bbSelector");
            }
            this.mediaBrowser.open();
        },
        
        setCurrentEditable: function(editable){
            this.editable = editable; 
        },
        
        simulateClick: function(img){
            if(img) $(this.editable).find(img).eq(0).triggerHandler("click");
        },
        
        onMediaSelection : function(data){
            this.imagePlugin.ui.imgSrcField.setValue(data.value);
            var elem = this.imagePlugin.ui.imgTitleField.getInputElem();
            var target = this.imagePlugin.ui.imgTitleField.getTargetObject();
            if(target){
                this.imagePlugin.ui.imgTitleField.setValue(data.title);
                $(target).attr("title",data.title);
                this.editable.activate();
                this.imagePlugin.srcChange();
            }
        },
        
        removeImage: function(){
            var currentImg = this.imagePlugin.ui.imgSrcField.getTargetObject();
            if(currentImg){
                var parent = $(currentImg).parents(".aloha-image-box-active");
                if(parent){
                    $(parent).remove();  
                }
            }
        }
        
    }
    
    Plugin.create("bbimageBrowser",{
        dependencies:["image"],  
        init : function(){
            Scopes.createScope("bbimage", 'Aloha.empty');
            this.mediaBrowserBtn = null;
            this.removeBtn = null; 
            this._createMediaBrowserBtn();
            this._bindAlohaEvents();
        },
        
        _bindAlohaEvents: function(){
            var self = this;
            Aloha.bind("aloha-editable-activated",function(e,editable){
                Delegate.setCurrentEditable(editable.editable);
                self.removeBtn.show();
            });
            Aloha.bind("aloha-image-unselected",function(){
                self.removeBtn.hide();
            });
        },
        
        _createMediaBrowserBtn: function(){
            
            this.mediaBrowserBtn = Ui.adopt("imageBrowser", Button, {
                tooltip : "Show the media browser",
                icon: "aloha-icon-tree",
                scope: 'Aloha.continuoustext',
                click: function() {
                    Delegate.showBrowser();
                }
            });
            
            this.removeBtn = Ui.adopt("removeImage", Button, {
                tooltip: "Remove image",
                icon : "bb5-button bb5-ico-del",
                scope: "Aloha.continoustext",
                click: function(){
                    Delegate.removeImage();
                }
            });
            /*hidden by default*/
            this.removeBtn.hide();
        },
        setScope: function(){
            Scopes.createScope('bbimage', 'Aloha.empty');
        }
            
        
    });
})