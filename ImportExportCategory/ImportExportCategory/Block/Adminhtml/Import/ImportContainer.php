<?php

namespace Institute\ImportExportCategory\Block\Adminhtml\Import;

use Magento\Backend\Block\Widget\Context;

class ImportContainer extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @param Context $context
     */
    protected function __construct(
        Context $context
    ) {
        $this->_objectId = 'catimport';
        $this->_blockGroup = 'Institute_ImportExportCategory';
        $this->_controller = 'adminhtml_import';
        parent::__construct($context);

        $this->buttonList->remove('save');
    }

    /**
     * @return string
     */
    public function getImportUrl()
    {
        return $this->getUrl('importexportcategory/import/upload');
    }

    /**
     * @return mixed
     */
    public function getDownloadSampleUrl()
    {
        return $this->getUrl('importexportcategory/*/index/', ['download_sample' => 'yes']);
    }
}
