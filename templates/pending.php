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

        </div>
    </div>
</div>
