<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title><?= e($layout->title) ?></title>

        <?php foreach ($layout->css as $css): ?>
            <link rel="stylesheet" href="<?= e($css) ?>" />
        <?php endforeach; ?>
    </head>
    <body>
        <div class="container-fluid">
            <?php include __DIR__ . '/navbar.php' ?>

            <div class="row">
                <?php include __DIR__ . '/main.php' ?>
                <?php include __DIR__ . '/sidebar.php' ?>
            </div>
        </div>

        <?php foreach ($layout->js as $js): ?>
            <script src="<?= e($js) ?>"></script>
        <?php endforeach; ?>
    </body>
</html>
