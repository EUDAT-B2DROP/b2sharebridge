

<div id="app" class="b2sharebridge">

    <?php $_['appNavigation']->printPage(); ?>

    <div id="app-content">
        <div id="app-content-wrapper">

            <table class="publish-queue-list">
                <?php if (sizeof($_['publications']) > 0) : ?>
                    <thead>
                    <tr>
                        <th>Transfer ID</th>
                        <th>#Files</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Deposit URL</th>
                        <th>Triggered At</th>
                        <th>Last Update</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_['publications'] as $publication): ?>
                        <tr>
                            <td><?php p($publication->getId()) ?></td>
                            <td><?php p($publication->getFileCount()) ?></td>
                            <?php // TODO: echo as user specific timedate ?>
                            <td><?php p($publication->getTitle()) ?></td>
                            <td><?php p($_['statuscodes']->getForNumber($publication->getStatus())) ?></td>
                            <td><?php if ($publication->getStatus() > 2) : ?>
                                    ERROR: <?php  p($publication->getErrorMessage()) ?>
                                <?php elseif ($publication->getStatus() == 0) : ?>
                                    <a href="<?php p($publication->getUrl()) ?>" target="_blank">Deposit URL</a>
                                <?php endif; ?>
                            </td>
                            <td><?php p(date('D\, j M Y H:i:s', $publication->getCreatedAt())) ?></td>
                            <td><?php p(date('D\, j M Y H:i:s', $publication->getUpdatedAt())) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                <?php else: ?>
                    <tr>
                        <td>No deposits for this category found.</td>
                    </tr>
                <?php endif; ?>
            </table>

            <div style="margin-top: 20px;">&nbsp;</div>
        </div>
    </div>
</div>
