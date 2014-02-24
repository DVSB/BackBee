(function (window) {
    "use strict";
    /**
     * Bundle controller object.
     * 
     * @example How to add one action
     * 
     * Javascript:
     * {string} namespace can be compose like actionName or webserviceName::actionName.
     * {object} {} all key describe under are optionals.
     * 
     * bb.bundle.addAction(namespace, {
     *      params: function (event) {function to catch query params},
     *      success: function (response) {function to apply the query result},
     *      error: function (response) {function to }
     * });
     * 
     * html:
     * <a class="bb-bundle-link" href="namespace">link</a>
     * namespace is the same as the bb.bundle.addAction namespace parameter
     * work on all tags
     */
    var BundleController = function () {
        /**
         * Bundle web services
         *
         * @type {array}
         */
        this.webservices = BB4.ToolsbarManager.getTbInstance('bundletb').selectedBundle.webservices;

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
            params: {},
            success: function (response) {
                console.log(response);
            },
            error: function (responce) {
                console.warn(responce);
            }
        };

        /**
         * execute the action call by a.link
         *
         * @param {string} namespace
         * @param {object} event
         * @returns {undefined}
         */
        this.executeAction = function (namespace, event) {

            var request = this.actions[namespace];
            if (request !== undefined) {
                request.webservice.request(request.action, {
                    params: request.params.call(event, event),
                    success: request.success,
                    error: request.error
                });
            } else {
                console.warn(namespace + ' is missing.');
            }
        };

        /**
         * Compelte the action with missing options
         *
         * @param {object} action
         * @param {webservice} webservice
         * @param {string} actionName
         * @returns {object}
         */
        this.completeAction = function (action, webservice, actionName) {
            if (webservice !== undefined) {
                action.webservice = webservice;
            } else {
                console.error(action + ' webservice is missing.');
            }
            action.action = actionName;
            return window.bb.jquery.extend(action, this.defaultAction);
        };

        /**
         * Add a new action
         *
         * @param {string} namespace
         * @param {object} action
         * @returns {undefined}
         */
        this.addAction = function (namespace, action) {
            var colonPos = namespace.indexOf('::'),
                webservice = {},
                name = '';

            if (colonPos === -1) {
                webservice = BB4.ToolsbarManager.getTbInstance('bundletb').selectedBundle.webservice;
                name = namespace;
            } else {
                webservice = this.webservices[namespace.substr(0, colonPos)];
                name = namespace.substr(colonPos + 2);
            }

            this.actions[namespace] = this.completeAction(action, webservice, name);
        };
    };

    /**
     * Create a function callable ewternally with bb.bundle.addAction
     */
    window.bb.bundle = {
        addAction: function () {
            return BundleController.addAction.apply(BundleController, arguments);
        }
    };

    /**
     * Add click listener on all object with bb-bundle-link class
     * 
     * @param {object} event
     * @todo catch the bundle request ended to reload click listeners.
     */
    window.bb.jquery('.bb-bundle-link').click(function (event) {
        event.preventDefault();
        BundleController.executeAction(window.bb.jquery(event.target).attr('href'), event);
    });
}(window));