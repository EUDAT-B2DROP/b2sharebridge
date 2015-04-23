<?php
/** @var OC_L10N $l */
/** @var array $_ */
?>
<div class="section" id="eudat_b2share">
    <h2><?php p($l->t('EUDAT B2SHARE Bridge'));?></h2>
    <p name="b2share_url_field" id="b2shareUrlField">
        <input type="text" name="b2share_endpoint_url" id="b2shareUrl" placeholder="https://b2share.eudat.eu" style="width: 400px"
               value="<?php p($_['b2share_endpoint_url']); ?>" />
        <br />
        <em><?php p($l->t('External B2SHARE API endpoint')); ?></em>
    </p>
</div>