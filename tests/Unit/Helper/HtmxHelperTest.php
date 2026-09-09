<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\Application\WebApplicationInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use PHPUnit\Framework\TestCase;

final class HtmxHelperTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    private int|false|null $statusBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;
        $this->statusBackup = http_response_code();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        if (\is_int($this->statusBackup) && $this->statusBackup > 0) {
            http_response_code($this->statusBackup);
        }

        parent::tearDown();
    }

    public function testIsRequestDetectsHtmxHeader(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = 'true';

        $this->assertTrue(HtmxHelper::isRequest($this->applicationWithInput(new Input([]))));
    }

    public function testIsRequestIsCaseInsensitive(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = 'TRUE';

        $this->assertTrue(HtmxHelper::isRequest($this->applicationWithInput(new Input([]))));
    }

    public function testIsRequestReturnsFalseWhenHeaderMissing(): void
    {
        unset($_SERVER['HTTP_HX_REQUEST']);

        $this->assertFalse(HtmxHelper::isRequest($this->applicationWithInput(new Input([]))));
    }

    public function testTriggerSetsJsonHeader(): void
    {
        $app = new FakeHtmxApplication();

        HtmxHelper::trigger($app, [
            'hbMessage' => [
                'type' => 'success',
                'text' => 'จองแล้ว',
            ],
        ]);

        $this->assertSame(
            [['HX-Trigger', '{"hbMessage":{"type":"success","text":"จองแล้ว"}}', true]],
            $app->headers
        );
    }

    public function testPushUrlSetsHistoryHeader(): void
    {
        $app = new FakeHtmxApplication();

        HtmxHelper::pushUrl($app, '/destinations?search=paris');

        $this->assertSame(
            [['HX-Push-Url', '/destinations?search=paris', true]],
            $app->headers
        );
    }

    public function testTriggerAcceptsWebApplicationInterface(): void
    {
        $app = $this->createMock(WebApplicationInterface::class);
        $app->expects($this->once())
            ->method('setHeader')
            ->with('HX-Trigger', '{"ok":1}', true);

        HtmxHelper::trigger($app, ['ok' => 1]);
    }

    public function testTriggerRejectsNonWebApplication(): void
    {
        $this->expectException(\LogicException::class);

        HtmxHelper::trigger(new \stdClass(), ['hbMessage' => ['text' => 'x']]);
    }

    public function testSendHtmlWritesFragmentAndCloses(): void
    {
        $app = new FakeHtmxApplication();

        $this->expectOutputString('<p>fragment</p>');

        HtmxHelper::sendHtml($app, '<p>fragment</p>');

        $this->assertSame([['Content-Type', 'text/html; charset=utf-8', true]], $app->headers);
        $this->assertTrue($app->headersSent);
        $this->assertTrue($app->closed);
    }

    public function testSendHtmlSetsHttpStatusForErrors(): void
    {
        $app = new FakeHtmxApplication();

        $this->expectOutputString('<div>nope</div>');

        HtmxHelper::sendHtml($app, '<div>nope</div>', 422);

        $this->assertSame(422, http_response_code());
        $this->assertTrue($app->closed);
    }

    public function testRenderLayoutIncludesFixtureFile(): void
    {
        $html = HtmxHelper::renderLayout(
            'htmx_test',
            ['x' => 'tokyo'],
            dirname(__DIR__, 2) . '/fixtures/layouts',
        );

        $this->assertSame('fragment:tokyo', $html);
    }

    public function testSendLayoutWritesRenderedFixture(): void
    {
        $app = new FakeHtmxApplication();

        $this->expectOutputString('fragment:tokyo');

        HtmxHelper::sendLayout(
            $app,
            'htmx_test',
            ['x' => 'tokyo'],
            200,
            dirname(__DIR__, 2) . '/fixtures/layouts',
        );

        $this->assertTrue($app->closed);
    }

    public function testRenderLayoutRejectsUnsafeNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        HtmxHelper::renderLayout('../secret', []);
    }

    public function testRenderLayoutRejectsEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        HtmxHelper::renderLayout('', []);
    }

    public function testRenderLayoutThrowsWhenFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);

        HtmxHelper::renderLayout('missing_layout_xyz', []);
    }

    private function applicationWithInput(Input $input): CMSApplicationInterface
    {
        $app = $this->createMock(CMSApplicationInterface::class);
        $app->method('getInput')->willReturn($input);

        return $app;
    }
}

final class FakeHtmxApplication
{
    /** @var list<array{0: string, 1: string, 2: bool}> */
    public array $headers = [];

    public bool $headersSent = false;

    public bool $closed = false;

    public function setHeader(string $name, string $value, bool $replace = false): self
    {
        $this->headers[] = [$name, $value, $replace];

        return $this;
    }

    public function sendHeaders(): self
    {
        $this->headersSent = true;

        return $this;
    }

    public function close(int $code = 0): void
    {
        unset($code);
        $this->closed = true;
    }
}
