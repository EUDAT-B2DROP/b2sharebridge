<div class="section" id="eudat_b2share">
    <h2>EUDAT B2SHARE Bridge</h2>

    <p id="b2shareUrlField">
        <input title="publish_baseurl" type="text" name="publish_baseurl"
               id="b2shareUrl"
               placeholder="https://b2share.eudat.eu" style="width: 400px"
               value="<?php p($_['publish_baseurl']); ?>"/>
        <em>External B2SHARE API endpoint</em>
    </p>
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

</div>
