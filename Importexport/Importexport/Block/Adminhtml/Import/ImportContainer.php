<?php

namespace Institute\Importexport\Block\Adminhtml\Import;

use Institute\Importexport\Model\AttributeImportExport;
use Magento\Backend\Block\Widget\Context;

class ImportContainer extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var AttributeImportExport
     */
    protected $AttributeImportExport;

    protected function __construct(
        Context $context,
        AttributeImportExport $AttributeImportExport
    ) {
        $this->AttributeImportExport = $AttributeImportExport;
        $this->_objectId = 'attimport';
        $this->_blockGroup = 'Institute_Importexport';
        $this->_controller = 'adminhtml_import';
        parent::__construct($context);

        $this->buttonList->remove('save');
    }

    public function getImportUrl()
    {
        return $this->getUrl('importexport/import/upload');
    }

    public function getDownloadSampleUrl()
    {
        return $this->getUrl('importexport/*/index/', array('download_sample' => 'yes'));
    }

    public function listAttributeSet()
    {
        $attributeSetList = $this->AttributeImportExport->getAttributeSetList();
        return $attributeSetList;
    }
}
