<div id="app-navigation">
    <ul class="with-icon">
        <?php foreach ($_['navigations'] as $navigation) { ?>
            <li<?php if ($_['activeNavigation'] === $navigation['id']) : ?> class="active"<?php 
           endif; ?>>
                <a data-navigation="<?php p($navigation['id']) ?>" href="<?php p($navigation['url']) ?>">
                    <?php p($navigation['name']) ?>
                </a>
            </li>
        <?php } ?>
    </ul>
</div>
