<?php

namespace Woland;

use \Psr\Http\Message\RequestInterface;

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
        /// TODO: remove. Temporary solution to display assets.
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
            header('Content-Type: ' . mime_content_type($path->info->getPathname()));
            header('Content-Length: ' . $path->info->getSize());
            header('Cache-control: max-age=3600');

            $this->disableOutputBuffering();
            readfile($path->info->getPathname());

            return;
        }

        $this->renderHtml('layout.php', [
            'layout' => (object) [
                'css'   => $this->getCss(),
                'js'    => $this->getJs(),
                'title' => $this->getTitle($path),
            ],

            'files'     => $this->getFilesIterator($path),
            'navbar'    => array_keys($this->favorites),
            'sidebar'   => new Sidebar($path),
            'path'      => $path,
        ]);
    }

    private function getFilesIterator(RequestedPath $path)
    {
        if ($path->isNone()) {
            return new \ArrayIterator();
        }

        return new \GlobIterator($path->info->getPathname() . '/*');
    }

    /// @return string
    private function getTitle(RequestedPath $path)
    {
        if ($path->isNone()) {
            $title = _('index');
        } else {
            $title = $path->favoriteName;
            if (strlen($path->path) > 0) {
                $title .= ' - ' . $path->path;
            }
        }

        return "Woland - $title";
    }

    /**
     * Output the contents of the given asset with the proper MIME.
     *
     * Since every request will go through the app we also need to serve
     * static files.
     * TODO: To be removed when file rendering with MIME handling is
     * implemented. Assets would just be another favorite and the only special
     * case would be not showing the link in the navbar.
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
            '/_/css/bootstrap-theme.min.css',
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
