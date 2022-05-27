<?php

namespace Institute\ImportExportCategory\Controller\Adminhtml\Export;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;

class Export extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface
     * @param \Magento\Cms\Api\BlockRepositoryInterface $blockRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        JsonFactory $resultJsonFactory,
        RequestInterface $request,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepositoryInterface,
        BlockRepositoryInterface $blockRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->_fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->resultPageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->blockRepository = $blockRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct($context);
    }

    /**
     * @return mixed
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
        $csvfilename = "categories" . date('Y-m-d') . ".csv";

        $post = $this->request->getPostValue();
        $rootCategoryId = $post['catgory_id'];
//        $rootCategory = $this->getCategoryName($rootCategoryId);

        $subCategories = $this->getChildrenCategories($rootCategoryId);
        $subCategories = $subCategories->getChildrenCategories();

        foreach ($subCategories as $subCategory) {
            $subCategory = $this->categoryRepositoryInterface->get($subCategory->getId());
//            $name = $subCategory->getName();
            $isActive = $subCategory->getIsActive();
            $includeInMenu = $subCategory->getIncludeInMenu();
            $isAnchor = $subCategory->getIsAnchor();
            $displayMode = $subCategory->getDisplayMode();
            $description = $subCategory->getDescription();
            $landingPage = $subCategory->getLandingPage();
            $cmsIdentifier = $this->getCmsBlock($landingPage);
            $metaTitle = $subCategory->getMetaTitle();
            $metaKeywords = $subCategory->getMetaKeywords();
            $metaDescription = $subCategory->getMetaDescription();
            $path = $this->getExactPath($subCategory->getPath());

            $lineData = [
                $path,
                $isActive,
                $includeInMenu,
                $isAnchor,
                $displayMode,
                $description,
                $cmsIdentifier,
                $metaTitle,
                $metaKeywords,
                $metaDescription
            ];
            $stream->writeCsv($lineData);

            $childSubCategories = $this->getSubCategories($subCategory);

            if (isset($childSubCategories)) {
                $this->getRecursiveChildCategory($childSubCategories, $stream);
            } else {
                continue;
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
        return [
            'categories',
            'is_active',
            'include_in_menu',
            'is_anchor',
            'display_mode',
            'description',
            'cms_block_identifier',
            'meta_title',
            'meta_keywords',
            'meta_description'
        ];
    }


    /**
     * @param $parentCategory
     * @return mixed
     */
    public function getSubCategories($parentCategory)
    {

        $SubcategoryParentid = $parentCategory->getId();
        $subCategories = $parentCategory->load($SubcategoryParentid)->getChildrenCategories();

        return $subCategories;
    }

    /**
     * @param $childSubCategories
     * @param $stream
     * @return void
     */
    public function getRecursiveChildCategory($childSubCategories, $stream)
    {
        foreach ($childSubCategories as $childSubCategory) {
            $childSubCategory = $this->categoryRepositoryInterface->get($childSubCategory->getId());
//            $name = $childSubCategory->getName();
            $isActive = $childSubCategory->getIsActive();
            $includeInMenu = $childSubCategory->getIncludeInMenu();
            $isAnchor = $childSubCategory->getIsAnchor();
            $displayMode = $childSubCategory->getDisplayMode();
            $description = $childSubCategory->getDescription();
            $landingPage = $childSubCategory->getLandingPage();
            $cmsIdentifier = $this->getCmsBlock($landingPage);
            $metaTitle = $childSubCategory->getMetaTitle();
            $metaKeywords = $childSubCategory->getMetaKeywords();
            $metaDescription = $childSubCategory->getMetaDescription();
            $path = $this->getExactPath($childSubCategory->getPath());

            $lineData = [
                $path,
                $isActive,
                $includeInMenu,
                $isAnchor,
                $displayMode,
                $description,
                $cmsIdentifier,
                $metaTitle,
                $metaKeywords,
                $metaDescription
            ];
            $stream->writeCsv($lineData);

            $SubChildSubCategories = $this->getSubCategories($childSubCategory);
            if (isset($SubChildSubCategories)) {
                $this->getRecursiveChildCategory($SubChildSubCategories, $stream);
            } else {
                continue;
            }
        }
    }

    /**
     * @param $blockId
     * @return array|void
     */
    public function getCmsBlock($blockId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('block_id', $blockId, 'eq')->create();
        $cmsBlock = $this->blockRepository->getList($searchCriteria)->getItems();

        $cmsblocks = [];

        foreach ($cmsBlock as $cmsBlock) {
            $cmsblocks = $cmsBlock->getIdentifier();
        }
        if (!empty($cmsblocks)) {
            return $cmsblocks;
        }
    }

    /**
     * @param $path
     * @return string
     */
    public function getExactPath($path)
    {
        $pathArr = explode('/', $path);
        $catName = [];
        foreach ($pathArr as $key => $catId) {
            if ($key == 0) {
                continue;
            } else {
                $catName[] = $this->getCategoryName($catId);
            }
        }
        $path = implode('/', $catName);
        return $path;
    }

    /**
     * @param $parentid
     * @return mixed
     */
    public function getChildrenCategories($parentid)
    {
        $childCategory = $this->categoryFactory->create()->load($parentid);
        return $childCategory;
    }

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getCategoryName($categoryId)
    {
        $category = $this->categoryFactory->create()->load($categoryId);
        $categoryName = $category->getName();
        return $categoryName;
    }
}
