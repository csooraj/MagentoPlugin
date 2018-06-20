<?php
namespace Demac\WebService\Api;
/**
 * Interface WebServiceRepositoryInterface
 * @package Demac\WebService\Api
 */
interface WebServiceRepositoryInterface
{
    /**
     * @return int
     */
    public function getCatalogProductCount();
    /**
     * @param int $categoryId
     *
     * @return mixed
     */
    public function getCategoryProductCount($categoryId);
    /**
     * @return mixed
     */
    public function getProducts();
}
