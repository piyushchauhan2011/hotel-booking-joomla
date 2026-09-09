<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\Application\WebApplicationInterface;
use Joomla\CMS\Application\CMSApplicationInterface;

\defined('_JEXEC') or die;

/**
 * Detect HTMX requests and return HTML fragments without the site template chrome.
 */
class HtmxHelper
{
    public static function isRequest(CMSApplicationInterface $app): bool
    {
        return strtolower($app->getInput()->server->getString('HTTP_HX_REQUEST', '')) === 'true';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function trigger(object $app, array $payload): void
    {
        $web = self::webApp($app);
        \assert(method_exists($web, 'setHeader'));
        $web->setHeader(
            'HX-Trigger',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            true,
        );
    }

    public static function pushUrl(object $app, string $url): void
    {
        $web = self::webApp($app);
        \assert(method_exists($web, 'setHeader'));
        $web->setHeader('HX-Push-Url', $url, true);
    }

    public static function sendHtml(object $app, string $html, int $status = 200): void
    {
        $web = self::webApp($app);
        \assert(method_exists($web, 'setHeader') && method_exists($web, 'sendHeaders') && method_exists($web, 'close'));

        if ($status !== 200) {
            http_response_code($status);
        }

        $web->setHeader('Content-Type', 'text/html; charset=utf-8', true);
        $web->sendHeaders();
        echo $html;
        $web->close();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function sendLayout(
        object $app,
        string $layout,
        array $data,
        int $status = 200,
        string $basePath = '',
    ): void {
        self::sendHtml($app, self::renderLayout($layout, $data, $basePath), $status);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function renderLayout(string $layout, array $data, string $basePath = ''): string
    {
        if ($layout === '' || strpbrk($layout, './\\') !== false) {
            throw new \InvalidArgumentException('Invalid HTMX layout name.');
        }

        if ($basePath === '') {
            $basePath = JPATH_ROOT . '/components/com_hotelbooking/layouts';
        }

        $file = $basePath . '/' . $layout . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException('HTMX layout not found: ' . $layout);
        }

        ob_start();
        $displayData = $data;
        include $file;

        return (string) ob_get_clean();
    }

    private static function webApp(object $app): object
    {
        if ($app instanceof WebApplicationInterface) {
            return $app;
        }

        if (
            method_exists($app, 'setHeader')
            && method_exists($app, 'sendHeaders')
            && method_exists($app, 'close')
        ) {
            return $app;
        }

        throw new \LogicException('HTMX fragment responses require a web application.');
    }
}
