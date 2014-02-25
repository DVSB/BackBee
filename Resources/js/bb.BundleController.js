var bb = bb || {};

(function (window) {
    "use strict";

    define(["jscore"], function () {

        return (function(global){
            /**
             * bundle popin identifier
             * 
             * @type {string}
             */
            var popinClass = '.bb5-dialog-admin-bundle';

            /**
             * Bundle controller object.
             * 
             * @example How to add one action
             * 
             * Javascript:
             * {string} namespace can be compose like actionName or webserviceName::actionName.
             * {object} {} all key describe under are optionals.
             * 
             * bb.require(['bb.BundleController'], function () {
             *     bb.bundle.addAction(namespace, {
             *         params: function (event) {function to catch query params return object},
             *         success: function (response) {function to apply the query result},
             *         error: function (response) {function to apply the query error}
             *     });
             * });
             * 
             * link :
             * <a class="bb-bundle-link" data-href="namespace">link</a>
             * form :
             * <form class="bb-bundle-form" data-action="namespace">inputs</form>
             * 
             * namespace is the same as the bb.bundle.addAction namespace parameter
             * work on all tags
             */
            var BundleController = new JS.Class({
                
                /**
                 * BundleController contructor
                 * 
                 * @returns {undefined}
                 */
                initialize: function () {

                    /**
                     * Bundle web services
                     *
                     * @type {array}
                     */
                    this.webservices = window.bb.ToolsbarManager.getTbInstance('bundletb').selectedBundle.webservices;

                    /**
                     * actions.
                     *
                     * @type {array}
                     */
                    this.actions = [];

                    /**
                     * default action
                     *
                     * @type {object}
                     */
                    this.defaultAction = {
                        params: function (event) {
                            return {};
                        },
                        success: function (response) {
                            window.bb.jquery(popinClass).html(response.result);
                        },
                        error: function (responce) {
                            console.warn(responce);
                        }
                    };
                },

                /**
                 * execute the action call by a.link
                 *
                 * @param {string} namespace
                 * @param {object} event
                 * @returns {undefined}
                 */
                executeAction: function (namespace, event) {
                    var request = this.actions[namespace];
                    if (request !== undefined) {
                        request.webservice.request(request.action, {
                            params: request.params.call(event.currentTarget, event),
                            success: request.success,
                            error: request.error
                        });
                    } else {
                        console.warn(namespace + ' is missing.');
                    }
                },

                /**
                 * Compelte the action with missing options
                 *
                 * @param {object} action
                 * @param {webservice} webservice
                 * @param {string} actionName
                 * @returns {object}
                 */
                completeAction: function (action, webservice, actionName) {
                    if (webservice !== undefined) {
                        action.webservice = webservice;
                    } else {
                        console.error(action + ' webservice is missing.');
                    }
                    action.action = actionName;
                    return window.bb.jquery.extend(true, {}, this.defaultAction, action);
                },

                /**
                 * Add a new action
                 *
                 * @param {string} namespace
                 * @param {object} action
                 * @returns {undefined}
                 */
                addAction: function (namespace, action) {
                    var colonPos = namespace.indexOf('::'),
                        webservice = {},
                        name = '';

                    if (colonPos === -1) {
                        webservice = window.bb.ToolsbarManager.getTbInstance('bundletb').selectedBundle.webservice;
                        name = namespace;
                    } else {
                        webservice = this.webservices[namespace.substr(0, colonPos)];
                        name = namespace.substr(colonPos + 2);
                    }
                    this.actions[namespace] = this.completeAction(action, webservice, name);
                }
            });

            var bundleController = new BundleController();
            /**
             * Exposing addAction function from BundleController
             */
            window.bb.bundle = {
                addAction: function () {
                    return bundleController.addAction.apply(bundleController, arguments);
                }
            };

            /**
             * Add click listener on all object with bb-bundle-link class
             * 
             * @param {object} event
             */
            window.bb.jquery(popinClass).delegate('.bb-bundle-link', 'click', function (event) {
                event.preventDefault();
                console.log('delegate');
                bundleController.executeAction(window.bb.jquery(event.target).attr('data-href'), event);
            });

            /**
             * Add submit listener on all object with bb-bundle-form class
             * 
             * @param {object} event
             * @todo catch the bundle request ended to reload click listeners.
             */
            window.bb.jquery(popinClass).delegate('.bb-bundle-form', 'submit', function (event) {
                event.preventDefault();
                bundleController.executeAction(window.bb.jquery(event.target).attr('data-action'), event);
            });
        })(window); 
    });
}(window));