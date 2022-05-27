<?php

namespace Institute\Importexport\Model;

use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Api\Data\AttributeGroupInterfaceFactory;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Config;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AttributeImportExport extends \Magento\Framework\Model\AbstractModel
{
    protected $eavSetupFactory;
    protected $product;
    protected $attributeSetInterfaceFactory;
    protected $attributeSetManagement;
    protected $attributeGroupFactory;
    protected $attributeGroupRepository;
    protected $eavConfig;
    protected $categorySetupFactory;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param EavSetupFactory $eavSetupFactory
     * @param Product $product
     * @param AttributeSetInterfaceFactory $attributeSetInterfaceFactory
     * @param AttributeSetManagementInterface $attributeSetManagement
     * @param AttributeGroupInterfaceFactory $attributeGroupFactory
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     * @param Config $eavConfig
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        EavSetupFactory $eavSetupFactory,
        Product $product,
        AttributeSetInterfaceFactory $attributeSetInterfaceFactory,
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeGroupInterfaceFactory $attributeGroupFactory,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        Config $eavConfig,
        CategorySetupFactory $categorySetupFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context, $registry);

        $this->eavSetupFactory = $eavSetupFactory;
        $this->product = $product;
        $this->attributeSetFactory = $attributeSetInterfaceFactory;
        $this->attributeSetManagement = $attributeSetManagement;
        $this->attributeGroupFactory = $attributeGroupFactory;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->eavConfig = $eavConfig;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $attributes
     * @param $defaultAttributeSetId
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Validate_Exception
     */
    public function createProductAttribute($attributes, $defaultAttributeSetId)
    {
        $attributeName = $attributes[0];
        $attributeSetName = trim($attributes[1]['attribute_set_name']);
        $attributeGroupName = trim($attributes[1]['attribute_group_name']);


        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();
        $categorySetup = $this->categorySetupFactory->create();
        $productEntity = \Magento\Catalog\Model\Product::ENTITY;
        $attrSetName = null;
        $attributeGroupId = null;
        /**
         * Initialise Attribute Set Id
         */
        if (!empty($attributeSetName)) {
            $attributeSet = $this->attributeSetFactory->create();

            $attributeSetCollection = $attributeSet->getCollection();
            $attributeSetData = $attributeSetCollection->addFieldToSelect('*')
                ->addFieldToFilter('attribute_set_name', $attributeSetName)
                ->getFirstItem();

            $attributeDataArr = $attributeSetData->getData();
            if (
                empty($attributeDataArr) || (isset($attributeDataArr['value'])
                    && trim($attributeDataArr['value']) == '')
            ) {
                // create attribute set here
                $attrSetName = $attributeSetName;
                $this->createAttributeSet($attrSetName, $defaultAttributeSetId);
                $attributeSetId = $eavSetup->getAttributeSetId($productEntity, $attributeSetName);
            } else {
                $attributeSetId = $eavSetup->getAttributeSetId($productEntity, $attributeSetName);
            }

            /**
             * Initialise Attribute Group Id
             */
            if (isset($attributeGroupName)) {
                $attributeGroup = $this->attributeGroupFactory->create();

                $attributeGroupCollection = $attributeGroup->getCollection();
                $attributeGroupData = $attributeGroupCollection->addFieldToSelect('*')
                    ->addFieldToFilter('attribute_group_name', $attributeGroupName)
                    ->addFieldToFilter('attribute_set_id', $attributeSetId)
                    ->getFirstItem();
                if (empty($attributeGroupData->getData())) {
                    try {
                        $this->createAttributeGroup($attributeGroupName, $attributeSetId, $attrSetName);
                    } catch (\Exception $e) {
                        // echo "<pre>";
                        echo $e->getTraceasString();
                        // echo "<br>";
                        // print_r($attributeGroupName);
                        // echo "<br>";
                        // print_r($attributeSetId);
                        // echo "<br>";
                        // die;
                    }


                    $attributeGroupId = $eavSetup->getAttributeGroupId(
                        $productEntity,
                        $attributeSetId,
                        $attributeGroupName
                    );
                } else {
                    $attributeGroupId = $eavSetup->getAttributeGroupId(
                        $productEntity,
                        $attributeSetId,
                        $attributeGroupName
                    );
                }
            }


            /**
             * Add attributes to the eav/attribute
             */
            $attribute_code = str_replace(' ', '_', strtolower($attributeName));
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attribute_code);
            if (!$attribute || !$attribute->getAttributeId()) {
                $eavSetup->addAttribute(
                    $productEntity,
                    $attribute_code,
                    [
                        'group' => $attributeGroupId ? '' : 'General', //Let empty,if we want to set an attributegroupid
                        'type' => $attributes[1]['type'],
                        'backend' => '',
                        'frontend' => '',
                        'label' => $attributes[1]['label'],
                        'input' => $attributes[1]['input'],
                        'class' => '',
                        'source' => '',
                        'global' => $attributes[1]['global'],
                        'visible' => $attributes[1]['visible'],
                        'required' => $attributes[1]['required'],
                        'user_defined' => true,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => $attributes[1]['filterable'],
                        'comparable' => false,
                        'visible_on_front' => $attributes[1]['visible_on_front'],
                        'used_in_product_listing' => $attributes[1]['used_in_product_listing'],
                        'attribute_group_name' => $attributes[1]['attribute_group_name'],
                        'attribute_set_name' => $attributes[1]['attribute_set_name'],
                        'unique' => false

                    ]
                );
                // echo "<p>" . $attribute_code . " => attribute is created successfully</p><br>";
            } else {
                $eavSetup->addAttribute(
                    $productEntity,
                    $attribute_code,
                    [
                        'group' => $attributeGroupId ? '' : 'General', //Let empty,if we want to set an attributegroupid
                        'type' => $attributes[1]['type'],
                        'backend' => '',
                        'frontend' => '',
                        'label' => $attributes[1]['label'],
                        'input' => $attributes[1]['input'],
                        'class' => '',
                        'source' => '',
                        'global' => $attributes[1]['global'],
                        'visible' => $attributes[1]['visible'],
                        'required' => $attributes[1]['required'],
                        'user_defined' => true,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => $attributes[1]['filterable'],
                        'comparable' => false,
                        'visible_on_front' => $attributes[1]['visible_on_front'],
                        'used_in_product_listing' => $attributes[1]['used_in_product_listing'],
                        'attribute_group_name' => $attributes[1]['attribute_group_name'],
                        'attribute_set_name' => $attributes[1]['attribute_set_name'],
                        'unique' => false

                    ]
                );
                // echo "<p>".$attribute_code ." => attribute is allready exist.</p><br>";
            }

            /**
             * Set attribute group Id if needed
             */

            if (!is_null($attributeGroupId)) {

                /**
                 * Set the attribute in the right attribute group in the right attribute set
                 */
                $eavSetup->addAttributeToGroup($productEntity, $attributeSetId, $attributeGroupId, $attribute_code);
            }
            /**
             * Add options if needed
             */
            if (isset($attributes[1]['options'])) {
                $values = [];
                $values = explode('|', $attributes[1]['options']);

                if (count($values)) {
                    $options = [
                        'attribute_id' => $eavSetup->getAttributeId($productEntity, $attribute_code),
                        'values' => $values
                    ];


                    $eavSetup->addAttributeOption($options);
                }
            }
        } else {
            echo "attribute set name is required.";
            die();
        }
    }

    /**
     * @param $attrSetName
     * @param $defaultAttributeSetId
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createAttributeSet($attrSetName, $defaultAttributeSetId)
    {
        if ($defaultAttributeSetId == "") {
            echo "Please select AttributeSet Dropdown Option";
            die();
        } else {
            $attributeSet = $this->attributeSetFactory->create();
            $attributeSet->setAttributeSetName($attrSetName);
            $this->attributeSetManagement->create($attributeSet, $defaultAttributeSetId);
        }
    }

    /**
     * @param $attributeGroupName
     * @param $attributeSetId
     * @param $attrSetName
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function createAttributeGroup($attributeGroupName, $attributeSetId, $attrSetName = null)
    {

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();
        $productEntity = \Magento\Catalog\Model\Product::ENTITY;
        $attributeGroup = $this->attributeGroupFactory->create();

        $attributeGroup->setAttributeSetId($attributeSetId);
        $attributeGroup->setAttributeGroupName($attributeGroupName);
        $this->attributeGroupRepository->save($attributeGroup);
    }


    public function getAttributeSetList()
    {
        $attributeSetList = null;
        try {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $attributeSet = $this->attributeSetRepository->getList($searchCriteria);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        if ($attributeSet->getTotalCount()) {
            $attributeSetList = $attributeSet;
        }

        return $attributeSetList;
    }
}
