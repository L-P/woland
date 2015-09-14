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

function file_to_link(\SplFileInfo $file, \Woland\RequestedPath $path)
{
    return esprintf(
        '<a href="%s">%s</a>',
        file_to_uri($file, $path),
        $file->getBasename()
    );
}

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
