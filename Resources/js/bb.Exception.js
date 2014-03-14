(function (window) {
    "use strict";

    var bb = window.bb || {};

    define(["jscore"], function () {
        return (function () {
            var Exception = new JS.Class({
                initialize: function () {
                    this.name = '';
                    this.message = '';
                    this.params = {};
                    this.stack = this.getStack();
                },

                raise: function (name, message, params) {
                    this.name = name;
                    this.message = message;
                    this.params = params || {};
                },

                getStack: function() {
                    var err = new Error(),
                        stack = err.stack.split("\n"),
                        cleanStack = stack.slice(4);

                    for (var key in cleanStack) {
                        cleanStack[key] = this.parseStackLine(cleanStack[key]);
                    }
                    return cleanStack;
                },

                pushError: function (error) {
                    if (bb.errors === undefined) {
                        bb.errors = [];
                    }
                    bb.errors.push(error);
                    bb.lastError = error;
                },

                parseStackLine : function (line) {
                    if (line === '') return;

                    var splitedLine = line.split('@');
                    var call = splitedLine[0];
                    splitedLine = splitedLine[1].split(':');

                    return {
                        line: splitedLine[2],
                        file: splitedLine[0] + ':' + splitedLine[1],
                        call: call
                    };
                }
            });

            bb.throw = function (name, message) {
                var error = new Exception();
                error.raise.apply(error, arguments);
                error.pushError(error);
                throw(name + ' : ' + message);
            }
        }(window));
    });
}(window));