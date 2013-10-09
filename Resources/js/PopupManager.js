/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var bb = bb || {};

bb.PopupManager = (function($,gExport){
    var instance = null;
    var _settings = {
        dialogSettings : {
            resizable: false,
            zIndex: 600001,
            dialogCls: 'bb5-ui bb5-dialog-wrapper',
            dialogClass: ".bb5-dialog-wrapper",
            modal: false,
            autoOpen: false,
            hide: "fade",
            bindEnterKeyToValid: false
        }
    };
    var _availableDialogType = {
        CONFIRMATION:"bb5-dialog-confirmation", 
        ALERT:"bb5-dialog-alert", 
        EDIT:"bb5-dialog-editproperty", 
        DELETE:"bb5-dialog-deletion", 
        INFO:"bb5-dialog-info"
    };
    
    var _dialogTpl = "<div class='bb5-ui' id='bb5-dialog'></div>";
    
    var _init = function(userConfigs){
        var userConfigs = (typeof userConfigs=="object") ? userConfigs : {};
        _settings = $.extend(true,_settings,userConfigs);
        return publicApi;
    };
  
    var _dialogTypes = {
        authenticationDialog :"<div class='bb5-ui bb5-dialog-authentication'><div class='bb5-alert'></div><label>${username} <input type='text' name='username' /></label><label>${password} <input name='password' type='password' /></label></div>",
        alertDialog :"<div class='bb-alert-dialog'></div>",
        confirmDialog :"<div class='bb-confirm-dialog'></div>",
        zoneEditor : "<div class='zoneEditorDialog'><input class='content' text='strange'></input></div>",
        layoutEditor : "<div class='layoutEditorDialog'><input class='content' text='layoutName'></input></div>",
        themeEditor : "<div class='themeEditorDialog'><input class='content' text='themeName'></input></div>",
        contentParamsEditor :"<div class='bb5-ui bb5-content-params-editor'></div>",
        contentFormsEditor :"<div class='bb5-ui bb5-ui-edition-editcontent'></div>",
        mediaFormEditor:"<div class='bb5-ui bb5-ui-bbmediaselector-editmedia'></div>",
        pageBrowserDialog :"<div class='bb5-ui bb5-dialog-treeview'></div>",
        mediaSelectorAlert : "<div id='bb5-ui-bbmediaselector-message' class='bb5-ui bb5-ui-error-alert'></div>",
        bundleAdminDialog : "<div class='bb5-ui bb5-dialog-admin-bundle'></div>",
        selectorDialog : "<div class='bb5-ui bb5-dialog-selector-content'></div>",
        metadataEditor : "<div class='bb5-ui bb5-dialog-metadata-editor'></div>"
    };
    var dialogsContainer = {};
    
    var DialogItem = function(dialogSettings){
        this.callbacks = {
            save :   function(){
                console.log("overwrite save");
            },
            cancel : function(){
                console.log("overwrite cancel");
            } 
        };
        this._dialogStates = {
            isFixed:false
        };
        /*create dialog*/
        var dialogEl = dialogSettings.dialogEl || false;
        var self = this;
        if(!$(dialogEl)) throw "DialogEl can't be null";
        
        this.settings = dialogSettings.dialogConfig || {}; 
        if ('undefined' != typeof(this.settings.i18n))
            dialogEl = $.template(dialogEl).apply(this.settings.i18n);

        if(this.dialog == false) throw new Error("Dialog can't be null");        
        // var userOnCreate = (typeof dialogSettings.create =="function" ) ?  dialogSettings.create :$.noop;
        
        /*handle content here*/
        if(("content" in this.settings) && $(this.settings.content).length!=0){
            $(dialogEl).append($(this.settings.content));
        }
        
        this.dialog = $(dialogEl).dialog(this.settings);
        if(!this.dialog) throw "Dialog can't be null";
        this.dialogUi = $(this.dialog).parent(_settings.dialogSettings.dialogClass).eq(0);
        
        
        /*onOpen*/
        this.on("open.core",function(){
            $(self.dialog).dialog("moveToTop");
        });
        
                
        /*fixresize*/
        this.on("resizestop.core",function(event,ui){
            var position = [(Math.floor(ui.position.left) - $(window).scrollLeft()),(Math.floor(ui.position.top) - $(window).scrollTop())];
            $(event.target).parent().css('position', 'fixed');
            self.setOption('position',position);
            return true;
        });
         
        /*Adding keypress listner enter key to valid*/
        this.on("open.core",function(){
            if(self._dialogStates.isFixed) return true;
            $(self.dialog).keypress(function(e) {
                if ($(self.dialog).dialog('isOpen') && e.keyCode === $.ui.keyCode.ENTER && self.settings.bindEnterKeyToValid) {
                    $(self.dialog).parent().find("button[data-default='true']:eq(0)").trigger("click");
                }
            });
        /*var closeBtn =  $(self.dialogUi).find(".ui-dialog-titlebar-close");
              $(closeBtn).replaceWith("<button class=\"bb5-button bb5-ico-close bb5-button-square bb5-invert\"></button>");
              $(self.dialogUi).find(".bb5-ico-close").bind("click",$.proxy(self.close,self));
              self._dialogStates.isFixed = true;*/ 
        });
       
        /* hack to keep the dialog in the viewport after a drag */
        this.on("dragstop.core",function(e,ui){
            var dialog = $(self.dialog).parent(_settings.dialogSettings.dialogClass).eq(0);
            var top = parseInt($(dialog).css("top"));
            /* up */
            if(top < 0){
                top = Math.abs(top);
                var position = self.getOption("position"); 
                position[1] = top;
                self.setOption("position",position);
                return;
            }
            
            /* down */
            var dialogHeight = parseInt($(dialog).css("height"));
            var offsetTop = ui.offset.top; 
            var winHeight = $(window).height();
            var dialogCurrentTop = ui.position.top;
            /*as dialog is fixed*/
            var adjustPosition = ((dialogCurrentTop+dialogHeight) > winHeight) ? true : false;
            if(adjustPosition){
                var adjustSize =  dialogCurrentTop+dialogHeight -  winHeight; 
                var newTop = (dialogCurrentTop - adjustSize) - 15; // margin de 2px
                $(dialog).animate({
                    top:newTop+"px"
                });
            }        
        });
        /* hack to keep the dialog in the viewport after a drag */
        this.on("open.core",function(e,ui){
            var dialog = $(self.dialog).parent(_settings.dialogSettings.dialogClass).eq(0);
            var top = parseInt($(dialog).css("top"));
            if( top < 0 ){
                top = Math.abs(top);
                var position = self.getOption("position");
                position[1] = top;
                self.setOption("position",position);
            }
        });
    };
    
    DialogItem.fn = DialogItem.prototype;
    
    DialogItem.fn.show = function(){
        var btnContainer = $(this.dialog).parents(".bb5-dialog-wrapper").eq(0).find(".ui-dialog-buttonset");
        $(btnContainer).find("button").addClass("bb5-button bb5-button-square");
        $(this.dialog).dialog("open");
    };
    
    DialogItem.fn.btnDisable = function() {
        var btnContainer = $(this.dialog).parents(".bb5-dialog-wrapper").eq(0).find(".ui-dialog-buttonset");
        $(btnContainer).find("button").attr('disabled', 'disabled');
    };
    
    DialogItem.fn.btnEnable = function() {
        var btnContainer = $(this.dialog).parents(".bb5-dialog-wrapper").eq(0).find(".ui-dialog-buttonset");
        $(btnContainer).find("button").removeAttr('disabled');
    };
    
    /*alias*/
    DialogItem.fn.open = function(){
        return this.show();
    };
    
    DialogItem.fn.close = function(){
        /*cleanExtra*/
        $(this.dialogUi).data("bb-dialog-extra",null);
        $(this.dialog).dialog("close");
    };
    
    DialogItem.fn.destroy = function(){
        $(this.dialog).dialog("destroy");
        $(this.dialog).remove();
    };
    
    DialogItem.fn.getParam = function(paramKey){
        var paramKey = paramKey || "none";
        return $(this.dialog).dialog("option",paramKey);
    };
    
    DialogItem.fn.getOption = function(paramKey){
        return this.getParam(paramKey);
    };
     
    DialogItem.fn.setOption = function(key,value){
        $(this.dialog).dialog("option",key,value);
        if(key == "buttons"){
            /* fix buttons style */
            var btnContainer = $(this.dialog).parents(".bb5-dialog-wrapper").eq(0).find(".ui-dialog-buttonset");
            $(btnContainer).find("button").addClass("bb5-button bb5-button-square"); 
        }
    };
    
    DialogItem.fn.isOpen = function(){
        return $(this.dialog).dialog("isOpen");
    }
     
    DialogItem.fn.invoke = function(methodName){
        if(typeof methodName=="string"){
            return $(this.dialog).dialog(methodName);
        }
    }
    
    /*add multi events support*/
    DialogItem.fn.on = function(event,callback,context){
        var context = context || this;
        var event = event || "none";
        var callback = (typeof callback==="function") ? $.proxy(callback,context) : function(){
            console.log("callback has to be a function")
        };
        this.callbacks[event] = callback; //useful for custom event
        $(this.dialog).bind("dialog"+event,callback);
    };
 
    DialogItem.fn.unbind = function(event){
        var event = event||null;
        if(event in this.callbacks){
            this.dialog.unbind("dialog"+event,this.callbacks[event]);
            this.callbacks[event] = function(){};
        }
        return this;
    };
    
    DialogItem.fn.setContent = function(content){
        var content = content || null;
        if(content){
            $(this.dialog).html($(content));  
        }
    };
    
    DialogItem.fn.setExtra = function(data,value){
        var data = data || null;
       
        if(data && !value){
            $(this.dialogUi).data("bb-dialog-extra",data);
        }else{
            $(this.dialogUi).data(data,value);
        }
    };
        
    DialogItem.fn.getExtra = function(key){
        var key = key || "bb-dialog-extra";
        return  $(this.dialogUi).data(key);
    }
    
    DialogItem.fn.addButton = function(data){
        var data = data || false;
        var buttons =  $(this.dialog).dialog("option","buttons");
        if(typeof buttons.length == "undefined"){
            buttons = [];
        }
        buttons.push(data);
        $(this.dialog).dialog("option","buttons",buttons);
    }
    
    var _buildDialog = function(dialogParams){
        var dialogTemplate = $("<div></div>").clone();
        $(dialogTemplate).addClass(dialogParams.contentCls);
        _dialogTypes[dialogParams.type] = dialogTemplate;  
        return dialogTemplate;
    };
    
    
    var publicApi = {
        create : function (type, dialogConfig) {
            var dialogConfig = dialogConfig || {}; 
            if(!(type) || (typeof type !="string")) throw new Error("Type can't be null or undefined"); 
            var dialogSettings = $.extend(true,{}, _settings.dialogSettings,dialogConfig);
            
            dialogSettings.contentCls = type;
            //var oldDialog = dialogsContainer[type];
            //if(oldDialog) oldDialog.destroy();
            if("dialogEl" in dialogConfig){
                var dialogEl = dialogConfig.dialogEl; 
            }else{
                var dialogEl = _dialogTypes[type] || false;
                if(!dialogEl){
                    dialogConfig.type = type;
                    dialogEl = _buildDialog(dialogConfig);
                }    
            }
           
            
            var supDialogCls = (typeof dialogConfig.dialogType == "string") ? dialogSettings.dialogType :""; 
            dialogSettings.dialogClass = _settings.dialogSettings.dialogCls.trim() +' '+supDialogCls.trim();           
            // var dialog = $(dialogEl).dialog(dialogSettings);
          
            
            var dialogItem = new DialogItem({
                dialogEl:dialogEl,
                dialogConfig:dialogSettings
            });
           
            
            /*saveDialog*/
            // dialogsContainer[type] = dialogItem;
            return dialogItem;
        },
        
        registerDialogType : function(dialogKey,html){
            if(dialogKey && html){
                _dialogTypes[dialogKey] = html;
            }
        },
        dialogType: _availableDialogType
    };
  
    return {
        init : _init,
        dialogType: _availableDialogType
    };
    
})(jQuery,window);