define(['aloha','aloha/plugin','aloha/pluginmanager','aloha/jquery','ui/scopes','ui/ui','PubSub','ui/button'],function(Aloha,Plugin,PluginMng,jQuery,Scopes,Ui,PubSub,Button){
    
    
    
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
                    draggable: false,
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
        
        isAnImage : function(media){
            var result = false;
            if(media.data.type && media.data.type=="BackBuilder\\ClassContent\\Media\\image"){
                result = true;
            }
            return result;
        },
        
        simulateClick: function(img){
            $(this.editable).find(img).trigger("click");
            if(img) $(this.editable).find(img).eq(0).triggerHandler("click");
        },
        
        _clearFields : function(){
            var widthField = jQuery("#" + this.imagePlugin.ui.imgResizeWidthField.getInputId());
            var heightField = jQuery("#" + this.imagePlugin.ui.imgResizeHeightField.getInputId());
            var titleField = jQuery("#"+this.imagePlugin.ui.imgTitleField.getInputId())
            titleField.val("");
            widthField.val("");
            heightField.val("");
            this.imagePlugin.ui.imgSrcField.setValue("");
        },
        
        onMediaSelection : function(data){
            if(!this.isAnImage(data)){console.warn("Please add a valid image!"); return;}
            
            var self = this;
            this.imagePlugin.ui.imgSrcField.setValue(data.value);
            var elem = this.imagePlugin.ui.imgTitleField.getInputElem();
            var target = this.imagePlugin.ui.imgTitleField.getTargetObject();
            if(target){
                this.imagePlugin.ui.imgTitleField.setValue(data.title);
                $(target).attr("title",data.title);
                
                /* reset width and height */
                var $wrapper = this.imagePlugin.imageObj.closest('.Aloha_Image_Resize');
                /* clean size */
                var widthField = jQuery("#" + this.imagePlugin.ui.imgResizeWidthField.getInputId());
                var heightField = jQuery("#" + this.imagePlugin.ui.imgResizeHeightField.getInputId());
                widthField.val("");
                heightField.val("");
                $(target).css("height","");
                $(target).css("width","");
                $($wrapper).css("height","");
                $($wrapper).css("width","");
                this.imagePlugin.srcChange();
                $(target).bind("load", function(){
                    var imgHeight = $(this).height();
                    var imgWidth = $(this).width();
                    $(target).css("height",imgHeight+"px");
                    $(target).css("width",imgWidth+"px");
                    $($wrapper).css("height",imgHeight+"px");
                    $($wrapper).css("width",imgWidth+"px");
                    heightField.val(imgHeight);
                    widthField.val(imgWidth);
                    self.editable.activate();
                });
                
                /*handle error*/
                $(target).bind("error", function(){
                    console.warn("Image can't be loaded");
                });
            }
        },
        
        cleanImage:function(){
            if(!this.imagePlugin) return false;
            var currentImage = this.imagePlugin.getPluginFocus();
            if(currentImage){
                this.imagePlugin.endResize();
            }
        },
        removeImage: function(){
            var currentImg = this.imagePlugin.ui.imgSrcField.getTargetObject();
            if(currentImg){
                var parent = $(currentImg).parents(".aloha-image-box-active");
                if(parent){
                    $(parent).remove(); 
                    this._clearFields();
                    Scopes.setScope("Aloha.continuoustext");
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
            
            /* Image must be clean before his parent get updated */
            Aloha.bind("aloha-editable-deactivated", function(){
                Delegate.cleanImage();
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
                icon : "aloha-icon-eraser",
                scope: "Aloha.continoustext",
                click: function(){
                    Delegate.removeImage();
                }
            });
        /*hidden by default*/
        // this.removeBtn.hide();
        },
        setScope: function(){
            Scopes.createScope('bbimage', 'Aloha.empty');
        }
            
        
    });
})