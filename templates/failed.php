<?php
style('b2sharebridge', 'style');
style('files', 'files');
?>

<div id="app" class="b2sharebridge">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('part.navigation')); ?>
        <?php print_unescaped($this->inc('part.settings')); ?>
    </div>

    <div id="app-content">
        <div id="app-content-wrapper">

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
