var bb = bb || {};

bb.ContentWrapper = (function($, global) {

    var wsManager = null;
    var _contentsCollection = null;
    var _dirtyContentCollection = null;

    var _settings = {
        changeWebService: "ws_local_contentBlock",
        contentWebService: "ws_local_contentBlock",
        classContentWebService: "ws_local_classContent",
        contentSetClass: ".bb5-droppable-item",
        rootContentSetCls: "rootContentSet",
        bbContentClass: ".bb5-content",
        resizableContentClass: ".bb5-resizable-item",
        draggableContentClass: ".bb5-draggable-item",
        emptyContentCls: "bb5-content-void",
        rootContentSetCls :"rootContentSet"
    };


    var _init = function() {
    }

    var _contentsCollection = new bb.SmartList({
        idKey: "id"
    });

    var _dirtyContentCollection = new bb.SmartList({
        idKey: 'uid'
    });

    /*Get contentBy ref*/
    var _getContentByRef = function(ref) {
        return _contentsCollection.get(ref);
    }

    var _isAContentSet = function(contentNode) {
        return (bb.jquery(contentNode).hasClass(_settings.contentSetClass.replace(".", ""))) ? true : false;
    }

    var _isARootContentSet = function(contentNode) {
        return bb.jquery(contentNode).hasClass(_settings.rootContentSetCls);
    }

    var _isABbContent = function(contentNode) {
        return (bb.jquery(contentNode).hasClass(_settings.bbContentClass.replace(".", ""))) ? true : false;
    }

    var _isASubContent = function(contentNode){
        var dataElement = bb.jquery(contentNode).attr("data-element");
        var isASubContent = (dataElement && dataElement != 0) ? true : false;

        return isASubContent;
    }

    var _isAnAutoBlock = function(contentNode) {
        var isAnAutoBlock;
        var forbiden_action = bb.jquery(contentNode).attr("data-forbidenactions");
        if (forbiden_action && 0 <= forbiden_action.indexOf('subcontent-selection')) {
            isAnAutoBlock = true;
        } else {
            var contentType = bb.jquery(contentNode).attr("data-type");
            var autoBlocPattern = /autoblo[c|ck]/gi;
            isAnAutoBlock = autoBlocPattern.test(contentType);
        }
        /*disable droppable for container && disable sortable for droppable*/
        return isAnAutoBlock;
    }

    var _getContentSize = function(contentNode) {
        return bb.Utils.readSizeFromClasses(bb.jquery(contentNode).attr("class")) || null;
    }

    /* var _handleIntemContainer = function(contentNode){
     var contentUid = contentNode.attr("data-uid");
     if(typeof contentUid=="string"){
     var hasItemContainer = bb.jquery(contentNode).attr("data-itemcontainer") || false;
     if(!hasItemContainer){
     var itemContainer = bb.jquery(contentNode).find("[data-refparent='"+contentUid+"']").eq(0);
     if(itemContainer && itemContainer.length){
     var id = bb.Utils.generateId("itemcontainer");
     bb.jquery(contentNode).attr("data-itemcontainer",id);
     bb.jquery(hasItemContainer).addClass(id);
     }

     }
     }
     return contentNode;
     }*/

    var _getContentAccept = function(contentNode) {
        /*not useful for contentSet format {
         *title:['BackBee\ClassContent\Element\text'],
         *body:['BackBee\ClassContent\Element\text']
         *}*/
        if (!_isAContentSet(contentNode))
        {
            var accept = {};
            var subContent = bb.jquery(contentNode).find("[data-element]").each(function(i, item) {
                /*ne prendre que les enfants immédiats du noeud*/
                if (bb.jquery(item).attr("data-parent") == bb.jquery(contentNode).attr("data-uid")) {
                    var nodeName = bb.jquery(item).attr("data-element");
                    accept[nodeName] = bb.jquery(item).attr("data-type");
                }
            });
            return accept;
        }
    }

    /*node parser
     *
     *itemcontainer is useful for contentset
     *
     **/
    var _getInfosFromNode = function(node) {
        if (!node)
            return false;
        var contentInfos = {};
        /*refparent is coupled with itemcontainer*/
        var availableContentAttrs = ["uid", "isloaded", "rendermode", "maxentry", "refparent", "type", "accept", "contentplugins", "element","rteconf", "parent", "draftuid", "itemcontainer"];
        bb.jquery.each(availableContentAttrs, function(i, attr) {
            contentInfos[attr] = bb.jquery(node).attr("data-" + attr) || false;
        });
        return contentInfos;
    }

    var _persist = function(asyncPersist) {
        var asyncPersist = (typeof asyncPersist == "boolean") ? asyncPersist : true; /*{async:true} par defaut*/
        var cleanData = _dirtyContentCollection.toArray();
        if (bb.jquery.isEmptyObject(cleanData[0]))
            return;

        wsManager = bb.webserviceManager.getInstance(_settings.classContentWebService);
        wsManager.request("update", {
            params: cleanData,
            async: asyncPersist, //make other scripts wait if "false"
            success: function(result) {
                _dirtyContentCollection.reset();
                //console.log(result);
            },
            error: function(result) {
            }
        });
    }

    var _wrapContent = function(contentInfos, persistOnChange) {
        var contentNode = false;
        var contentParams = false;
        /*contentInfos is a BbContentWrapper*/
        if (contentInfos instanceof BbContentWrapper) {
            return contentInfos;
        }
        /*contentEl is a jsonObject*/
        if (bb.jquery.isPlainObject(contentInfos)) {
            contentParams = contentInfos;
            if (contentParams.contentEl) {
                contentParams.isAContentSet = _isAContentSet(contentParams.contentEl);
                contentParams.isRootContentSet = _isARootContentSet(contentParams.contentEl);
                contentParams.isAnAutoBlock = _isAnAutoBlock(contentParams.contentEl);
            }
        } else {
            contentNode = bb.jquery(contentInfos);
            if (contentNode.length) {
                /*if content already exist*/
                var bbContentRef = bb.jquery(contentNode).attr("data-bbContentRef") || false;
                if (bbContentRef) {
                    return _getContentByRef(bbContentRef);
                } else {
                    contentParams = _getInfosFromNode(contentNode);
                    contentParams.contentEl = contentNode;
                    contentParams.isAContentSet = _isAContentSet(contentNode);
                    contentParams.isRootContentSet = _isARootContentSet(contentNode);
                    contentParams.isASubContent = _isASubContent(contentNode);
                    contentParams.size = _getContentSize(contentNode);
                    contentParams.isABbContent = _isABbContent(contentNode);
                    contentParams.isAnAutoBlock = _isAnAutoBlock(contentNode);
                    if (!contentParams.isAContentSet)
                        contentParams.accept = _getContentAccept(contentNode);
                }
            }
        }

        /**
         * refparent means this contentEl is in fact a reference to an other contentEl.
         *  In this case the mainparent that has a ref to an itemcontainer.
         */
        if ("refparent" in contentParams) {
            if (typeof contentParams.refparent == "string") {
                var uid = contentParams.refparent;
                var mainParent = bb.jquery(contentNode).parents("[data-uid='" + uid + "']").eq(0);
                if (mainParent && mainParent.length) {
                    return $bb(mainParent);
                } else {
                    contentParams.uid = null;
                }
            }
        }

        contentParams = ((contentParams) && bb.jquery.isPlainObject(contentParams)) ? contentParams : false;
        var persistOnChange = (persistOnChange && persistOnChange == true) ? true : false;
        if (!contentParams)
            return false;
        var params = {
            bbContent: contentParams,
            persistOnChange: persistOnChange
        };
        if (typeof contentParams.uid != "string") {
            throw "content's uid cannot be null";
        }
        return new BbContentWrapper(params);
    }

    /*Wrapper class*/
    var BbContentWrapper = function(contentInfos)
    {
        var unmutableProperties = ["uid", "type"];
        var defaultContent = {
            accept: [],
            data: [],
            draftuid: null,
            isDraft: false,
            label: null,
            maxentry: null,
            param: null,
            revision: null,
            state: null,
            uid: null,
            size: null,
            value: null
        };

        this.contentEl = null;
        this.parentNode = null;
        this.persistOnChange = false;
        this._contentProperties = {};
        this._settings = {};

        /*content callbacks*/
        this.callbacks = {
            "change": function(changeParams) {
                console.log(changeParams);
            },
            "delete": null
        };

        if (typeof this._init !== "function") {
            this._init = function(contentParams) {

                this._settings = bb.jquery.extend({}, this._settings, contentParams);

                this._contentProperties = (bb.jquery.isPlainObject(this._settings.bbContent)) ? bb.jquery.extend({}, defaultContent, this._settings.bbContent) : {};
                if ("contentEl" in this._contentProperties)
                    delete(this._contentProperties["contentEl"]); //fixes chrome recursive json encode
                this.persistOnChange = contentParams.persistOnChange;

                this.id = bb.Utils.generateId("bbContent");

                this.isContentSet = this._contentProperties.isAContentSet; //change to isAContentSet
                this.isAContentSet = this.isContentSet;

                this.isABbContent = this._contentProperties.isABbContent; //basic subcontents are not bbContent

                this.isASubContent = this._contentProperties.isASubContent;

                this.isARootContentSet = this._contentProperties.isRootContentSet;
                this.isAnAutoBlock = this._contentProperties.isAnAutoBlock;

                this.contentEl = this._settings.bbContent.contentEl || false;
                this.parentNode = null;
                if (this.contentEl && this.isABbContent) { //rootContentSets don't have parent
                    bb.jquery(this.contentEl).attr("data-bbContentRef", this.id); //update les autres contenus avec  le même uid??
                    var parentNode = (bb.jquery(this.contentEl).hasClass(_settings.rootContentSetCls)) ? null : bb.jquery(this.contentEl).parents(_settings.bbContentClass).eq(0);  //contentSet doesn't have parent -- parent(".bb5-content")
                    /*it MUST be a contentset*/
                    if (parentNode && parentNode.length == 0) {
                        parentNode = bb.jquery(this.contentEl).parents('.' + _settings.rootContentSetCls);
                    }
                    if (parentNode && parentNode.length) {
                        this.parentNode = $bb(parentNode);
                    }
                }
                if (this.isASubContent && !this.isABbContent) {
                    /*Cas des sous-contenus de base*/
                    var parentNodeUid = bb.jquery(this.contentEl).attr("data-parent");
                    var parentNode = bb.jquery(this.contentEl).parents(_settings.bbContentClass).eq(0);
                    if (parentNode && parentNode.length) {
                        this.parentNode = $bb(parentNode);
                    }
                }
                if (bb.jquery(this.contentEl).attr("data-forbidenactions")) {
                    var forbiden_action = bb.jquery(this.contentEl).attr("data-forbidenactions");
                    this.forbidenActions = forbiden_action.split(',');
                }

                /*useful for some contentsets*/
                itemContainer = false;
                var itemContainerClass = ("itemcontainer" in this._contentProperties) ? this._contentProperties["itemcontainer"] : false;
                if (itemContainerClass) {
                    var markertype = (itemContainerClass.indexOf('.') == 0) ? itemContainerClass : "." + itemContainerClass;
                    var itemContainer = (this.contentEl).find(markertype).eq(0) || false;
                }
                this.itemContainer = itemContainer;
                _contentsCollection.set(this.id, this);
                return this;
            }
        }

        BbContentWrapper.prototype.updateContentRendermode = function() {
            var currentRm = bb.jquery(this.contentEl).attr("data-rendermode") || false;
            this.set("rendermode", currentRm, true);
        }

        BbContentWrapper.prototype.updateParentNode = function() {
            var parentNode = (bb.jquery(this.contentEl).hasClass(_settings.rootContentSetCls)) ? null : bb.jquery(this.contentEl).parents(_settings.bbContentClass).eq(0);
            /*it MUST be a contentset*/
            if (parentNode && parentNode.length == 0) {
                parentNode = bb.jquery(this.contentEl).parents('.' + _settings.rootContentSetCls);
            }
            if (bb.jquery.isArray(parentNode) && parentNode.length) {
                this.parentNode = (parentNode) ? $bb(parentNode) : null;
            }
        }

        /*ajouter plusieurs valeur*/
        BbContentWrapper.prototype.set = function(key, newValue, notify) {
            var notify = (typeof notify == "boolean") ? notify : true;
            if (bb.jquery.inArray(key, unmutableProperties) != -1)
                return; //do nothing

            if (!(key in this._contentProperties))
                throw "property [" + key + "] doesn\'t exist";
            var changeEventParams = {
                changedProperty: key,
                oldValue: this._contentProperties[key],
                newValue: newValue
            };
            this._contentProperties[key] = newValue;
            changeEventParams.serializeContent = this._contentProperties;

            if (this.persistOnChange && notify) {
                this.callbacks["change"](changeEventParams); //persiste on change -->triger per item change event
            }
            else {
                if (notify)
                    _dirtyContentCollection.replaceItem(this._contentProperties);
            }
            return this;
        }

        BbContentWrapper.prototype.get = function(key) {
            if (!(key in this._contentProperties) || !this._contentProperties[key])
                return -1; /*test no params,null or false value */
            return this._contentProperties[key];
        }

        BbContentWrapper.prototype.getType = function() {
            return this.get("type");

        }

        BbContentWrapper.prototype.getUid = function() {
            return this.get("uid");
        }

        BbContentWrapper.prototype.getPlugins = function() {
            return this.get("contentplugins");
        }

        BbContentWrapper.prototype.getContentParams = function() {
            var params = this.get("param") || false;
            var self = this;
            /*new content without params --> retrieve form serveur*/
            if (params == -1) {
                wsManager = bb.webserviceManager.getInstance(_settings.classContentWebService);
                wsManager.request("getContentParameters", {
                    params: {
                        nodeInfos: this._contentProperties
                    },
                    async: false,
                    success: function(response) {
                        self.set("param", response.result);
                        params = response.result;
                    },
                    error: function() {
                        new Error("content.getContentParams");
                    }
                });
            }
            return params;
        }

        /*Events Settings*/
        BbContentWrapper.prototype.bind = function(eventName, callback, context) {
            var eventName = ((eventName) && typeof eventName == "string") ? eventName : false;
            var callback = (typeof callback == "function") ? callback : false;
            if (!eventName || !callback)
                return false;
            if (context) {
                callback = bb.jquery.proxy(callback, context);
            }
            this.callbacks[eventName] = callback;
            return this;
        }

        BbContentWrapper.prototype.destroy = function(params) {
            var params = params || {};

            /*effacer le noeud*/
            bb.jquery(this.contentEl).remove();
            /*mettre à jour la collection*/
            if (this.parentNode)
                this.parentNode.updateData();
            if (this.parentNode.isEmpty()) {
                this.parentNode.showEmptyZone();
            }
            _contentsCollection.deleteItem(this);

            if (typeof this.callbacks["delete"] == "function") {
                this.callbacks["delete"].call(params);
            } else {
                if (("onDestroy" in params) && typeof params["onDestroy"] == "function") {
                    params["onDestroy"].call();
                }
            }
        }

        BbContentWrapper.prototype.showEmptyZone = function() {
            if (!this.isContentSet)
                return false; //useful only for contentset
            var container = (this.itemContainer) ? this.itemContainer : this.contentEl;
            var nbContent = this.getNbContent();
            if (nbContent > 0)
                return;
            bb.jquery(container).addClass(_settings.emptyContentCls);
            bb.jquery(container).animate({
                minHeight: "100px"
            }, "slow");
        }

        BbContentWrapper.prototype.hideEmptyZone = function() {
            bb.jquery(this.contentEl).removeClass(_settings.emptyContentCls);
            var container = (this.itemContainer) ? this.itemContainer : this.contentEl;
            bb.jquery(container).removeClass(_settings.emptyContentCls);
        }


        BbContentWrapper.prototype.render = function(context) {
            /*Render content is the content*/
        }

        BbContentWrapper.prototype.unbind = function(eventName) {
        }

        BbContentWrapper.prototype.trigger = function(eventName, params) {
        }


        BbContentWrapper.prototype.setParentNode = function(node) {
            this.parentNode = $bb(node);
        }

        BbContentWrapper.prototype.getContentEl = function() {
            return bb.jquery(this.contentEl);
        }

        BbContentWrapper.prototype.setContentEl = function(contentEl) {
            if (!contentEl)
                throw "contentEl can't be null";
            this.contentEl = contentEl;
            bb.jquery(this.contentEl).attr("data-bbContentRef", this.id); //update properties from Dom?
            this.updateContentRendermode();
        }

        /*update*/
        BbContentWrapper.prototype.updateContentRender = function() {
            var self = this;
            wsManager = bb.webserviceManager.getInstance(_settings.classContentWebService);
            var serializeContent = this._contentProperties;
            var parentNode = this.parentNode;
            var rendermode = (parentNode.isAContentSet) ? parentNode.get("rendermode"):this.get("rendermode");
            rendermode = (rendermode < 0) ? "" : rendermode;
            wsManager.request("updateContentRender", {
                params : {
                    renderMode : rendermode, /*(!this.parentNode) ? "" : (this.parentNode.get("rendermode") == -1)? "" : this.parentNode.get("rendermode")*/
                    content : serializeContent,
                    page_uid : bb.frontApplication.getPageId()
                },
                success: function(response) {
                    try {
                        var result = response.result;
                        var render = bb.jquery(result.render).get(0).innerHTML;
                        if (!bb.jquery.trim(render).length) {
                            self.updateData();
                            return;
                        }

                        /*As we replace the main content we should update */
                        var newContentRender = bb.jquery(result.render);
                        self.contentEl.replaceWith(newContentRender);
                        self.setContentEl(newContentRender);
                        self.updateData();
                        var contentManager = bb.ManagersContainer.getInstance().getManager("ContentManager");
                        contentManager.initDroppableImage(self.contentEl);
                    } catch (e) {
                        console.log(e);
                    }
                },
                error: function(response) {
                    bb.Utils.handleAppError("error when calling BbContentWrapper.updateContentRender", response);
                }
            });
        }

        BbContentWrapper.prototype.setChild = function(content) {
            /*check if content has Child*/
            if (content instanceof BbContentWrapper) {
                if (!this.isContentSet) {
                    /*contentNode*/
                }
            } else {
                throw "content must be a BbContentWrapper instance";
            }
        }
        /*Will be overridden by ContentSet*/
        BbContentWrapper.prototype.updateData = function(notify) {
            var notify = (typeof notify == "boolean") ? notify : true;
            var subContents = bb.jquery(this.contentEl).find("[data-element]");
            var self = this;
            var data = {};
            //var hasChanged = false;
            if(subContents.length){
                subContents.each(function(i,element){
                    if(bb.jquery(element).attr('data-parent') == self.getUid()){
                        var elName =  bb.jquery(element).attr("data-element");
                        //if (elName != "0")
                        data[elName] = bb.jquery(element).attr("data-uid");
                        // hasChanged = true;
                    }
                });
            }
            this.set("data", data, notify);
            return data;
        }

        /* return */
        BbContentWrapper.prototype.getSubContents = function(){
            var result = [], self = this;
            var $ = bb.jquery;
            var subContents = bb.jquery(this.contentEl).find("[data-element]");
            if(subContents.length){
                subContents.each(function(i,element){
                    if($(element).attr('data-parent') == self.getUid()){
                        result.push(element);
                    }
                });
            }
            return result;
        }


        BbContentWrapper.prototype.getSize = function() {
            return this.get("size");
        }
        BbContentWrapper.prototype.setSize = function(newSize) {
            var newSize = parseInt(newSize) || false;
            if (!newSize)
                return false;
            this.set("size", newSize);
        }

        /*getContent children bbContent only*/
        BbContentWrapper.prototype.getChildren = function() {
            var container = [];
            var contents = bb.jquery(this.contentEl).find(_settings.bbContentClass);
            bb.jquery(contents).each(function(i, content) {
                var bbContent = $bb(content);
                container.push(bbContent);
            });
            return container;
        }
        this._init(contentInfos);


        /******Add new method for ContentSet******/
        if (this.isContentSet) {
            this.getNbContent = function() {
                return this._contentProperties.data.length;
            }
            this.isEmpty = function() {
                return !this._contentProperties.data.length > 0;
            }
            this.checkAcceptMaxEntry = function(bbContenTtype) {
                var result = false;
                var bbContenTtype = bbContenTtype || "none";

                var accept = (this.get("accept") == -1) ? "all" : this.get("accept").split(',');
                accept = bb.jquery.isArray(accept) ? accept : bb.jquery.makeArray(accept);
                if ((bb.jquery.inArray(bbContenTtype, accept) != -1) || (accept[0] == "all") && this.getMaxEntry()) {
                    result = true;
                }
                return result;
            }
            this.append = function(params) {
                var self = this;
                /*  params {content:content,
                 *   placeHolder:null,
                 *   beforeAppend : func
                 *   afterAppend : func
                 *   nodeScripts: null
                 *   }
                 *  sender is known
                 **/

                var sender = (params.sender) ? $bb(sender) : null;
                var content = (params.content) ? params.content : null;
                var placeHolder = (params.placeHolder) ? params.placeHolder : null;
                var beforeRequest = (typeof params.beforeRequest == "function") ? params.beforeRequest : bb.jquery.noop;
                var afterAppend = (typeof params.afterAppend == "function") ? params.afterAppend : bb.jquery.noop;
                var dropType = (typeof params.dropType == "string") ? params.dropType : "none";
                if (!content)
                    return false;
                /* Same container no Need to make request */
                if (content instanceof BbContentWrapper) {
                    var bbContent = content;
                    //var recieverRenderMode = this.get("rendermode");
                    //var contentRenderMode = content.get("rendermode");
                    content = {};
                    content.uid = bbContent.get("uid");
                    content.type = bbContent.get("type");
                    content.serializedContent = bbContent._contentProperties;
                    content = [content];
                    if (bbContent.parentNode.isEqualTo(this)) {
                        this.updateData();
                        return;
                    }
                }

                /*append bbContents to the contentSet*/
                wsManager = bb.webserviceManager.getInstance(_settings.classContentWebService);
                beforeRequest.call(this);
                wsManager.request("getContentsData", {
                    params: {
                        renderMode: (this.get("rendermode") == -1) ? "" : this.get("rendermode"),
                        contents: content,
                        page_uid: bb.frontApplication.getPageId()
                                //receiverclass: this.getType(),
                                //receiveruid: this.getUid()
                    },
                    success: function(response) {
                        var result = response.result;
                        var nbItem = result.length;
                        bb.jquery.each(result, function(i, item) {
                            var cp = i + 1;
                            var itemRender = bb.jquery(item.render).eq(0);
                            bb.jquery(itemRender).addClass(_settings.draggableContentClass.replace(".", " "));
                            if (placeHolder) {
                                bb.jquery(placeHolder).replaceWith(itemRender);
                            } else {
                                // bb.jquery(self.contentEl).append(itemRender); dont append
                                /*if subcontent has an itemContainer useit instead of the contentEl*/
                                if (self.itemContainer) {
                                    bb.jquery(self.itemContainer).prepend(itemRender);
                                } else {
                                    bb.jquery(self.contentEl).prepend(itemRender);
                                }
                            }

                            /*after newContent*/
                            afterAppend.call(self, $bb(itemRender));
                            /*wired up content here
                             *
                             *Le contentSet reçoit un nouveau contenu
                             * 1. newItem.parentNode.updateData();
                             * 2. newItem.setParentNode(this); switch content Parent
                             * 3. this{explicit contentSet}.updateData();
                             * 4. BbContent.updateContentEl(newContentEl);
                             **/
                            if (bbContent instanceof BbContentWrapper) {
                                /*move content*/
                                var sender = bbContent.parentNode;
                                sender.updateData();
                                if (sender.isEmpty()) {
                                    sender.showEmptyZone();
                                }
                                bbContent.setParentNode(self);
                                bbContent.setContentEl(itemRender);

                            } else {
                                /*create new bbContent*/
                                if (!item.serialized) {
                                    /*strange case where item.serialized is null*/
                                    var newItem = $bb(itemRender);
                                } else {
                                    item.serialized.contentEl = itemRender;
                                    var newItem = $bb(item.serialized);
                                    /*update item's parent if its*/
                                    if (self.itemContainer) {
                                        newItem.setParentNode(self);
                                    }
                                }
                                newItem.updateData();
                            }

                            if (bb.jquery(self.contentEl).hasClass("row")) {
                                var itemSize = self.getSize();

                                bb.jquery(itemRender).addClass("span" + itemSize);
                                bb.jquery(itemRender).addClass(_settings.resizableContentClass.replace(".", ""));
                                newItem.setSize(itemSize);
                            }

                            var contentManager = bb.ManagersContainer.getInstance().getManager("ContentManager"); //passer en after
                            contentManager.handleNewContent(itemRender);//show media path
                            bb.jquery(document).trigger("content:newContentAdded", [self, bb.jquery(itemRender), null]);
                            //if(cp == nbItem) bb.Utils.scrollToContent(bb.jquery(itemRender)); //scroll to last

                            var scripts = bb.jquery(item.render, "script").slice(1);
                            if (0 < bb.jquery(item.render, "script").slice(1).length) {
                                bb.jquery.each(scripts, function(i, script) {
                                    eval(bb.jquery(script).get(0).innerHTML);
                                });
                            }
                        });
                        self.updateData();
                        self.hideEmptyZone();
                        return;
                    },
                    error: function(response) {
                        console.log("Error pendant le chargement");
                    }
                });
                /*trigger change update container*/
            }
            this.remove = function(bbContent) {
                /*trigger change update container*/
                this.updateData();
            }
            this.replaceContent = function(bbContent) {
                console.log("replaceContent");
                /*trigger change update container*/
            }
            this.isEqualTo = function(content) {
                if (content instanceof BbContentWrapper) {
                    return this.id == content.id;
                }
                else
                    return false;
            }
            this.getMaxEntry = function() {
                var contentMaxEntry = parseInt(this._contentProperties.maxentry);
                if (contentMaxEntry) {
                    contentMaxEntry = this.getNbContent() - contentMaxEntry;
                    contentMaxEntry = Math.max(contentMaxEntry, 0);
                }
                else {
                    contentMaxEntry = 9999;
                }
                return contentMaxEntry;
            }
            this.getAccept = function() {
                return (this.get("accept") == -1) ? "all" : this.get("accept");
            }
            /*old BbContentWrapper.prototype.updateData*/
            this.updateData = function(notify) {
                var notify = (typeof notify == "boolean") ? notify : true;
                /*find all contentSet's subContents*/
                var data = [];
                var subContents = bb.jquery(this.getAllContents()).map(function(i, content) {
                    var subContInfos = {};
                    subContInfos.nodeType = bb.jquery(content).attr("data-type");
                    subContInfos.uid = bb.jquery(content).attr("data-uid");
                    data.push(subContInfos);
                    return bb.jquery(content).attr("data-uid");
                });
                this.set("data", data, notify);
                return subContents;
            }
            this.getAllContents = function() {
                var itemContainer = (this.itemContainer) ? this.itemContainer : this.contentEl;
                return bb.jquery(itemContainer).children(_settings.bbContentClass);
            }
            /*initContentSet Data*/
            this.updateData(false);
        }
    }
    var publicApi = {
        $bb: _wrapContent,
        wrapContent: _wrapContent,
        persist: _persist
    }
    global.$bb = global.bbContentWrapper = _wrapContent;
    return publicApi;
})(bb.jquery, window);




