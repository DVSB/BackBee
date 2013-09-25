(function (bb) {
    "use strict";
    var _StateManager = {
        _stateContext: '',
        _user_preferences: {},

        buildAccessors: function (key) {
            var f = key.charAt(0).toUpperCase();

            this['setState' + f + key.substr(1)] = function (value) {
                this.setState(key.toString(), value);
            };

            this['getState' + f + key.substr(1)] = function () {
                return this.getState(key.toString());
            };
        },

        register: function (context) {
            this._stateContext = context;
            this._user_preferences = new bb.UserPreferences();
            for (var key in this._statesWatchable) {
                this.buildAccessors(key, this._statesWatchable[key]);
            }
        },

        stateRestorer: function (value, callback) {
            callback(this, value);
        },

        restore: function () {
            var state_value;
            for (var key in this._statesRestore) {
                state_value = '';
                state_value = this._user_preferences.getValue(this._stateContext, key, this._statesWatchable[key]);
                this.stateRestorer(state_value, this._statesRestore[key]);
            }
        },

        setState: function (state, value) {
            if (state in this._statesInterpretor) {
                value = this._statesInterpretor[state](this, value);
            }
            this._user_preferences.setValue(this._stateContext, state, value);
        },

        getState: function (state) {
             var value = this._user_preferences.getValue(this._stateContext, state, this._statesWatchable[state]);
             return value;
        }
    };

    var _extend = function (destination, identifier) {
        if (!identifier) {
            identifier = "object_undefined";
        }
        if (!('_statesRestore' in destination)) {
            destination._statesRestore = {};
        }
        if (!('_statesInterpretor' in destination)) {
            destination._statesInterpretor = {};
        }
        if (!('_statesWatchable' in destination)) {
            if (console) {
                console.error("the " + identifier + "object must be have a _statesWatchable object in this own property.");
            }
        } else {
            for (var k in _StateManager) {
                if (!destination.hasOwnProperty(k)) {
                    destination[k] = _StateManager[k];
                }
            }
            destination.register(identifier);
            destination.restore();
        }

        return destination;
    };

    bb.StateManager = _StateManager;
    bb.StateManager.extend = _extend;
}(bb));