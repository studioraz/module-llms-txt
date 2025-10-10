<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Model;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageSearchResultsInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MageOS\LlmTxt\Model\StoreDataCollector;

final class StoreDataCollectorTest extends TestCase
{
    private StoreDataCollector $collector;
    private StoreManagerInterface|MockObject $storeManager;
    private CategoryCollectionFactory|MockObject $categoryCollectionFactory;
    private ProductCollectionFactory|MockObject $productCollectionFactory;
    private PageRepositoryInterface|MockObject $pageRepository;
    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilder;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->categoryCollectionFactory = $this->createMock(CategoryCollectionFactory::class);
        $this->productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $this->pageRepository = $this->createMock(PageRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->collector = new StoreDataCollector(
            $this->storeManager,
            $this->categoryCollectionFactory,
            $this->productCollectionFactory,
            $this->pageRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function test_collect_returns_store_data_structure(): void
    {
        $storeId = 1;
        $storeName = 'Test Store';
        $baseUrl = 'https://example.com/';

        // Mock store
        $store = $this->getMockBuilder(StoreInterface::class)
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();
        $store->method('getName')->willReturn($storeName);
        $store->method('getBaseUrl')->willReturn($baseUrl);
        $this->storeManager->method('getStore')->with($storeId)->willReturn($store);

        // Mock category collection
        $categoryCollection = $this->createMock(CategoryCollection::class);
        $categoryCollection->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollection->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollection->method('setStoreId')->willReturnSelf();
        $categoryCollection->method('setOrder')->willReturnSelf();
        $categoryCollection->method('setPageSize')->willReturnSelf();
        $categoryCollection->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->categoryCollectionFactory->method('create')->willReturn($categoryCollection);

        // Mock product collection
        $productCollection = $this->createMock(ProductCollection::class);
        $productCollection->method('addAttributeToSelect')->willReturnSelf();
        $productCollection->method('addAttributeToFilter')->willReturnSelf();
        $productCollection->method('setStoreId')->willReturnSelf();
        $productCollection->method('addStoreFilter')->willReturnSelf();
        $productCollection->method('setOrder')->willReturnSelf();
        $productCollection->method('setPageSize')->willReturnSelf();
        $productCollection->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->productCollectionFactory->method('create')->willReturn($productCollection);

        // Mock CMS pages
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $searchResults = $this->createMock(PageSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([]);
        $this->pageRepository->method('getList')->willReturn($searchResults);

        $result = $this->collector->collect($storeId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('store_name', $result);
        $this->assertArrayHasKey('store_url', $result);
        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('cms_pages', $result);
        $this->assertSame($storeName, $result['store_name']);
        $this->assertSame($baseUrl, $result['store_url']);
    }

    public function test_collect_includes_category_data(): void
    {
        $storeId = 1;

        $store = $this->getMockBuilder(StoreInterface::class)
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();
        $store->method('getName')->willReturn('Test');
        $store->method('getBaseUrl')->willReturn('https://example.com/');
        $this->storeManager->method('getStore')->willReturn($store);

        // Create mock category
        $category = $this->getMockBuilder(CategoryInterface::class)
            ->addMethods(['getUrlKey', 'getDescription'])
            ->getMockForAbstractClass();
        $category->method('getName')->willReturn('Electronics');
        $category->method('getUrlKey')->willReturn('electronics');
        $category->method('getDescription')->willReturn('Best electronics');

        $categoryCollection = $this->createMock(CategoryCollection::class);
        $categoryCollection->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollection->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollection->method('setStoreId')->willReturnSelf();
        $categoryCollection->method('setOrder')->willReturnSelf();
        $categoryCollection->method('setPageSize')->willReturnSelf();
        $categoryCollection->method('getIterator')->willReturn(new \ArrayIterator([$category]));
        $this->categoryCollectionFactory->method('create')->willReturn($categoryCollection);

        // Mock other collections
        $productCollection = $this->createMock(ProductCollection::class);
        $productCollection->method('addAttributeToSelect')->willReturnSelf();
        $productCollection->method('addAttributeToFilter')->willReturnSelf();
        $productCollection->method('setStoreId')->willReturnSelf();
        $productCollection->method('addStoreFilter')->willReturnSelf();
        $productCollection->method('setOrder')->willReturnSelf();
        $productCollection->method('setPageSize')->willReturnSelf();
        $productCollection->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->productCollectionFactory->method('create')->willReturn($productCollection);

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);
        $searchResults = $this->createMock(PageSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([]);
        $this->pageRepository->method('getList')->willReturn($searchResults);

        $result = $this->collector->collect($storeId);

        $this->assertCount(1, $result['categories']);
        $this->assertSame('Electronics', $result['categories'][0]['name']);
        $this->assertStringContainsString('electronics.html', $result['categories'][0]['url']);
    }
}
