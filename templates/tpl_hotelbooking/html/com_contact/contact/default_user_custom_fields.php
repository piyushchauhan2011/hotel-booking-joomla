<?php

/**
 * Linked User fields on a public Contact (Maya’s Work / Preferences).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\Contact\Site\View\Contact\HtmlView $this */
$params          = $this->item->params;
$displayGroups   = $params->get('show_user_custom_fields');
$userFieldGroups = [];
?>

<?php if (!$displayGroups || !$this->contactUser) : ?>
    <?php return; ?>
<?php endif; ?>

<?php foreach ($this->contactUser->jcfields as $field) : ?>
    <?php if ($field->value && (in_array('-1', $displayGroups) || in_array($field->group_id, $displayGroups))) : ?>
        <?php $userFieldGroups[$field->group_title][] = $field; ?>
    <?php endif; ?>
<?php endforeach; ?>

<?php foreach ($userFieldGroups as $groupTitle => $fields) : ?>
    <?php $id = ApplicationHelper::stringURLSafe($groupTitle); ?>
    <aside class="hb-article-facts hb-contact-user-fields" id="user-custom-fields-<?php echo $id; ?>" aria-label="<?php echo $this->escape($groupTitle ?: Text::_('COM_CONTACT_USER_FIELDS')); ?>">
        <p class="hb-article-facts-title"><?php echo $groupTitle ?: Text::_('COM_CONTACT_USER_FIELDS'); ?></p>
        <dl>
        <?php foreach ($fields as $field) : ?>
            <?php if (!$field->value) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="hb-article-facts-row">
                <?php if ($field->params->get('showlabel')) : ?>
                    <dt><?php echo Text::_($field->label); ?></dt>
                <?php endif; ?>
                <dd><?php echo $field->value; ?></dd>
            </div>
        <?php endforeach; ?>
        </dl>
    </aside>
<?php endforeach; ?>
