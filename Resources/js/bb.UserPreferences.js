(function ($, bb) {
    "use strict";
    var _UserPreferences = function() {

        if (_UserPreferences.prototype._UserPreferencesInstance) {
          return _UserPreferences.prototype._UserPreferencesInstance;
        }

        _UserPreferences.prototype._UserPreferencesInstance = this;

        var _user = null;
        var _values = { 
            "updated_at" : 0
        };
        var _db = DbManager.init('BB4');
        var _wsManager = bb.webserviceManager.getInstance('ws_local_user');

        var saveValues = function () {
            _values.updated_at = new Date().getTime();
            _db.set(_user, _values);
        };

        this.setValue = function (selector, key, value) {
            if (selector in _values) {
                _values[selector][key] = value;
            } else {
                _values[selector] = {};
                this.setValue(selector, key, value);
                return;
            }
            saveValues();
        };

        this.getValue = function (selector, key, default_value) {
            if ((selector in _values) && (key in _values[selector]) && (typeof _values[selector][key] !== 'undefined')) {
                return _values[selector][key];
            }
            return default_value;
        };

        this.getIdentity = function () {
            return _user;
        };

        var save = function () {
            _values.updated_at = new Date().getTime();
            var params = {
                'identity': _user,
                'values': _values
            };
            _wsManager.request('setBBUserPreferences', {'params': params});
        };

        var _getBBUserIdentity = function () {
            var result = '';
            
            _wsManager.request('getBBUserPreferences', {
                async : false,
                success : function(response){
                    if (response.result !== null) {
                        result = response.result;
                    } else {
                        result.identity = 'anonymous';
                    }
                },
                error: function(){
                    new Error('content.getContentParams');
                }
            });
            if (result.preferences) {
                _values = JSON.parse(result.preferences);
            }
            return result.identity;
        };

        //bb.jquery(bb).bind('bb.started', function () {
            _user = _getBBUserIdentity();
        //});

        bb.jquery(bb).bind('bb.ended', function () {
            save();
        });

        var tmpValues = _db.get(_user);
        if (tmpValues) {
            if (tmpValues.updated_at >= _values.updated_at) {
                _values = tmpValues;
            }
        }
        saveValues();
    };
    bb.UserPreferences = _UserPreferences;
}(bb.jquery, bb));