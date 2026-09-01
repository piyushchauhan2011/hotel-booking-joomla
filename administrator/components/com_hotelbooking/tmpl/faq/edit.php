<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Faq\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="faq-form" class="form-validate">

    <div class="main-card">
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
    </div>

    <?php
    echo $this->form->renderField('id');
    echo $this->form->renderField('ordering');
    ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
