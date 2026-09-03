<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.tpl_hotelbooking
 *
 * @copyright   (C) 2026 Piyush Chauhan.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Child of Cassiopeia. This file owns the HTML shell so we can add hotel-*
 * module positions. Core Cassiopeia files are not edited.
 *
 * jdoc cheat sheet (Joomla replaces these tags after this PHP runs):
 *   type="metas|styles|scripts"  document head
 *   type="modules" name="X"      modules assigned to position X in admin
 *   type="component"             the active menu item's view
 *   type="message"               system messages (errors, notices)
 * style="card" wraps each module in Cassiopeia's card chrome; style="none" does not.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app   = Factory::getApplication();
$input = $app->getInput();
$wa    = $this->getWebAssetManager();

$this->addHeadLink(HTMLHelper::_('image', 'joomla-favicon.svg', '', [], true, 1), 'icon', 'rel', ['type' => 'image/svg+xml']);
$this->addHeadLink(HTMLHelper::_('image', 'favicon.ico', '', [], true, 1), 'alternate icon', 'rel', ['type' => 'image/vnd.microsoft.icon']);
$this->addHeadLink(HTMLHelper::_('image', 'joomla-favicon-pinned.svg', '', [], true, 1), 'mask-icon', 'rel', ['color' => '#000']);

$option    = $input->getCmd('option', '');
$view      = $input->getCmd('view', '');
$layout    = $input->getCmd('layout', '');
$task      = $input->getCmd('task', '');
$itemid    = $input->getCmd('Itemid', '');
$sitename  = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$menu      = $app->getMenu()->getActive();
$pageclass = $menu !== null ? $menu->getParams()->get('pageclass_sfx', '') : '';

$isHotelLanding = $option === 'com_hotelbooking' && $view === 'hotel';

$paramsColorName = $this->params->get('colorName', 'colors_standard');
$assetColorName  = 'theme.' . $paramsColorName;

$paramsFontScheme = $this->params->get('useFontScheme', false);
$fontStyles       = '';

if ($paramsFontScheme) {
    if (stripos($paramsFontScheme, 'https://') === 0) {
        $this->getPreloadManager()->preconnect('https://fonts.googleapis.com/', ['crossorigin' => 'anonymous']);
        $this->getPreloadManager()->preconnect('https://fonts.gstatic.com/', ['crossorigin' => 'anonymous']);
        $this->getPreloadManager()->preload($paramsFontScheme, ['as' => 'style', 'crossorigin' => 'anonymous']);
        $wa->registerAndUseStyle('fontscheme.current', $paramsFontScheme, [], ['rel' => 'lazy-stylesheet', 'crossorigin' => 'anonymous']);

        if (preg_match_all('/family=([^?:]*):/i', $paramsFontScheme, $matches) > 0) {
            $fontStyles = '--cassiopeia-font-family-body: "' . str_replace('+', ' ', $matches[1][0]) . '", sans-serif;
			--cassiopeia-font-family-headings: "' . str_replace('+', ' ', $matches[1][1] ?? $matches[1][0]) . '", sans-serif;
			--cassiopeia-font-weight-normal: 400;
			--cassiopeia-font-weight-headings: 700;';
        }
    } elseif ($paramsFontScheme === 'system') {
        $fontStylesBody    = $this->params->get('systemFontBody', '');
        $fontStylesHeading = $this->params->get('systemFontHeading', '');

        if ($fontStylesBody) {
            $fontStyles = '--cassiopeia-font-family-body: ' . $fontStylesBody . ';
            --cassiopeia-font-weight-normal: 400;';
        }
        if ($fontStylesHeading) {
            $fontStyles .= '--cassiopeia-font-family-headings: ' . $fontStylesHeading . ';
    		--cassiopeia-font-weight-headings: 700;';
        }
    } else {
        $wa->registerAndUseStyle('fontscheme.current', $paramsFontScheme, ['version' => 'auto'], ['rel' => 'lazy-stylesheet']);
        $this->getPreloadManager()->preload($wa->getAsset('style', 'fontscheme.current')->getUri() . '?' . $this->getMediaVersion(), ['as' => 'style']);
    }
}

$wa->usePreset('template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'))
    ->useStyle('template.active.language')
    ->registerAndUseStyle($assetColorName, 'global/' . $paramsColorName . '.css')
    ->useStyle('template.user')
    ->useScript('template.user')
    ->useStyle('tpl_hotelbooking.custom')
    ->useScript('tpl_hotelbooking.custom')
    ->addInlineStyle(":root {
		--hue: 214;
		--template-bg-light: #f0f4fb;
		--template-text-dark: #495057;
		--template-text-light: #ffffff;
		--template-link-color: var(--link-color);
		--template-special-color: #001B4C;
		$fontStyles
	}");

$wa->registerStyle('template.active', '', [], [], ['template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr')]);

if ($this->params->get('logoFile')) {
    $logo = HTMLHelper::_('image', Uri::root(false) . htmlspecialchars($this->params->get('logoFile'), ENT_QUOTES), $sitename, ['loading' => 'eager', 'decoding' => 'async'], false, 0);
} elseif ($this->params->get('siteTitle')) {
    $logo = '<span title="' . $sitename . '">' . htmlspecialchars($this->params->get('siteTitle'), ENT_COMPAT, 'UTF-8') . '</span>';
} else {
    $logo = HTMLHelper::_('image', 'logo.svg', $sitename, ['class' => 'logo d-inline-block', 'loading' => 'eager', 'decoding' => 'async'], true, 0);
}

$hasClass = '';

if ($this->countModules('sidebar-left', true)) {
    $hasClass .= ' has-sidebar-left';
}

if ($this->countModules('sidebar-right', true)) {
    $hasClass .= ' has-sidebar-right';
}

if ($isHotelLanding && $this->countModules('hotel-sidebar', true)) {
    $hasClass .= ' has-hotel-sidebar';
}

$wrapper = $this->params->get('fluidContainer') ? 'wrapper-fluid' : 'wrapper-static';

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');

$stickyHeader = $this->params->get('stickyHeader') ? 'position-sticky sticky-top' : '';

$wa->getAsset('style', 'fontawesome')->setAttribute('rel', 'lazy-stylesheet');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
    <jdoc:include type="metas" />
    <jdoc:include type="styles" />
    <jdoc:include type="scripts" />
</head>

<body id="top" class="site <?php echo $option
    . ' ' . $wrapper
    . ' view-' . $view
    . ($layout ? ' layout-' . $layout : ' no-layout')
    . ($task ? ' task-' . $task : ' no-task')
    . ($itemid ? ' itemid-' . $itemid : '')
    . ($pageclass ? ' ' . $pageclass : '')
    . $hasClass
    . ($isHotelLanding ? ' hotel-landing-page' : '')
    . ($this->direction == 'rtl' ? ' rtl' : '');
?>">
    <header class="header container-header full-width<?php echo $stickyHeader ? ' ' . $stickyHeader : ''; ?>">

        <?php if ($this->countModules('topbar')) : ?>
            <div class="container-topbar">
                <jdoc:include type="modules" name="topbar" style="none" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('below-top')) : ?>
            <div class="grid-child container-below-top">
                <jdoc:include type="modules" name="below-top" style="none" />
            </div>
        <?php endif; ?>

        <?php if ($this->params->get('brand', 1)) : ?>
            <div class="grid-child">
                <div class="navbar-brand">
                    <a class="brand-logo" href="<?php echo $this->baseurl; ?>/">
                        <?php echo $logo; ?>
                    </a>
                    <?php if ($this->params->get('siteDescription')) : ?>
                        <div class="site-description"><?php echo htmlspecialchars($this->params->get('siteDescription')); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('menu', true) || $this->countModules('search', true)) : ?>
            <div class="grid-child container-nav">
                <?php if ($this->countModules('menu', true)) : ?>
                    <jdoc:include type="modules" name="menu" style="none" />
                <?php endif; ?>
                <?php if ($this->countModules('search', true)) : ?>
                    <div class="container-search">
                        <jdoc:include type="modules" name="search" style="none" />
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($isHotelLanding) : ?>
        <div class="site-grid hotel-landing">
            <?php if ($this->countModules('hotel-hero', true)) : ?>
                <div class="hotel-landing-hero full-width">
                    <jdoc:include type="modules" name="hotel-hero" style="none" />
                </div>
            <?php endif; ?>

            <div class="grid-child container-component">
                <jdoc:include type="modules" name="breadcrumbs" style="none" />
                <jdoc:include type="message" />
                <main>
                    <jdoc:include type="component" />
                </main>
            </div>

            <div class="grid-child hotel-landing-body">
                <div class="hotel-landing-main">
                    <?php if ($this->countModules('hotel-intro', true)) : ?>
                        <div class="hotel-landing-intro">
                            <jdoc:include type="modules" name="hotel-intro" style="card" />
                        </div>
                    <?php endif; ?>

                    <?php if ($this->countModules('hotel-rooms', true)) : ?>
                        <div id="hotel-rooms" class="hotel-landing-rooms">
                            <jdoc:include type="modules" name="hotel-rooms" style="none" />
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($this->countModules('hotel-sidebar', true)) : ?>
                    <aside class="hotel-landing-sidebar">
                        <jdoc:include type="modules" name="hotel-sidebar" style="card" />
                    </aside>
                <?php endif; ?>
            </div>

            <?php if ($this->countModules('hotel-bottom', true)) : ?>
                <div class="grid-child hotel-landing-bottom">
                    <jdoc:include type="modules" name="hotel-bottom" style="card" />
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="site-grid">
            <?php if ($this->countModules('banner', true)) : ?>
                <div class="container-banner full-width">
                    <jdoc:include type="modules" name="banner" style="none" />
                </div>
            <?php endif; ?>

            <?php if ($this->countModules('top-a', true)) : ?>
                <div class="grid-child container-top-a">
                    <jdoc:include type="modules" name="top-a" style="card" />
                </div>
            <?php endif; ?>

            <?php if ($this->countModules('top-b', true)) : ?>
                <div class="grid-child container-top-b">
                    <jdoc:include type="modules" name="top-b" style="card" />
                </div>
            <?php endif; ?>

            <?php if ($this->countModules('sidebar-left', true)) : ?>
                <div class="grid-child container-sidebar-left">
                    <jdoc:include type="modules" name="sidebar-left" style="card" />
                </div>
            <?php endif; ?>

            <div class="grid-child container-component">
                <jdoc:include type="modules" name="breadcrumbs" style="none" />
                <jdoc:include type="modules" name="main-top" style="card" />
                <jdoc:include type="message" />
                <main>
                    <jdoc:include type="component" />
                </main>
                <jdoc:include type="modules" name="main-bottom" style="card" />
            </div>

            <?php if ($this->countModules('sidebar-right', true)) : ?>
                <div class="grid-child container-sidebar-right">
                    <jdoc:include type="modules" name="sidebar-right" style="card" />
                </div>
            <?php endif; ?>

            <?php if ($this->countModules('bottom-a', true)) : ?>
                <div class="grid-child container-bottom-a">
                    <jdoc:include type="modules" name="bottom-a" style="card" />
                </div>
            <?php endif; ?>

            <?php if ($this->countModules('bottom-b', true)) : ?>
                <div class="grid-child container-bottom-b">
                    <jdoc:include type="modules" name="bottom-b" style="card" />
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($this->countModules('footer', true)) : ?>
        <footer class="container-footer footer full-width">
            <div class="grid-child">
                <jdoc:include type="modules" name="footer" style="none" />
            </div>
        </footer>
    <?php endif; ?>

    <?php if ($this->params->get('backTop') == 1) : ?>
        <a href="#top" id="back-top" class="back-to-top-link" aria-label="<?php echo Text::_('TPL_CASSIOPEIA_BACKTOTOP'); ?>">
            <span class="icon-arrow-up icon-fw" aria-hidden="true"></span>
        </a>
    <?php endif; ?>

    <jdoc:include type="modules" name="debug" style="none" />
</body>

</html>
