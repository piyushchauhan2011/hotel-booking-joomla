<?php

namespace Learn\Module\Hotelhero\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];

        $image = trim((string) $params->get('hero_image', ''));

        if ($image !== '') {
            $cleaned = HTMLHelper::_('cleanImageURL', $image);
            $image   = $cleaned->url ?? $image;

            if ($image !== '' && !preg_match('#^(https?:)?//#i', $image)) {
                $image = Uri::root(true) . '/' . ltrim($image, '/');
            }
        }

        $data['heroTitle']    = trim((string) $params->get('hero_title', ''));
        $data['heroSubtitle'] = trim((string) $params->get('hero_subtitle', ''));
        $data['heroImage']    = $image;
        $data['ctaLabel']     = trim((string) $params->get('cta_label', ''));
        $data['ctaUrl']       = trim((string) $params->get('cta_url', ''));

        return $data;
    }
}
