<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Destination\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="destination-form" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_HOTELBOOKING_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php
                echo $this->form->renderField('description');
                echo $this->form->renderField('image');
                echo $this->form->renderField('gallery');
                ?>
            </div>
            <div class="col-lg-3">
                <?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'amenities', Text::_('COM_HOTELBOOKING_TAB_AMENITIES_OFFERS')); ?>
        <fieldset id="fieldset-amenities" class="options-form">
            <legend><?php echo Text::_('COM_HOTELBOOKING_TAB_AMENITIES_OFFERS'); ?></legend>
            <div>
                <?php
                echo $this->form->renderField('amenities');
                echo $this->form->renderField('offers');
                echo $this->form->renderField('faqs');
                ?>
            </div>
        </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'partner', Text::_('COM_HOTELBOOKING_FIELDSET_PARTNER_LABEL')); ?>
        <fieldset id="fieldset-partner" class="options-form">
            <legend><?php echo Text::_('COM_HOTELBOOKING_FIELDSET_PARTNER_LABEL'); ?></legend>
            <div>
                <?php echo $this->form->renderFieldset('partner'); ?>
            </div>
        </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php
    echo $this->form->renderField('id');
    echo $this->form->renderField('ordering');
    ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
