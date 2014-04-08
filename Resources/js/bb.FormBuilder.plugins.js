//@ sourceURL=ressources/js/bb.FormBuilder.plugins.js

(function($) {

    var FormBuilder = bb.FormBuilder;

    bb.jquery(function() {


        /*scalar type*/
        FormBuilder.registerRenderTypePlugin("scalar", {
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                this.fieldWrapper = bb.jquery("<p></p>");
                this.template = bb.jquery("<label class='fieldLabel'>test</label><input class='bb5-plugin-form-field fieldText' type='text' value=''></input>").clone();
                this.fiedId = this.id + "-" + this._settings.fieldInfos.fieldLabel;
            },
            render: function() {
                bb.jquery(this.fieldWrapper).append(this.template);
                bb.jquery(this.fieldWrapper).find(".fieldLabel").html(this._settings.fieldInfos.fieldLabel);
                var value = this._settings.fieldInfos.param.scalar;
                bb.jquery(this.fieldWrapper).find(".fieldText").attr("id", this.fieldId).val(value);
                return this.fieldWrapper;
            },
            parse: function() {
                var result = {
                    scalar: bb.jquery(this.fieldWrapper).find(".fieldText").val()
                };
                return result;
            }
        });

        /*scalar type*/
        FormBuilder.registerRenderTypePlugin("text", {
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                this.fieldWrapper = bb.jquery("<p></p>");

                var pattern = (this._settings.fieldInfos.param.array.pattern) ? " pattern='" + this._settings.fieldInfos.param.array.pattern + "'" : '';

                this.template = bb.jquery("<label class='fieldLabel'>test</label><input class='bb5-plugin-form-field fieldText' type='text' value=''" + pattern + "></input>").clone();
                this.fiedId = this.id + "-" + this._settings.fieldInfos.fieldLabel;
            },
            render: function() {
                bb.jquery(this.fieldWrapper).append(this.template);
                bb.jquery(this.fieldWrapper).find('.fieldLabel').html(this._settings.fieldInfos.param.array.label);
                bb.jquery(this.fieldWrapper).find('.fieldText').attr("id", this.fieldId).val(this._settings.fieldInfos.param.array.value);
                return this.fieldWrapper;
            },
            parse: function() {
                var result = {
                    'array': {
                        'value': bb.jquery(this.fieldWrapper).find(".fieldText").val()
                    }
                };
                return result;
            }

        });

        /*Checkbox type*/
        FormBuilder.registerRenderTypePlugin('checkbox', {
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                this.fieldWrapper = bb.jquery('<p></p>');
                this.template = bb.jquery('<label class="fieldLabel">test</label><input class="bb5-plugin-form-field fieldCheck" type="checkbox" value="1">').clone();
                this.fiedId = this.id + '-' + this._settings.fieldInfos.fieldLabel;
            },
            render: function() {
                bb.jquery(this.fieldWrapper).append(this.template);
                bb.jquery(this.fieldWrapper).find('.fieldLabel').html(this._settings.fieldInfos.param.array.label);
                if (this._settings.fieldInfos.param.array.checked == true) {
                    bb.jquery(this.fieldWrapper).find('.fieldCheck').attr('checked', 'checked');
                }
                var value = this._settings.fieldInfos.param.scalar;
                bb.jquery(this.fieldWrapper).find('.fieldCheck').attr('id', this.fieldId).val(value);
                return this.fieldWrapper;
            },
            parse: function() {
                var result = {
                    'array': {
                        'checked': bb.jquery(this.fieldWrapper).find('.fieldCheck').is(':checked')
                    }
                };
                return result;
            }
        });

        /*Checkbox type*/
        FormBuilder.registerRenderTypePlugin('checkbox-multiple', {
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                this.fieldWrapper = bb.jquery('<p></p>');
                this.template = bb.jquery('<label class="fieldLabel">test</label>').clone();
                this.inputtpl = bb.jquery('<label><input class="bb5-plugin-form-field fieldCheck" type="checkbox" value=""></label>');
                this.fiedId = this.id + '-' + this._settings.fieldInfos.fieldLabel;
            },
            render: function() {
                var self = this;
                bb.jquery(this.fieldWrapper).append(this.template);
                bb.jquery(this.fieldWrapper).find('.fieldLabel').html(this._settings.fieldInfos.param.array.label);
                bb.jquery.each(this._settings.fieldInfos.param.array.options, function(value, text) {
                    var inputtpl = self.inputtpl.clone();
                    bb.jquery(inputtpl).append(text).find('input').val(value);
                    if (-1 < bb.jquery.inArray(value, self._settings.fieldInfos.param.array.checked))
                        bb.jquery(inputtpl).find('input').attr('checked', 'checked');
                    bb.jquery(self.fieldWrapper).append(inputtpl);
                });
                return this.fieldWrapper;
            },
            parse: function() {
                var self = this;
                var checked = new Array();
                bb.jquery.each(this.fieldWrapper.find('input:checked'), function(item, input) {
                    checked[checked.length] = bb.jquery(input).val();
                });

                var result = {
                    'array': {
                        'checked': checked
                    }
                };
                return result;
            }
        });

        /********************** media-list ************
         *my_medias:
         *  label: "Choisissez un Media",
         *  maxentry: 1
         *  minentry: 0,
         *  accept: [bbMedianame], //not used
         *  medias: "" //json encoded list
         ******/
        /**************Media selection render type **************/
        FormBuilder.registerRenderTypePlugin("media-list", {
            _settings: {
                removeBtnClass: ".bb5-ico-del"
            },
            _template: {
                mediaWrapper: '<div>'
                        + '<p><label class="fieldLabel">Media list</label></p>'
                        + '<div class="bb5-listContainer">'
                        + '<ul class="bb5-list-media bb5-list-media-is-list clearfix"></ul>'
                        + '</div>'
                        + '<p class="bb5-edit-article-toolbar">\n\
                                    <span class="bb5-edit-article-toolbar-label">Ajouter un média</span> \n\
                                    <a class="bb5-button bb5-ico-lib bb5-button-link add_media_btn bb5-button-thick bb5-button-outline" href="#">Médiathèque</a> \n\
                                </p>'
                        + '</div>',
                mediaItem: '<li class="bb5-selector-item">'
                        + '<p><a href="javascript:;"><img class="media-pic" src="" alt=""></a></p>'
                        + '<p><a class="media-title" href="javascript:;">${mediaTitle}</a></p>'
                        + '<p style="visibility : hidden"><span data-i18n="mediaselector.width">L :</span> 200px, <span data-i18n="mediaselector.height">H :</span> 142px, 11.81 kB</p>'
                        + '<p>'
                        + '<button class="bb5-button bb5-ico-del" data-i18n="popupmanager.button.delete">Supprimer</button>'
                        + '</p>'
                        + '</li>'
            },
            _init: function() {
                this._context = {};
                this._context.mediaSelector = null;
                this.form = $(this._template.mediaWrapper).clone();
                this._addBtn = this.form.find(".add_media_btn").eq(0);
                this._mediaContainer = bb.jquery(this.form).find(".bb5-list-media").eq(0);
                var label = this._settings.fieldInfos.param.array.label;
                bb.jquery(this.form).find(".fieldLabel").html(label); //i18nkey
                /* use for populate */
                var mediaInfos = this._settings.fieldInfos.param.array.medias;
                var maxEntry = this._settings.fieldInfos.param.array.maxentry;
                var minEntry = this._settings.fieldInfos.param.array.minentry;
                this.maxEntry = (maxEntry == "undefined" || isNaN(parseInt(maxEntry))) ? 999 : parseInt(maxEntry);
                this.minEntry = (maxEntry == "undefined" || isNaN(parseInt(minEntry))) ? 0 : parseInt(minEntry);
                this.minEntry = (this.maxEntry > this.minEntry) ? this.minEntry : 0;
                this._mediaList = new bb.SmartList({
                    idKey: "uid",
                    maxEntry: maxEntry,
                    onChange: bb.jquery.proxy(this._handleListChange, this),
                    onDelete: bb.jquery.proxy(this._removeMedia, this)
                });
                if (mediaInfos && mediaInfos.length) {
                    var mediaData = JSON.parse(mediaInfos);
                    this._mediaList.setData(mediaData);
                }
                this._bindEvents();
            },
            _handleListChange: function(collection, name, mediaData) {
                var render = bb.jquery(this._template.mediaItem).clone();
                bb.jquery(render).addClass("media-" + mediaData.uid);
                bb.jquery(render).find(".media-title").eq(0).html(mediaData.title);
                bb.jquery(render).find(".media-pic").eq(0).attr("src", mediaData.value).attr("alt", mediaData.title);
                mediaData.render = "";//bb.jquery(mediaData.render).get(0);
                bb.jquery(render).find(this._settings.removeBtnClass).data("mediaUid", mediaData.uid);
                this._mediaContainer.append(render);
            },
            _bindEvents: function() {
                this._addBtn.bind("click", bb.jquery.proxy(this._showMediaBrowser, this));
                this.form.delegate(this._settings.removeBtnClass, "click", bb.jquery.proxy(this._deleteMedia, this));
            },
            /* remove media */
            _removeMedia: function(container, name, id) {
                bb.jquery(this._mediaContainer).find(".media-" + id).remove();
            },
            /* add an accept key in params*/
            _typeIsValid: function(media) {
                var valid = true;
                /*if(media.data.content.classname.indexOf("Media\\image") != -1){
                 valid = true;
                 }
                 if(!valid){
                 alert("Media of type " +media.data.content.classname+" is not allowed!");
                 }
                 valid = true;*/
                return valid;
            },
            /* add media */
            _addMedia: function(params) {
                if (!this._typeIsValid(params))
                    return;
                if ("uid" in params) {
                    params.render = "";
                    this._mediaList.set(params.uid, params);
                }
            },
            _deleteMedia: function(e) {
                var mediaUid = bb.jquery(e.currentTarget).data("mediaUid");
                if (mediaUid) {
                    this._mediaList.deleteItemById(mediaUid);
                }
            },
            _showMediaBrowser: function() {
                var self = this;
                if (this._context.mediaSelector) {
                    /*afficher - selectionner noeud*/
                    if (this._context.mediaSelector.data("bbSelector")) {
                        this._context.mediaSelector.data("bbSelector").open();
                        return false;
                    }
                }

                if (!this._context.mediaSelector) {
                    var selectorMedia = bb.i18n.__('toolbar.editing.mediaselectorLabel');
                    var mediaSelectorContainer = $("<div id='bb5-form-mediasselector'/>").clone();
                    $("body").append(mediaSelectorContainer);
                    this._context.mediaSelector = $(mediaSelectorContainer).bbSelector({
                        popup: true,
                        pageSelector: false,
                        linkSelector: false,
                        mediaSelector: true,
                        contentSelector: false,
                        resizable: false,
                        selectorTitle: selectorMedia,
                        callback: function(item) {
                            bb.jquery('#bb5-param-mediasselector').bbSelector('close');
                        },
                        beforeWidgetInit: function() {
                            var bbSelector = bb.jquery(this.element).data('bbSelector');
                            bbSelector.onWidgetInit(bbSelector._panel.MEDIA_LINK, function() {
                                var bbMediaSelector = bb.jquery(this).data('bbMediaSelector') || false;
                                if (bbMediaSelector) {
                                    bbMediaSelector.setCallback(function(params) {
                                        self._addMedia(params);
                                        bbSelector.close();
                                    });
                                }
                            });
                        }
                    });
                }
                this._context.mediaSelector.data("bbSelector").open();
            },
            validate: function() {
                return true;
            },
            render: function() {
                return this.form;
            },
            parse: function() {
                var mediaContent = this._mediaList.toArray(true);

                var result = {
                    'array': {
                        medias: JSON.stringify(mediaContent)
                    }
                };
                return result;
            }
        });


        /*----------------link-list------------------------*/
        FormBuilder.registerRenderTypePlugin("link-list", {
            _settings: {
                formTplClass: ".link-list-from-tpl",
                itemTplClass: ".linkItem-tpl",
                itemCls: "bb5-link-item",
                itemContainerClass: ".linksContainer",
                blockLabelCls: ".block-label",
                highLightCls: "bb5-link-item-highlighted",
                actionContainerClass: ".btnContainer",
                fieldErrorCls: "hasError"
            },
            _context: {
                linkSelector: null,
                parsedData: [],
                selected: null
            },
            _template: {
                itemTpl: null
            },
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                this.form = bb.jquery(this._settings.formTplClass).eq(0).clone();
                this._template.itemTpl = this.form.find(this._settings.itemTplClass).eq(0);
                this.linksContainer = bb.jquery(this.form).find(this._settings.itemContainerClass).eq(0);
                var fieldLabel = (this._settings.fieldInfos.param.array.label != "undefined") ? this._settings.fieldInfos.param.array.label : "";
                var maxEntry = this._settings.fieldInfos.param.array.maxentry;
                var minEntry = this._settings.fieldInfos.param.array.minentry;
                this.maxEntry = (maxEntry == "undefined" || isNaN(parseInt(maxEntry))) ? 999 : parseInt(maxEntry);
                this.minEntry = (maxEntry == "undefined" || isNaN(parseInt(minEntry))) ? 0 : parseInt(minEntry);
                this.minEntry = (this.maxEntry > this.minEntry) ? this.minEntry : 0;
                bb.jquery(this.form).find(this._settings.blockLabelCls).eq(0).text(fieldLabel);
                this.linksContainer.empty();
                bb.jquery(this.form).removeClass(this._settings.formTplClass.replace(".", ""));
                var linksInfos = this._settings.fieldInfos.param.array.links;
                if (linksInfos && linksInfos.length) {
                    this._populate(JSON.parse(linksInfos));
                }
                this._bindEvents();
            },
            _populate: function(linksInfos) {
                var self = this;
                if (bb.jquery.isArray(linksInfos)) {
                    bb.jquery.each(linksInfos, function(i, linkData) {
                        self._populateLink(linkData);
                    });
                }
            },
            /**
             *return true of it's ok to add a new item
             **/
            _checkBondaries: function() {
                var result = false;
                var nbItems = bb.jquery(this.linksContainer).find(".bb5-link-item").length;
                return !this.maxEntry <= nbItems;
            },
            _bindEvents: function() {
                var self = this;

                bb.jquery(this.form).find(".addLinkBtn").die().unbind().bind("click", function(e) {
                    self._showLinkSelector();
                });

                bb.jquery(this.form).find("." + this._settings.itemCls).live("mouseenter", function(e) {
                    var currentLink = e.currentTarget;
                    bb.jquery("." + self._settings.itemCls).removeClass(self._settings.highLightCls);
                    self._highlight(currentLink);
                    return;
                });

                bb.jquery(this.form).find("." + this._settings.itemCls).live("mouseleave", function(e) {
                    bb.jquery("." + self._settings.itemCls).removeClass(self._settings.highLightCls);
                    bb.jquery("." + self._settings.itemCls).find(self._settings.actionContainerClass).hide();
                    return;
                });

                /*bind delete action*/
                bb.jquery(this.form).find(".bb5-ico-del").live("click", function(e) {
                    var target = e.target;
                    var linknode = bb.jquery(target).parents("." + self._settings.itemCls).eq(0);
                    if (linknode) {
                        bb.jquery(linknode).remove();
                        return;
                    }
                });

            },
            _handleError: function(fieldsWithErrors) {
                var self = this;
                if (bb.jquery.isArray(fieldsWithErrors)) {
                    bb.jquery.each(fieldsWithErrors, function(i, node) {
                        bb.jquery(node).addClass(self._settings.fieldErrorCls);
                        bb.jquery(node).unbind("click").bind("click", function(e) {
                            bb.jquery(node).removeClass(self._settings.fieldErrorCls);
                        });
                    });
                }
            },
            _highlight: function(node) {
                if (!node)
                    return;
                /*clean all*/
                bb.jquery("." + this._settings.itemCls).removeClass(this._settings.highLightCls);
                bb.jquery(node).addClass(this._settings.highLightCls);
                bb.jquery(node).find(this._settings.actionContainerClass).show();

            },
            _showLinkSelector: function() {
                var self = this;
                if (!this._checkBondaries())
                    return false;
                if (this._context.linkSelector) {
                    /*afficher - selectionner noeud*/
                    if (this._context.linkSelector.data("bbSelector")) {
                        this._context.linkSelector.data("bbSelector").open();
                        return false;
                    }
                }

                if (!this._context.linkSelector) {
                    var selectorLink = bb.i18n.__('toolbar.editing.linkselectorLabel');
                    var linkSelectorContainer = bb.jquery("<div id='bb5-param-linksselector' class='bb5-selector-wrapper'></div>").clone();
                    this._context.linkSelector = bb.jquery(linkSelectorContainer).bbSelector({
                        popup: true,
                        pageSelector: true,
                        linkSelector: true,
                        mediaSelector: false,
                        contentSelector: false,
                        resizable: false,
                        selectorTitle: selectorLink,
                        callback: function(item) {
                            bb.jquery('#bb5-param-linksselector').bbSelector('close');
                        },
                        beforeWidgetInit: function() {
                            var bbSelector = bb.jquery(this.element).data('bbSelector');
                            bbSelector.onWidgetInit(bbSelector._panel.INTERNAL_LINK, function() {
                                var bbPageSelector = bb.jquery(this).data('bbPageSelector') || false;
                                if (bbPageSelector) {
                                    bbPageSelector.setCallback(function(params) {
                                        self._populateLink(params);
                                        bbSelector.close();
                                    });
                                }
                            });

                            /*for External link*/
                            bbSelector.onWidgetInit(bbSelector._panel.EXTERNAL_LINK, function() {
                                var bbLinkSelector = bb.jquery(this).data('bbLinkSelector');
                                bbLinkSelector.setCallback(function(params) {
                                    var linkPattern = /^([\w]+:\/\/)/gi//@fixme allow choices
                                    params.value = (linkPattern.test(params.value)) ? params.value : "http://" + params.value;
                                    self._populateLink(params);
                                    bbSelector.close();
                                });
                            });
                        }

                        //open: bb.jquery.proxy(self.bbSelectorHandlers.openHandler,self,"bbLinkInternalContainer"),
                        //resizeStart: bb.jquery.proxy(this.bbSelectorHandlers.resizeStartHandler,this,"bbLinkInternalContainer"),
                        //resize : bb.jquery.proxy(this.bbSelectorHandlers.resizeHandler,this,"bbLinkInternalContainer")
                    });
                }
                this._context.linkSelector.data("bbSelector").open();
            },
            /*afficher le lien {url, title, target, pageuid}*/
            _populateLink: function(data) {
                var itemTpl = bb.jquery(this._template.itemTpl).clone().removeClass("linkItem-tpl").addClass(this._settings.itemCls);
                bb.jquery(itemTpl).data("uid", data.uid);
                var linkId = bb.Utils.generateId("link");
                bb.jquery(itemTpl).find(".link").attr("disabled", 1).val(data.value);
                bb.jquery(itemTpl).find(".title").val(data.title);
                if (data.target == "_blank") {
                    bb.jquery(itemTpl).find(".targetBlank").attr("checked", 1);
                }
                if (data.target == "_self") {
                    bb.jquery(itemTpl).find(".targetSelf").attr("checked", 1);
                }
                bb.jquery(itemTpl).find(".targetBlank,.targetSelf").attr("name", linkId);
                this.linksContainer.append(itemTpl);
                this.linksContainer.stop().animate({
                    scrollTop: bb.jquery(this.linksContainer).height()
                }, 800);
                this._parseLinks();
            },
            _parseLinks: function() {
                var self = this;
                self._context.parsedData = [];
                bb.jquery.each(this.form.find("." + this._settings.itemCls), function(i, link) {
                    var linksInfos = {};
                    linksInfos.uid = bb.jquery(link).data("uid") || false;
                    linksInfos.target = bb.jquery(link).find('.target:checked').val();
                    linksInfos.title = bb.jquery(link).find(".title").val();
                    linksInfos.value = bb.jquery(link).find(".link").val();
                    self._context.parsedData.push(linksInfos);
                });
            },
            render: function() {
                return this.form;
            },
            onOpen: function(e, data) {
            },
            /*validate form: This function is called before submit*/
            validate: function() {
                var isValid = true;
                var linksWithErrors = [];
                bb.jquery.each(this.form.find("." + this._settings.itemCls), function(i, linkNode) {
                    var titleField = bb.jquery(linkNode).find(".title").eq(0);
                    var title = bb.jquery(titleField).val();
                    if (!bb.jquery.trim(title).length) {
                        linksWithErrors.push(bb.jquery(linkNode).find(".title"));
                    }
                });
                if (linksWithErrors.length > 0) {
                    this._handleError(linksWithErrors);
                    isValid = false;
                }
                /* handle minetry */
                if (this.minEntry) {
                    if (bb.jquery(this.linksContainer).find(".bb5-link-item").length < this.minEntry) {
                        isValid = false;
                    }
                }
                return isValid;
            },
            parse: function() {
                this._parseLinks();
                var result = {
                    "array": {
                        links: JSON.stringify(this._context.parsedData)
                    }
                };
                return result;
            }
        });



        /*----------------node-selector------------------------*/
        FormBuilder.registerRenderTypePlugin("node-selector", {
            _settings: {
                addNodeBtnCls: ".addParentNodeBtn",
                formTplClass: ".node-selector-form-tpl",
                formfielClass: ".bb5-plugin-form-field",
                fieldWrapperClass: ".fieldWrapper",
                treeBtnClass: ".bb5-ico-tree",
                addPageBtnClass: ".add_page_btn",
                rmPageBtnClass: ".bb-remove-page"
            },
            _template: {
                itemTpl: "<li class='page-item'>{{title}}<i class='bb5-button bb5-ico-del bb-remove-page'></i></li>",
                mainWrapper: '<div>'
                        + '<p><label class="fieldLabel">Page(s)</label></p>'
                        + '<div class="bb5-page-wrapper">'
                        + '<ul class="bb5-page-container clearfix"></ul>'
                        + '</div>'
                        + '<p class="bb5-edit-article-toolbar">\n\
                                    <span class="bb5-edit-article-toolbar-label">Add a page</span> \n\
                                    <a class="bb5-button bb5-ico-tree add_page_btn bb5-button-outline bb5-button-thick" href="#">Page browser</a> \n\
                                </p>'
                        + '</div>'
            },
            i18n: {
                pageBrowserTitle: "Selectionner une page"
            },
            _context: {
                parsedData: {},
                pageBrowser: ""
            },
            _init: function() {
                this.allowMultipleSelection = ("allowMultipleSelection" in this._settings.fieldInfos.param.array) ? this._settings.fieldInfos.param.array.allowMultipleSelection : false;
                this.id = this._settings.formId + '-' + this.id;
                this.form = bb.jquery(this._settings.formTplClass).clone();
                if (this.allowMultipleSelection) {
                    var multiSelec = $(this._template.mainWrapper).clone();
                    $(this.form).find(".parentnode-tree").closest(".fieldWrapper").replaceWith($(multiSelec));
                }

                var fieldLabel = (typeof this._settings.fieldInfos.param.array.label == "string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
                bb.jquery(this.form).removeClass(this._settings.formTplClass.replace(".", ""));
                bb.jquery(this.form).attr("id", this.id);
                this.kwRenderer = this._initKeywordsRenderer();
                /* for retro-compatibility we just keep ids*/
                if (this.allowMultipleSelection) {
                    this.pagesContainer = $(this.form).find(".bb5-page-container").eq(0);
                    var pageInfos = this._settings.fieldInfos.param.array.nodeInfos;
                    this.maxEntry = (this._settings.fieldInfos.param.array.maxentry) ? this._settings.fieldInfos.param.array.maxentry : 999;
                    this._pageList = new bb.SmartList({
                        idKey: "uid",
                        maxEntry: this.maxEntry,
                        onChange: bb.jquery.proxy(this._handleListChange, this),
                        onDelete: bb.jquery.proxy(this._removePage, this)
                    });
                    if (pageInfos && pageInfos.length) {
                        this._pageList.setData(JSON.parse(pageInfos));
                    }
                }
                this.bindEvents();
                this.kwRenderer = this._initKeywordsRenderer();
                this._populateForm();
            },
            _handleListChange: function(collection, key, item) {
                var render = this._template.itemTpl.replace("{{title}}", item.title);
                render = $(render).attr("data-page-uid", item.uid);
                $(render).attr("id", item.uid);
                this.pagesContainer.append(render);
                this._updateParseData();
            },
            _removePage: function(container, name, id) {
                bb.jquery(this.pagesContainer).find("#" + id).remove();
                this._updateParseData();
            },
            _updateParseData: function() {
                var $ = bb.jquery;
                var rawData = this._pageList.toArray(true);
                var nodeIds = [];
                $.each(rawData, function(i, data) {
                    nodeIds.push(data.uid);
                });
                this._context.parsedData.nodeInfos = JSON.stringify(rawData);
                this._context.parsedData.parentnode = nodeIds;
            },
            callbacks: {
                clickOnFieldHandler: function(e) {
                    var currentTarget = e.currentTarget;
                    if (bb.jquery(currentTarget).hasClass("parentnode-tree")) {
                        this.showPageTree(bb.jquery(currentTarget).next());
                    }
                    if (bb.jquery(currentTarget).hasClass("classcontent-tree")) {
                        this.initOrShowContentSelector(bb.jquery(currentTarget).next());
                    }
                },
                showPageTree: function() {
                    this.showPageTree({
                        multipleSelection: true
                    });
                }
            },
            _populateForm: function() {
                var self = this;
                var parentnodeTitle = this._settings.fieldInfos.param.array["parentnodeTitle"] || "";
                bb.jquery(this.form).find(this._settings.formfielClass).each(function(i, field) {
                    var key = bb.jquery(field).attr("data-key");
                    if (bb.jquery.inArray(key, self._settings.disabledFields) != -1) {
                        bb.jquery(field).parents(self._settings.fieldWrapperClass).hide();
                        return true;
                    }
                    var fieldValue = (key == "limit") ? parseInt(self._settings.fieldInfos.param.array[key]) : self._settings.fieldInfos.param.array[key][0];
                    bb.jquery(field).val(fieldValue);
                    if (key == "parentnode") {
                        bb.jquery(field).val(parentnodeTitle);
                    }
                    if (key == 'classcontent')
                        bb.jquery(field).val(bb.jquery(field).val().replace('BackBuilder\\ClassContent\\', ''));
                    if (key != "limit")
                        bb.jquery(field).attr("data-fieldvalue", fieldValue);
                    bb.jquery(field).trigger("change");
                    return true;
                });
                if (this.kwRenderer) {
                    bb.jquery(this.form).append(this.kwRenderer.render());
                }

            },
            /**
             * Keyword has its own renderer that we gonna use.
             * Instance is created by calling bb.FormBuilder.createSubformRenderer
             * this function return a renderer
             **/
            _initKeywordsRenderer: function() {
                var keywordsRender = false;
                if (typeof this._settings.fieldInfos.param.array.keywordsselector != "undefined") {
                    var keywordSelector = this._settings.fieldInfos.param.array.keywordsselector;
                    keywordsRender = bb.FormBuilder.createSubformRenderer("keywordsselector", keywordSelector, this._settings.formId);
                }
                return keywordsRender;
            },
            showPageTree: function(context) {
                var pageBrowser = bb.jquery("<div id='bb5-form-pagebrowser'><div id='browser' class='filetree'></div></div>").clone();
                var self = this;
                if (this._context.pageBrowser) {
                    /*afficher - selectionner noeud*/
                    if (this._context.pageBrowser.data("bbPageBrowser")) {
                        this._context.pageBrowser.data("bbPageBrowser").open();
                        return false;
                    }

                } else {
                    this._context.pageBrowser = bb.jquery(pageBrowser).bbPageBrowser({
                        title: this.i18n.pageBrowserTitle,
                        popup: {
                            width: 269,
                            height: 500,
                            position: [0, 60] //handle destroy on close
                        },
                        editMode: false,
                        enableMultiSite: true,
                        site: bb.frontApplication.getSiteUid(),
                        breadcrumb: bb.frontApplication.getBreadcrumbIds(),
                        select: function(e, data) {
                            if (typeof (context) == "object" && (context.multipleSelection)) {
                                var selectedPage = bb.jquery("#node_" + data.node_id).find("a").get(0).textContent;
                                var data = {
                                    uid: data.node_id,
                                    title: selectedPage
                                };
                                self._pageList.set(data.uid, data);

                            } else {
                                bb.jquery(context).attr("data-fieldValue", data.node_id);
                                bb.jquery(context).val(bb.jquery("#node_" + data.node_id).find("a").get(0).textContent);
                                bb.jquery(context).trigger("change");
                                bb.jquery(this).bbPageBrowser("close");
                                self._context.pageBrowser = false;
                            }
                        }
                    });
                    this._context.pageBrowser.data("bbPageBrowser").open();
                }
            },
            initOrShowContentSelector: function(formField) {
                var contentTypeSelector = bb.jquery("<div id='bb5-form-contentTypeSelector'><div class='bb5-windowpane-treewrapper-inner' class='filetree'></div></div>").clone();
                var self = this;
                if (this._context.contentTypeSelector) {
                    if (this._context.contentTypeSelector.data("bbContentTypeBrowser")) {
                        this._context.contentTypeSelector.data("bbContentTypeBrowser").open();
                        return false;
                    }

                }
                this._context.contentTypeSelector = bb.jquery(contentTypeSelector).bbContentTypeBrowser({
                    popup: {
                        width: 200,
                        height: 500,
                        position: [0, 120]
                    },
                    site: bb.frontApplication.getSiteUid(),
                    ready: function() {
                    },
                    select: function(e, nodeInfos) {
                        if (nodeInfos.nodeType != "contentType")
                            return false; //main category do nothing
                        var selectedNode = nodeInfos.node;
                        var selectedContentType = bb.jquery(selectedNode).find("a").get(0).textContent;
                        var pattern = /contentType_(\w+)/i;
                        var fieldValue = selectedContentType.substr(1);
                        if (pattern.test(bb.jquery(selectedNode).attr('rel'))) {
                            var result = pattern.exec(bb.jquery(selectedNode).attr('rel'));
                            fieldValue = result[1];
                        }
                        if (selectedContentType.indexOf('BackBuilder\\ClassContent\\') === -1) {
                            fieldValue = 'BackBuilder\\ClassContent\\' + fieldValue;
                        }

                        bb.jquery(formField).attr("data-fieldValue", fieldValue);
                        bb.jquery(formField).trigger("change");
                        bb.jquery(formField).val(selectedContentType);
                        bb.jquery(this).bbContentTypeBrowser("close");
                        self._context.contentTypeSelector = false;
                    }
                });
                this._context.contentTypeSelector.data("bbContentTypeBrowser").open();
            },
            bindEvents: function() {
                var $ = bb.jquery;
                bb.jquery(this.form).delegate(this._settings.treeBtnClass, "click", bb.jquery.proxy(this.callbacks["clickOnFieldHandler"], this));
                bb.jquery(this.form).delegate(this._settings.addPageBtnClass, "click", bb.jquery.proxy(this.callbacks["showPageTree"], this));
                $(this.form).delegate(this._settings.rmPageBtnClass, "click", $.proxy(this.removePage, this));
                this.initFormAutoBind();
            },
            removePage: function(e) {
                var target = e.target;
                var pageNode = $(target).closest(".page-item");
                this._pageList.deleteItemById($(pageNode).data("pageUid"));
            },
            initFormAutoBind: function() {
                bb.jquery(this.form).delegate(this._settings.formfielClass, "change", bb.jquery.proxy(this.handleFieldsChange, this));
            },
            handleFieldsChange: function(e) {
                var nodeType = bb.jquery(e.currentTarget).attr("data-key") || "none";
                var nodeValue = bb.jquery(e.currentTarget).attr("data-fieldValue") || bb.jquery(e.currentTarget).val();
                if (nodeType == "limit") {
                    this._context.parsedData[nodeType] = parseInt(bb.jquery.trim(nodeValue));
                } else {
                    if (nodeType != "none") {
                        this._context.parsedData[nodeType] = [bb.jquery.trim(nodeValue)];
                    }
                    if (nodeType == "parentnode") {
                        this._context.parsedData["parentnodeTitle"] = bb.jquery(e.currentTarget).val();
                    }
                }

            },
            render: function() {
                return this.form;
            },
            parse: function() {
                if (this.kwRenderer) {
                    var keywordsData = this.kwRenderer.parse();
                    /*add keywords if not empty*/
                    if (keywordsData.array.selected) {
                        var selectedKeywords = keywordsData.array.selected;
                        this._context.parsedData["keywordsselector"] = {
                            selected: selectedKeywords
                        }
                    }
                }
                /**/
                var result = {
                    "array": this._context.parsedData
                };
                return result;
            },
            onClose: function() {
                /*masquer tous les arbes*/
                if (this._context.contentTypeSelector) {
                    if (this._context.contentTypeSelector) {
                        if (this._context.contentTypeSelector.data("bbContentTypeBrowser")) {
                            this._context.contentTypeSelector.data("bbContentTypeBrowser").destroy();
                        }
                    }
                }
                if (this._context.pageBrowser) {
                    if (this._context.pageBrowser.data("bbPageBrowser")) {
                        this._context.pageBrowser.data("bbPageBrowser").destroy();
                    }

                }
            }


        });


        /*--------------select renderType-------------------------*/
        FormBuilder.registerRenderTypePlugin("select", {
            _settings: {
                emptyLabel: "Provide a label for this field"
            },
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                var template = bb.jquery("<p><label>Provide a label for this field</label><select></select></p>").clone();
                bb.jquery(template).attr("id", this.id);
                var form = this._populateForm();
                var fieldLabel = (typeof this._settings.fieldInfos.param.array.label == "string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
                bb.jquery(template).find("label").text(fieldLabel);
                bb.jquery(template).find("select").append(form);
                if ('undefined' != typeof (this._settings.fieldInfos.param.array.onchange))
                    bb.jquery(template).find("select").attr('onchange', this._settings.fieldInfos.param.array.onchange);
                this.form = template;

            },
            _populateForm: function() {

                var options = this._settings.fieldInfos.param.array.options;
                var selection = this._settings.fieldInfos.param.array.selected;
                var dFragment = document.createDocumentFragment();

                bb.jquery.each(options, function(value, option) {
                    var optionTpl = bb.jquery("<option></option>").clone();
                    bb.jquery(optionTpl).attr("value", value);
                    bb.jquery(optionTpl).html(option);
                    if (selection == value)
                        bb.jquery(optionTpl).attr("selected", "selected");
                    dFragment.appendChild(bb.jquery(optionTpl).get(0));
                });
                return dFragment;
            },
            render: function() {
                if ('undefined' != typeof (this._settings.fieldInfos.param.array.onrender))
                    eval(this._settings.fieldInfos.param.array.onrender);

                return this.form;
            },
            parse: function() {
                var result = {
                    "array": {
                        selected: bb.jquery(this.form).find("select").eq(0).val()
                    }
                };
                return result;
            }

        });
        /*--------------select-multiple renderType-------------------------*/
        FormBuilder.registerRenderTypePlugin("select-multiple", {
            _settings: {
                emptyLabel: "Provide a label for this field"
            },
            _init: function() {
                var self = this;

                this.id = this._settings.formId + '-' + this.id;
                var template = bb.jquery('<p><label>Provide a label for this field</label><select class="available" style="width:150px;"><option>' + bb.i18n.__('parameters.add_multiple') + '</option></select><br/><select class="selected" size="4" style="width:150px;height:65px;float:left;"></select><button style="display:block;" class="bb5-button bb5-ico-arrow_n bb5-button-square"></button><button style="display:block;" class="bb5-button bb5-ico-del bb5-button-square"></button><button style="display:block;" class="bb5-button bb5-ico-arrow_s bb5-button-square"></button></p>').clone();
                bb.jquery(template).attr("id", this.id).addClass('bb5-param-' + this._settings.fieldInfos.fieldLabel);
                var available = this._populateAvailable();
                var form = this._populateForm();
                var fieldLabel = (typeof this._settings.fieldInfos.param.array.label == "string") ? this._settings.fieldInfos.param.array.label : this._settings.emptyLabel;
                bb.jquery(template).find("label").text(fieldLabel);
                bb.jquery(template).find("select.available").append(available);
                bb.jquery(template).find("select.selected").append(form);
                if ('undefined' != typeof (this._settings.fieldInfos.param.array.onchange))
                    bb.jquery(template).find("select").attr('onchange', this._settings.fieldInfos.param.array.onchange);

                this.form = template;
                this.initvalues = this._settings.fieldInfos.param.array.selected;
                for (var i = 0; i < this.initvalues.length; i++)
                    this.initvalues[i] = null;

                bb.jquery(self.form).find("select.available").bind('change', function() {
                    bb.jquery(self.form).find("select.selected").append(bb.jquery(this).find(':selected').remove());
                });
                bb.jquery(self.form).find('button.bb5-ico-del').unbind('click').bind('click', function() {
                    bb.jquery(self.form).find('select.available').append(bb.jquery(self.form).find('select.selected option:selected').remove()).get(0).selectedIndex = 0;
                });
                bb.jquery(self.form).find('button.bb5-ico-arrow_n').unbind('click').bind('click', function() {
                    bb.jquery(self.form).find('select.selected option:selected').after(bb.jquery(self.form).find('select.selected option:selected').prev().remove());
                });
                bb.jquery(self.form).find('button.bb5-ico-arrow_s').unbind('click').bind('click', function() {
                    bb.jquery(self.form).find('select.selected option:selected').before(bb.jquery(self.form).find('select.selected option:selected').next().remove());
                });
            },
            _populateForm: function() {

                var options = this._settings.fieldInfos.param.array.options;
                var selection = this._settings.fieldInfos.param.array.selected;
                var dFragment = document.createDocumentFragment();

                bb.jquery.each(selection, function(index, value) {
                    if (options[value]) {
                        var optionTpl = bb.jquery("<option></option>").clone();
                        bb.jquery(optionTpl).attr("value", value);
                        bb.jquery(optionTpl).html(options[value]);
                        dFragment.appendChild(bb.jquery(optionTpl).get(0));
                    }
                });

                return dFragment;
            },
            _populateAvailable: function() {

                var options = this._settings.fieldInfos.param.array.options;
                var selection = this._settings.fieldInfos.param.array.selected;
                var dFragment = document.createDocumentFragment();

                bb.jquery.each(options, function(value, option) {
                    if (-1 == bb.jquery.inArray(value + '', selection)) {
                        var optionTpl = bb.jquery("<option></option>").clone();
                        bb.jquery(optionTpl).attr("value", value);
                        bb.jquery(optionTpl).html(option);
                        dFragment.appendChild(bb.jquery(optionTpl).get(0));
                    }
                });
                return dFragment;
            },
            render: function() {
                if ('undefined' != typeof (this._settings.fieldInfos.param.array.onrender))
                    eval(this._settings.fieldInfos.param.array.onrender);

                return this.form;
            },
            parse: function() {
                var values = [];
                bb.jquery(this.form).find('select.selected option').each(function() {
                    values[values.length] = bb.jquery(this).val();
                })

                var result = {
                    "array": {
                        selected: bb.jquery.extend(this.initvalues, values)
                    }
                };
                return result;
            }

        });

        /*--------------keyword-selector-autocomplete renderType-------------------------*/
        FormBuilder.registerRenderTypePlugin("keyword-selector-autocomplete", {
            _settings: {
                selectedCtnClass: ".selectedContainer",
                keywordContainerClass: ".selectedKeywords"
            },
            _init: function() {
                this.id = this._settings.formId + '-' + this.id;
                this.fieldWrapper = bb.jquery("<p></p>");
                var template = bb.jquery("<label class='fieldLabel'>Choisir un mot clé</label>"
                        + "<p class='selectedContainer'><strong>Sélection(s) :</strong><span class='selectedKeywords'></span></p>"
                        + "<textarea class='bb5-plugin-form-field fieldKeyword bb5-form-inputtext' value=''></textarea>").clone();
                this.fiedId = this.id + "-" + this._settings.fieldInfos.fieldLabel;
                this.template = this.fieldWrapper.append(template);
                this.maxEntry = this._settings.fieldInfos.param.array.maxentry || 0;
                this.keywordField = bb.jquery(this.template).find(".fieldKeyword").eq(0);
                this.keywordSelectedContainer = bb.jquery(this.template).find(this._settings.selectedCtnClass).eq(0);
                this.selectedContainer = bb.jquery(this.template).find(this._settings.keywordContainerClass).eq(0);
                this.keywordSelectedContainer.hide();
                this.selected = this._settings.fieldInfos.param.array.selected || [];
                this.selectedItems = {};
                this.keywordsList = {};
                this._populateKeywords();
                this._initAutoComplete();
            },
            render: function() {
                return this.template;
            },
            /* Check mandatory params*/
            _checkParams: function() {

            },
            _hasManyEntries: function() {

            },
            _findKeywordByIds: function(keywordIds) {
                var self = this;
                bb.webserviceManager.getInstance('ws_local_keyword').request('getKeywordByIds', {
                    params: {
                        ids: keywordIds
                    },
                    async: false,
                    useCache: true,
                    success: function(response) {
                        if (response.result) {
                            var keywordString = '';
                            bb.jquery.each(response.result, function(item, keyword) {
                                if (keyword.label) {
                                    self.keywordsList[keyword.label] = keyword.value;
                                    if ('' !== keywordString) {
                                        keywordString += ', ';
                                    }
                                    keywordString += keyword.label;
                                }
                            });
                            bb.jquery(self.keywordField).val(keywordString);
                        }
                    }
                });
                return;
            },
            _cleanSelection: function(selected) {
                var selected = (bb.jquery.isPlainObject(selected)) ? selected : {};
                var keywordIds = [];
                bb.jquery.each(selected, function(key, label) {
                    keywordIds.push(key);
                });
                return keywordIds;
            },
            /*handle multiple selections*/
            _handleKeywordSelection: function(terms, selectedKeyword) {
                var self = this;
                var selectedItems = [];

                if (selectedKeyword) {
                    this.keywordsList[selectedKeyword.label] = selectedKeyword.value;
                }

                if (this.maxEntry > 0) {
                    var termArr = [];
                    var cmpt = 0;
                    while (cmpt <= this.maxEntry) {
                        if (0 === terms.length) {
                            break;
                        }

                        term = terms.pop();
                        if ('undefined' !== typeof (term) && '' !== term) {
                            termArr.push(term);
                            cmpt++;
                        }
                    }
                    terms = termArr.reverse();
                }

                this.selected = [];
                bb.jquery.each(terms, function(i, keyword) {
                    if ('undefined' !== self.keywordsList[keyword]) {
                        self.selected.push(self.keywordsList[keyword]);
                    }
                });
                this.keywordField.val(terms.join(", "));
            },
            _split: function(val) {
                return val.split(/,\s*/);
            },
            _populateKeywords: function() {
                bb.jquery(this.keywordField).val('');

                if (!this.selected.length) {
                    return false;
                }

                this._findKeywordByIds(this.selected);
            },
            _updateFieldSpace: function() {
                /*bb.jquery(this.keywordField).animate({
                 height : "16px"
                 },"fast");*/

            },
            _getKeywordList: function() {
                var self = this;
                var list = null;
                bb.webserviceManager.getInstance('ws_local_keyword').request('getKeywordsList', {
                    params: {},
                    async: false,
                    useCache: true,
                    success: function(response) {
                        list = response.result;
                    }
                });
                return list;
            },
            _getLast: function(term) {
                return this._split(term).pop();
            },
            _initAutoComplete: function() {
                var self = this;
                bb.jquery(this.keywordField).autocomplete({
                    minLength: 3,
                    source: function(request, response) {
                        var term = self._getLast(request.term);
                        bb.webserviceManager.getInstance('ws_local_keyword').request('getKeywordsList', {
                            params: {
                                term: term
                            },
                            async: false,
                            useCache: true,
                            success: function(rservice) {
                                list = rservice.result;
                                //response(bb.jquery.ui.autocomplete.filter(self.keywordsList,self._getLast(request.term)));
                                response(list);
                            }
                        });
                    },
                    focus: function() {
                        return false;
                    },
                    change: function(event, ui) {
                        var terms = self._split(this.value);
                        self._handleKeywordSelection(terms, ui.item);
                    },
                    /*@fix empty list etc*/
                    select: function(event, ui) {
                        var terms = self._split(this.value);
                        terms.pop();//last
                        terms.push(ui.item.label);
                        terms.push("");
                        self.selectedItems[ui.item.label] = ui.item.value;
                        self._handleKeywordSelection(terms, ui.item);
                        return false;
                    }
                });
            },
            parse: function() {
                var result = {
                    "array": {
                        selected: this.selected
                    }
                };
                return result;
            }

        });
    });

})(bb.jquery);
