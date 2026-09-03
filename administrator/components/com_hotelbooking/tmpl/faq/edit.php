<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Faq\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

$app     = Factory::getApplication();
$input   = $app->getInput();
$assoc   = Associations::isEnabled();
$isModal = $input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

$this->ignore_fieldsets = ['basic', 'item_associations'];
$this->useCoreUI        = true;
?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="faq-form" class="form-validate">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_HOTELBOOKING_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php
                echo $this->form->renderField('question');
                echo $this->form->renderField('answer');
                ?>
            </div>
            <div class="col-lg-3">
                <?php
                echo $this->form->renderField('scope');
                echo $this->form->renderField('published');
                echo $this->form->renderField('language');
                ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if (!$isModal && $assoc) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'associations', Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
            <fieldset id="fieldset-associations" class="options-form">
                <legend><?php echo Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS'); ?></legend>
                <div>
                    <?php echo LayoutHelper::render('joomla.edit.associations', $this); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php elseif ($isModal && $assoc) : ?>
            <div class="hidden"><?php echo LayoutHelper::render('joomla.edit.associations', $this); ?></div>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php
    echo $this->form->renderField('id');
    echo $this->form->renderField('ordering');
    ?>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="forcedLanguage" value="<?php echo $this->escape($input->get('forcedLanguage', '', 'cmd')); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
