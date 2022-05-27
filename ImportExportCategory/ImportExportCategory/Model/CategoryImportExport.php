<?php

namespace Institute\ImportExportCategory\Model;

class CategoryImportExport extends \Magento\Framework\Model\AbstractModel
{
    protected $storeManager;

    protected $categoryFactory;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Cms\Api\BlockRepositoryInterface $blockRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->blockRepository = $blockRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $row
     * @param $catArr
     * @param $key
     * @return void
     */
    public function recursivelyCreateCategories($row, $catArr, $key)
    {
        $catArr = explode("/", $row['categories']);

        if (count($catArr) > 1) {
            $parentKey = $key - '1';
            $parentCatName = $catArr[$parentKey];
            $parentUrlKey = $this->getUrlKey($parentCatName);
            $parentCatId = $this->getCategoryId($parentUrlKey);
        }

        if (empty($catArr) || $row['categories'] == '') {
            return;
        }
        //check if name = $catArr[$key] with $parentCatId exists
        $name = $catArr[$key];
        if ($name == '') {
            return;
        }
        $category = $this->categoryFactory->create();
        $cate = $category->getCollection()
            ->addAttributeToFilter('name', $name)
            ->addAttributeToFilter('parent_id', $parentCatId)
            ->getFirstItem();

        $cate_exists = $cate->getId();

        //$exists_id = checkCategoryExists(); //returns id if exists, false if not
        if (isset($cate_exists)) {
            $key += 1;

            echo 'Parent exists with id: ' . $cate_exists . "\n";
            if (!empty($catArr[$key])) {
                $this->recursivelyCreateCategories($row, $catArr, $key);
            } else {
                return;
            }
        } else {
            $path = $this->categoryFactory->create()->load($parentCatId)->getPath();

            // catagory name
            $category = $this->categoryFactory->create();
            $cat_name = ucfirst($name);

            //create
            //run function
            $category->setName($cat_name);
            $category->setIsActive($row['is_active']);
            $category->setUrlKey($this->getUrlKey($cat_name));
            $category->setData('description', $row['description']);
            $category->setData('meta_title', $row['meta_title']);
            $category->setData('meta_keywords', $row['meta_keywords']);
            $category->setData('meta_description', $row['meta_description']);
            $category->setData('landing_page', $this->getCmsBlock($row['cms_block_identifier']));
            $category->setDisplayMode($row['display_mode']);
            $category->setParentId($parentCatId);

            $category->setPath($path);
            $category->save();

            $key += 1;

            if (!empty($catArr[$key])) {
                $this->recursivelyCreateCategories($row, $catArr, $key);
            } else {
                return;
            }
        }
    }


    /**
     * @param $catName
     * @return string
     */
    public function getUrlKey($catName)
    {
        $site_url = strtolower($catName);
        $clean_url = trim(preg_replace('/[\W_]+/u', '-', $site_url));
        return $clean_url;
    }

    /**
     * @param $urlKey
     * @return string
     */
    public function getCategoryId($urlKey)
    {
        $category = $this->categoryFactory->create()->loadByAttribute('url_key', $urlKey);
        if (!is_bool($category)) {
            $categoryId = $category->getId();
        } else {
            $categoryId = "";
        }
        return $categoryId;
    }

    /**
     * @param $identifier
     * @return array
     */
    public function getCmsBlock($identifier)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('identifier', $identifier, 'eq')->create();
        $cmsBlock = $this->blockRepository->getList($searchCriteria)->getItems();

        $cmsblocks = [];

        foreach ($cmsBlock as $cmsBlock) {
            $cmsblocks = $cmsBlock->getId();
        }
        return $cmsblocks;
    }
}
