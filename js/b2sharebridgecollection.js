/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

    /**
     * @class OCA.Activity.ActivityCollection
     * @classdesc
     *
     * Displays activity information for a given file
     */
    var B2shareBridgeCollection = OC.Backbone.Collection.extend(
        /**
        * @lends OCA.Activity.ActivityCollection.prototype 
        */ {

        /**
         * Id of the file for which to filter activities by
         *
         * @var int
         */
    _objectId: null,

        /**
         * Type of the object to filter by
         *
         * @var string
         */
    _objectType: null,

        //model: OCA.B2shareBridge.B2shareBridgeModel,

        /**
         * Sets the object id to filter by or null for all.
         * 
         * @param {int} objectId file id or null
         */
    setObjectId: function(objectId) {
        this._objectId = objectId;
    },

        /**
         * Sets the object type to filter by or null for all.
         * 
         * @param {int} objectType file id or null
         */
    setObjectType: function(objectType) {
        this._objectType = objectType;
    },

    url: function() {
    }
        }
    );

    OCA.B2shareBridge = OCA.B2shareBridge || {};

    OCA.B2shareBridge.B2shareBridgeCollection = B2shareBridgeCollection;
})();

