<?php

namespace Learn\Component\Hotelbooking\Administrator\Field\Modal;

use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class FaqField extends ModalSelectField
{
    protected $type = 'Modal_Faq';

    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $result = parent::setup($element, $value, $group);

        if (!$result) {
            return $result;
        }

        $language = (string) $this->element['language'];

        $linkFaqs = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkFaqs->setQuery([
            'option'                => 'com_hotelbooking',
            'view'                  => 'faqs',
            'layout'                => 'modal',
            'tmpl'                  => 'component',
            Session::getFormToken() => 1,
        ]);
        $linkFaq = clone $linkFaqs;
        $linkFaq->setVar('view', 'faq');

        if ($language) {
            $linkFaqs->setVar('forcedLanguage', $language);
            $linkFaq->setVar('forcedLanguage', $language);

            $this->dataAttributes['data-language'] = $language;
        }

        $urlSelect = $linkFaqs;
        $urlEdit   = clone $linkFaq;
        $urlEdit->setVar('task', 'faq.edit');
        $urlNew = clone $linkFaq;
        $urlNew->setVar('task', 'faq.add');

        $this->urls['select'] = (string) $urlSelect;
        $this->urls['new']    = (string) $urlNew;
        $this->urls['edit']   = (string) $urlEdit;

        $this->modalTitles['select'] = Text::_('COM_HOTELBOOKING_SELECT_A_FAQ');
        $this->modalTitles['new']    = Text::_('COM_HOTELBOOKING_FAQ_NEW');
        $this->modalTitles['edit']   = Text::_('COM_HOTELBOOKING_FAQ_EDIT');

        $this->hint = $this->hint ?: Text::_('COM_HOTELBOOKING_SELECT_A_FAQ');

        return $result;
    }

    protected function getValueTitle()
    {
        $value = (int) $this->value ?: '';
        $title = '';

        if ($value) {
            $db    = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName('question'))
                ->from($db->quoteName('#__hotelbooking_faqs'))
                ->where($db->quoteName('id') . ' = :value')
                ->bind(':value', $value, ParameterType::INTEGER);
            $db->setQuery($query);

            $title = $db->loadResult();
        }

        return $title ?: $value;
    }

    protected function getLayoutData()
    {
        $data             = parent::getLayoutData();
        $data['language'] = (string) $this->element['language'];

        return $data;
    }

    protected function getRenderer($layoutId = 'default')
    {
        $layout = parent::getRenderer($layoutId);
        $layout->setComponent('com_hotelbooking');
        $layout->setClient(1);

        return $layout;
    }
}
