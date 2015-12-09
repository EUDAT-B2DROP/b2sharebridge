<?php
?>
<div class="section" id="eudat_b2share">
    <h2><?php p($l->t('EUDAT B2SHARE Bridge'));?></h2>
    <p name="b2share_url_field" id="b2shareUrlField">
        <input type="text" name="publish_baseurl" id="b2shareUrl" value='<?php p($_['publish_baseurl']); ?>' style="width: 400px" disabled/>
        <em><?php p($l->t('External publishing endpoint')); ?></em>
    </p>
</div>