/*LayoutToolsbar*/
BB4.ToolsbarManager.register("themetb",{
  
    _settings: {
        mainContainer: "#bb5-theming",
        webservices: {
            contentLessWS: "ws_local_less"
        },
        lessVariables : [],
        defaultFonts :  []
    },
    
    _events: {
        ".bb5-BtnGridSaveTheme click" : "saveLessVariables",
        ".selectTheme click"        : "loadVariableTheme",
        ".showTplTheme click"       : "displayThemes",
        ".editThemeNameBtn click"   : "displayThemeEditor"
    },
    
    _createDialogs : function(){
        var popupDialog = bb.PopupManager.init({});
        var self = this;
        /*themeEditorDialog*/
        this.themeEditorDialog = popupDialog.create("themeEditor", {
            title:"Editer le nom du Theme",
            buttons:{
                "Enregister" :function(){
                    var inputValue = self.themeEditorDialog.dialog.find(".content").val();
                    if (inputValue == "")
                    {
                        alert('Le nom du thème ne peut être vide');
                        return false;
                    }
                    self._callbacks["saveLessVariables_action"].call(self);
                    self.sendDataForNewTheme(inputValue);
                    $(this).dialog("close");
                    return false;
                },
                        
                "Annuler":function(a){
                    $(this).dialog("close");
                    return false;
                }
            }
        });  
    },
    
    _initLessVariables: function () {
        var self = this;
        $.each($(self._settings.mainContainer + ' .bb5-tabarea_content'), function (index, item) {
            var rows = $(item).find('.lessEditor');
            $.each(rows, function (i, row) {
                if ($(row).attr("data-less") != "")
                {
                    var itemValue    = $(row).attr("data-less");
                    var nameLabel    = $(row).parents('p').find('label').get(0);
                    var objLess      = {};                    
                    objLess['name'] = nameLabel.innerHTML;
                    objLess['value'] = itemValue;
                    self._settings.lessVariables.push(objLess);
                }
            });
            self._settings.lessVariables.push({
                name: 'baseFontFamily', 
                value: "Arial"
            });
        });
       
    },
    
    _resetPlaceHolder: function () {
        var self = this;
        $.each(self._settings.lessVariables, function (index, item) {
            $.each($(self._settings.mainContainer + ' .bbAreaInner p'), function (indexRender, itemLabel) {
                if ($(itemLabel).find('label').html() == item.name)
                    $(itemLabel).find('.inputLessVariables').attr('placeholder', item.value);
            });
        });
        self._settings.lessVariables = [];
    },
    
    _bindEvent: function () {
        $(document).bind('click', function(e) {
            //var p = e.currentTarget.parents(".bb5-grid-extras");
            //console.log(e.currentTarget);
            $('#bb5-tplThemeContainer').hide();
            
        });
    },
    
    _init: function(){
        //less.refresh(true);
        this._bindEvent();
        this.loadLessVariables();
        this.saveLessVariables();
        this.loadVaraibalesTheme();
        this.initThemes();
        this.displayThemes();
        this._createDialogs();
        this.displayThemeEditor();
    },
    
    loadLessVariables: function (themeName) {
        var self= this;
        if(!themeName) return;
        this.contentLessWebservice = bb.webserviceManager.getInstance(this._settings.webservices.contentLessWS);
        this.contentLessWebservice.request("getLessVariablesBB4" , {
            useCache : true,
            cacheTags: ["userSession"],
            params: {
                theme: themeName
            },
            success: function(response){
                var resultSet = response.result;
                self.renderVariablesTheme(resultSet);
            },
            error: function(response){
                throw response.error;
            }
        });
    },
   
    initColorPiker: function () {
        $('.bb5-colorpicker').ColorPicker({
            onSubmit: function(hsb, hex, rgb, el) {
                $(el).attr("data-less", "#" + hex);
                $(el).attr("style", "background: #" + hex);
                $(el).ColorPickerHide();
            },
            onBeforeShow: function () {
                //console.log(this);
                var style = $(this).attr("style");
                style = style.substr(style.lastIndexOf("#"), style.length);
                if (this.value == "")
                    $(this).ColorPickerSetColor(style);
                else
                    $(this).ColorPickerSetColor(this.value);
            }
        });
    /*.bind('keyup', function(){
            //console.log()
            $(this).ColorPickerSetColor(this.value);
        });
		*/
    },
    
    renderVariablesTheme: function (resultSet) {
        var self = this;

        this.contentLessWebservice = bb.webserviceManager.getInstance(this._settings.webservices.contentLessWS);
        this.contentLessWebservice.request("getLessFonts" , {
            success: function (data) {
                self._settings.defaultFonts = data.result;
                self.renderHTMLFields(resultSet);
                self.initColorPiker();
            //console.log(resultSet);
            },
            error: function (response) {
                //this._settings.defaultFonts = [];
                throw response.error;
            }
        });
    },
    
    renderHTMLFields: function (resultSet) {
        var self = this;
        var i = 0;
        var index = 0;
        var html = "";
        $.each(resultSet[0]["attributes"], function (attrName, item) {
            if (i % 3 == 0) {
                html += '<div class="bb5-tabarea_content">';
                index = i;
            }

            html += '<p><label>' + attrName + '</label>';
            
            //console.log("widget", item.widget);
            if (item.widget == "font" && self._settings.defaultFonts.length > 0) {
                //console.log(self._settings.defaultFonts);
                //<select name="fonts" class="bb5-select inputLessVariables">
                html += '<select name="fonts" class="bb5-select lessEditor">';
                $.each(self._settings.defaultFonts, function (index, fontName) {
                    //console.log(fontName);
                    var isSelected = (fontName == item.value) ? 'selected="selected" ': '';
                    html += '<option ' + isSelected + 'value="' + fontName + '" style="font-family:' + fontName + ';">' + fontName + '</option>';
                });
                html += '</select></p>';
            }else {
                ////<p><label>textColor </label><button class="bb5-colorsample bb5-colorpicker" style="background-color:#333;"></button></p>
                html += '<button class="bb5-colorsample lessEditor';
                if (item.widget == "color") html += ' bb5-colorpicker"'
                html += ' style="background-color:' + item.value + ';" data-less="' + item.value + '"></button></p>';
            }
            if (i == index + 3 - 1)
                html += '</div>';
            i++;
        });
        $(self._settings.mainContainer + ' .bb5-tabarea-31 #lessVariablesEditables').html(html);
    //$('.bb5-select').selectgroup();
    },
    
    saveLessVariables: function () {
        var self = this;
        this._callbacks["saveLessVariables_action"] = function(){
            self._initLessVariables();
            this.contentLessWebservice.request("sendLessVariablesBB4" , {
                params: {
                    data: self._settings.lessVariables
                    },
                success: function(response){
                    //self._resetPlaceHolder();
                    self.generateStyle('default');
                    less.refresh(true);
                    $('.protoStylesheet').attr('href', bb.baseurl + 'ressources/themes/default/css/bb4-proto.css');
                    self._settings.lessVariables = [];
                //$('.bootstrapStylesheet').attr('href', bb.baseurl + 'ressources/themes/default/css/bootstrap.css');
                },
                error: function(response){
                    self._settings.lessVariables = [];
                    throw response.error;
                }
            });
        };
    },
    
    loadVaraibalesTheme: function () {
        var self = this;
        var dataTheme;
        this._callbacks["loadVariableTheme_action"] = function(e) {
            dataTheme = $(e.currentTarget).attr('data-theme');
            self.contentLessWebservice = bb.webserviceManager.getInstance(self._settings.webservices.contentLessWS);
            self.contentLessWebservice.request("changeTheme" , {
                params: {
                    name: dataTheme
                },
                success: function(response){
                    var resultSet = response.result;
                    self.loadLessVariables(dataTheme);
                    less.refresh(true);
                    $('.protoStylesheet').attr('href', bb.baseurl + 'ressources/themes/default/css/bb4-proto.css');
                //$('.bootstrapStylesheet').attr('href', bb.baseurl + 'ressources/themes/default/css/bootstrap.css');
                },
                error: function(response){
                    throw response.error;
                }
            });
        };
    },
    
    initThemes: function () {
        var self = this;
        self.contentLessWebservice = bb.webserviceManager.getInstance(self._settings.webservices.contentLessWS);
        self.contentLessWebservice.request("getThemes" , {
            useCache : true,
            cacheTags: ["userSession"],
            params: {},
            success: function(response){
                if(response.error){
                    console.warm("TollssbarTheme.initThemes has Error");
                    return;
                }
               
                var resultSet = response.result;
                self._settings.availableThemes = resultSet;
                self.loadThemes(self._settings.availableThemes);
            //self.initDisplay(self._settings.availableThemes.length);
            },
            error: function(response){
                throw response.error;
            }
        });
    },
    
    loadThemes: function (themes) {
        var htmlString = "";
        $.each(themes, function (index, item) {
            //console.log("theme : ", item);
            //<li><label><button class="bb5-button bb5-button-bulky bb5-theme-choice-2" title="Choisr ce thème n°2"></button> Thème N°2</label></li>
            htmlString += '<li><label><button class="bb5-button bb5-button-bulky bb5-theme-choice-' + (index + 1) + ' selectTheme" data-theme="' + item + '" title="Choisr ce thème: ' + item + '"></button> ' + item + '</label></li>';
        //htmlString += '<li><label><button class="bb5-button bb5-theme-choice-' + (index + 1) + '" title="Choisr le thème ' + item + '" data-theme="' + item + '"></button> ' + item + '</label></li>';
        });
        $('#bb5-theme-extra').html(htmlString);
    },
    
    displayThemes: function () {
        this._callbacks["displayThemes_action"] = function(e) {
            $('#bb5-tplThemeContainer').toggle();
            e.stopPropagation();
        };
    },
    
    sendDataForNewTheme: function (themeName) {
        var self = this;
        self.contentLessWebservice = bb.webserviceManager.getInstance(self._settings.webservices.contentLessWS);
        self.contentLessWebservice.request("generateNewTheme" , {
            params: {
                name: themeName
            },
            success: function(response){
                $('#themeNameField').val(response.result);
                self.generateStyle(themeName);
                self.initThemes();
            },
            error: function(response){
                throw response.error;
            }
        });
    },
    
    displayThemeEditor: function () {
        var self = this;
        var inputValue;
        this._callbacks["displayThemeEditor_action"] = function(e) {
            inputValue = $(self._settings.mainContainer + ' #themeNameField').val();
            $('.themeEditorDialog input').val(inputValue);
            self.themeEditorDialog.show();
        };
    },
    
    reloadThemesDisplay: function () {
        
    },
    
    generateStyle: function (themeName) {
        //console.log(themeName);
        var self = this;
        self.contentLessWebservice = bb.webserviceManager.getInstance(self._settings.webservices.contentLessWS);
        self.contentLessWebservice.request("generateStyle" , {
            params: {
                name: themeName
            },
            success: function(response){
            //console.log(response.result);
            },
            error: function(response){
                throw response.error;
            }
        }); 
    }
});