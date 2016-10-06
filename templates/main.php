<?php
style('b2sharebridge', 'style');
style('files', 'files');
?>

<div id="app" class="b2sharebridge">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('part.navigation')); ?>
    </div>

    <div id="app-content">
        <div id="app-content-wrapper">

            <h1>Queued Jobs <small>(<?php p(sizeof($_['transfers'])) ?>)</small></h1>
            <table class="publish-queue-list">
                <?php if(sizeof($_['transfers']) > 0): ?>
                    <thead>
                        <tr>
                            <th>Transfer ID</th>
                            <th>Filename</th>
                            <th>Request Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_['transfers'] as $transfer): ?>
                            <tr>
                                <td><?php p($transfer['id']) ?></td>
                                <td><?php p($transfer['filename']) ?></td>
                                <?php // TODO: echo as user specific timedate ?>
                                <td><?php p(date('D\, j M Y H:i:s', $transfer['date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php else: ?>
                <tr>
                    <td>No queued jobs.</td>
                </tr>
                <?php endif; ?>
            </table>

            <div style="margin-top: 20px;">&nbsp;</div>

            <h1>Publishing History <small>(<?php p(sizeof($_['publications'])) ?>)</small></h1>
            <table class="publish-queue-list">
                <?php if(sizeof($_['publications']) > 0): ?>
                    <thead>
                        <tr>
                            <th>Transfer ID</th>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Publish URL</th>
                            <th>Publish Date</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_['publications'] as $publication): ?>
                            <tr>
                                <td><?php p($publication->getId()) ?></td>
                                <td><?php p($publication->getFilename()) ?></td>
                                <?php // TODO: echo as user specific timedate ?>
                                <td><?php p($_['statuscodes'][$publication->getStatus()]) ?></td>
                                <td><a target="_blank" href=<?php p($publication->getUrl()) ?>>URL</a></td>
                                <td><?php p(date('D\, j M Y H:i:s', $publication->getCreatedAt())) ?></td>
                                <td><?php p(date('D\, j M Y H:i:s', $publication->getUpdatedAt())) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php else: ?>
                    <tr>
                        <td>No published files found.</td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <div style="margin-top: 20px;">&nbsp;</div>
            
            <h1>Error History <small>(<?php p(sizeof($_['fails'])) ?>)</small></h1>
            <table class="publish-queue-list">
                <?php if(sizeof($_['fails']) > 0): ?>
                    <thead>
                        <tr>
                            <th>Transfer ID</th>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Publish URL</th>
                            <th>Publish Date</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_['fails'] as $fail): ?>
                            <tr>
                                <td><?php p($fail->getId()) ?></td>
                                <td><?php p($fail->getFilename()) ?></td>
                                <?php // TODO: echo as user specific timedate ?>
                                <td><?php p($_['statuscodes'][$fail->getStatus()]) ?></td>
                                <td><a target="_blank" href=<?php p($fail->getUrl()) ?>>URL</a></td>
                                <td><?php p(date('D\, j M Y H:i:s', $fail->getCreatedAt())) ?></td>
                                <td><?php p(date('D\, j M Y H:i:s', $fail->getUpdatedAt())) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php else: ?>
                    <tr>
                        <td>No published files found.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
