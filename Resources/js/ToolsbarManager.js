/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var BB4 = (BB4) ? BB4 : {};

bb.ToolsbarManager = (function($, gExport) {


    var _instanceContainer = {};
    var _toolsbarContainer = {};
    var _toolsbarTabs = null;
    var _editTabs = null;
    var _settings = {
        selectedTab: "bb5-editing",
        selectedEditTab: "bb5-edit-tabs-data",
        btnTabClass: ".tabBtn",
        toolsbarContainerId: "#bb5-maintabs",
        tabsMainContaineId: "#bb5-toolbar-wrapper",
        pathContainerId: "#bb5-exam-path-wrapper",
        toolsBarWrapperId: "#bb5-exam-path-wrapper",
        pathInfosContainerId: "#bb5-exam-path",
        activePathClass: ".bb5-exam-path-current-item",
        pathCloseBtnCls: "bb5-ico-pathclose",
        userinfosId: "bb5-topmost-login",
        contentActions: {
            commit: '#bb5-shortcuts .bb5-ico-commit',
            validate: '#bb5-shortcuts .bb5-ico-validate',
            update: '#bb5-shortcuts .bb5-ico-refresh',
            revert: '#bb5-shortcuts .bb5-ico-cancel'
        }
    };

    var _tabsInfos = {
        "bb5-grid": 0,
        "bb5-blocks": 1,
        "bb5-theming": 2,
        "bb5-editing": 3,
        "bb5-status": 4,
        "bb5-bundle": 5,
        "bb5-personal": 6
    };


    var _editTabInfos = {
        "bb5-edit-tabs-data": 0,
        "bb5-edit-tabs-blocks": 1,
        "bb5-edit-tabs-page": 2
    };

    var _init = function(userConfigs) {
        // bb.jquery(_settings.toolsBarWrapperId).mask(bb.i18n.loading); 
        bb.jquery.extend(true, _settings, userConfigs);

        if ("bb5-editing" == _settings.selectedTab) {
            _settings.selectedEditTab = "bb5-edit-tabs-data";
        }

        if (_settings.selectedTab == "bb5-edit-tabs-data" || _settings.selectedTab == "bb5-edit-tabs-blocks" || _settings.selectedTab == "bb5-edit-tabs-page") {
            _settings.selectedEditTab = _settings.selectedTab;
            _settings.selectedTab = "bb5-editing";
        }

        bb.ManagersContainer.getInstance().register("ToolsbarManager", publicApi);
        _createTabs();
        _bindEvents();
        return publicApi;
    }


    var _createTabs = function() {
        var tabToSelect = _tabsInfos[_settings.selectedTab] || 0;
        var subTabToSelect = _editTabInfos[_settings.selectedEditTab] || 0;

        /*main toolsbar tabs*/
        _toolsbarTabs = new bb.LpTabs({
            selected: tabToSelect,
            mainContainer: _settings.toolsbarContainerId,
            onSelect: function(event, ui) {
                var selectedTab = bb.jquery(ui.panel).attr("id").replace("#", "");
                var previousTab = tabToSelect;
                _tabClickHandler(selectedTab, _settings.selectedTab);
                _settings.selectedTab = selectedTab;
                return false;
            },
            onCreate: function(event, ui) {
                setTimeout(function() {
                    bb.jquery(_settings.toolsBarWrapperId).unmask();
                }, 2000);
                bb.jquery(_settings.toolsBarWrapperId).css({
                    "visibility": ""
                }); //show the toolsbar
            }
        });

        /*main edits subs tabs*/
        _editTabs = new bb.LpTabs({
            mainContainer: "#bb5-edittabs",
            selected: subTabToSelect,
            onCreate: function(event, ui) {
            },
            onSelect: function(event, ui) {
                var selectedTab = bb.jquery(ui.panel).attr("id").replace("#", "");
                _tabClickHandler(selectedTab, _settings.selectedEditTab);
                _settings.selectedEditTab = selectedTab;
                return false;

            }
        });


        if (typeof _settings.onInit === "function") {
            _settings.onInit.call();
            _tabClickHandler(_settings.selectedTab, "none");
        }
    };

    var _getTbInstance = function(tbName) {
        var tbName = tbName || "none";
        return _instanceContainer[tbName];
    }



    var _buildPath = function(pathData) {
        var data = pathData.items || [];
        if (data.length == 0)
            return false;
        var pathFragment = document.createDocumentFragment();

        bb.jquery.each(data, function(i, pathItem) {
            var pathHtml = bb.jquery("<a></a>").clone();
            bb.jquery(pathHtml).addClass(pathData.itemClass.replace(".", ""));
            var pathTitle = bb.jquery(pathItem).attr(pathData.itemTitleKey);
            var itemId = bb.jquery(pathItem).attr(pathData.itemIdKey);
            bb.jquery(pathHtml).html(pathTitle);

            /*selected item*/
            if (i + 1 == data.length) {
                bb.jquery(pathHtml).addClass(_settings.activePathClass.replace(".", ""));
            }
            bb.jquery(pathHtml).attr("path-data-uid", itemId);
            bb.jquery(pathHtml).data("nodeInfo", pathItem);
            pathFragment.appendChild(bb.jquery(pathHtml).get(0));
        });

        return pathFragment;
    };

    var _updatePathContent = function(pathInfos) {
        var content = _buildPath(pathInfos);
        bb.jquery(_settings.pathInfosContainerId).html(bb.jquery(content));
        publicApi.showPath();
    };

    var _getSelectedEditTab = function() {
        return _editTabs.getSelectedTab();
    }
    /*Public API*/
    var publicApi = {
        showPath: function() {
            bb.jquery(_settings.pathContainerId).slideDown();
        },
        hidePath: function() {
            bb.jquery(_settings.pathContainerId).slideUp();
        },
        selectTab: function() {
        },
        updateContentPath: _updatePathContent,
        getSelectedEditTab: _getSelectedEditTab
    };

    var _bindEvents = function() {
        bb.jquery('#' + _settings.userinfosId + ' button').bind("click", function() {
            bb.end();
        });

        bb.jquery("." + _settings.pathCloseBtnCls).bind("click", function() {
            publicApi.hidePath();
        });

        bb.jquery(_settings.contentActions.commit).bind('click', function(e) {
            bb.ContentWrapper.persist(false); //make persist a synchrone request
            bb.StatusManager.getInstance().commit();
            bb.StatusManager.getInstance().update();
        });
        bb.jquery(_settings.contentActions.validate).bind('click', function(e) {
            bb.ContentWrapper.persist(false); //make persist a synchrone request
        });
        bb.jquery(_settings.contentActions.update).bind('click', function(e) {
            bb.StatusManager.getInstance().update();
        });
        bb.jquery(_settings.contentActions.revert).bind('click', function(e) {
            bb.StatusManager.getInstance().revert();
        });
    }

    /*a tab has been clicked*/
    var _tabClickHandler = function(selectedTab, previousTab) {
        _settings.selectedTab = selectedTab;
        bb.jquery(document).trigger("tabItem:click", [selectedTab, previousTab]);
        return false;
    }


    /*Toolsbar API*/
    var _setToolsbar = function(toolsbarName, toolsbar) {
        _toolsbarContainer[toolsbarName] = toolsbar;
    }

    var _getToolsbar = function(toolsbarName, config) {
        var config = config || {};
        var toolsbar = null;
        var instance = _instanceContainer[toolsbarName] || null;
        if (instance) {
            toolsbar = instance;
        }
        else {
            toolsbar = new _toolsbarContainer[toolsbarName](config);
            _instanceContainer[toolsbarName] = toolsbar;
        }
        return toolsbar;
    }

    /*constructor*/
    var AToolsbar = function(userConfigs) {
        this._settings = userConfigs._settings || {};
        this._events = userConfigs._events || {};
        this._callbacks = {};
        this._instanceid = bb.Utils.generateId("toolsbar");
    }


    /*Abstract toolsbar prototype*/
    AToolsbar.prototype = {
        _parseEvents: function() {
            var self = this;
            bb.jquery.each(this._events, function(eventInfos, value) {
                var eventInfos = eventInfos.split(" ");
                var eventO = {};
                eventO.eventType = eventInfos.pop();
                eventO.selector = eventInfos.join(" ");
                eventO.eventName = bb.jquery.trim(value);
                self._eventInfosContainer.push(eventO);
            });

        },
        _bindEvents: function(userEvent) {
            var self = this;
            var userEvent = (typeof userEvent == "string") ? userEvent : false;

            /*remove delegates*/
            //bb.jquery(this._settings.mainContainer).undelegate();

            /* Bind Events */
            bb.jquery.each(this._eventInfosContainer, function(i, eventInfo) {
                var methodKey = eventInfo.eventName + "_action";
                if (userEvent) {
                    if (userEvent != methodKey) {
                        return true; //continue
                    }
                }

                /*fonction générique*/
                if (!self._callbacks[methodKey]) {
                    self._callbacks[methodKey] = new Function("console.log('overwrite " + methodKey + " function');");
                }

                if (self._beforeCallbacks && self._beforeCallbacks[eventInfo.eventName]) {
                    var eventCallback = bb.jquery.proxy(self._beforeCallbacks[eventInfo.eventName], self);

                } else {
                    var eventCallback = bb.jquery.proxy(self._callbacks[methodKey], self);
                }

                bb.jquery(self._settings.mainContainer)
                        .undelegate(eventInfo.selector, eventInfo.eventTypeeventCallback) //remove default function
                        .delegate(eventInfo.selector, eventInfo.eventType, eventCallback);

            });
        },
        _initialize: function(userConfigs) {
            this._eventInfosContainer = [];
            bb.jquery.extend(true, this._settings, userConfigs._settings);
            bb.jquery.extend(true, this._events, userConfigs._events);
            if (typeof this._init == "function")
                this._init();
            this._parseEvents();
            this._bindEvents();
        },
        on: function(eventName, eventCallback) {
            var eventName = (typeof eventName != "string") ? "none" : eventName;
            var callback = (typeof eventCallback != "function") ? function() {
                console.log("error");
            } : eventCallback;
            var completeEventName = eventName + "_action";
            this._callbacks[completeEventName] = callback;
            this._bindEvents(completeEventName);
        },
        trigger: function(eventName, selector) {
            var self = this;
            bb.jquery.each(this._eventInfosContainer, function(index, eventInfos) {
                if (eventName == eventInfos.eventName) {
                    var elements = bb.jquery(self._settings.mainContainer).find(eventInfos.selector);
                    if (selector)
                        elements = elements.filter(selector);
                    elements.trigger(eventInfos.eventType);
                }
            });
        },
        unbind: function(eventName, eventCallback) {
            console.log("Implement unbind");
        }
    };

    var _registerToolsbar = function(toolsbarName, toolsbarSettings) {
        /*
         *Créer un nouveau contructeur
         * avec les paramètres par defaut
         **/
        var toolsbarName = (typeof toolsbarName === "string") ? toolsbarName : false;
        if (!toolsbarName)
            throw new Error("toolsbarName must be a string and can't be null");
        var userConfigs = {
            settings: toolsbarSettings._settings,
            events: toolsbarSettings._events
        };

        /*create constructor: magic inside!*/
        var ToolsbarConstructor = (function(userParams) {
            return function(userConfig) {
                AToolsbar.call(this, userParams);
                this._initialize(userConfig);
            }

        })(toolsbarSettings);

        /*user defined class methods*/
        var protoTb = {};
        bb.jquery.each(toolsbarSettings, function(key, value) {
            if (typeof value === "function") {
                protoTb[key] = value;
            }
            else {
                if (key != "_settings" && key != "_events") {
                    protoTb[key] = value;
                }
            }
        });

        ToolsbarConstructor.prototype = bb.jquery.extend(true, protoTb, AToolsbar.prototype);//extend this with that
        _setToolsbar(toolsbarName, ToolsbarConstructor);

    }

    return {
        setToolsbar: _setToolsbar,
        createToolsbar: _getToolsbar,
        register: _registerToolsbar,
        init: _init,
        getTbInstance: _getTbInstance
    };
})(bb.jquery, window);

/***************************************************/

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


BB4.ToolsbarManager = bb.ToolsbarManager;
