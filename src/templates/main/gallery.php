<?php
assert('!$path->isNone()');

echo '<div class="panel panel-default">',
    '<div class="panel-body gallery">'
;
$totalSize = 0;
foreach ($files as $file) {
    eprintf(
        '<a href="%s" class="thumbnail"><img src="%s" alt="picture" /></a>',
        file_to_uri($file, $path),
        file_to_uri($file, $path) . '?thumbnail'
    );
    $totalSize += $file->getSize();
}
eprintf(
    '<p class="gallery-footer">%s images, %s.</p>',
    count($files),
    bytes_to_human_readable($totalSize)
);
echo '</div></div>';
