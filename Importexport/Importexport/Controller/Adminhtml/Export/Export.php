<?php

namespace Institute\Importexport\Controller\Adminhtml\Export;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Exception;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Catalog\Api\ProductAttributeGroupRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;

class Export extends Action
{
    /**
    * @var PageFactory
    */
    protected $resultPageFactory;
    protected $resultJsonFactory;
    protected $request;
    protected $groupCollection;
    protected $eavConfig;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

     /**
     * @var ProductAttributeGroupRepositoryInterface
     */
    private $productAttributeGroup;

    /**
     * @var Collection
     */
    private $attributeCollectionFactory;

    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchHelper;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollection
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param ProductAttributeGroupRepositoryInterface $productAttributeGroup
     * @param \Magento\Swatches\Helper\Data $swatchHelper
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param CollectionFactory $attributeCollectionFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollection,
        \Magento\Eav\Model\Config $eavConfig,
        ProductAttributeGroupRepositoryInterface $productAttributeGroup,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        CollectionFactory $attributeCollectionFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->_fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->resultPageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->request = $request;
        $this->groupCollection = $groupCollection;
        $this->eavConfig = $eavConfig;
        $this->productAttributeGroup = $productAttributeGroup;
        $this->swatchHelper = $swatchHelper;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        //export csv logic

        $name = date('m_d_Y_H_i_s');
        $filepath = 'export-attribute-set_' . $name . '.csv';
        $this->directory->create('export'); /* Open file */
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $stream->writeCsv($this->getColumnHeader());

        $content = [];
        $content['type'] = 'filename'; // must keep filename
        $content['value'] = $filepath;
        $content['rm'] = '1'; //remove csv from var folder
        $csvfilename = "attribute-set" . date('Y-m-d') . ".csv";

        $post = $this->request->getPostValue();

        $attributeSetId = $post['attribute_set_id'];
        $attributeSetData = $this->getAttributeSet($attributeSetId);
        $attribute_set_name = $attributeSetData->getAttributeSetName();

        $attributeGroupCollection = $this->groupCollection->create();
        $attributeGroupCollection->addFieldToFilter('attribute_group_name', $attribute_set_name);
        $attributeGroupCollection->addFieldToFilter('attribute_set_id', $attributeSetId);
        $attributeGroupId = '';
        foreach ($attributeGroupCollection as $attributeGroup) {
            $attributeGroupId = $attributeGroup->getId();
            break;
        }
        $attributeCollection = $this->attributeCollectionFactory->create();
        $attributeCollection->setAttributeSetFilter($attributeSetId);
        //$attributeCollection->addFieldToFilter('is_user_defined',1); // remove default
        //$attributeCollection->addFieldToFilter('attribute_group_id',317); // remove event
        if ($attributeGroupId != '') {
            $attributeCollection->setAttributeGroupFilter($attributeGroupId);
        }

        if (!empty($attributeSetData->getData())) {
            if (!empty($attributeCollection->getData())) {
                foreach ($attributeCollection as $att) {
                    $attribute = $this->eavConfig->getAttribute('catalog_product', $att->getAttributeCode());

                    //attribute options
                    $options = $attribute->getSource()->getAllOptions();
                    $option_array = array();
                    foreach ($options as $opt) {
                        if ($opt['label'] != '' && $opt['value'] != '') {
                            $option_array[] = $opt['label'];
                        }
                    }
                    $att_options = implode("|", $option_array);


                    //attribute apply-to values data
                    $att_apply_vals = $attribute->getApplyTo();
                    $apply_array = array();
                    if (!empty($att_apply_vals)) {
                        foreach ($att_apply_vals as $apply_val) {
                            $apply_array[] = $apply_val;
                        }
                    }
                    $apply_val_data = implode("|", $apply_array);

                    //swatch options
                    $is_swatch = $this->swatchHelper->isSwatchAttribute($attribute);
                    if ($is_swatch == 1) {
                        $swatch_type = $attribute->getSwatchInputType();
                        if ($swatch_type == 'text' || $swatch_type == 'visual') {
                            $swatch_option_array = array();
                            foreach ($options as $opt) {
                                if ($opt['label'] != '' && $opt['value'] != '') {
                                    $swatch_val = $this->getAtributeSwatchHashcode($opt['value']);
                                    if (!empty($swatch_val)) {
                                        $swatch_option_array[] = $swatch_val[$opt['value']]['value'];
                                    }
                                }
                            }
                            if (!empty($swatch_option_array)) {
                                $att_swatch_options = implode("|", $swatch_option_array);
                                if ($attribute->getSwatchInputType() == 'text') {
                                    $text_swatch_options = $att_swatch_options;
                                    $visual_swatch_options = '';
                                } elseif ($attribute->getSwatchInputType() == 'visual') {
                                    $text_swatch_options = '';
                                    $visual_swatch_options = $att_swatch_options;
                                }
                            } else {
                                $text_swatch_options = '';
                                $visual_swatch_options = '';
                            }
                        } else {
                            $text_swatch_options = '';
                            $visual_swatch_options = '';
                        }
                    } else {
                        $text_swatch_options = '';
                        $visual_swatch_options = '';
                    }

                    //attribute group data
                    $att_groupdata = $this->getAttributeGroupById($att->getAttributeGroupId());

                    $lineData = array($att->getEntityTypeId(),$att->getAttributeCode(),
                        $attributeSetData->getAttributeSetName(),$att_groupdata->getAttributeGroupName(),
                        $att_groupdata->getAttributeGroupCode(),$attribute->getIsGlobal(),
                        $att->getIsUserDefined(),$attribute->getIsFilterable(),
                        $attribute->getIsVisible(),$att->getIsRequired(),
                        $attribute->getIsVisibleOnFront(),$attribute->getIsSearchable(),
                        $att->getIsUnique(),$att->getFrontendClass(),$attribute->getIsVisibleInAdvancedSearch(),
                        $attribute->getIsComparable(),$attribute->getIsFilterableInSearch(),
                        $attribute->getIsUsedForPriceRules(),$attribute->getIsUsedForPromoRules(),
                        $att->getSortOrder(),$attribute->getPosition(),$att->getFrontendInput(),
                        $att->getBackendType(),$att->getBackendModel(),$att->getSourceModel(),
                        $att->getFrontendLabel(),$att->getDefaultValue(),$apply_val_data,
                        $att->getIsWysiwygEnabled(),$att->getIsRequiredInAdminStore(),
                        $attribute->getIsUsedInGrid(),$attribute->getIsVisibleInGrid(),
                        $attribute->getIsFilterableInGrid(),$attribute->getSearchWeight(),
                        $attribute->getIsHtmlAllowedOnFront(),
                        $attribute->getUsedInProductListing(),
                        $attribute->getUsedForSortBy(),$attribute->getSwatchInputType(),
                        $att_options,$visual_swatch_options,$text_swatch_options);
                    $stream->writeCsv($lineData);
                }
            }
        }

        //exit;
        return $this->_fileFactory->create($csvfilename, $content, DirectoryList::VAR_DIR);
        //export csv end
    }

    /**
     * @return string[]
     */
    public function getColumnHeader()
    {
        return array('entity_type_id','attribute_code','attribute_set',
            'attribute_group_name','attribute_group_code','is_global',
            'is_user_defined','is_filterable','is_visible','is_required',
            'is_visible_on_front','is_searchable','is_unique',
            'frontend_class','is_visible_in_advanced_search',
            'is_comparable','is_filterable_in_search',
            'is_used_for_price_rules','is_used_for_promo_rules',
            'sort_order','position','frontend_input','backend_type',
            'backend_model','source_model','frontend_label','default_value',
            'apply_to','is_wysiwyg_enabled','is_required_in_admin_store',
            'is_used_in_grid','is_visible_in_grid','is_filterable_in_grid',
            'search_weight','is_html_allowed_on_front',
            'used_in_product_listing','used_for_sort_by',
            'swatch_input_type','attribute_options',
            'attribute_options_swatchvisual','attribute_options_swatchtext');
        ;
    }

    /**
     * @param $attributeSetId
     * @return \Magento\Eav\Api\Data\AttributeSetInterface
     * @throws Exception
     */
    public function getAttributeSet($attributeSetId)
    {
        try {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        return $attributeSet;
    }

    /**
     * @param $groupId
     * @return AttributeGroupInterface
     * @throws Exception
     */
    public function getAttributeGroupById($groupId)
    {
        try {
            $productAttributeGroup = $this->productAttributeGroup->get($groupId);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        return $productAttributeGroup;
    }

    /**
     * @param $optionid
     * @return array
     * @throws Exception
     */
    public function getAtributeSwatchHashcode($optionid)
    {
        try {
            $hashcodeData = $this->swatchHelper->getSwatchesByOptionsId([$optionid]);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
        return $hashcodeData;
    }
}
