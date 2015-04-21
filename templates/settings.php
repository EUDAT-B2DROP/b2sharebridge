<?php
/** @var OC_L10N $l */
/** @var array $_ */
?>
<div id="eudat_b2share" class="section">
    <h2><?php p($l->t('EUDAT B2SHARE Bridge'));?></h2>
    <p id="enable">
        <input type="checkbox" name="b2share_bridge_enabled" id="b2share_bridge_enabled"
               value="1" <?php if ($_['b2share_bridge_enabled'] === 'yes') print_unescaped('checked="checked"'); ?> />
        <label for="b2share_bridge_enabled"><?php p($l->t('Publishing to B2SHARE enabled'));?></label><br/>
    </p>
    <p class="<?php if ($_['b2share_bridge_enabled'] === 'no') p('hidden');?>">
        <input type="text" name="b2share_endpoint_url" id="b2share_endpoint_url"
               value="<?php p($_['b2share_endpoint_url']); ?>" />
        <label for="b2share_endpoint_url"><?php p($l->t('External B2SHARE API endpoint'));?></label><br/>
    </p>
</div>