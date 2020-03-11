<?php
script('b2sharebridge', 'settings-admin');
?>
<div class="section" id="eudat_b2share">
    <h2>EUDAT B2SHARE Bridge</h2>
    <p id="maxB2shareUploadsPerUser">
            <input title="max_uploads" type="text" name="max_uploads"
                   id="maxB2shareUploads"
                   placeholder="5" style="width: 400px"
                   value="<?php p($_['max_uploads']); ?>"/>
            <em># of uploads per user at the same time</em>
        </p>
        <p id="maxB2shareUploadSizePerFile">
            <input title="max_upload_filesize" type="text" name="max_upload_filesize"
                   id="maxB2shareUploadFilesize"
                   placeholder="512" style="width: 400px"
                   value="<?php p($_['max_upload_filesize']); ?>"/>
            <em>MB maximum filesize per upload</em>
        </p>
        <p>
            <input type="checkbox" name="check_ssl" id="checkSsl" class="checkbox"
                   value="1" <?php if ($_['check_ssl']) print_unescaped('checked="checked"'); ?> />
            <label for="checkSsl">
                <?php p($l->t('Check valid secure (https) connections to B2SHARE'));?>
            </label>
        </p>



    <?php foreach ($servers as $server): ?>
        <div id="b2share_server_<?php p($server->getId()) ?>">
        <p id="b2shareUrlField_<?php p($server->getId()) ?>">
            <input title="publish_baseurl" type="text" name="publish_baseurl"
                   id="url_<?php p($server->getId()) ?>"
                   placeholder="https://b2share.eudat.eu" style="width: 400px"
                   value="<?php p($server->getPublishUrl()); ?>"/>
                   <em>Publish URL</em>
        </p>
        <p id="b2shareNameField_<?php p($server->getId()) ?>">
            <input title="name_<?php p($server->getId()) ?>" type="text" name="name"
                   id="name_<?php p($server->getId()) ?>"
                   style="width: 400px"
                   value="<?php p($server->getName()); ?>"/>
                   <em>Server name</em>
        </p>
        <button id="#delete_<?php p($server->getId());?>">Delete server</button>
        </div>
    <?php endforeach ?>
    <button id="add-server">Add new server</button>
    <button id="send">Save changes</button>
    <div id="saving"><span class="msg"></span><br /></div>
</div>
