<div id="app-navigation">
    <ul class="with-icon">
        <li>
            <a href="all">All Deposits</a>
        </li>
        <li>
            <a href="pending">Pending Deposits</a>
        </li>
        <li>
            <a href="published">Published Deposits</a>
        </li>
        <li>
            <a href="failed">Failed Deposits</a>
        </li>
    </ul>
</div>

<!--<div id="app-navigation">
    <?php /*foreach ($_['navigations'] as $navigationGroup => $navigationEntries) { */?>
        <?php /*if ($navigationGroup !== 'apps'): */?><ul><?php /*endif; */?>

        <?php /*foreach ($navigationEntries as $navigation) { */?>
            <li<?php /*if ($_['activeNavigation'] === $navigation['id']): */?> class="active"<?php /*endif; */?>>
                <a data-navigation="<?php /*p($navigation['id']) */?>" href="<?php /*p($navigation['url']) */?>">
                    <?php /*p($navigation['name']) */?>
                </a>
            </li>
        <?php /*} */?>

        <?php /*if ($navigationGroup !== 'top'): */?></ul><?php /*endif; */?>
    <?php /*} */?>

    <div id="app-settings">
        <div id="app-settings-header">
            <button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
        </div>

        <div id="app-settings-content">
            <input type="checkbox"<?php /*if ($_['rssLink']): */?> checked="checked"<?php /*endif; */?> id="enable_rss" class="checkbox" />
            <label for="enable_rss"><?php /*p($l->t('Enable RSS feed'));*/?></label>
            <input id="rssurl"<?php /*if (!$_['rssLink']): */?> class="hidden"<?php /*endif; */?> type="text" readonly="readonly" value="<?php /*p($_['rssLink']); */?>" />
        </div>
    </div>
</div>-->
