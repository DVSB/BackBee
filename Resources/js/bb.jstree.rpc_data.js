(function ($) {
    bb.jquery.jstree.plugin("rpc_data", {
        __init : function() {
            var s = this._get_settings().rpc_data;
            if (s.progressive_unload) {
                this.get_container().bind("after_close.jstree", function(e, data) {
                    data.rslt.obj.children("ul").remove();
                });
            }
        },
        defaults: {
            // `data` can be a function:
            //  * accepts two arguments - node being loaded and a callback to pass the result to
            //  * will be executed in the current tree's scope & ajax won't be supported
            data: false,
            ajax: false,
            correct_state: true,
            progressive_render: false,
            progressive_unload: false
        },
        _fn: {
            load_node: function(obj, s_call, e_call) {
                var _this = this;
                this.load_node_json(
                        obj,
                        function() {
                            _this.__callback({"obj": _this._get_node(obj)});
                            s_call.call(this);
                        },
                        e_call
                        );
            },
            _is_loaded: function(obj) {
                var s = this._get_settings().rpc_data;
                obj = this._get_node(obj);
                return obj == -1 || !obj || (!s.ajax && !s.progressive_render && !bb.jquery.isFunction(s.data)) || obj.is(".jstree-open, .jstree-leaf") || obj.children("ul").children("li").length > 0;
            },
            refresh: function(obj) {
                obj = this._get_node(obj);
                var s = this._get_settings().rpc_data;
                if(obj && obj !== -1 && s.progressive_unload && (bb.jquery.isFunction(s.data) || !!s.ajax)) {
                    obj.removeData("jstree_children");
                }
                return this.__call_old();
            },
            load_node_json: function(obj, s_call, e_call) {
                var s = this.get_settings().rpc_data, d,
                        error_func = function() {
                        },
                        success_func = function() {
                        },
                        myself = this;

                obj = this._get_node(obj);

                if (
                        obj &&
                        obj !== -1 &&
                        (s.progressive_render || s.progressive_unload) &&
                        !obj.is(".jstree-open, .jstree-leaf") &&
                        obj.children("ul").children("li").length === 0 &&
                        obj.data("jstree_children")
                        ) {
                    d = this._parse_json(obj.data("jstree_children"), obj);
                    if (d) {
                        obj.append(d);
                        if (!s.progressive_unload) {
                            obj.removeData("jstree_children");
                        }
                    }
                    this.clean_node(obj);
                    if (s_call) {
                        s_call.call(this);
                    }
                    return;
                }

                if (obj && obj !== -1) {
                    if (obj.data("jstree_is_loading")) {
                        return;
                    } else {
                        obj.data("jstree_is_loading", true);
                    }
                }
                switch (!0) {
                    case (!s.data && !s.ajax):
                        throw "Neither data nor ajax settings supplied.";
                    // function option added here for easier model integration (also supporting async - see callback)
                    case (bb.jquery.isFunction(s.data)):
                        s.data.call(this, obj, bb.jquery.proxy(function (d) {
                            d = this._parse_json(d, obj);
                            if (!d) {
                                if (obj === -1 || !obj) {
                                    if (s.correct_state) {
                                        this.get_container().children("ul").empty();
                                    }
                                } else {
                                    obj.children("a.jstree-loading").removeClass("jstree-loading");
                                    obj.removeData("jstree_is_loading");
                                    if (s.correct_state) {
                                        this.correct_state(obj);
                                    }
                                }
                                if (e_call) {
                                    e_call.call(this);
                                }
                            } else {
                                if (obj === -1 || !obj) {
                                    this.get_container().children("ul").empty().append(d.children());
                                } else {
                                    obj.append(d).children("a.jstree-loading").removeClass("jstree-loading");
                                    obj.removeData("jstree_is_loading");
                                }
                                this.clean_node(obj);
                                if (s_call) {
                                    s_call.call(this);
                                }
                            }
                        }, this));
                        break;
                    case (!!s.data && !s.ajax) || (!!s.data && !!s.ajax && (!obj || obj === -1)):
                        if (!obj || obj == -1) {
                            d = this._parse_json(s.data, obj);
                            if (d) {
                                this.get_container().children("ul").empty().append(d.children());
                                this.clean_node();
                            } else {
                                if (s.correct_state) {
                                    this.get_container().children("ul").empty();
                                }
                            }
                        }
                        if (s_call) {
                            s_call.call(this);
                        }
                        break;
                    case (!s.data && !!s.ajax) || (!!s.data && !!s.ajax && obj && obj !== -1):
                        var error_func = function(x, t, e) {
                            var ef = myself.get_settings().rpc_data.ajax.error;
                            if (ef) {
                                ef.call(myself, x, t, e);
                            }
                            if (obj != -1 && obj.length) {
                                obj.children("a.jstree-loading").removeClass("jstree-loading");
                                obj.removeData("jstree_is_loading");
                                if (t === "success" && s.correct_state) {
                                    myself.correct_state(obj);
                                }
                            } else {
                                if (t === "success" && s.correct_state) {
                                    myself.get_container().children("ul").empty();
                                }
                            }
                            if (e_call) {
                                e_call.call(myself);
                            }
                        };
                        var success_func = function(d, t, x) {
                            var sf = myself.get_settings().rpc_data.ajax.success;
                            if (sf) {
                                d = sf.call(myself, d, t, x) || d;
                            }
                            if (d === "" || (d && d.toString && d.toString().replace(/^[\s\n]+$/,"") === "") || (!bb.jquery.isArray(d) && !bb.jquery.isPlainObject(d))) {
                                return error_func.call(myself, x, t, "");
                            }
                            d = myself._parse_json(d.result, obj);

                            if (d) {
                                if (obj === -1 || !obj) {
                                    myself.get_container().children("ul").empty().append(d.children());
                                } else {
                                    obj.append(d).children("a.jstree-loading").removeClass("jstree-loading");
                                    obj.removeData("jstree_is_loading");
                                }
                                myself.clean_node(obj);
                                if (s_call) {
                                    s_call.call(myself);
                                }
                            } else {
                                if (obj === -1 || !obj) {
                                    if (s.correct_state) {
                                        myself.get_container().children("ul").empty();
                                        if (s_call) {
                                            s_call.call(myself);
                                        }
                                    }
                                } else {
                                    obj.children("a.jstree-loading").removeClass("jstree-loading");
                                    obj.removeData("jstree_is_loading");
                                    if (s.correct_state) {
                                        myself.correct_state(obj);
                                        if (s_call) {
                                            s_call.call(myself);
                                        }
                                    }
                                }
                            }
                            myself.get_container().trigger('rpc_data_loaded', [obj, myself]);
                        };
                        s.ajax.error = error_func;
                        s.ajax.success = success_func;
                        if (bb.jquery.isFunction(s.ajax.data)) {
                            s.ajax.data = s.ajax.data.call(this, obj);
                        }

                        if (!s.ajax.webservice)
                            throw "No RPC webservice supplied.";
                        if (!s.ajax.webservice.instance)
                            throw "No RPC webservice instance supplied.";
                        if (!s.ajax.webservice.method)
                            throw "No RPC webservice method supplied.";

                        s.ajax.webservice.instance.request(s.ajax.webservice.method, {
                            params: s.ajax.data,
                            success: s.ajax.success,
                            error: s.ajax.error
                        });

                        break;
                }
            },
            _parse_json: function(js, obj, is_callback) {
                var d = false,
                        p = this._get_settings(),
                        s = p.rpc_data,
                        t = p.core.html_titles,
                        tmp, i, j, ul1, ul2;

                if (!js) {
                    return d;
                }
                if (s.progressive_unload && obj && obj !== -1) {
                    obj.data("jstree_children", d);
                }
                if (bb.jquery.isArray(js)) {
                    d = bb.jquery();
                    if (!js.length) {
                        return false;
                    }
                    for (i = 0, j = js.length; i < j; i++) {
                        tmp = this._parse_json(js[i], obj, true);
                        if (tmp.length) {
                            d = d.add(tmp);
                        }
                    }
                } else if (typeof (js) == 'object' && typeof (js.results) !== 'undefined') {
                    d = this._parse_json(js.results, obj, true);

                    if (js.numresults > js.firstresult + js.maxresult) {
                        d = d.add(bb.jquery('<li><a class = "nextresults" id = "nextresults" href = "#" data-nextresults="' + (js.firstresult + js.maxresult) + '">...next results...</a></li>'));
                    }

                } else {
                    if (typeof js == "string") {
                        js = {data: js};
                    }
                    if (!js.data && js.data !== "") {
                        return d;
                    }
                    d = bb.jquery("<li />");
                    if (js.attr) {
                        d.attr(js.attr);
                    }
                    if (js.metadata) {
                        d.data(js.metadata);
                    }
                    if (js.state) {
                        d.addClass("jstree-" + js.state);
                    }
                    if (!bb.jquery.isArray(js.data)) {
                        tmp = js.data;
                        js.data = [];
                        js.data.push(tmp);
                    }
                    bb.jquery.each(js.data, function (i, m) {
                        tmp = bb.jquery("<a />");
                        if (bb.jquery.isFunction(m)) {
                            m = m.call(this, js);
                        }
                        if (typeof m == "string") {
                            tmp.attr('href', '#')[ t ? "html" : "text" ](m);
                        } else {
                            if (!m.attr) {
                                m.attr = {};
                            }
                            if (!m.attr.href) {
                                m.attr.href = '#';
                            }
                            tmp.attr(m.attr)[ t ? "html" : "text" ](m.title);
                            if (m.language) {
                                tmp.addClass(m.language);
                            }
                        }

                        tmp.prepend("<ins class='jstree-icon" + ('undefined' != js.attr.state ? (!(js.attr.state & 1) ? ' bb5-jstree-offline' : '') + (js.attr.state & 2 ? ' bb5-jstree-hidden' : '') : '') + "'>&#160;</ins>");
                        if (!m.icon && js.icon) {
                            m.icon = js.icon;
                        }
                        if (m.icon) {
                            if (m.icon.indexOf("/") === -1) {
                                tmp.children("ins").addClass(m.icon);
                            } else {
                                tmp.children("ins").css("background", "url('" + m.icon + "') center center no-repeat");
                            }
                        }
                        d.append(tmp);
                    });
                    d.prepend("<ins class='jstree-icon'>&#160;</ins>");
                    if (js.children) {
                        if (s.progressive_render && js.state !== "open") {
                            d.addClass("jstree-closed").data("jstree_children", js.children);
                        }
                        else {
                            if (s.progressive_unload) {
                                d.data("jstree_children", js.children);
                            }
                            if (bb.jquery.isArray(js.children) && js.children.length) {
                                tmp = this._parse_json(js.children, obj, true);
                                if (tmp.length) {
                                    ul2 = bb.jquery("<ul />");
                                    ul2.append(tmp);
                                    d.append(ul2);
                                }
                            } else if (typeof (js.children) == 'object' && typeof (js.children.results) !== 'undefined') {
                                tmp = this._parse_json(js.children, obj, true);
                                if (tmp.length) {
                                    ul2 = bb.jquery("<ul />");
                                    ul2.append(tmp);
                                    d.append(ul2);
                                }
                            }
                        }
                    }
                }
                if (!is_callback) {
                    ul1 = bb.jquery("<ul />");
                    ul1.append(d);
                    d = ul1;
                }
                return d;
            },
            get_json: function(obj, li_attr, a_attr, is_callback) {
                var result = [],
                        s = this._get_settings(),
                        _this = this,
                        tmp1, tmp2, li, a, t, lang;
                obj = this._get_node(obj);
                if (!obj || obj === -1) {
                    obj = this.get_container().find("> ul > li");
                }
                li_attr = bb.jquery.isArray(li_attr) ? li_attr : [ "id", "class" ];
                if (!is_callback && this.data.types) {
                    li_attr.push(s.types.type_attr);
                }
                a_attr = bb.jquery.isArray(a_attr) ? a_attr : [ ];

                obj.each(function () {
                    li = bb.jquery(this);
                    tmp1 = { data : [] };
                    if (li_attr.length) { tmp1.attr = { }; }
                    bb.jquery.each(li_attr, function (i, v) {
                        tmp2 = li.attr(v);
                        if (tmp2 && tmp2.length && tmp2.replace(/jstree[^ ]*/ig, '').length) {
                            tmp1.attr[v] = (" " + tmp2).replace(/ jstree[^ ]*/ig, '').replace(/\s+$/ig, " ").replace(/^ /, "").replace(/ $/, "");
                        }
                    });
                    if (li.hasClass("jstree-open")) {
                        tmp1.state = "open";
                    }
                    if (li.hasClass("jstree-closed")) {
                        tmp1.state = "closed";
                    }
                    if (li.data()) {
                        tmp1.metadata = li.data();
                    }
                    a = li.children("a");
                    a.each(function () {
                        t = bb.jquery(this);
                        if (
                            a_attr.length ||
                            bb.jquery.inArray("languages", s.plugins) !== -1 ||
                            t.children("ins").get(0).style.backgroundImage.length ||
                            (t.children("ins").get(0).className && t.children("ins").get(0).className.replace(/jstree[^ ]*|$/ig,'').length)
                        ) {
                            lang = false;
                            if(bb.jquery.inArray("languages", s.plugins) !== -1 && bb.jquery.isArray(s.languages) && s.languages.length) {
                                bb.jquery.each(s.languages, function (l, lv) {
                                    if(t.hasClass(lv)) {
                                        lang = lv;
                                        return false;
                                    }
                                });
                            }
                            tmp2 = {attr: { }, title: _this.get_text(t, lang)};
                            bb.jquery.each(a_attr, function (k, z) {
                                tmp2.attr[z] = (" " + (t.attr(z) || "")).replace(/ jstree[^ ]*/ig,'').replace(/\s+$/ig," ").replace(/^ /,"").replace(/ $/,"");
                            });
                            if (bb.jquery.inArray("languages", s.plugins) !== -1 && bb.jquery.isArray(s.languages) && s.languages.length) {
                                bb.jquery.each(s.languages, function (k, z) {
                                    if(t.hasClass(z)) { tmp2.language = z; return true; }
                                });
                            }
                            if (t.children("ins").get(0).className.replace(/jstree[^ ]*|$/ig, '').replace(/^\s+$/ig, "").length) {
                                tmp2.icon = t.children("ins").get(0).className.replace(/jstree[^ ]*|$/ig, '').replace(/\s+$/ig, " ").replace(/^ /, "").replace(/ $/, "");
                            }
                            if (t.children("ins").get(0).style.backgroundImage.length) {
                                tmp2.icon = t.children("ins").get(0).style.backgroundImage.replace("url(", "").replace(")", "");
                            }
                        } else {
                            tmp2 = _this.get_text(t);
                        }
                        if (a.length > 1) {
                            tmp1.data.push(tmp2);
                        } else {
                            tmp1.data = tmp2;
                        }
                    });
                    li = li.find("> ul > li");
                    if (li.length) {
                        tmp1.children = _this.get_json(li, li_attr, a_attr, true);
                    }
                    result.push(tmp1);
                });
                return result;
            }
        }
    });
})(bb.jquery);
//*/
