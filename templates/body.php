<div id="app" class="b2sharebridge">

    <?php $_['appNavigation']->printPage(); ?>

    <div id="app-content">
        <div id="app-content-wrapper">

            <table class="publish-queue-list">
                <?php if (sizeof($_['publications']) > 0) : ?>
                    <thead>
                    <tr>
                        <th>Transfer ID</th>
                        <th>Filename</th>
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
                            <td><?php p($publication->getFilename()) ?></td>
                            <?php // TODO: echo as user specific timedate ?>
                            <td><?php p($_['statuscodes']->getForNumber($publication->getStatus())) ?></td>
                            <td><a target="_blank"
                                   href=<?php p($publication->getUrl()) ?>>URL</a>
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
