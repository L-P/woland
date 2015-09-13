<nav class="col-md-3 col-md-pull-9 sidebar">
<?php

function displayNestedArray(array $array, $path)
{
    echo '<ul>';
    foreach ($array as $value) {
        if ($path->info->getPathname() == $value[0]->getPathname()) {
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

displayNestedArray(
    $sidebar->getNestedArray(),
    $path
);

?>
</nav>
