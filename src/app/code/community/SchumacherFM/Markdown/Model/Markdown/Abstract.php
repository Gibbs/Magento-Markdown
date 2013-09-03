<?php
/**
 * @category    SchumacherFM_Markdown
 * @package     Model
 * @author      Cyrill at Schumacher dot fm / @SchumacherFM
 * @copyright   Copyright (c)
 */
abstract class SchumacherFM_Markdown_Model_Markdown_Abstract
{
    protected $_tag = '';
    protected $_isDisabled = FALSE;

    /**
     * @var string
     */
    protected $_currentRenderedText = '';

    protected $_preserveContainer = array();

    /**
     * @var SchumacherFM_Markdown_Model_Michelf_Markdown
     */
    protected $_renderer = null;

    protected $_options = array(
        'force'          => FALSE,
        'protectMagento' => TRUE,
    );

    public function __construct()
    {
        /**
         * due to some weired parsings ... every text field which should contain MD must start with this tag
         */
        $this->_tag        = Mage::helper('markdown')->getDetectionTag();
        $this->_isDisabled = Mage::helper('markdown')->isDisabled();

        $isExtra         = Mage::helper('markdown')->isMarkdownExtra() ? '_extra' : '';
        $this->_renderer = Mage::getModel('markdown/michelf_markdown' . $isExtra);
    }

    /**
     * @return SchumacherFM_Markdown_Model_Michelf_Markdown
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options = null)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * @param       string $text
     *
     * @return string
     */
    public function renderMarkdown($text)
    {
        return $this->_isDisabled
            ? $text
            : $this->_renderMarkdown($text);
    }

    /**
     * @param  string $text
     *
     * @return string
     */
    protected function _renderMarkdown($text)
    {
        $force                      = isset($this->_options['force']) && $this->_options['force'] === TRUE;
        $protectMagento             = isset($this->_options['protectMagento']) && $this->_options['protectMagento'] === TRUE;
        $this->_currentRenderedText = $text; // @todo optimize
        if (!$this->_isMarkdown() && $force === FALSE) {
            return $this->_currentRenderedText;
        }

        $this->_removeMarkdownTag();
        if ($protectMagento === TRUE) {
            $this->_preserveMagentoVariablesEncode();
        }

        $this->_currentRenderedText = $this->getRenderer()->defaultTransform($this->_currentRenderedText);

        if ($protectMagento === TRUE) {
            $this->_preserveMagentoVariablesDecode();
        }
        return $this->_currentRenderedText;
    }

    /**
     * removes the markdown detection tag
     *
     * @return $this
     */
    protected function _removeMarkdownTag()
    {
        $this->_currentRenderedText = str_replace($this->_tag, '', $this->_currentRenderedText);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _preserveMagentoVariablesEncode()
    {
        $matches = array();
        preg_match_all('~(\{\{[a-z]+.+\}\})~ismU', $this->_currentRenderedText, $matches, PREG_SET_ORDER);
        if (count($matches) > 0) {
            foreach ($matches as $match) {
                $key                            = md5($match[0]);
                $this->_preserveContainer[$key] = $match[0];
            }
            $this->_currentRenderedText = str_replace(
                $this->_preserveContainer, array_keys($this->_preserveContainer), $this->_currentRenderedText
            );
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _preserveMagentoVariablesDecode()
    {
        if (count($this->_preserveContainer) === 0) {
            return $this;
        }
        $this->_currentRenderedText = str_replace(
            array_keys($this->_preserveContainer), $this->_preserveContainer, $this->_currentRenderedText
        );
        $this->_preserveContainer   = array();
        return $this;
    }

    /**
     * checks if text contains no html ... if so considered as markdown ... not a nice way...
     *
     * @return bool
     */
    private function _isMarkdown()
    {
        $flag = !empty($this->_currentRenderedText);
        return $flag === TRUE && strpos($this->_currentRenderedText, $this->_tag) !== FALSE;
    }

    /**
     * @param string reference $text
     *
     * @return bool
     */
    public function isMarkdown(&$text)
    {
        return strpos($text, $this->_tag) !== FALSE;
    }

    /**
     * @param Mage_Core_Block_Abstract $block
     *
     * @return bool
     */
    protected function _isAllowedBlock($block)
    {
        return $block instanceof Mage_Core_Block_Abstract;
    }

}