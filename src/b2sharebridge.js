
(function () {
    
    OCA.B2shareBridge = OCA.B2shareBridge || {};

    /**
     * @namespace
     */
    OCA.B2shareBridge.Util = {
        /**
         * Initialize the b2sharebridge plugin.
         *
         * @param {OCA.Files.FileList} fileList file list to be extended
         */
        attach: function (fileList) {
            if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
                return;
            }
            var fileActions = fileList.fileActions;

            fileActions.registerAction(
                {
                    name: 'B2SHARE',
                    displayName: 'B2SHARE',
                    mime: 'all',
                    permissions: OC.PERMISSION_READ,
                    icon: OC.imagePath('b2sharebridge', 'filelisticon'),
                    actionHandler: function (fileName) {
                        fileList.showDetailsView(fileName, 'filetab-main');
                    },
                }
            );
            //fileList.registerTabView(new OCA.B2shareBridge.B2shareBridgeTabView('B2shareBridgeTabView',{order: -30}));
        }
    };

})();

OC.Plugins.register('OCA.Files.FileList', OCA.B2shareBridge.Util);
