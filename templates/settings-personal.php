<?php
script('b2sharebridge', 'settings');
style('b2sharebridge', 'settings');
?>
<div class="section" id="eudat_b2share">
    <h2>EUDAT B2SHARE Bridge</h2>
    <p id="b2shareUrlField">
        <input title= "publish_baseurl" type="text"  id="b2shareUrl" value="<?php p($_['publish_baseurl']); ?>"
               style="width: 400px" disabled/>
        <em>External publishing endpoint</em>
    </p>
</div>