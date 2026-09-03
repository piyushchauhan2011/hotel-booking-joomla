<?php

namespace Learn\Module\Hotelhero\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Learn\Component\Hotelbooking\Site\Helper\DestinationContextHelper;

\defined('_JEXEC') or die;

class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $this->getApplication()->bootComponent('com_hotelbooking');

        $data   = parent::getLayoutData();
        $params = $data['params'];

        $heroTitle    = trim((string) $params->get('hero_title', ''));
        $heroSubtitle = trim((string) $params->get('hero_subtitle', ''));
        $heroImage    = $this->resolveImage((string) $params->get('hero_image', ''));
        $ctaLabel     = trim((string) $params->get('cta_label', ''));
        $ctaUrl       = trim((string) $params->get('cta_url', ''));

        if ((int) $params->get('use_url_hotel', 1) === 1) {
            $destination = DestinationContextHelper::getDestination($params, $this->getApplication());

            if ($destination) {
                $heroTitle    = $destination->name;
                $heroSubtitle = DestinationContextHelper::plainExcerpt($destination->description ?? '');
                $heroImage    = DestinationContextHelper::imageUrl($destination->image ?? '');
                $ctaLabel     = $ctaLabel !== '' ? $ctaLabel : Text::_('MOD_HOTELHERO_DEFAULT_CTA');
                $ctaUrl       = $ctaUrl !== '' ? $ctaUrl : '#hotel-rooms';
            }
        }

        $data['heroTitle']    = $heroTitle;
        $data['heroSubtitle'] = $heroSubtitle;
        $data['heroImage']    = $heroImage;
        $data['ctaLabel']     = $ctaLabel;
        $data['ctaUrl']       = $ctaUrl;

        return $data;
    }

    private function resolveImage(string $image): string
    {
        $image = trim($image);

        if ($image === '') {
            return '';
        }

        $cleaned = HTMLHelper::_('cleanImageURL', $image);
        $image   = $cleaned->url ?? $image;

        if ($image !== '' && !preg_match('#^(https?:)?//#i', $image)) {
            $image = Uri::root(true) . '/' . ltrim($image, '/');
        }

        return $image;
    }
}
