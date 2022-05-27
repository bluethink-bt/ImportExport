<?php

namespace Institute\Importexport\Controller\Adminhtml\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Institute\Importexport\Model\AttributeImportExport;

class Upload extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    protected $uploaderFactory;

    protected $varDirectory;

    protected $csvProcessor;

    protected $storeID;

    protected $AttributeImportExport;

    protected $ratings;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param AttributeImportExport $AttributeImportExport
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Store\Model\StoreManager $storeManager,
        AttributeImportExport $AttributeImportExport
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR); // Get default 'var' directory
        $this->csvProcessor = $csvProcessor;

        $this->storeID = $storeManager->getStore()->getId();
        $this->AttributeImportExport = $AttributeImportExport;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('importexport/import/index');

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'attribute_import_file']);
            $uploader->checkAllowedExtension('csv');
            $uploader->skipDbProcessing(true);
            $result = $uploader->save($this->getWorkingDir());

            $this->validateIfHasExtension($result);
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $resultRedirect;
        }

        $this->processUpload($result);

        $this->messageManager->addSuccess(__('Attribute Set Imported Successfully'));

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
        return $this->varDirectory->getAbsolutePath('importexport/');
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
        $defaultAttributeSetId = $this->getRequest()->getPost('attributeset_exitstid');


        $sourceFile = $this->getWorkingDir() . $result['file'];

        $rows = $this->csvProcessor->getData($sourceFile);
        $header = array_shift($rows);

        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }
            $row = $data;

            try {
                $attribute_code = str_replace(' ', '_', strtolower($row['attribute_code']));

                $attributes = [
                    $attribute_code,
                    [
                        'type' => $row['backend_type'],
                        'backend' => '',
                        'frontend' => '',
                        'label' => $row['frontend_label'],
                        'input' => $row['frontend_input'],
                        'class' => '',
                        'source' => '',
                        'global' => $row['is_global'],
                        'visible' => $row['is_visible'],
                        'required' => $row['is_required'],
                        'user_defined' => true,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => $row['is_filterable'],
                        'comparable' => false,
                        'visible_on_front' => $row['is_visible_on_front'],
                        'used_in_product_listing' => $row['used_in_product_listing'],
                        'attribute_group_name' => $row['attribute_group_name'],
                        'attribute_set_name' => $row['attribute_set'],
                        'options' => $row['attribute_options'],
                        'unique' => false
                    ]
                ];
                $this->AttributeImportExport->createProductAttribute($attributes, $defaultAttributeSetId);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        //die();
    }
}
