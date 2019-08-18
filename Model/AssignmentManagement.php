<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Console
 */
namespace Magebild\Console\Model;

use Magebild\Console\Api\AssignmentManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Store\Api\WebsiteRepositoryInterface;

class AssignmentManagement implements AssignmentManagementInterface
{
    const DEFAULT_WEBSITE_ID = 1;

    protected $categoryFactory;

    protected $productRepository;

    protected $productFactory;

    protected $websiteRepository;

    protected $appState;

    private $errorMessages;

    private $limit;

    private $isDryRun;

    private $useLog;

    private $logger;

    /**
     * AssignmentManagement constructor.
     * @param CategoryFactory $categoryFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param \Magento\Framework\App\State $appState
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param Monolog $logger
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        \Magento\Framework\App\State $appState,
        WebsiteRepositoryInterface $websiteRepository,
        Monolog $logger
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->appState = $appState;
        $this->websiteRepository = $websiteRepository;
        $this->errorMessages = [];
        $this->limit = 10;
        $this->logger = $logger;
    }

    /**
     * @param $categoryId
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getProducts($categoryId)
    {
        /** @var Category $category */
        $category = $this->categoryFactory->create()->load($categoryId);
        $products = $category->getProductCollection();
        return $products;
    }

    /**
     * @param array $ids
     * @param null $storeIds
     * @throws \Exception
     */
    public function process($ids = [], $storeIds = null)
    {
        $cleanStoreIds = $this->filterStoreIds($storeIds);
        if (is_array($ids) && !empty($ids)) {
            foreach ($ids as $categoryId) {
                if (is_numeric($categoryId)) {
                    $products = $this->getProducts($categoryId);
                    if ($products && $products->count()) {
                        $this->assignProductToWebsite($products, $cleanStoreIds);
                    }
                }
            }
        }
    }

    /**
     * @param $products
     * @param $storeIds
     * @throws \Exception
     */
    private function assignProductToWebsite($products, $storeIds)
    {
        $ctr  = 1;
        $messages = [];
        foreach ($products as $product) {
            $websiteIds = $product->getWebsiteIds();
            $newAssignments = array_diff($storeIds, $websiteIds);
            if (!empty($newAssignments)) {
                $existingWebsiteIds = [];
                //Check Website Ids if exists
                foreach ($newAssignments as $websiteId) {
                    try {
                        $_website = $this->websiteRepository->getById($websiteId);
                        if ($_website->getId()) {
                            $existingWebsiteIds[] = $websiteId;
                        }
                    } catch (NoSuchEntityException $noSuchEntityException) {
                        $message = $noSuchEntityException->getMessage();
                        array_push($this->errorMessages, $message);
                    }
                }
                $merged = array_merge($websiteIds, $newAssignments);
                $_product = $this->productFactory->create()
                    ->load($product->getId());

                if (!$this->isDryRun) {
                    $this->appState->emulateAreaCode('adminhtml', function () use ($_product, $merged) {
                        $_product->setWebsiteIds($merged);
                        $_product->save();
                    });
                }
                if ($this->useLog) {
                    $websites = implode(',', $existingWebsiteIds);
                    $messages [] = sprintf('SKU >> %d Website >> %d', $_product->getSku(), $websites);
                }
            }
            $ctr ++;
            if ($ctr > $this->limit) {
                break;
            }
        }
        if ($this->useLog) {
            $this->logger->info(implode(',', $messages));
        }
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @param $storeIds
     * @return array
     */
    private function filterStoreIds($storeIds)
    {
        $filteredIds = [];
        if (is_array($storeIds) && !empty($storeIds)) {
            foreach ($storeIds as $storeId) {
                if (is_numeric($storeId)) {
                    $filteredIds[] = $storeId;
                }
            }
        }
        return $filteredIds;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        // TODO: Implement setOptions() method.
        if (is_array($options)) {
            if (array_key_exists('limit', $options)) {
                if (!is_null($options['limit'])) {
                    $this->limit = $options['limit'];
                }
            }
            if (array_key_exists('dry-run', $options)) {
                $this->isDryRun = $options['dry-run'];
            }
            if (array_key_exists('log', $options)) {
                $this->useLog = $options['log'];
            }
        }
    }
}
