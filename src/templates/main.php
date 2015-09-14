<main class="col-md-9 col-md-push-3">
    <div class="panel panel-default">
            <?php
            if (!$path->isNone()) {
                require __DIR__ . '/list.php';
            }
            ?>
    </div>
</main>
