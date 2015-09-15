#EXTM3U
<?php

$lines = [];
foreach ($files as $file) {
    if (strpos(get_mime($file->getPathname()), 'audio/') === 0) {
        $name = substr($file->getBasename(), 0, -strlen($file->getExtension()) - 1);
        $lines[] = "#EXTINF:0,$name";
        $lines[] = "$baseUrl/" . file_to_uri($file, $path);
    }
}
echo implode("\n", $lines);
