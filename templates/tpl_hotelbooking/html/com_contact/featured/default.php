<?php

/**
 * About / featured contacts: intro plus team cards.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\Contact\Site\View\Featured\HtmlView $this */

Factory::getLanguage()->load('com_hotelbooking', JPATH_SITE . '/components/com_hotelbooking');
?>
<div class="com-contact-featured blog-featured hb-about">
<?php if ($this->params->get('show_page_heading') != 0) : ?>
    <h1>
        <?php echo $this->escape($this->params->get('page_heading')); ?>
    </h1>
<?php endif; ?>

    <p class="hb-about-intro"><?php echo Text::_('COM_HOTELBOOKING_ABOUT_INTRO'); ?></p>

<?php echo $this->loadTemplate('items'); ?>

<?php if ($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2 && $this->pagination->pagesTotal > 1)) : ?>
    <div class="com-contact-featured__pagination w-100">
        <?php if ($this->params->def('show_pagination_results', 1)) : ?>
            <p class="counter float-end pt-3 pe-2">
                <?php echo $this->pagination->getPagesCounter(); ?>
            </p>
        <?php endif; ?>

        <?php echo $this->pagination->getPagesLinks(); ?>
    </div>
<?php endif; ?>
</div>
