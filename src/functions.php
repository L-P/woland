<?php

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
 * Coalesce function for arrays because 5.5 does not have ??.
 *
 * @param mixed[] $array
 * @param mixed $key
 * @param mixed $default
 */
function array_get(array $array, $key, $default = null)
{
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

/**
 * Wrap finfo_* and fix a MIME name.
 *
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
    assert('$mime !== false');

    return array_get($translate, $mime, $mime);
}


/**
 * Rotate an Imagick image using its JPEG orientation.
 *
 * http://stackoverflow.com/a/31943940
 */
function image_autorotate(\Imagick $image)
{
    switch ($image->getImageOrientation()) {
        case \Imagick::ORIENTATION_TOPLEFT:
            break;
        case \Imagick::ORIENTATION_TOPRIGHT:
            $image->flopImage();
            break;
        case \Imagick::ORIENTATION_BOTTOMRIGHT:
            $image->rotateImage("#000", 180);
            break;
        case \Imagick::ORIENTATION_BOTTOMLEFT:
            $image->flopImage();
            $image->rotateImage("#000", 180);
            break;
        case \Imagick::ORIENTATION_LEFTTOP:
            $image->flopImage();
            $image->rotateImage("#000", -90);
            break;
        case \Imagick::ORIENTATION_RIGHTTOP:
            $image->rotateImage("#000", 90);
            break;
        case \Imagick::ORIENTATION_RIGHTBOTTOM:
            $image->flopImage();
            $image->rotateImage("#000", 90);
            break;
        case \Imagick::ORIENTATION_LEFTBOTTOM:
            $image->rotateImage("#000", -90);
            break;
        default:
            // Invalid orientation
            return;
    }

    $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
}

/// array_reduce for iterators.
function iterator_reduce(\Iterator $iterator, callable $func, $initial = null)
{
    $carry = $initial;
    foreach ($iterator as $item) {
        $carry = $func($carry, $item);
    }
    return $carry;
}

/**
 * @return mixed[] @see get_default_settings
 */
function get_settings()
{
    $home = posix_getpwuid(posix_getuid())['dir'];

    $confPath = "$home/.config/woland.json";
    if (!file_exists($confPath)) {
        throw new \RuntimeException("No configuration found at `$confPath`.");
    }

    $userSettings = json_decode(file_get_contents($confPath), true);
    if ($userSettings === false) {
        throw new \RuntimeException("Invalid JSON found in `$confPath`.");
    }

    return $userSettings + get_default_settings();
}

/**
 * @return mixed[]
 */
function get_default_settings()
{
    return [
        'debug'     => php_sapi_name() === 'cli-server',
        'favorites' => [],
        'realm'     => 'Woland',
        'secure'    => true,
        'users'     => [],
    ];
}

/**
 * @param mixed[] $settings @see get_default_settings
 * @return callable f($container) -> Twig
 */
function get_twig_creator(array $settings)
{
    return function ($c) use ($settings) {
        $cache = $settings['debug'] ? false : $settings['cache'] . '/templates';
        $view = new \Slim\Views\Twig(
            dirname(__DIR__) . '/src/templates',
            compact('cache')
        );

        $view->addExtension(new \Slim\Views\TwigExtension(
            $c['router'],
            $c['request']->getUri()
        ));

        $view->addExtension(new \Woland\TwigExtension());
        $view->getEnvironment()->getExtension('core')
            ->setDateFormat('Y-m-d H:i:s P')
        ;

        return $view;
    };
}
