<?php

/**
 * Cassiopeia override of com_content article/default.php
 * Adds: card wrapper, auto table of contents, share buttons.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

// TOC/share strings live in com_hotelbooking's language file, which isn't
// auto-loaded on a com_content page — load it explicitly. getLanguagePath()
// appends /language/<lang> to the base path, so it must point at the
// component's own directory, not JPATH_SITE.
Factory::getLanguage()->load('com_hotelbooking', JPATH_SITE . '/components/com_hotelbooking');

// Create shortcuts to some parameters.
$params  = $this->item->params;
$canEdit = $params->get('access-edit');
$user    = $this->getCurrentUser();
$info    = $params->get('info_block_position', 0);
$htag    = $this->params->get('show_page_heading') ? 'h2' : 'h1';

// Check if associations are implemented. If they are, define the parameter.
$assocParam        = (Associations::isEnabled() && $params->get('show_associations'));
$currentDate       = Factory::getDate()->format('Y-m-d H:i:s');
$isNotPublishedYet = $this->item->publish_up > $currentDate;
$isExpired         = !is_null($this->item->publish_down) && $this->item->publish_down < $currentDate;

/*
 * Build a table of contents from <h2>/<h3> tags in the article body,
 * slugging + injecting ids on any heading that doesn't already have one.
 */
$hbToc      = [];
$hbBodyHtml = $this->item->text;

if (!empty($hbBodyHtml) && (str_contains($hbBodyHtml, '<h2') || str_contains($hbBodyHtml, '<h3'))) {
    $hbUsedSlugs = [];
    $hbSlugify   = static function (string $text) use (&$hbUsedSlugs): string {
        $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', strip_tags($text))), '-');
        $slug = $slug !== '' ? $slug : 'section';
        $base = $slug;
        $i    = 2;

        while (isset($hbUsedSlugs[$slug])) {
            $slug = $base . '-' . $i;
            $i++;
        }

        $hbUsedSlugs[$slug] = true;

        return $slug;
    };

    $hbBodyHtml = preg_replace_callback(
        '/<h([23])([^>]*)>(.*?)<\/h\1>/is',
        function (array $m) use ($hbSlugify, &$hbToc) {
            [$full, $level, $attrs, $inner] = $m;

            if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                $id = $idMatch[1];
            } else {
                $id     = $hbSlugify($inner);
                $attrs .= ' id="' . $id . '"';
            }

            $hbToc[] = ['level' => (int) $level, 'id' => $id, 'text' => trim(strip_tags($inner))];

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $hbBodyHtml
    );
}

// Share data
$hbShareUrl   = htmlspecialchars(Uri::getInstance()->toString(), ENT_QUOTES);
$hbShareTitle = htmlspecialchars($this->item->title, ENT_QUOTES);
?>
<div class="com-content-article item-page hb-article-card<?php echo $this->pageclass_sfx; ?>">
    <meta itemprop="inLanguage" content="<?php echo ($this->item->language === '*') ? Factory::getApplication()->get('language') : $this->item->language; ?>">
    <?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
    <?php endif;
    if (!empty($this->item->pagination) && !$this->item->paginationposition && $this->item->paginationrelative) {
        echo $this->item->pagination;
    }
    ?>

    <?php $useDefList = $params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
    || $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author') || $assocParam; ?>

    <?php if ($params->get('show_title')) : ?>
    <div class="page-header">
        <<?php echo $htag; ?>>
            <?php echo $this->escape($this->item->title); ?>
        </<?php echo $htag; ?>>
        <?php if ($this->item->state == ContentComponent::CONDITION_UNPUBLISHED) : ?>
            <span class="badge bg-warning text-light"><?php echo Text::_('JUNPUBLISHED'); ?></span>
        <?php endif; ?>
        <?php if ($isNotPublishedYet) : ?>
            <span class="badge bg-warning text-light"><?php echo Text::_('JNOTPUBLISHEDYET'); ?></span>
        <?php endif; ?>
        <?php if ($isExpired) : ?>
            <span class="badge bg-warning text-light"><?php echo Text::_('JEXPIRED'); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($canEdit) : ?>
        <?php echo LayoutHelper::render('joomla.content.icons', ['params' => $params, 'item' => $this->item]); ?>
    <?php endif; ?>

    <?php // Content is generated by content plugin event "onContentAfterTitle" ?>
    <?php echo $this->item->event->afterDisplayTitle; ?>

    <?php if ($useDefList && ($info == 0 || $info == 2)) : ?>
        <?php echo LayoutHelper::render('joomla.content.info_block', ['item' => $this->item, 'params' => $params, 'position' => 'above']); ?>
    <?php endif; ?>

    <div class="hb-share" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_ARIA'); ?>">
        <span class="hb-share-label"><?php echo Text::_('COM_HOTELBOOKING_SHARE_LABEL'); ?></span>
        <a class="hb-share-btn hb-share-btn--x" target="_blank" rel="noopener noreferrer" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_X'); ?>"
            href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($hbShareUrl); ?>&text=<?php echo rawurlencode($hbShareTitle); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.9 2H22l-7.6 8.7L23 22h-6.9l-5.4-6.7L4.6 22H1.5l8.1-9.3L1 2h7l4.9 6.1L18.9 2Zm-1.2 18h1.9L7.4 4H5.4l12.3 16Z"/></svg>
        </a>
        <a class="hb-share-btn hb-share-btn--facebook" target="_blank" rel="noopener noreferrer" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_FACEBOOK'); ?>"
            href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($hbShareUrl); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 21v-7.5h2.5l.4-3h-2.9V8.4c0-.87.24-1.46 1.5-1.46H16.5V4.3c-.26-.04-1.16-.11-2.2-.11-2.18 0-3.68 1.33-3.68 3.77V10.5H8.1v3h2.52V21h2.88Z"/></svg>
        </a>
        <a class="hb-share-btn hb-share-btn--linkedin" target="_blank" rel="noopener noreferrer" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_LINKEDIN'); ?>"
            href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode($hbShareUrl); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.94 8.5H3.56V20h3.38V8.5ZM5.25 3.3a1.96 1.96 0 1 0 0 3.92 1.96 1.96 0 0 0 0-3.92ZM20.44 20h-3.37v-6.06c0-1.44-.03-3.3-2.02-3.3-2.02 0-2.33 1.58-2.33 3.2V20H9.35V8.5h3.24v1.57h.05c.45-.85 1.56-1.75 3.2-1.75 3.42 0 4.6 2.25 4.6 5.18V20Z"/></svg>
        </a>
        <a class="hb-share-btn hb-share-btn--whatsapp" target="_blank" rel="noopener noreferrer" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_WHATSAPP'); ?>"
            href="https://wa.me/?text=<?php echo rawurlencode($hbShareTitle . ' ' . $hbShareUrl); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.06-1.33A10 10 0 1 0 12 2Zm0 1.8a8.2 8.2 0 0 1 6.83 12.72l-.24.36.66 2.4-2.46-.65-.35.22A8.2 8.2 0 1 1 12 3.8Zm-3 4.3c-.2 0-.5.07-.77.37-.26.3-1 1-1 2.4 0 1.42 1.02 2.8 1.17 3 .15.2 2 3.13 4.9 4.28 2.42.96 2.9.77 3.43.72.52-.05 1.7-.7 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.27-.2-.57-.34-.3-.15-1.7-.84-1.97-.94-.26-.1-.46-.15-.65.15-.2.3-.75.94-.92 1.13-.17.2-.34.22-.63.08-.3-.15-1.24-.46-2.36-1.46-.87-.78-1.46-1.74-1.63-2.03-.17-.3-.02-.46.13-.6.13-.14.3-.35.44-.53.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.65-1.63-.9-2.23-.23-.58-.47-.5-.65-.5Z"/></svg>
        </a>
        <a class="hb-share-btn hb-share-btn--email" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_EMAIL'); ?>"
            href="mailto:?subject=<?php echo rawurlencode($hbShareTitle); ?>&body=<?php echo rawurlencode($hbShareUrl); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm1.4 2 7.1 5.6L18.6 7H4.4Zm15.6 1.2-6.6 5.2a1.5 1.5 0 0 1-1.8 0L4 8.2V17h16V8.2Z"/></svg>
        </a>
        <button type="button" class="hb-share-btn hb-share-btn--copy" data-hb-copy-link="<?php echo $hbShareUrl; ?>" data-copied-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_COPIED'); ?>" aria-label="<?php echo Text::_('COM_HOTELBOOKING_SHARE_COPY'); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16 1H4a2 2 0 0 0-2 2v14h2V3h12V1Zm3 4H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 16H8V7h11v14Z"/></svg>
        </button>
    </div>

    <?php
    $hbTripNames = ['trip-neighborhood', 'trip-best-season', 'trip-official-site'];
    $hbFacts     = [];

    foreach ($this->item->jcfields ?? [] as $hbField) {
        if (!\in_array($hbField->name, $hbTripNames, true)) {
            continue;
        }

        $hbRaw = \is_array($hbField->rawvalue) ? implode(', ', $hbField->rawvalue) : trim((string) $hbField->rawvalue);

        if ($hbRaw === '') {
            continue;
        }

        $hbFacts[] = $hbField;
    }
    ?>
    <?php if ($hbFacts) : ?>
    <aside class="hb-article-facts" aria-label="<?php echo Text::_('COM_HOTELBOOKING_ARTICLE_FACTS_TITLE'); ?>">
        <p class="hb-article-facts-title"><?php echo Text::_('COM_HOTELBOOKING_ARTICLE_FACTS_TITLE'); ?></p>
        <dl>
            <?php foreach ($hbFacts as $hbField) : ?>
                <div class="hb-article-facts-row">
                    <dt><?php echo htmlspecialchars($hbField->label ?: $hbField->title, ENT_QUOTES, 'UTF-8'); ?></dt>
                    <dd><?php echo $hbField->value; ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </aside>
    <?php endif; ?>

    <?php if (!empty($hbToc)) : ?>
    <nav class="hb-toc" aria-label="<?php echo Text::_('COM_HOTELBOOKING_TOC_ARIA'); ?>">
        <p class="hb-toc-title"><?php echo Text::_('COM_HOTELBOOKING_TOC_TITLE'); ?></p>
        <ol>
            <?php foreach ($hbToc as $hbHeading) : ?>
                <li class="hb-toc-level-<?php echo (int) $hbHeading['level']; ?>">
                    <a href="#<?php echo htmlspecialchars($hbHeading['id']); ?>"><?php echo htmlspecialchars($hbHeading['text']); ?></a>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <?php if ($info == 0 && $params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
        <?php $this->item->tagLayout = new FileLayout('joomla.content.tags'); ?>

        <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
    <?php endif; ?>

    <?php // Content is generated by content plugin event "onContentBeforeDisplay" ?>
    <?php echo $this->item->event->beforeDisplayContent; ?>

    <?php if ((int) $params->get('urls_position', 0) === 0) : ?>
        <?php echo $this->loadTemplate('links'); ?>
    <?php endif; ?>
    <?php if ($params->get('access-view')) : ?>
        <?php echo LayoutHelper::render('joomla.content.full_image', $this->item); ?>
        <?php
        if (!empty($this->item->pagination) && !$this->item->paginationposition && !$this->item->paginationrelative) :
            echo $this->item->pagination;
        endif;
        ?>
    <div class="com-content-article__body">
        <?php echo $hbBodyHtml; ?>
    </div>

        <?php if ($info == 1 || $info == 2) : ?>
            <?php if ($useDefList) : ?>
                <?php echo LayoutHelper::render('joomla.content.info_block', ['item' => $this->item, 'params' => $params, 'position' => 'below']); ?>
            <?php endif; ?>
            <?php if ($params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
                <?php $this->item->tagLayout = new FileLayout('joomla.content.tags'); ?>
                <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        if (!empty($this->item->pagination) && $this->item->paginationposition && !$this->item->paginationrelative) :
            echo $this->item->pagination;
            ?>
        <?php endif; ?>
        <?php if ((int) $params->get('urls_position', 0) === 1) : ?>
            <?php echo $this->loadTemplate('links'); ?>
        <?php endif; ?>
        <?php // Optional teaser intro text for guests ?>
    <?php elseif ($params->get('show_noauth') && $user->guest) : ?>
        <?php echo LayoutHelper::render('joomla.content.intro_image', $this->item); ?>
        <?php echo HTMLHelper::_('content.prepare', $this->item->introtext); ?>
        <?php // Optional link to let them register to see the whole article. ?>
        <?php if ($params->get('show_readmore') && $this->item->fulltext != null) : ?>
            <?php $menu = Factory::getApplication()->getMenu(); ?>
            <?php $active = $menu->getActive(); ?>
            <?php $itemId = $active->id; ?>
            <?php $link = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false)); ?>
            <?php $link->setVar('return', base64_encode(RouteHelper::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language))); ?>
            <?php echo LayoutHelper::render('joomla.content.readmore', ['item' => $this->item, 'params' => $params, 'link' => $link]); ?>
        <?php endif; ?>
    <?php endif; ?>
    <?php
    if (!empty($this->item->pagination) && $this->item->paginationposition && $this->item->paginationrelative) :
        echo $this->item->pagination;
        ?>
    <?php endif; ?>
    <?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
    <?php echo $this->item->event->afterDisplayContent; ?>
</div>
