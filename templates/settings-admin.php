<div class="section" id="eudat_b2share">
    <!-- TODO: if we have translations, add l10n support
    $l = \OC::$server->getL10N('files_sharing');
    <h2>  php p($l->t('EUDAT B2SHARE Bridge'));?>  </h2>-->
    <h2>EUDAT B2SHARE Bridge</h2>

    <p id="b2shareUrlField">
        <input title="publish_baseurl" type="text" name="publish_baseurl" id="b2shareUrl"
               placeholder="https://b2share.eudat.eu" style="width: 400px" value="<?php p($_['publish_baseurl']); ?>" />
        <em>External B2SHARE API endpoint</em>
    </p>
</div>