<?php

namespace Learn\Plugin\System\Hbconsent\Extension;

use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Event\Contact\SubmitContactEvent;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Event\Privacy\CollectCapabilitiesEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Writes guest Contact-form ticks and cookie-banner Accept clicks into
 * #__privacy_consents so they appear under Users → Privacy → Consents.
 */
final class Hbconsent extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    public const COOKIE_NAME = 'hb_cookie_consent';
    public const SUBJECT_CONTACT = 'Contact form';
    public const SUBJECT_COOKIES = 'Cookie banner';

    public static function getSubscribedEvents(): array
    {
        return [
            'onSubmitContact'                   => 'onSubmitContact',
            'onAjaxHbconsent'                   => 'onAjaxHbconsent',
            'onBeforeCompileHead'               => 'onBeforeCompileHead',
            'onAfterRender'                     => 'onAfterRender',
            'onPrivacyCollectAdminCapabilities' => 'onPrivacyCollectAdminCapabilities',
        ];
    }

    public function onSubmitContact(SubmitContactEvent $event): void
    {
        $data = $event->getData();

        if (!\array_key_exists('consentbox', $data)) {
            return;
        }

        $app  = $this->getApplication();
        $user = $app->getIdentity();

        $this->loadLanguage();
        $this->storeConsent(
            (int) ($user->id ?? 0),
            self::SUBJECT_CONTACT,
            Text::sprintf(
                'PLG_SYSTEM_HBCONSENT_BODY_CONTACT',
                htmlspecialchars((string) ($data['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ($data['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->clientIp(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->userAgent(), ENT_QUOTES, 'UTF-8')
            )
        );
    }

    public function onAjaxHbconsent(AjaxEvent $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site') || !Session::checkToken('request')) {
            $event->addResult(['success' => false]);

            return;
        }

        $this->loadLanguage();
        $user = $app->getIdentity();

        $this->storeConsent(
            (int) ($user->id ?? 0),
            self::SUBJECT_COOKIES,
            Text::sprintf(
                'PLG_SYSTEM_HBCONSENT_BODY_COOKIES',
                htmlspecialchars($this->clientIp(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->userAgent(), ENT_QUOTES, 'UTF-8')
            )
        );

        $this->setConsentCookie();
        $event->addResult(['success' => true]);
    }

    public function onBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site') || $this->hasBannerCookie() || $app->getInput()->get('tmpl') === 'component') {
            return;
        }

        if ($app->getDocument()->getType() !== 'html') {
            return;
        }

        $wa = $app->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_hotelbooking');
        $wa->useScript('plg_system_hbconsent.banner');
    }

    public function onAfterRender(AfterRenderEvent $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site') || $this->hasBannerCookie() || $app->getInput()->get('tmpl') === 'component') {
            return;
        }

        if ($app->getDocument()->getType() !== 'html') {
            return;
        }

        $this->loadLanguage();

        $ajaxUrl    = Route::_('index.php?option=com_ajax&plugin=hbconsent&group=system&format=json', false);
        $privacyUrl = $this->privacyPolicyUrl();
        $token      = Session::getFormToken();

        $banner = '<div id="hb-cookie-banner" class="hb-cookie-banner" role="dialog" aria-label="'
            . htmlspecialchars(Text::_('PLG_SYSTEM_HBCONSENT_BANNER_ARIA'), ENT_QUOTES, 'UTF-8')
            . '" data-ajax-url="' . htmlspecialchars($ajaxUrl, ENT_QUOTES, 'UTF-8')
            . '" data-token="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">'
            . '<p class="hb-cookie-banner__text">' . Text::_('PLG_SYSTEM_HBCONSENT_BANNER_TEXT') . '</p>'
            . '<div class="hb-cookie-banner__actions">';

        if ($privacyUrl !== '') {
            $banner .= '<a class="hb-cookie-banner__policy" href="' . htmlspecialchars($privacyUrl, ENT_QUOTES, 'UTF-8') . '">'
                . Text::_('PLG_SYSTEM_HBCONSENT_BANNER_POLICY') . '</a>';
        }

        $banner .= '<button type="button" class="hb-cookie-banner__dismiss" data-hb-cookie="dismiss">'
            . Text::_('PLG_SYSTEM_HBCONSENT_BANNER_DISMISS') . '</button>'
            . '<button type="button" class="hb-cookie-banner__accept" data-hb-cookie="accept">'
            . Text::_('PLG_SYSTEM_HBCONSENT_BANNER_ACCEPT') . '</button>'
            . '</div></div>';

        $body = $app->getBody();

        if (stripos($body, '</body>') !== false) {
            $app->setBody(str_ireplace('</body>', $banner . '</body>', $body));
        }
    }

    public function onPrivacyCollectAdminCapabilities(CollectCapabilitiesEvent $event): void
    {
        $this->loadLanguage();

        $event->addResult([
            Text::_('PLG_SYSTEM_HBCONSENT') => [
                Text::_('PLG_SYSTEM_HBCONSENT_CAPABILITY_CONTACT'),
                Text::_('PLG_SYSTEM_HBCONSENT_CAPABILITY_COOKIES'),
            ],
        ]);
    }

    private function storeConsent(int $userId, string $subject, string $body): void
    {
        $row = (object) [
            'user_id' => $userId,
            'subject' => $subject,
            'body'    => $body,
            'created' => Factory::getDate()->toSql(),
            'state'   => 1,
        ];

        try {
            $this->getDatabase()->insertObject('#__privacy_consents', $row);
        } catch (\Exception) {
            // Same as core Privacy Consent: a failed insert must not block the page.
        }
    }

    private function hasBannerCookie(): bool
    {
        return $this->getApplication()->getInput()->cookie->get(self::COOKIE_NAME) !== null;
    }

    private function setConsentCookie(): void
    {
        $app = $this->getApplication();
        $app->getInput()->cookie->set(
            self::COOKIE_NAME,
            '1',
            [
                'expires'  => time() + 365 * 86400,
                'path'     => '/',
                'secure'   => $app->isHttpsForced() || Uri::getInstance()->isSsl(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }

    private function clientIp(): string
    {
        return (string) $this->getApplication()->getInput()->server->get('REMOTE_ADDR', '', 'string');
    }

    private function userAgent(): string
    {
        return (string) $this->getApplication()->getInput()->server->get('HTTP_USER_AGENT', '', 'string');
    }

    private function privacyPolicyUrl(): string
    {
        $articleId = (int) $this->params->get('privacy_article', 0);

        if ($articleId < 1) {
            return '';
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'alias', 'catid', 'language']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $articleId, ParameterType::INTEGER);
        $article = $db->setQuery($query)->loadObject();

        if (!$article) {
            return '';
        }

        $slug = $article->alias ? ($article->id . ':' . $article->alias) : $article->id;

        return Route::_(RouteHelper::getArticleRoute($slug, $article->catid, $article->language));
    }
}
