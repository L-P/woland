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
        sort($favorites);
        foreach ($favorites as $name) {
            if ($path->favoriteName === $name) {
                eprintf(
                    '<li class="active"><a href="%1$s">%1$s'
                    . '<span class="sr-only">'
                    . _('(current)')
                    . '</span></a></li>',
                    $name
                );
            } else {
                eprintf('<li><a href="/%1$s">%1$s</a></li>', $name);
            }
        }
        ?>
        </ul>
    </div>
</nav>
