<?php

namespace Institute\ImportExportCategory\Block\Adminhtml\Export;

use Institute\ImportExportCategory\Model\CategoryImportExport;
use Magento\Backend\Block\Widget\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryFactory;

class ExportContainer extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var CategoryImportExport
     */
    protected $CategoryImportExport;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @param Context $context
     * @param CategoryImportExport $CategoryImportExport
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    protected function __construct(
        Context $context,
        CategoryImportExport $CategoryImportExport,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory
    ) {
        $this->CategoryImportExport = $CategoryImportExport;
        $this->_storeManager = $storeManager;
        $this->_categoryFactory = $categoryFactory;
        $this->_objectId = 'catexport';
        $this->_blockGroup = 'Institute_ImportExportCategory';
        $this->_controller = 'adminhtml_export';
        parent::__construct($context);

        $this->buttonList->remove('save');
    }

    /**
     * @return mixed
     */
    public function getExportUrl()
    {
        return $this->getUrl('importexportcategory/export/export');
    }

    /**
     * @return array
     */
    public function getRootCategory()
    {
        $ids = [\Magento\Catalog\Model\Category::TREE_ROOT_ID];
        $category = [];
        foreach ($this->_storeManager->getGroups() as $store) {
            $category[] = ['id' => $store->getRootCategoryId(),
                              'category_name' => $this->getCategoryName($store->getRootCategoryId()) ];
        }
        $category = array_unique($category, SORT_REGULAR);
        return $category;
    }

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getCategoryName($categoryId)
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        $categoryName = $category->getName();
        return $categoryName;
    }
}
