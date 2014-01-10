if (!Aloha) var Aloha = {};
Aloha.settings = {         
    i18n : {
        current : 'fr'
    },
    floatingmenu: {
        behaviour: 'float',
        element: 'aloha',
        pin: false,
        draggable: false,
        marginTop: 0,
        horizontalOffset: 0,
        topalignOffset: 0
    },
    sidebar: {
        disabled: true
    },
    plugins:
    {
        format:{
            // all elements with no specific configuration get this configuration
            config: [ 'b', 'i', 'p', 'sub', 'sup', 'del', 'title', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'removeFormat' ],
            editables:{}
        },
        highlighteditables:{
            editables:{}
        },
        list:{
            config : [ 'ul' , 'ol'],
            editables:{}
        },
        link:{
            target : ''
            /*editables:{}*/
        },
        undo:{
            editables:{}
        },
        paste:{
            editables:{}
        },
        block:{
            editables:{}
        },
        table:{
            config: ['table'],
            editables: [{
                'title': []
            }]
        },
        image:{
            config: ['img'],
            fixedAspectRatio: false,
            maxWidth: 1024,
            minWidth: 10,
            maxHeight: 786,
            minHeight: 10,
            globalselector: '.global',
            ui: {
                oneTab: false,
                insert: true,
                aspectRatioToggle: true,
                align: false, 
                meta: true,
                margin: false,
                crop: false,
                resizable: true
            },
            editables:{}
        },
        wordinfo:{
            languages:['fr'],
            editables:{}

        },
        linkbrowser: {
            editables:{}
        }
    }
};

var AlohaManager = {
    
    _editables: [],
    _contentName: "",
    _contents : [],
    _isEditable: false,
    _alohaIsLoaded : false,
    _editableContents :[],
    
    
    init: function () {

    },
    
    contentExist: function (inputValue) {
        var self = this;
        var bool = false;
        if (self._contents.length == 0) 
            bool = false;
        else {
            bb.jquery.each(self._contents, function (key, item) {
                bb.jquery.each(item, function (attr, Objectvalue) {
                    if (attr == inputValue){
                        bool = true;
                    }
                });
            });
        }
        return bool;
    },
    
    getContent: function (name) {
        var self = this;
        var content;
        bb.jquery.each(self._contents, function (key, item) {
            bb.jquery.each(item, function (attr, value) {
                if (attr == name) {
                    content = item;
                }
            });
        });
        return content[name];
    },
    
    setContent: function (e) {
        var self            = this;
        var parent          = bb.jquery(e.target).parents('.contentAloha');
        if (typeof parent.attr('class') != 'undefined')
            self._contentName = bb.jquery(parent).attr('data-type');
    },
    
    getMainNodeContainer : function(e){
        var mainNode = bb.jquery(e.target).parents(".bb5-content").eq(0);
        return mainNode;
 
    },
    
    initAloha: function (editablesFields) {
        var self = this;
        if (!self._isEditable) return false;
        var editablesFields = editablesFields || [];
        
        if( editablesFields.length == 0 ) return;
        
        bb.jquery.each(editablesFields, function (key, object) {
            if (!object.length == 0) {
                return true;
            }
            
            /*use bb content uniqId*/
            var bbContentId = self.mainContainer.id;
            bb.jquery.each(object, function (attr, value) {
                /* Apply style here */
                Aloha.settings.plugins.format.editables['[data-bbContentRef="'+bbContentId+'"][data-type="' + self._contentName + '"] [data-aloha="' + attr + '"]'] = value;
            });
        });
       
        
        Aloha.ready(function (e) {
  
           // bb.jquery('#bb5-mainLayoutRow [data-aloha]').addClass('aloha-link-text');
           // bb.jquery('#bb5-mainLayoutRow [data-aloha]').attr('contenteditable', true);
            
           var contentSelectors = [];
            bb.jquery.each(editablesFields, function (key, object) {
                if (!object.length == 0) { /*console.log('null Object');*/
                    return true;
                }
             
                bb.jquery.each(object, function (attr, value) {
                    //contentNodes = 'div.contentAloha[data-type="' + self._contentName + '"] [data-aloha="' + attr + '"]';
                    var contentNodeSelector = '[data-aloha="' + attr + '"]';
                    contentSelectors.push(contentNodeSelector);
                });
            });
            
            var contentNodes = bb.jquery(contentSelectors.join(','),self.mainContainer.contentEl);
            self._editableContents.push(contentNodes);
            bb.jquery(contentNodes).addClass('aloha-link-text').attr('contenteditable', true);
            Aloha.jQuery(contentNodes).aloha();

            //solution problemes Aloha
            bb.jquery('.x-tab-panel').appendTo('#bb5-edit-tabs-data #aloha');
            bb.jquery('.x-tab-panel-header').live('hover', function() {
                bb.jquery(this).css('cursor', 'auto');
            });
            bb.jquery('.aloha-floatingmenu-pin').hide();

        });
       
    },
    
    
    applyAloha: function () {
            
        var self = this;
        self._isEditable = true;
        bb.jquery('div [data-aloha]:not([data-aloha="image"])').die().live('click', function (e) {
           if(!self._isEditable) return true;
            //e.stopPropagation();
            var mainContainer = self.getMainNodeContainer(e);
            self.mainContainer = $bb(mainContainer);
            self.setContent(e); 
            
            /*only load the main container*/
            if(self._contentName != bb.jquery(mainContainer).attr("data-type")){
                self._contentName =  bb.jquery(mainContainer).attr("data-type");
            }
            
            if (self.contentExist(self._contentName) == false)
            {
                bb.webserviceManager.getInstance('ws_local_contentBlock').request('getDataContentType', {
                    params: {
                        name: self._contentName
                    },
                    success: function(result) {
                        self._alohaIsLoaded = false;
                        
                        self.initAloha(result.result.editables);
                        if (!self.contentExist(self._contentName))
                        {
                            var tabedit = {};
                            tabedit[self._contentName] = result.result.editables;
                            self._contents.push(tabedit);
                        }
                    },
                    error: function(result) {
                        console.log("error", result);
                    }
                });
            }
            else
            {
              self.initAloha(self.getContent(self._contentName));
            }
        });
        
        /*Aloha change*/
        
        Aloha.bind('aloha-editable-deactivated', function(e,editableContent) {
            var bbContent = $bb(editableContent.editable.obj);
            bbContent.set("value",editableContent.editable.getContents());
            bbContent.parentNode.updateData();
            return false;
        });
       
    },
    
    cleanAloha : function(){
         if(this._editableContents.length!=0){
             bb.jquery.each(this._editableContents,function(i,nodes){
                if(nodes.length){
                    Aloha.jQuery(nodes).removeClass("aloha-editable-highlight");
                    Aloha.jQuery(nodes).removeClass("aloha-link-text");
                } 
             });
            Aloha.jQuery('*').mahalo();
         } 
        // bb.jquery('div [data-aloha]:not([data-aloha="image"])').die()
    },
    
    stop: function() {
        this._isEditable = false;
        this.cleanAloha("aloha");
    },
    
    prepareSendData: function () {
        var data = [];
        bb.jquery.each(bb.jquery('.bb5-tabarea_content'), function (index, item) {
            //var contentType     = bb.jquery(item).attr('data-type');
            var dataContent     = {};
            var contentFields   = {};
            bb.jquery.each(bb.jquery(item).find('[data-aloha]').not('[data-type="image"]'), function (key, field) {
                var dataField        = {};
                dataField['uid']     = bb.jquery(field).attr('data-uid').not();
                dataField['type']    = bb.jquery(field).attr('data-type');
                dataField['value']   = bb.jquery(field).html();
                contentFields[key]   = dataField;
            });
            dataContent = contentFields;
            data[index] = dataContent;
        });
        return data;
    },
    
    sendData: function () {
        var self = this;
        bb.jquery('.saveContent').click(function() {
            bb.webserviceManager.getInstance('ws_local_contentBlock').request('saveContents', {
                params: {
                    data: self.prepareSendData()
                },
                success: function(result) {
                    //console.log(result);
                    self.initAloha(result.result.editables);
                    if (!self.contentExist(self._contentName))
                    {
                        var tabedit = {};
                        tabedit[self._contentName] = result.result.editables;
                        self._contents.push(tabedit);
                    }
                },
                error: function(result) {
                }
            });
        });
    }
}
