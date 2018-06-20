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
              $categoryProducts = $category->getProductCollection()->addAttributeToSelect('*')->addMediaGalleryData();
              foreach ($categoryProducts as $product) {
                $attributeList = array();
                $optionsArray  = array();
                $imagesArray  = array();
                $reviewsArray  = array();
                $configurableAttrs = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                foreach ($configurableAttrs as $attr) {
                  $label = $attr['label'];
                  $values = $attr['values'];
                  array_push($optionsArray, array( $label => $values));
                }
                $attributeData = $product->getAttributes();
                $images =  $product->getImage();
                $galleryImages = $product->getMediaGalleryImages();
                if(!empty($galleryImages)){
                  foreach ($galleryImages as $image) {
                    $imageUrl = $image->getUrl();
                    array_push($imagesArray, $imageUrl);
                  }
                }
                $reviewFactory = $objectManager->create('Magento\Review\Model\Review');
                $reviewFactory->getEntitySummary($product, 1);
                $ratingSummary = $product->getRatingSummary()->getRatingSummary();
                $rating = $objectManager->get("Magento\Review\Model\ResourceModel\Review\CollectionFactory");
                $reviewCollection = $rating->create()
                        ->addStatusFilter(
                            \Magento\Review\Model\Review::STATUS_APPROVED
                        )->addEntityFilter(
                            'product',
                            $product->getId()
                        )->setDateOrder();
                if(!empty($reviewCollection)){
                  foreach ($reviewCollection as $review) {
                    $title = $review->getTitle();
                    $detail = $review->getDetail();
                    $reviewData = $review->getData();
                    $createdDate = $reviewData["created_at"];
                    $nickName = $reviewData["nickname"];
                    array_push($reviewsArray, array( "title" => $title, "detail" => $detail, "date" => $createdDate, "nickName" => $nickName ));
                  }
                }
                foreach ($attributeData as $attribute_code) {
                  $label = $attribute_code->getStoreLabel();
                  $attributeValue = $product->getResource()->getAttribute($attribute_code)->getFrontend()->getValue($product);
                  array_push($attributeList, array( $label => $attributeValue));
                }
                array_push($productList, array("labels" => $attributeList, "options" => $optionsArray, "images" => $images, "gallery" => $imagesArray, "ratingPercentage" => $ratingSummary, "reviews" => $reviewsArray ));
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
