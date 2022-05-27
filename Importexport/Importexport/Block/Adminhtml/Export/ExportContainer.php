<?php

namespace Institute\Importexport\Block\Adminhtml\Export;

use Institute\Importexport\Model\AttributeImportExport;
use Magento\Backend\Block\Widget\Context;

class ExportContainer extends \Magento\Backend\Block\Widget\Form\Container
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
        $this->_objectId = 'attexport';
        $this->_blockGroup = 'Institute_Importexport';
        $this->_controller = 'adminhtml_export';
        parent::__construct($context);

        $this->buttonList->remove('save');
    }

    public function getExportUrl()
    {
        return $this->getUrl('importexport/export/export');
    }

    public function listAttributeSet()
    {
        $attributeSetList = $this->AttributeImportExport->getAttributeSetList();
        return $attributeSetList;
    }
}
