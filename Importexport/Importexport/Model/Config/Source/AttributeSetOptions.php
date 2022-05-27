<?php

namespace Institute\Importexport\Model\Config\Source;

use Institute\Importexport\Model\AttributeImportExport;

class AttributeSetOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var AttributeImportExport
     */
    protected $AttributeImportExport;

    /**
     * @param AttributeImportExport $AttributeImportExport
     */
    public function __construct(
        AttributeImportExport $AttributeImportExport
    ) {
        $this->AttributeImportExport = $AttributeImportExport;
    }

    /**
     * @return array|string[][]|null
     */
    public function getAllOptions()
    {
        $attributesets = $this->listAttributeSet();
        $this->_options = [['label' => 'Please select', 'value' => '']];
        foreach ($attributesets->getItems() as $attributeset) {
            $this->_options[] = ['label' => $attributeset->getAttributeSetName(),
                'value' => $attributeset->getAttributeSetId()];
        }
        return $this->_options;
    }

    /**
     * @param $value
     * @return false|mixed|string
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * @return \Magento\Eav\Api\Data\AttributeSetSearchResultsInterface|null
     */
    public function listAttributeSet()
    {
        $attributeSetList = $this->AttributeImportExport->getAttributeSetList();
        return $attributeSetList;
    }
}
