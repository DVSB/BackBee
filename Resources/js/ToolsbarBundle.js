/*LayoutToolsbar*/
BB4.ToolsbarManager.register("bundletb",{

    _settings : {
        toolsbarContainer : "#bb5-bundle",
        mainContainer     : "#bb5-bundle",
        bundleSlideId     : "#bb5-slider-extnd",
        bundleNameId      : "#bb5-use-bundle-name",
        bundleDescId      : "#bb5-use-bundle-access"
    },
    
    _events: {
        ".bb5-button.bb5-extnd-choice-x click" : "bundleClick"
    },
	
    _beforeCallbacks : {
        "bundleClick" : function(e) {
            this._unselectBundle();
            this._callbacks["bundleClick_action"].call(this, e);
        }
    },
    _context :{
        isOpen : false, 
        delay : false
    },
    _init : function(){
        this.toolsbar = $(this._settings.mainContainer);
		
        this.loaded = false;
        this.selectedBundle = null;
        this.bundleConfirmDialog = null;
        this.bundleParamDialog = null;
        this.bundleAdminDialog = null;
        this.bundleWebservice = bb.webserviceManager.getInstance('ws_local_bundle');
		        
        this.bundleContainer = new SmartList({
            idKey:"id",
            onInit : $.proxy(this._buildBundleSlide,this)
        });
        
        this._callbacks["bundleClick_action"] = function(e) {
            var selectedBundleId = $(e.currentTarget).attr("id").replace("bundle_","");
            this._selectBundle(selectedBundleId);
        };
        this._bindPrivateEvents();
        this._createDialogs();
        this._handleWindowsResize();
        this._delay = false;
    },
    
    _handleWindowsResize :function(){
        var self = this;
        $(window).resize(function(e){
            if(this._delay != false) clearTimeout(this._delay);
            var onResize = function(){
              
                if(self.bundleAdminDialog && self.bundleAdminDialog.invoke("isOpen")){
                    self.bundleAdminDialog.setOption("width",$(window).innerWidth() - 100);
                    self.bundleAdminDialog.setOption("height",$(window).innerHeight() - 100);
                }
            }
            self._delay = setTimeout(onResize,100);
        });
        
    },
    _buildBundleSlide : function(bundles){
        var self = this; 
		
        this._resetSlider();
        $.each(bundles,function(key,bundle){
            self._addBundleItem(bundle);
        });
        
        var slider = $(this._settings.bundleSlideId).bxSlider({
            nextText:'<span><i class="visuallyhidden focusable">'+bb.i18n.__('Next')+'</i></span>',
            prevText:'<span><i class="visuallyhidden focusable">'+bb.i18n.__('Previous')+'</i></span>',
            displaySlideQty:4,
            moveSlideQty:1,
            pager:false,
            infiniteLoop : false
        });
		
        return slider;
    },
    
    _createDialogs : function(){
        var self = this;
        
        var popupDialog = bb.PopupManager.init({});
        
        this.bundleConfirmDialog = popupDialog.create("confirmDialog",{
            title: "Confirmation"
        });     
        
        this.bundleAdminDialog = popupDialog.create("bundleAdminDialog",{
            title:"Administration",
            position: ["center","center"],
            
            resizable : false,
            draggable: false,
            minHeight: 560,
            minWidth: 990,
            width: ($(window).innerWidth() - 100),
            height: ($(window).innerHeight() - 50),
            position: ["center","center"],
            buttons:{
                "Fermer":function(){
                    $(this).dialog("close");
                    return false;
                }
            }
        });
        this.bundleAdminDialog.on('open', function() {
            self.bundleAdminDialog.setContent($(''));
            self.selectedBundle.webservice.request('admin', {
                success : function(response){
                    self.bundleAdminDialog.setContent($(response.result));
                },
                error: function(response){
                    self.bundleAdminDialog.setContent($(response.error));
                }
            });
        });
    },
	
    _bindPrivateEvents : function() {
        var self = this;
    },
    
    _selectBundle : function(bundleId){
        var self = this;
		
        var bundle = null;
        if (bundle = this.bundleContainer.get(bundleId)) {
            this.selectedBundle = bundle;
            $('li.pager #bundle_'+bundleId).addClass('bb5-layout-selected');
            $(this._settings.bundleNameId).append(bundle.name || bundle.id);
            if (bundle.description)
                $(this._settings.bundleDescId).append('<textarea rows="3" cols="50" readonly>'+bundle.description+'</textarea>');
            if (bundle.service) {
                $(this._settings.bundleDescId).append('<p><button class="bb5-button bb5-button-square bb5-bundle-'+bundle.id+'-admin">Administrer</button></p>');
                $('button.bb5-bundle-'+bundle.id+'-admin', $(this._settings.bundleDescId)).bind('click', function(e) {
                    self.bundleAdmin();
                });	
            }
        }
    },

    _unselectBundle : function() {
        this.selectedBundle = null;
		
        $(this._settings.bundleSlideId+' button').removeClass('bb5-layout-selected');
        $(this._settings.bundleNameId).empty();
        $(this._settings.bundleDescId).empty();		
    },
	
    bundleAdmin : function() {
        var self = this;
		
        if (this.selectedBundle && this.selectedBundle.service) {
            bb.webserviceManager.setup({
                endPoint: 'index.php',
                webservices:[{
                    name: 'ws_local_bundle_'+this.selectedBundle.id,
                    namespace: this.selectedBundle.service.split('\\').join('_')
                }]
            });
            this.selectedBundle.webservice = bb.webserviceManager.getInstance('ws_local_bundle_'+this.selectedBundle.id);
			
            $(this.bundleAdminDialog).dialog('option', 'title', "Administration : "+(this.selectedBundle.name||$this.selectedBundle.id));	
            this.bundleAdminDialog.show();
        }
    },
	
    getBundleParamDialog : function(){
        return this.bundleParamDialog;
    },
	
    //class="bbGridSlide"
    _resetSlider : function(){
        var sliderTemplate = $('<div id="bb5-use-bundle-wrapper" class="bb5-tabarea_content">'
            +'<div class="bb5-slider-extnd-wrapper slider-fx">'
            +'<ul id="bb5-slider-extnd"></ul>'
            +'</div>'
            +'</div>');
                    
        var slider = $(sliderTemplate).clone();
        $("#bb5-use-bundle-wrapper").replaceWith(slider);
    },
	
    _addBundleItem : function(bundle) {
        var item = $('<li></li>').clone();    
        var btn = $("<button class='bb5-button bb5-button-bulky bb5-extnd-choice-x'><i></i></button>").clone();
        $(btn).attr("id","bundle_"+bundle.id);
        $(btn).attr("title",bundle.name || bundle.id);
        $('i', $(btn)).attr("style", "background-image:url("+bb.baseurl+bb.resourcesdir+"img/extnd-x/picto-extnd.png);");
        if ('on' == bundle.enable) {
            $('i', $(btn)).append("<span></span>");
        }
        $(item).append(btn);
        $(this._settings.bundleSlideId).append(item);
    },
	
    enable : function() {
        var self = this;
		
        if (!this.loaded) {
            this.bundleWebservice.request('findAll', {
                success: function(result) {
                    if (result.result) {
                        self.bundleContainer.setData(result.result);
                        var slider = self._buildBundleSlide(result.result);
                        self.loaded = true;
                    }
                },

                error: function(result) {
                }
            });
        }
    }
});