<?php
/**
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
* @author FMM Modules
* @copyright FMM Modules 2021
* @license Single domain
*/

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class fmm_recentlyviewedproductsRVProductsContModuleFrontController extends ModuleFrontController
{
    private $templateFile = 'module:fmm_recentlyviewedproducts/views/templates/front/RVProductsCont.tpl';

    private $currentProductId;
    
    // public $php_self = "Sampah";

    public function initContent()
    {
        parent::initContent();
        
        $products; 

        $existingProductsQuery = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT p.id_product
            FROM ' . _DB_PREFIX_ . 'product p
            WHERE p.active = 1'
        );

        $existingProductsQuery = array_map(function ($entry) {
            return $entry['id_product'];
        }, $existingProductsQuery);
        
        // dump($existingProductsQuery);exit;
     
        if (!isset($this->context->cookie->viewed) || empty($this->context->cookie->viewed)) {
            // dump($configuration);exit;
            return;
        }

        $viewedProductsIds = array_reverse(explode(',', $this->context->cookie->viewed));
    
        $existingProducts = $existingProductsQuery;

        $viewedProductsIds = array_filter($viewedProductsIds, function ($entry) use ($existingProducts) {
            return in_array($entry, $existingProducts);
        });


        // $getViewedProductIds = array_slice($viewedProductsIds, 0, (int) (Configuration::get('FMM_AH_PRODUCTS_VIEWED_NBR')));

        // dump($getViewedProductIds);exit;

        // $products = $this->getViewedProducts();
        

        $productIds = $viewedProductsIds;

        if (!empty($productIds)) {
            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );

            $products_for_template = array();

            if (is_array($productIds)) {
                foreach ($productIds as $productId) {
                        $products_for_template[] = $presenter->present(
                            $presentationSettings,
                            $assembler->assembleProduct(array('id_product' => $productId)),
                            $this->context->language
                        );
                    
                }
            }

            $products = $products_for_template;
        }

        // dump($products);exit;


        if (!empty($products)) {
            $this->context->smarty->assign([
                'products' => $products,
                // 'path' => 'sampah'
            ]);
        }

        

        $this->setTemplate(
            'module:fmm_recentlyviewedproducts/views/templates/front/RVProductsCont.tpl'
        );
    }
}
