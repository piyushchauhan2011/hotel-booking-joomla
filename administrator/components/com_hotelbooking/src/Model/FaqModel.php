<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

\defined('_JEXEC') or die;

class FaqModel extends AdminModel
{
    public $typeAlias = 'com_hotelbooking.faq';

    protected $associationsContext = 'com_hotelbooking.item.faq';

    public function getTable($name = 'Faq', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && Associations::isEnabled() && !empty($item->id)) {
            $item->associations = [];

            $associations = Associations::getAssociations(
                'com_hotelbooking',
                '#__hotelbooking_faqs',
                'com_hotelbooking.item.faq',
                $item->id,
                'id',
                '',
                ''
            );

            foreach ($associations as $tag => $association) {
                $item->associations[$tag] = $association->id;
            }
        }

        return $item;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        if (Associations::isEnabled()) {
            $languages = LanguageHelper::getContentLanguages(false, false, null, 'ordering', 'asc');

            if (\count($languages) > 1) {
                $addform = new \SimpleXMLElement('<form />');
                $fields  = $addform->addChild('fields');
                $fields->addAttribute('name', 'associations');
                $fieldset = $fields->addChild('fieldset');
                $fieldset->addAttribute('name', 'item_associations');

                foreach ($languages as $language) {
                    $field = $fieldset->addChild('field');
                    $field->addAttribute('name', $language->lang_code);
                    $field->addAttribute('type', 'modal_faq');
                    $field->addAttribute('language', $language->lang_code);
                    $field->addAttribute('label', $language->title);
                    $field->addAttribute('translate_label', 'false');
                    $field->addAttribute('select', 'true');
                    $field->addAttribute('new', 'true');
                    $field->addAttribute('edit', 'true');
                    $field->addAttribute('clear', 'true');
                }

                $form->load($addform, false);
            }
        }

        parent::preprocessForm($form, $data, $group);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_hotelbooking.faq', 'faq', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_hotelbooking.edit.faq.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }
}
