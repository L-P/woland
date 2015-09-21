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

    protected $favorites;

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
        $this->favorites = $app->settings['favorites'];
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
            $path = RequestedPath::fromRequest($request, $this->favorites);
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

    private function renderPlaylist(ResponseInterface $response, RequestedPath $path, UriInterface $uri)
    {
        $files = $this->getFilesIterator($path);
        $baseUrl = sprintf(
            '%s://%s:%d',
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPort()
        );

        $body = new Stream('php://temp', 'wb+');
        ob_start();
        require $this->getTemplatesDir() . '/playlist.php';
        $body->write(ob_get_clean());

        return $response
            ->withHeader('Content-Type', 'application/x-mpegurl; charset=UTF-8')
            ->withHeader('Cache-Control', 'max-age=3600')
            ->withBody($body)
        ;
    }

    /**
     * Output a single file.
     *
     * No verification is made here, if the path exists, it will be sent.
     * The caller is responsible for checking that the user is allowed to
     * display the file.
     */
    private function renderSingleFile(ResponseInterface $response, RequestedPath $path)
    {
        return $response
            ->withHeader('Content-Type', mime_content_type($path->info->getPathname()))
            ->withHeader('Cache-control', 'max-age=3600')
            ->withBody(new Stream($path->info->getPathname()))
        ;
    }

    /// Render a dir listing.
    private function renderDir(ResponseInterface $response, RequestedPath $path)
    {
        $files = $this->getFilesIterator($path);
        $typeMajority = $this->getTypeMajority($files);

        return $this->renderHtml($response, 'layout.php', [
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
        } else if ($typeMajority === 'image') {
            $view = 'gallery';
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
    private function renderHtml(ResponseInterface $response, $template, array $data = [])
    {
        ob_start();
        render_template($this->getTemplatesDir() . "/$template", $data);
        $body = ob_get_clean();

        return new HtmlResponse($body);
    }
}
