/*jslint browser: true */
/*global M: false */
/*vim: set ff=unix:ai:et:sw=4:sts=4:ts=4 */
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2013 Blackboard Inc., All Rights Reserved.              *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                      *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Blackboard Instant Messenger Moodle Integration;       *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih                                                  *
 *                                                                            *
 ******************************************************************************/
// Requires YUI modules 'node', 'node-event-html5', 'dom', 'io', and 'json'
(function (M) {
    "use strict";
    var Y,
        secretMutationObserver,
        launchCheckConfig = function (id, response) {
            var responseJson = Y.JSON.parse(response.responseText),
                checkConfigNode = Y.one('#pronto_checkconfig_result'),
                resultNode = document.createElement("iframe");

            if (responseJson.success) {
                resultNode.setAttribute('src', responseJson.url);
                resultNode.setAttribute('height', '200');
                resultNode.setAttribute('width', '400');
            } else {
                resultNode = Y.one(Y.DOM.create('div'));
                resultNode.addClass('error-message');
                resultNode.setHTML(responseJson.errorMessage ||
                    "An error occurred checking the configuration.");
            }
            checkConfigNode.setHTML(resultNode);
        },

        checkConfig = function (e) {
            var uri = M.cfg.wwwroot + "/mod/pronto/validation.php",
                cfg = {
                    method: 'POST',
                    form: {
                        id: 'adminsettings'
                    },
                    on: {
                        complete: launchCheckConfig
                    }
                };
            Y.io(uri, cfg);
        },

        /*
         * Return an event handler that sets the validity text
         * of the event target to the given invalidText.
         */
        setInvalidText = function (invalidText) {
            return function (e) {
                var domNode = e.currentTarget.getDOMNode();

                domNode.setCustomValidity('');
                if (!domNode.validity.valid) {
                    domNode.setCustomValidity(invalidText);
                }
            };
        },

        /*
         * Sets the validation attributes on the input element.
         * Adds event listeners to set the custom validation messages
         * when validation fails.
         */
        setValidationOnInput = function (node, attributes, invalidText) {
            node.setAttrs(attributes);

            if (typeof invalidText === 'string') {
                node.on('invalid', setInvalidText(invalidText));
                node.on('input', setInvalidText(invalidText));
            }
        },

        /*
         * Set validation attributes on fields
         */
        addValidations = function () {
            setValidationOnInput(Y.one("#id_s__pronto_url"),
                {'required': 'required', 'type': 'url'});

            setValidationOnInput(Y.one("#id_s__pronto_account"),
                {'pattern': '^[a-zA-Z0-9_\\-]*$'},
                M.util.get_string('invalidlettersnumbers', 'pronto'));
        };

    M.mod_pronto = {
        init: function (YUI) {
            var checkConfigButton = YUI.one('#pronto_checkconfig_btn');
            Y = YUI;

            if (checkConfigButton !== null) {
                checkConfigButton.on('click', checkConfig);
            }

            addValidations();
        }
    };
}(window.M));
