<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>Woland - <?= e($path->path) ?: _('index') ?></title>

        <?php foreach ($layout->css as $css): ?>
            <link rel="stylesheet" href="<?= e($css) ?>" />
        <?php endforeach; ?>
    </head>
    <body>
        <?php include __DIR__ . '/navbar.php' ?>

        <?php foreach ($layout->js as $js): ?>
            <script src="<?= e($js) ?>"></script>
        <?php endforeach; ?>
    </body>
</html>
