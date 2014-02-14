/* bb-link */
/* Prevent aloha from editing some preserved markup */
define(["aloha","aloha/plugin",'aloha/pluginmanager','ui/ui','ui/button','aloha/jquery'], function(Aloha,Plugin,PluginManager,Ui,Button,jQuery){
    'use strict';
    /* the $ is from bb jquery
     * we can't use aloha/jquery as it doesn't have bb.ui plugins
     * */
    return Plugin.create("linkbb",{
       
        /* add link btn */
        init: function(){
            var self = this;
            this._createbbButton();
            this._createFields();
            this.linkPlugin = PluginManager.plugins["link"];
            this.linkSelector = null;
            this.currentEditable = null;
            this.savedRange = null;
            this._bindEvents();
            this.linkSelectorIsVisible = false;
            this.iLinks = {};
        },
        
        _createFields : function(){
            
        },
        
        _bindEvents : function(){
            var self = this;
            Aloha.bind("aloha-link-selected", function(e){
                self.currentEditable = e.currentTarget.activeEditable;
                var linkObj =  self.currentEditable.obj;
                if(linkObj){
                    var linkContent = self.currentEditable.originalContent;
                    if(linkContent.indexOf("<i></i>")==0){
                        self.iLinks[self.currentEditable.getId()] = linkObj;
                    }
                }
                this.savedRange = Aloha.Selection.getRangeObject();
                self.bbButton.show();
            /**/
            }); 
            
            Aloha.bind("aloha-editable-deactivated",function(e,editable){
                var editableObj = editable.editable.obj;
                if(self.iLinks[editable.editable.getId()]){
                    var newContent = editable.editable.getContents();
                    var icoMarkup = "<i></i>"; 
                    newContent = newContent.replace(icoMarkup,"");
                    $(editable.editable.obj).html(icoMarkup+newContent);
                    editableObj.html(icoMarkup+newContent);
                }
            });
        },
       
        _selectLink : function(item){
            if(!item.value || jQuery.trim(item.value)=="") return;
            this.linkPlugin.toggleLinkScope(true);
            this.linkPlugin.hrefField.setValue(item.value);
            this.linkPlugin.hrefField.focus();
            var link = this.linkPlugin.hrefField.getTargetObject();
            if(link){
                $(link).attr("href",item.value);
                $(link).attr("title",item.title);
            }
            this.linkPlugin.hrefChange();
            this.linkPlugin.hrefField.foreground();
            if(this.currentEditable){
                this.currentEditable.activate(); 
            }
        },
        
        _showBbLinkBrowser: function(){
          
            var self = this;
            if(this.linkSelector){
                if(this.linkSelector.data("bbSelector")){
                    this.linkSelector.data("bbSelector").open();
                    return false;
                }
            }
            
            if (!this.linkSelector) {
                var selectorLink = bb.i18n.__('toolbar.editing.linkselectorLabel');
                var linkSelectorContainer = $("<div id='bb5-bblink-linksselector' class='bb5-selector-wrapper'></div>").clone();
                this.linkSelector = bb.jquery(linkSelectorContainer).bbSelector({
                    popup: true,
                    pageSelector: true,
                    linkSelector: true,
                    mediaSelector: false,
                    contentSelector : false,
                    resizable: false,
                    selectorTitle : selectorLink,
                    callback: function(item) {
                        jQuery('#bb5-bblink-linksselector').bbSelector('close');
                    },
                    open: function(){
                        self.linkSelectorIsVisible = true;
                    },
                    close: function(){
                        self.linkSelectorIsVisible = false;
                    },
                    beforeWidgetInit:function(){
                        var bbSelector = $(this.element).data('bbSelector');
                        bbSelector.onWidgetInit(bbSelector._panel.INTERNAL_LINK, function () { 
                            var bbPageSelector = $(this).data('bbPageSelector') || false;
                            if(bbPageSelector){
                                bbPageSelector.setCallback(function(params) {
                                    self._selectLink(params);
                                    bbSelector.close();
                                    return false;
                                }); 
                            }
                        });
                        
                        bbSelector.onWidgetInit(bbSelector._panel.EXTERNAL_LINK, function () {
                            var bbLinkSelector = $(this).data('bbLinkSelector');
                            bbLinkSelector.setCallback(function (params) {
                                var linkPattern = /^([\w]+:\/\/)/gi;
                                params.value = (linkPattern.test(params.value)) ? params.value : "http://"+params.value;
                                self._selectLink(params);
                                bbSelector.close();
                                return false;
                            });
                        });
                    }
                });
            }
            this.linkSelector.data("bbSelector").open();
        },
        
        _createbbButton: function(){
            this.bbButton = Ui.adopt("linkBrowser",Button,{
                tooltip: "Show choose a link",
                icon: 'aloha-icon-tree',
                scope: 'Aloha.link',
                click: $.proxy(this._showBbLinkBrowser,this)
            }); 
        }
       
    });
});