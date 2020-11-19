<?php
script('b2sharebridge', 'settings-personal');
?>
<div class="section" id="eudat_b2share">
    <h2>EUDAT B2SHARE Bridge</h2>
    <?php foreach ($servers as $server): ?>
    <p id="b2shareUrlField">
        <input title="publish_baseurl" type="text" id="b2shareUrl_<?php p($server['id'])?>"
               value="<?php p($server['publishUrl']); ?>"
               style="width: 400px" disabled/>
        <em>External publishing endpoint</em>
    </p>
    <p id="b2shareAPITokenField">
        <input title= "b2share API token" type="text" id="b2share_apitoken_<?php p($server['id'])?>" value="<?php p($server['token']); ?>" name="b2share_apitoken"
               style="width: 400px" />
        <em>B2Share API token</em>
        <div id="lostpassword_<?php p($server['id'])?>"><span class="msg"></span><br /></div>
    </p>
    <p id="b2shareManageAPIToken">
        <button id="b2share_save_apitoken_<?php p($server['id'])?>" href="#">Save B2SHARE API Token</button>
        <button id="b2share_delete_apitoken_<?php p($server['id'])?>" href="#">Delete B2SHARE API Token</button>
    </p>
    <?php endforeach ?>
</div>