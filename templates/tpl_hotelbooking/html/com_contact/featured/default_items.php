<?php

/**
 * Featured contacts as people cards (Role fields from FieldsHelper).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Contact\Site\Helper\RouteHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

/** @var \Joomla\Component\Contact\Site\View\Featured\HtmlView $this */

Factory::getLanguage()->load('com_hotelbooking', JPATH_SITE . '/components/com_hotelbooking');

$roleNames = ['job-title', 'department'];
?>
<div class="com-contact-featured__items">
    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('COM_CONTACT_NO_CONTACTS'); ?>
        </div>
    <?php else : ?>
        <h2 class="hb-about-team"><?php echo Text::_('COM_HOTELBOOKING_TEAM'); ?></h2>
        <ul class="hb-people-grid">
            <?php foreach ($this->items as $item) : ?>
                <?php
                $fields = FieldsHelper::getFields('com_contact.contact', $item, true);
                $role   = [];

                foreach ($fields as $field) {
                    if (!\in_array($field->name, $roleNames, true)) {
                        continue;
                    }

                    $raw = \is_array($field->rawvalue) ? implode(', ', $field->rawvalue) : trim((string) $field->rawvalue);

                    if ($raw === '') {
                        continue;
                    }

                    $role[] = $field;
                }

                $contactId = $item->slug ?? $item->id;
                ?>
                <li class="hb-person-card<?php echo (int) $item->published === 0 ? ' hb-person-card--unpublished' : ''; ?>">
                    <a class="hb-person-card__link" href="<?php echo Route::_(RouteHelper::getContactRoute($contactId, $item->catid, $item->language)); ?>">
                        <?php if (!empty($item->image)) : ?>
                            <span class="hb-person-card__photo">
                                <?php echo LayoutHelper::render(
                                    'joomla.html.image',
                                    [
                                        'src' => $item->image,
                                        'alt' => $item->name,
                                    ]
                                ); ?>
                            </span>
                        <?php endif; ?>
                        <span class="hb-person-card__name"><?php echo $this->escape($item->name); ?></span>
                        <?php if ((int) $item->published === 0) : ?>
                            <span class="badge bg-warning text-light"><?php echo Text::_('JUNPUBLISHED'); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if ($role) : ?>
                        <dl class="hb-person-card__role">
                            <?php foreach ($role as $field) : ?>
                                <div>
                                    <dt><?php echo htmlspecialchars($field->label ?: $field->title, ENT_QUOTES, 'UTF-8'); ?></dt>
                                    <dd><?php echo $field->value; ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
