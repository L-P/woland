<?php
/**
 * Aliases mostly used in templates.
 * This is supposed to be a quick and dirty project so I did not bother using
 * a template engine other than PHP.
 */

/**
 * Partial htmlentities.
 *
 * @param string $str
 * @return string
 */
function e($str)
{
    return htmlentities($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/// sprintf alias that put all parameters through e()
function esprintf($str)
{
    return call_user_func_array(
        'sprintf',
        array_merge(
            [$str],
            array_map('e', array_slice(func_get_args(), 1))
        )
    );
}

/// printf alias that put all parameters through e()
function eprintf($str)
{
    return call_user_func_array(
        'printf',
        array_merge(
            [$str],
            array_map('e', array_slice(func_get_args(), 1))
        )
    );
}

/**
 * Get absolute URI from a SplFileInfo.
 *
 * @return string
 */
function file_to_uri(\SplFileInfo $file, \Woland\RequestedPath $path)
{
    if (
        strpos($file->getPathname(), $path->favoritePathname . '/') !== 0
        && $file->getPathname() !== $path->favoritePathname
    ) {
        throw new \RuntimeException('Unknown path.');
    }

    $relative = substr($file->getPathname(), strlen($path->favoritePathname));
    $encodedRelative = implode('/', array_map('urlencode', explode('/', $relative)));

    $uri = sprintf('/%s%s', $path->favoriteName, $encodedRelative);
    return $uri . ($file->isDir() ? '/' : '');
}

/**
 * Create an HTML link from a SplFileInfo.
 *
 * @return string
 */
function file_to_link(\SplFileInfo $file, \Woland\RequestedPath $path)
{
    return esprintf(
        '<a href="%s">%s</a>',
        file_to_uri($file, $path),
        $file->getBasename()
    );
}

/**
 * Output a template.
 *
 * @param string $template full path to template file.
 * @param mixed[] array to extract before including the template.
 */
function render_template($template, $data)
{
    // I'd rather crash than silently EXTR_SKIP.
    if (array_key_exists('template', $data)) {
        throw new \LogicException('Template data array can\'t have a \'template\' key.');
    }

    extract($data);
    header('Content-Type: text/html; charset=UTF-8');
    require $template;
}

/**
 * eg. 1024 => "1 KiB"
 *
 * @param int $bytes
 * @return string
 */
function bytes_to_human_readable($bytes)
{
    $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

    if ($bytes === 0)
        return '0 B';

    $index = min(count($units) -1, floor(log($bytes, 1024)));
    $size = round($bytes / pow(1024, $index), 2);

    return $size . ' ' . $units[$index];
}

/**
 * @param int $time timestamp.
 * @return string
 */
function format_date($time)
{
    return date('Y-m-d H:i:s P', $time);
}

/// No coalesce operator in 5.5.
function array_get(array $array, $key, $default = null)
{
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

/**
 * @param string $pathname
 * @return string
 */
function get_mime($pathname)
{
    static $finfo = null;
    if ($finfo === null) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
    }

    $translate = [
        'application/ogg' => 'audio/ogg',
    ];

    $mime = finfo_file($finfo, $pathname);
    return array_get($translate, $mime, $mime);
}
