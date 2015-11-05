<?php
script('eudat', 'script');
style('eudat', 'style');
style('files', 'files');
?>

<div id="app" class="eudat">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('part.navigation')); ?>
        <?php print_unescaped($this->inc('part.settings')); ?>
    </div>

    <div id="app-content">
        <div id="app-content-wrapper">

            <h1>Queued Jobs <small>(<?php p(sizeof($_['jobs'])) ?>)</small></h1>
            <table class="publish-queue-list">
                <?php if(sizeof($_['jobs']) > 0): ?>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Filename</th>
                            <th>Request Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_['jobs'] as $job): ?>
                            <tr>
                                <td><?php p($job->getId()) ?></td>
                                <td><?php p($job->getFilename()) ?></td>
                                <?php // TODO: echo as user specific timedate ?>
                                <td><?php p($job->getRequestDate()) ?> (UTC)</td>
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

            <h1>Publish History/ Status <small>(<?php p(sizeof($_['fileStatus'])) ?>)</small></h1>
            <table class="publish-queue-list">
                <?php if(sizeof($_['fileStatus']) > 0): ?>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Publish Date</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_['fileStatus'] as $fileStatus): ?>
                            <tr>
                                <td><?php p($fileStatus->getId()) ?></td>
                                <td><?php p($fileStatus->getFilename()) ?></td>
                                <?php // TODO: echo as user specific timedate ?>
                                <td><?php p($fileStatus->getStatus()) ?></td>
                                <td><?php p($fileStatus->getCreatedAt()) ?></td>
                                <td><?php p($fileStatus->getUpdatedAt()) ?></td>
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
