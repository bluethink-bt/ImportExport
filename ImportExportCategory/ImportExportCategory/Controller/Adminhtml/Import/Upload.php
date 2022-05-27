<?php

namespace Institute\ImportExportCategory\Controller\Adminhtml\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManager;
use Institute\ImportExportCategory\Model\CategoryImportExport;

class Upload extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @var int
     */
    protected $storeID;

    /**
     * @var CategoryImportExport
     */
    protected $categoryImportExport;

    /**
     * @var
     */
    protected $ratings;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param CategoryImportExport $categoryImportExport
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequestInterface $request,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        Csv $csvProcessor,
        StoreManager $storeManager,
        CategoryImportExport $categoryImportExport
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->uploaderFactory = $uploaderFactory;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR); // Get default 'var' directory
        $this->csvProcessor = $csvProcessor;

        $this->storeID = $storeManager->getStore()->getId();
        $this->categoryImportExport = $categoryImportExport;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('importexportcategory/import/index');

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'category_import_file']);
            $uploader->checkAllowedExtension('csv');
            $uploader->skipDbProcessing(true);
            $result = $uploader->save($this->getWorkingDir());

            $this->validateIfHasExtension($result);
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $resultRedirect;
        }

        $this->processUpload($result);

        $this->messageManager->addSuccess(__('Category Imported Successfully'));

        return $resultRedirect;

        //return  $resultPage = $this->resultPageFactory->create();
    }

    /**
     * @param $result
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function validateIfHasExtension($result)
    {
        $extension = pathinfo($result['file'], PATHINFO_EXTENSION);

        $uploadedFile = $result['path'] . $result['file'];
        if (!$extension) {
            $this->varDirectory->delete($uploadedFile);
            throw new \Exception(__('The file you uploaded has no extension.'));
        }
    }

    /**
     * @return string
     */
    public function getWorkingDir()
    {
        return $this->varDirectory->getAbsolutePath('importexportcategory/');
    }

    /**
     * @param $result
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Validate_Exception
     */
    public function processUpload($result)
    {
        $sourceFile = $this->getWorkingDir() . $result['file'];

        $rows = $this->csvProcessor->getData($sourceFile);
        $header = array_shift($rows);

        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }

            try {
                $catArr = [];
                $this->categoryImportExport->recursivelyCreateCategories($data, $catArr, 1);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        // die();
    }
}
