<?php

namespace Woland;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Response\HtmlResponse;

class Controller
{
    /// @var \Slim\App;
    protected $app;

    /// @var \Woland\Cache
    protected $cache;

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
        $this->cache = new Cache();
    }

    /// @return ResponseInterface
    public function serveAsset(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $params
    ) {
        $path = sprintf(
            '%s/%s/%s',
            $this->getPublicDir(),
            $params['type'],
            $params['asset']
        );

        if (!in_array($params['type'], ['css', 'js'], true)) {
            throw new \RuntimeException("Invalid asset type  `{$params['type']}`.");
        }

        if (!file_exists($path)) {
            http_response_code(404);
            throw new \RuntimeException("Asset not found `$path`.");
        }

        $mime = [
            'css' => 'text/css',
            'js' => 'application/javascript',
        ][$params['type']];

        return $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'max-age=86400')
            ->withBody(new Stream($path))
        ;
    }

    /// @return ResponseInterface
    public function servePath(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $params
    ) {
        try {
            $path = RequestedPath::fromRequest(
                $request,
                $this->app->settings['favorites']
            );
        } catch (\RuntimeException $e) {
            http_response_code(404);
            throw $e;
        }

        if (!$path->isNone() && !$path->info->isDir()) {
            if (array_get($request->getQueryParams(), 'thumbnail') !== null) {
                return $this->renderThumbnail($response, $path);
            } else {
                return $this->renderSingleFile($response, $path);
            }
        } else {
            $view = array_get($request->getQueryParams(), 'view');
            if ($view === 'playlist') {
                return $this->renderPlaylist($response, $path, $request->getUri());
            } else {
                return $this->renderDir($response, $path);
            }
        }
    }

    /// @return ResponseInterface
    private function renderThumbnail(ResponseInterface $response, RequestedPath $path)
    {
        $thumb = $this->cache->get($path->info->getPathname());
        if ($thumb === null) {
            $thumb = $this->createThumbnail($path->info->getPathname());
            $this->cache->set($path->info->getPathname(), $thumb);
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($thumb);

        return $response
            ->withHeader('Content-Type', 'image/jpeg')
            ->withHeader('Content-Size', strlen($thumb))
            ->withBody($body)
        ;
    }

    /**
     * @param string $pathname
     * @return string binary
     */
    private function createThumbnail($pathname)
    {
        $image = new \Imagick($pathname);
        $image->thumbnailImage(200, 200, true);
        image_autorotate($image);
        $image->stripImage();
        return $image->getImageBlob();
    }

    /// @return ResponseInterface
    private function renderPlaylist(ResponseInterface $response, RequestedPath $path, UriInterface $uri)
    {
        $files = $this->getFilesIterator($path);
        $baseUrl = sprintf(
            '%s://%s:%d',
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPort()
        );

        $lines = ['#EXTM3U'];
        $twigExt = $this->app->view->getEnvironment()->getExtension('woland');
        foreach ($files as $file) {
            if (strpos(get_mime($file->getPathname()), 'audio/') === 0) {
                $name = substr($file->getBasename(), 0, -strlen($file->getExtension()) - 1);
                $lines[] = "#EXTINF:0,$name";
                $lines[] = "$baseUrl/" . $twigExt->fileToUri($file, $path);
            }
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write(implode("\n", $lines));

        return $response
            ->withHeader('Content-Type', 'application/x-mpegurl; charset=UTF-8')
            ->withHeader('Cache-Control', 'max-age=3600')
            ->withBody($body)
        ;
    }

    /**
     * Output a single file.
     *
     * @return ResponseInterface
     *
     * No verification is made here, if the path exists, it will be sent.
     * The caller is responsible for checking that the user is allowed to
     * display the file.
     */
    private function renderSingleFile(ResponseInterface $response, RequestedPath $path)
    {
        return $response
            ->withHeader('Content-Type', get_mime($path->info->getPathname()))
            ->withHeader('Cache-control', 'max-age=3600')
            ->withBody(new Stream($path->info->getPathname()))
        ;
    }

    /// @return ResponseInterface
    private function renderDir(ResponseInterface $response, RequestedPath $path)
    {
        $files = $this->getFilesIterator($path);
        $typeMajority = $this->getTypeMajority($files);
        $view = $this->getMainView($path, $typeMajority);
        $totalSize = iterator_reduce($files, function ($carry, $item) {
            return $carry + $item->getSize();
        }, 0);

        return $this->renderHtml($response, $view, [
            'layout' => [
                'css'   => $this->getCss(),
                'js'    => $this->getJs(),
                'title' => $this->getTitle($path),
            ],

            'typeMajority' => $typeMajority,
            'favorites'    => array_keys($this->app->settings['favorites']),
            'files'        => $files,
            'totalSize'    => $totalSize,
            'path'         => $path,
            'sidebar'      => new Sidebar($path, $this->app->settings['favorites']),
        ]);
    }

    /**
     * @param string|null $typeMajority
     * @return string
     */
    private function getMainView(RequestedPath $path, $typeMajority)
    {
        switch (true) {
        case $path->isNone():
            return 'main/none.html';
        case $typeMajority === 'image':
            return 'main/gallery.html';
        default:
            return 'main/list.html';
        }
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
        $mimeMajority = null;
        $first = current($mimes);
        if (count($mimes) > 0 && $first > (array_sum($mimes) - $first)) {
            $mimeMajority = key($mimes);
        }

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
     * @return ResponseInterface
     */
    private function renderHtml(ResponseInterface $response, $template, array $data = [])
    {
        return $this->app->view->render($response, $template, $data);
    }
}
