<?php
/**
 * SearchService.php
 * Business logic and Service/Repository abstraction for eCommerce search.
 * Decouples frontend API contract from underlying search engine (MySQL FULLTEXT, Typesense, Elasticsearch).
 */
require_once __DIR__ . '/../models/SearchModel.php';

class SearchService {
    private $searchModel;

    public function __construct() {
        $this->searchModel = new SearchModel();
    }

    /**
     * Get live suggestions formatted into standard contract:
     * {
     *   "products": [...],
     *   "categories": [...],
     *   "subCategories": [...],
     *   "subSubCategories": [...],
     *   "productTypes": [...]
     * }
     */
    public function getLiveSuggestions($keyword) {
        $keyword = trim($keyword);
        if (strlen($keyword) < 1) {
            return [
                'products' => [],
                'categories' => [],
                'subCategories' => [],
                'subSubCategories' => [],
                'productTypes' => []
            ];
        }

        $rawResults = $this->searchModel->getSuggestions($keyword, 10);
        return $rawResults;
    }

    /**
     * Get full search page results with filters and pagination
     */
    public function getSearchResults($params) {
        return $this->searchModel->getSearchResults($params);
    }
}
