<?php

function displayNestedArray(array $array, $path)
{
    echo '<ul>';
    foreach ($array as $value) {
        if (
            !$path->isNone()
            && $path->info->getPathname() == $value[0]->getPathname()
        ) {
            $uri = file_to_uri($value[0], $path);
            eprintf('<a href="%s" class="bg-info">%s</a>', $uri, $value[0]->getBasename());
        } else {
            echo file_to_link($value[0], $path);
        }

        if (count($value[1] > 0)) {
            displayNestedArray($value[1], $path);
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>

<nav class="col-md-3 col-md-pull-9">
    <?php if ($path->isNone()): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?= _('Favorites') ?></div>
            <div class="panel-body">
                <ul>
                <?php
                foreach($favorites as $name) {
                    eprintf('<li><a href="/%1$s/">%1$s</a></li>', $name);
                }
                ?>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <?php if (!$path->isFavoriteRoot() && $partial = $sidebar->getPartialTree()): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?= e($path->relative) ?></div>
            <div class="panel-body">
                <?php displayNestedArray($partial, $path); ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($full = $sidebar->getFullTree()): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?= e($path->favoriteName) ?></div>
            <div class="panel-body">
                <?php displayNestedArray($full, $path); ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</nav>
