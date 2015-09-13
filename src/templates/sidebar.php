<nav class="col-md-3 col-md-pull-9 sidebar">
<?php

function displayNestedArray(array $array, $path)
{
    echo '<ul>';
    foreach ($array as $value) {
        eprintf(
            '<li><a href="%s">%s</a>',
            file_to_uri($value[0], $path),
            $value[0]->getBasename()
        );
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
