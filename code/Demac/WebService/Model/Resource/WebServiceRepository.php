<?php
namespace Demac\WebService\Model\Resource;
use Demac\WebService\Api\WebServiceRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Api\Data\StoreInterface as StoreTest;
use \Magento\Store\Model\StoreRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnectionFactory;
/**
 * Class WebServiceRepository
 * @package Demac\WebService\Model
 */
class WebServiceRepository implements WebServiceRepositoryInterface
{
    /**
     * @var ResourceConnectionFactory
     */
    protected $_store;

    protected $_resourceConnection;

    protected $_storeRepository;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollection;
    /**
     * @var CategoryFactory
     */
    protected $_category;
    /**
     * WebServiceRepository constructor.
     *
     * @param ResourceConnectionFactory $_resourceConnection
     */
    public function __construct(StoreRepository $storeRepository, ResourceConnectionFactory $_resourceConnection, ProductCollectionFactory $_productCollection, CategoryFactory $_category)
    {
        $this->_resourceConnection = $_resourceConnection;
        $this->_productCollection = $_productCollection;
        $this->_category = $_category;
        $this->_storeRepository = $storeRepository;
    }
    /**
     * @return int
     */
    public function getCatalogProductCount()
    {
      $size = $this->_productCollection->create()->getSize();
      $product = $this->_productCollection->create();
      // $currentStore = $this->_store->getName();
      // return [[
      //     "size"=> $size,
      //     "product"=> $currentStore
      //   ]];
      $stores = $this->_storeRepository->getList();
        $websiteIds = array();
        $storeList = array();
        foreach ($stores as $store) {
            $websiteId = $store["website_id"];
            $storeId = $store["store_id"];
            $storeName = $store["name"];
            $storeList[$storeId] = $storeName;
            array_push($websiteIds, $websiteId);
        }
        return $storeList;
    }
    /**
     * @param $categoryId
     *
     * @return int
     */
    public function getCategoryProductCount($categoryId)
    {
        $size = 0;
        $category = $this->_category->create()->load($categoryId);
        if (isset($category) && !empty($category)) {
            $size = $category->getProductCollection()->getSize();
        }
        return $size;
    }

    /**
     * @return mixed
     */

    public function getProducts()
    {
      $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
      $categoryHelper = $objectManager->get('\Magento\Catalog\Helper\Category');
      $categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
      $categories = $categoryHelper->getStoreCategories();
      $categoryList = array();
      foreach ($categories as $category) {
          $subcategoryList = array();
          $categoryId = $category->getId();
          $categoryName = $category->getName();
          $subcategory = $objectManager->create('Magento\Catalog\Model\Category')->load($categoryId);
          $subcategories = $subcategory->getChildrenCategories();
          foreach ($subcategories as $sub) {
            $id = $sub->getId();
            $spList = array();
            $productMeta = array();
            $name = $sub->getName();
            $subParent = $objectManager->create('Magento\Catalog\Model\Category')->load($id);
            $subProducts = $subParent->getChildrenCategories();
            foreach ($subProducts as $sp) {
              $productList = array();
              $spData = $sp->getName();
              $id = $sp->getId();
              array_push($spList, $spData);
              $category = $categoryFactory->create()->load($id);
              $categoryProducts = $category->getProductCollection()->addAttributeToSelect('*');
              foreach ($categoryProducts as $product) {
                $productData = $product->getData();
                array_push($productList, $productData);
              }
              array_push($productMeta, array("name" => $spData , "data" => $productList));
            }
            array_push($subcategoryList, array("id" => $id, "name" => $name, "productList" => $productMeta));
          }
          array_push($categoryList, array("id" => $categoryId, "name" => $categoryName, "subcategory" => $subcategoryList ));
      }
      return $categoryList;
    }
}
