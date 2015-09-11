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
