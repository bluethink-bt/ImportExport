<?php

namespace Institute\Importexport\Controller\Adminhtml\Import;

use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\DirectoryList;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    protected $downloader;

    protected $directory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param FileFactory $fileFactory
     * @param DirectoryList $directory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        FileFactory $fileFactory,
        DirectoryList $directory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;

        $this->downloader = $fileFactory;
        $this->directory = $directory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (isset($this->getRequest()->getParams()['download_sample'])) {
            $heading = array(
                'attribute_code',
                'attribute_set',
                'attribute_group_name',
                'is_global',
                'is_user_defined',
                'is_filterable',
                'is_visible',
                'is_required',
                'is_visible_on_front',
                'used_in_product_listing',
                'is_searchable',
                'is_unique',
                'is_visible_in_advanced_search',
                'is_comparable',
                'is_filterable_in_search',
                'frontend_input',
                'backend_type',
                'attribute_options'
            );

            $filename = 'attribute_importer_sample.csv';
            $handle = fopen($filename, 'w');
            fputcsv($handle, $heading);

            $data = $this->getSampleData();
            foreach ($data as $d) {
                fputcsv($handle, $d);
            }

            $this->downloadCsv($filename);
        }

        return $resultPage = $this->resultPageFactory->create();
    }

    /**
     * @param $filename
     * @return \Magento\Framework\App\ResponseInterface|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function downloadCsv($filename)
    {
        if (file_exists($filename)) {
            $filePath = $this->directory->getPath("pub") . DIRECTORY_SEPARATOR . $filename;

            return $this->downloader->create($filename, @file_get_contents($filePath));
        }
    }

    /**
     * @return string[][]
     */
    public function getSampleData()
    {
        $data = array(
            array(
                '1',
                'option_test',
                'MyCustomAttribute',
                'Institute Product',
                '2',
                '1',
                '2',
                '1',
                '0',
                '1',
                '0',
                '0',
                '0',
                '0',
                '1',
                '0',
                'multiselect',
                'varchar',
                'Option1|Option2 |Option3'
            ),
            array(
                '2',
                'test_simple',
                'MyCustomAttribute',
                'Details Institute',
                '2',
                '1',
                '0',
                '1',
                '0',
                '1',
                '0',
                '0',
                '0',
                '0',
                '0',
                '0',
                'text',
                'varchar',
                'Option1|Option2 |Option3'
            ),
            array(
                '3',
                'new_width',
                'MyCustomAttribute',
                'Details Institute',
                '1',
                '1',
                '2',
                '1',
                '0',
                '1',
                '0',
                '0',
                '0',
                '0',
                '1',
                '0',
                'select',
                'varchar',
                'Option1|Option2 |Option3'
            ),
        );
        return $data;
    }
}
