<?php

namespace Woland;

class TwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'woland';
    }

    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];

        return [
            new \Twig_SimpleFunction('fileToLink', [$this, 'fileToLink'], $safe),
            new \Twig_SimpleFunction('displayNestedArray', [$this, 'displayNestedArray'], $safe),

            new \Twig_SimpleFunction('fileToUri', [$this, 'fileToUri']),
            new \Twig_SimpleFunction('albumArtUri', [$this, 'albumArtUri']),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('bytes', [$this, 'bytesToString']),
        ];
    }

    /**
     * Create an HTML link from a SplFileInfo.
     *
     * @return string
     */
    public function fileToLink(\SplFileInfo $file, \Woland\RequestedPath $path)
    {
        return esprintf(
            '<a href="%s">%s</a>',
            $this->fileToUri($file, $path),
            $file->getBasename()
        );
    }


    /**
     * Get absolute URI from a SplFileInfo.
     *
     * @return string
     */
    public function fileToUri(\SplFileInfo $file, \Woland\RequestedPath $path)
    {
        if (
            strpos($file->getPathname(), $path->favoritePathname . '/') !== 0
            && $file->getPathname() !== $path->favoritePathname
        ) {
            throw new \RuntimeException("Unknown path `$file`.");
        }

        $relative = substr($file->getPathname(), strlen($path->favoritePathname));
        $encodedRelative = implode('/', array_map('urlencode', explode('/', $relative)));

        $uri = sprintf('/%s%s', $path->favoriteName, $encodedRelative);
        return $uri . ($file->isDir() ? '/' : '');
    }

    public function albumArtUri(\Iterator $files, RequestedPath $path)
    {
        foreach ($files as $file) {
            if (strpos($file->getBasename(), 'cover.') === 0) {
                return $this->fileToUri($file, $path);
            }
        }

        return null;
    }

    /**
     * eg. 1024 => "1 KiB"
     *
     * @param int $bytes
     * @return string
     */
    public function bytesToString($bytes)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

        if ($bytes === 0) {
            return '0 B';
        }

        $index = min(count($units) -1, floor(log($bytes, 1024)));
        $size = round($bytes / pow(1024, $index), 2);

        return $size . ' ' . $units[$index];
    }

    public function displayNestedArray(array $array, RequestedPath $path)
    {
        $ret = '<ul>';
        foreach ($array as $value) {
            if (
                !$path->isNone()
                && $path->info->getPathname() == $value[0]->getPathname()
            ) {
                $uri = $this->fileToUri($value[0], $path);
                $ret .= esprintf('<a href="%s" class="bg-info">%s</a>', $uri, $value[0]->getBasename());
            } else {
                $ret .= $this->fileToLink($value[0], $path);
            }

            if (count($value[1] > 0)) {
                $ret .= $this->displayNestedArray($value[1], $path);
            }
            $ret .= '</li>';
        }
        $ret .= '</ul>';

        return $ret;
    }
}
