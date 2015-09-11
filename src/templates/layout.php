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
            <nav class="navbar navbar-default">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#js-navbar-collapse" aria-expanded="false">
                        <span class="sr-only"><?= _('Toggle navigation') ?></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="/">Woland</a>
                </div>
                <div class="collapse navbar-collapse" id="js-navbar-collapse">
                    <ul class="nav navbar-nav">
                    <?php
                    foreach(array_keys($favorites) as $name) {
                        if ($currentFavorite === $name) {
                            eprintf(
                                '<li class="active"><a href="%1$s">%1$s'
                                . '<span class="sr-only">'
                                . _('(current)')
                                . '</span></a></li>',
                                $name
                            );
                        } else {
                            eprintf('<li><a href="%1$s">%1$s</a></li>', $name);
                        }
                    }
                    ?>
                    </ul>
                </div>
            </nav>
        </div>

        <?php foreach ($layout->js as $js): ?>
            <script src="<?= e($js) ?>"></script>
        <?php endforeach; ?>
    </body>
</html>
