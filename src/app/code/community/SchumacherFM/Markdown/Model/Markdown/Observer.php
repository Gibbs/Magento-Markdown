<?php
/**
 * @category    SchumacherFM_Markdown
 * @package     Model
 * @author      Cyrill at Schumacher dot fm / @SchumacherFM
 * @copyright   Copyright (c)
 */
class SchumacherFM_Markdown_Model_Markdown_Observer extends SchumacherFM_Markdown_Model_Markdown_Abstract
{

    public function renderEmailTemplate(Varien_Event_Observer $observer)
    {
        if ($this->_isDisabled) { // @todo enable/disable only for emails ...
            return null;
        }

        $object = $observer->getEvent()->getObject();
        if (!($object instanceof Mage_Core_Model_Email_Template)) {
            return null;
        }

        Zend_Debug::dump($object->getData());
        exit;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function renderPage(Varien_Event_Observer $observer)
    {
        if ($this->_isDisabled) {
            return null;
        }

        /** @var Mage_Cms_Model_Page $page */
        $page = $observer->getEvent()->getPage();

        if ($page instanceof Mage_Cms_Model_Page) {
            $this->setOptions(array(
                'force'          => FALSE,
                'protectMagento' => TRUE,
            ));
            $content = $this->_renderMarkdown($page->getContent());
            $page->setContent($content);
        }

        return $this;
    }

    /**
     * renders every block as markdown except those having the html tags of method _isMarkdown in it
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function renderBlock(Varien_Event_Observer $observer)
    {
        if ($this->_isDisabled) {
            return null;
        }

        /** @var Mage_Cms_Model_Page $page */
        $block = $observer->getEvent()->getBlock();

        if ($this->_isAllowedBlock($block)) {
            /** @var Varien_Object $transport */
            $transport = $observer->getEvent()->getTransport();

            /**
             * you can set on any block the property ->setData('is_markdown',true)
             * then the block will get rendered as markdown even if it contains html
             */
            $isMarkdown = (boolean)$block->getIsMarkdown();
            $this->setOptions(array(
                'force'          => $isMarkdown,
                'protectMagento' => FALSE,
            ));
            $html = $transport->getHtml();
            $transport->setHtml($this->_renderMarkdown($html));

        }
        return $this;
    }

}