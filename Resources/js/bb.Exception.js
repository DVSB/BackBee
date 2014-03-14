(function (window) {
    "use strict";

    var bb = window.bb || {};

    define(["jscore"], function () {
        return (function () {
            var Exception = new JS.Class({
                /**
                 * BundleController contructor
                 * 
                 * @returns {undefined}
                 */
                initialize: function () {
                    this.name = '';
                    this.message = '';
                    this.params = {};
                    this.stack = this.getStack();
                },

                /**
                 * Function to build the Exception
                 * 
                 * @param {string} name
                 * @param {string} message
                 * @param {object} params
                 * @returns {undefined}
                 */
                raise: function (name, message, params) {
                    this.name = name;
                    this.message = message;
                    this.params = params || {};
                },

                /**
                 * Function to build the Exception
                 * 
                 * @returns {array}
                 */
                getStack: function() {
                    var err = new Error(),
                        stack = err.stack.split("\n"),
                        cleanStack = stack.slice(4);

                    for (var key in cleanStack) {
                        cleanStack[key] = this.parseStackLine(cleanStack[key]);
                    }
                    return cleanStack;
                },

                /**
                 * Function to stock the Exception in bb.lastError ans bb.errors
                 * 
                 * @param {Exception} error
                 * @returns {undefined}
                 */
                pushError: function (error) {
                    if (bb.errors === undefined) {
                        bb.errors = [];
                    }
                    bb.errors.push(error);
                    bb.lastError = error;
                },

                /**
                 * Function to parse a stak trace line
                 * 
                 * @param {string} line
                 * @returns {object}
                 */
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