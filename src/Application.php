<?php

namespace Woland;

use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\UriInterface;

/// Process and render requests. See __invoke for entry point.
class Application
{
    /**
     * @var string[] paths to display in the initial view. Those are also the
     * only paths you will be able to access through the app.
     *
     * ["name" => "path",]
     *
     * The key is displayed as the favorite name.
     */
    private $favorites;

    /**
     * @param string[] $favorites @see $this->favorites.
     */
    public function __construct(array $favorites)
    {
        if (count($favorites) < 1) {
            throw new \RuntimeException('$favorites can\'t be empty, you need at least one path.');
        }

        if (array_key_exists('_', $favorites)) {
            throw new \RuntimeException("'_' is a reserved favorite name.");
        }

        $this->favorites = array_map('realpath', $favorites);
    }

    public function __invoke(RequestInterface $request)
    {
        $path = $request->getUri()->getPath();
        if ($path === '/_' || strpos($path, '/_/') === 0) {
            $this->renderAsset($path);
            return;
        }

        try {
            $path = RequestedPath::fromRequest($request, $this->favorites);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            throw $e;
        }

        if (!$path->isNone() && !$path->info->isDir()) {
            $this->renderSingleFile($request, $path);
        } else {
            $view = array_get($request->getQueryParams(), 'view');
            if ($view === 'playlist') {
                $this->renderPlaylist($path, $request->getUri());
            } else {
                $this->renderDir($path);
            }
        }
    }

    private function renderPlaylist(RequestedPath $path, UriInterface $uri)
    {
        $files = $this->getFilesIterator($path);
        $baseUrl = sprintf(
            '%s://%s:%d',
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPort()
        );

        header('Content-Type: application/x-mpegurl; charset=UTF-8');
        header('Cache-control: max-age=3600');
        require $this->getTemplatesDir() . '/playlist.php';
    }

    /**
     * Output a single file.
     *
     * No verification is made here, if the path exists, it will be sent.
     * The caller is responsible for checking that the user is allowed to
     * display the file.
     */
    private function renderSingleFile(RequestInterface $request, RequestedPath $path)
    {
        header('Content-Type: ' . mime_content_type($path->info->getPathname()));
        header('Content-Length: ' . $path->info->getSize());
        header('Cache-control: max-age=3600');

        $this->disableOutputBuffering();
        readfile($path->info->getPathname());
    }

    /// Render a dir listing.
    private function renderDir(RequestedPath $path)
    {
        $files = $this->getFilesIterator($path);
        $typeMajority = $this->getTypeMajority($files);

        $this->renderHtml('layout.php', [
            'layout' => (object) [
                'css'   => $this->getCss(),
                'js'    => $this->getJs(),
                'title' => $this->getTitle($path),
            ],

            'typeMajority' => $typeMajority,
            'view'         => $this->getMainView($path, $typeMajority),
            'favorites'    => array_keys($this->favorites),
            'files'        => $files,
            'path'         => $path,
            'sidebar'      => new Sidebar($path, $this->favorites),
        ]);
    }

    /**
     * @param string|null $typeMajority
     * @return string full path the the 'main' view. The main view is the part
     * where the files are listed and displayed.
     */
    private function getMainView(RequestedPath $path, $typeMajority)
    {
        $view = 'list';

        if ($path->isNone()) {
            $view = 'none';
        }

        return $this->getTemplatesDir() . "/main/$view.php";
    }

    /**
     * @return string|null MIME type (eg. audio, image)
     */
    private function getTypeMajority(\Iterator $files)
    {
        $mimes = [];
        foreach ($files as $file) {
            $mime = get_mime($file->getPathname());
            $mimes[$mime] = array_get($mimes, $mime, 0) + 1;
        }

        arsort($mimes);
        $mimeMajority = key($mimes) ?: null;
        if ($mimeMajority !== null && strpos($mimeMajority, '/')) { // sic strpos
            return explode('/', $mimeMajority)[0];
        } else {
            return null;
        }
    }

    /**
     * Return an iterator of the files to display in the main view.
     *
     * @return Iterator<SplFileInfo>
     */
    private function getFilesIterator(RequestedPath $path)
    {
        if ($path->isNone()) {
            return new \ArrayIterator();
        }

        return new \GlobIterator($path->info->getPathname() . '/*');
    }

    /// @return string for the <title>
    private function getTitle(RequestedPath $path)
    {
        if ($path->isNone()) {
            $title = _('index');
        } else {
            $title = $path->favoriteName;
            if (!$path->isFavoriteRoot()) {
                $title .= ' - ' . $path->relative;
            }
        }

        return "Woland - $title";
    }

    /**
     * Output the contents of the given asset with the proper MIME.
     *
     * Since every request will go through the app we also need to serve
     * static files.
     *
     * @param string $path
     */
    private function renderAsset($path)
    {
        $parts = explode('/', $path);
        $asset = $this->getPublicDir() . '/' . implode('/', array_slice($parts, 2));

        if (count($parts) < 4 || !file_exists($asset)) {
            http_response_code(404);
            throw new \RuntimeException("Asset not found `$asset`.");
        }

        $type = $parts[2];
        if (!in_array($type, ['css', 'js'], true)) {
            throw new \RuntimeException("Invalid asset type  `$type`.");
        }

        $mime = [
            'css' => 'text/css',
            'js' => 'application/javascript',
        ][$type];

        header("Content-Type: $mime");
        header('Cache-control: max-age=86400'); // one day

        readfile($asset);
    }

    /// @return string
    private function getTemplatesDir()
    {
        return __DIR__ . '/templates';
    }

    /// @return string
    private function getPublicDir()
    {
        return realpath(__DIR__ . '/../public');
    }

    /// @return string[] CSS files to include.
    private function getCss()
    {
        return [
            '/_/css/woland.css',
            '/_/css/bootstrap.min.css',
        ];
    }

    /// @return string[] JS files to include.
    private function getJs()
    {
        return [
            '/_/js/jquery.min.js',
            '/_/js/bootstrap.min.js',
        ];
    }

    /**
     * @param string $template file name in template dir.
     * @param mixed[] array to extract before including the template.
     */
    private function renderHtml($template, array $data = [])
    {
        render_template($this->getTemplatesDir() . "/$template", $data);
    }

    private function disableOutputBuffering()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
