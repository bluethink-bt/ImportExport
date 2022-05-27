<?php

namespace Institute\ImportExportCategory\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
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
        Context $context,
        PageFactory $resultPageFactory,
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
            $heading = [
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

            $filename = 'category_importer_sample.csv';
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
        $data = [
            [
                'IS Category/ACS Center',
                '1',
                '1',
                '1',
                'products only',
                'description',
                '',
                'meta_title',
                'meta_keywords',
                'meta_description'
            ],
            [
                'IS Category/ACS Center/Chemistry In Practice',
                '1',
                '1',
                '1',
                'products only',
                'description',
                '',
                'meta_title',
                'meta_keywords',
                'meta_description'
            ],
            [
                'IS Category/ACS Center/Lab Safety',
                '1',
                '1',
                '1',
                'products only',
                'description',
                '',
                'meta_title',
                'meta_keywords',
                'meta_description'
            ],
        ];
        return $data;
    }
}
