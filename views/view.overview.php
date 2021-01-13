<?php include(__DIR__ . '/partial.header.php') ?>

<body>
    <? if ( empty($containers) ) exit ?>
    <div class="content">
    <div class="top">
        <h1> Domain List </h1>
        <button class="button button--add"><a href="/setup">+</a></button>

    </div>
    <table>

        <thead>
        <tr>
            <? foreach( ['Project', 'Status', 'URL', 'SSH Port', 'Actions'] as $heading ) { ?>
                <th> <?= $heading ?> </th>
            <? } ?>
        </tr>
        </thead>

        <tbody>
        <? foreach( $containers as $container ) { ?>
            <tr>
                <td><?= $container->getName() ?></td>
                <?php
                $status = $container->isRunning() ? 'running' : 'stopped'
                ?>
                <td data-status="<?= $status ?>"><span><?= ucwords($status) ?></span></td>

                <td><a href="<?= $container->getHttpUrl() ?>" target="_blank"> <?= $container->getHttpUrl() ?> </a></td>

                <td><?= $container->getSshPort() ?></td>

                <td>
                    <? if ( $container->isRunning() ) { ?>

                        <? if ( $container->getProjectType() === Project_Type::MAGENTO) { ?>
                            <button class="button button--update" onclick="updateContainer( '<?= $container->getName() ?>' )"> Update </button>
                        <? } ?>

                        <button class="button button--stop" onclick="stopContainer( '<?= $container->getName() ?>' )"> Stop </button>
                    <? } else { ?>
                        <button class="button button--start">Start</button>
                        <button class="button button--delete"> Delete </button>
                    <? } ?>
                </td>
            </tr>
        <? } ?>
        </tbody>

    </table>
    </div>

    <script type="text/javascript">
        function stopContainer( name ) {
            if ( confirm(`Are you sure you want to stop ${name} ?`) ) {
                window.location = `/stop?name=${name}`
            }
        }

        function updateContainer( name ) {
            if ( confirm(`Are you sure you want to update ${name} ?`) ) {
                window.location = `/update?name=${name}`
            }
        }

    </script>
    
</body>