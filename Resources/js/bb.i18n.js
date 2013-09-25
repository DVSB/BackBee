var bb = (bb) ? bb : {};

/**
 * Class providing translation features to bb5 toolbars
 * @category    BackBuilder5
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
var bbTranslator = function(locale, options) {
    var _options = $.extend(options || {}, {
        strict: true,
        default_locale: 'en',
        path: bb.resourcesdir+'js/lang/',
        available_language: ['en', 'fr'],
        debug: false
    });

    var _locale = _options.default_locale;
    var _loaded_cache = new Array();
    var _translate;

    _init = function(locale) {
        _log("Starting bbTranslator initialization");

        if ('undefined' != typeof(locale)) {
            _locale = locale;
        } else if ('undefined' != typeof(navigator.language)) {
            _locale = navigator.language;
        }

        $('#bb5-i18n-choice').empty();
        $.each(_options.available_language, function(index, language) {
            $('#bb5-i18n-choice').append('<a href="#'+language+'" onclick="return bb.i18n.setLocale(\''+language+'\');">'+language+'</a>');
            if (index+1 < _options.available_language.length) $('#bb5-i18n-choice').append(' - ');
        });

        _setLocale(_locale);

        _log("bbTranslator initialized");
    };

    _loadLangFile = function(locale) {
        if (-1 < $.inArray(locale, _loaded_cache)) return;

        var url = _options.path+locale+'.js';

        _log("Loading translation file "+url);
        jQuery.ajax({
            dataType: 'text',
            contentType: 'text/plaintext; charset=UTF-8',
            async: false,
            cache: true,
            url: bb.baseurl+url,
            success: _parseLangFile,
            error: function() {
                _log("Unable to find translation file "+url);
                if (locale.match(/^([a-z]{2})_.*$/)) {
                    locale = locale.replace(/^([a-z]{2})_.*$/, '$1');
                    _loadLangFile(locale);
                }
            }
        });
        _loaded_cache[_loaded_cache.length] = locale;
    };

    _parseLangFile = function(responseText) {
        try {
            $.globalEval('var translate = '+responseText);
            if ('undefined' == typeof(_translate) || null == _translate) {
                _translate = translate;
            } else {
                $.each(translate, function(item, value) {
                    _translate = _addTraduction(_translate, item, value);
                });
            }
        } catch(e) {
            console.log('bb.i18n error: '+e.message);
        }
    };

    _addTraduction = function(translate, item, value) {
        if (_options.strict) {
            if (typeof(value) == typeof(translate[item])) {
                if ('object' == typeof(value)) {
                    $.each(value, function(i, v) {
                        translate[item] = _addTraduction(translate[item], i, v);
                    })
                } else {
                    translate[item] = value;
                }
            }
        } else {
            switch (typeof(translate[item])) {
                case 'object':
                    translate[item] = $.extend({}, translate[item], value);
                    break;
                default:
                    translate[item] = value;
                    break;
            }
        }

        return translate;
    };

    _log = function(msg, force) {
        if (_options.debug || force) {
            console.log({"bb.i18n": msg});
        }
    };

    _setLocale = function(locale) {
        _loaded_cache = new Array();
        _translate = null;
        _locale = locale;

        if (_options.strict) {
            _log("Option strict set, loading default locale "+_options.default_locale);

            // Loading default language file
            _loadLangFile(_options.default_locale);
        }

        if (!_options.strict || _locale != _options.default_locale) {
            // Loading the language file
            _loadLangFile(_locale);
        }

        _i18nparse(document);

        if ($.datepicker && _translate.datepicker) {
            $.datepicker.regional[_locale] = _translate.datepicker;
            $.datepicker.setDefaults($.datepicker.regional[_locale]);
        }
        if ($.timepicker && _translate.timepicker) {
            $.timepicker.regional[_locale] = _translate.timepicker;
            $.timepicker.setDefaults($.timepicker.regional[_locale]);
        }
        
        $(document).trigger('locale.change');
        
        _log("Locale set to "+_locale);

        return this;
    };

    _getTranslation = function(item) {
        var str = item.replace(/\.(\w+)/g, '["$1"]').replace(/^(\w+)\[/, '["$1"][');
        if (-1 == str.indexOf('[')) str = '["'+str+'"]';

        try {
            eval('var trans = _translate'+str);
            if ('undefined' == typeof(trans)) {
                _log('Missing translation for _translate.'+item+' with locale '+_locale);
                return item;
            }
        } catch (e) {
            _log('Missing translation for _translate.'+item, true);
            return item;
        }

        _log("Translation found for _translate."+item);

        return trans;
    };

    _i18nparse = function(node) {
        $.each($(node).find('[data-i18n]'), function(index, element) {
            var attr = $(element).attr('data-i18n');
            var comp = attr.split(':');
            if (1 == comp.length) {
                $(element).empty().html(_getTranslation($(element).attr('data-i18n')));
            } else if (2 == comp.length) {
                $(element).attr(comp[0], _getTranslation(comp[1]));
            }
        });
    };

    bbTranslator.prototype.setLocale = function(locale) {
        _setLocale(locale);
        if (DbManager) DbManager.getDb("BB4").set("selectedLang", _locale);
        return false;
    };

    bbTranslator.prototype.__ = function() {
        if (0 == arguments.length) return '';

        var item = arguments[0];
        var trans = _getTranslation(item);
        for(i=1; i<arguments.length; i++) {
            trans = trans.replace('%'+i, arguments[i]);
        }

        return trans;
    };

    bbTranslator.prototype.i18nparsef = function() {
        return this.each( function() { _i18nparse(this); });
    };

    _init(locale);
};

bb.i18n = new bbTranslator();

jQuery.fn.extend({
    bb_i18nparse: bb.i18n.i18nparsef
});
