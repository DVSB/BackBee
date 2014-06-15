/**
 * @example How to route one action.
 * 
 * Javascript:
 * {string} namespace can be compose like actionName or webserviceName::actionName.
 * {object} {} optional parameter.
 * 
 * bb.require(['bb.BundleController'], function () {
 *     bb.bundle.addAction(namespace, {
 *         params: function (event) {
 *             // Function to catch query params return object.
 *             // By default return empty object. 
 *         },
 *         success: function (response) {
 *             // Function to apply the query result.
 *             // By default put the template into the popin content.
 *         },
 *         error: function (response) {
 *             // Function to apply the query error.
 *             // By default do console.warn of the error.
 *         }
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
(function (window) {
    "use strict";

    var bb = window.bb || {};
    bb.bundle = bb.bundle || {};

    define(["jscore"], function () {

        return (function () {
            /**
             * bundle popin identifier
             * 
             * @type {string}
             */
            var popinClass = '.bb5-dialog-admin-bundle',
            /**
             * Bundle controller object.
             */
                BundleController = new JS.Class({
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
                        this.webservices = bb.ToolsbarManager.getTbInstance('bundletb').selectedBundle.webservices;

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
                            params: function () {
                                return {};
                            },
                            success: function (response) {
                                bb.jquery(popinClass).html(response.result);
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
                        return bb.jquery.extend(true, {}, this.defaultAction, action);
                    },

                    /**
                     * Change the default action
                     *
                     * @param {object} action
                     * @returns {object}
                     */
                    setDefaultAction: function (action) {
                        this.defaultAction = bb.jquery.extend(true, {}, this.defaultAction, action);
                    },

                    /**
                     * Add a new action
                     *
                     * @param {string} namespace
                     * @param {object} action optional
                     * @returns {undefined}
                     */
                    addAction: function (namespace, action) {
                        var colonPos = namespace.indexOf('::'),
                            webservice = {},
                            name = '';

                        action = ((action === undefined) ? {} : action);
                        if (colonPos === -1) {
                            webservice = bb.ToolsbarManager.getTbInstance('bundletb').selectedBundle.webservice;
                            name = namespace;
                        } else {
                            webservice = this.webservices[namespace.substr(0, colonPos)];
                            name = namespace.substr(colonPos + 2);
                        }
                        this.actions[namespace] = this.completeAction(action, webservice, name);
                    }
                }),

                bundleController = new BundleController();
            /**
             * Exposing addAction function from BundleController
             */
            bb.bundle.addAction = function () {
                return bundleController.addAction.apply(bundleController, arguments);
            };
            bb.bundle.setDefaultAction = function () {
                return bundleController.setDefaultAction.apply(bundleController, arguments);
            };

            /**
             * Add click listener on all object with bb-bundle-link class
             * 
             * @param {object} event
             */
            bb.jquery(popinClass).delegate('.bb-bundle-link', 'click', function (event) {
                event.preventDefault();
                var target = bb.jquery(event.target);
                if (target.hasClass('link-confirm')) {
                    var is_confirmed = confirm(target.attr('data-confirm'));
                    if (!is_confirmed) {
                        return false;
                    }
                }
                bundleController.executeAction(target.attr('data-href'), event);
            });

            /**
             * Add submit listener on all object with bb-bundle-form class
             * 
             * @param {object} event
             */
            bb.jquery(popinClass).delegate('.bb-bundle-form', 'submit', function (event) {
                event.preventDefault();
                bundleController.executeAction(bb.jquery(event.target).attr('data-action'), event);
            });
        }(window));
    });
}(window));