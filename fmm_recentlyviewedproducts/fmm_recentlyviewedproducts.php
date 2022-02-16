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

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fmm_RecentlyViewedProducts extends Module implements WidgetInterface
{
    private $templateFile;
    private $currentProductId;

    public function __construct()
    {
        $this->name = 'fmm_recentlyviewedproducts';
        $this->author = 'PrestaShop';
        $this->version = '1.2.2';
        $this->tab = 'front_office_features';
        $this->need_instance = 0;

        $this->ps_versions_compliancy = array(
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        );

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Recentyl viewed products', array(), 'Modules.Recentlyviewedproducts.Admin');
        $this->description = $this->trans(
            'Recentyl viewed products.',
            array(),
            'Modules.Recentlyviewedproducts.Admin'
        );

        $this->templateFile = 'module:fmm_recentlyviewedproducts/views/templates/hook/fmm_recentlyviewedproducts.tpl';
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('FMM_AH_PRODUCTS_VIEWED_NBR', 8)
            // && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionModuleRegisterHookAfter')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('actionObjectProductDeleteAfter')
            && $this->registerHook('actionObjectProductUpdateAfter')
        ;
    }

    public function uninstall()
    {
        return parent::uninstall()
        && Configuration::deleteByName('FMM_AH_PRODUCTS_VIEWED_NBR');
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        $this->_clearCache($this->templateFile);
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        $this->_clearCache($this->templateFile);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitBlockViewed')) {
            if (!($productNbr = Tools::getValue('FMM_AH_PRODUCTS_VIEWED_NBR')) || empty($productNbr)) {
                $output .= $this->displayError($this->trans(
                    'You must fill in the \'Products displayed\' field.',
                    array(),
                    'Modules.Recentlyviewedproducts.Admin'
                ));
            } elseif (0 === (int) ($productNbr)) {
                $output .= $this->displayError($this->trans('Invalid number.', array(), 'Modules.Recentlyviewedproducts.Admin'));
            } else {
                Configuration::updateValue('FMM_AH_PRODUCTS_VIEWED_NBR', (int) $productNbr);

                $this->_clearCache($this->templateFile);

                $output .= $this->displayConfirmation($this->trans(
                    'The settings have been updated.',
                    array(),
                    'Admin.Notifications.Success'
                ));
            }
        }

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Number Of Products', array(), 'Modules.Recentlyviewedproducts.Admin'),
                        'name' => 'FMM_AH_PRODUCTS_VIEWED_NBR',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->trans(
                            'Give the number of products displayed in the left column.',
                            array(),
                            'Modules.Recentlyviewedproducts.Admin'
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $configFormLang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $configFormLang ? $configFormLang : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBlockViewed';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name .
            '&tab_module=' . $this->tab .
            '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'FMM_AH_PRODUCTS_VIEWED_NBR' => Tools::getValue('FMM_AH_PRODUCTS_VIEWED_NBR', Configuration::get('FMM_AH_PRODUCTS_VIEWED_NBR')),
        );
    }

    public function getCacheId($name = null)
    {
        $key = implode('|', $this->getViewedProductIds());

        return parent::getCacheId('ps_viewedproduct|' . $key);
    }

    public function renderWidget($hookName = null, array $configuration = array())
    {
        // dump($configuration);exit;
        if (isset($configuration['product']['id_product'])) {
            $this->currentProductId = $configuration['product']['id_product'];
        }

        if ('displayProductAdditionalInfo' === $hookName) {
            $this->addViewedProduct($this->currentProductId);
            return;
        }

        if (!isset($this->context->cookie->viewed) || empty($this->context->cookie->viewed)) {
            return;
        }

        if (!$this->isCached($this->templateFile, $this->getCacheId())) {
            $variables = $this->getWidgetVariables($hookName, $configuration);
            if (empty($variables)) {
                return false;
            }

            $this->smarty->assign($variables);
        }

        return $this->fetch($this->templateFile, $this->getCacheId());
    }

    public function getWidgetVariables($hookName = null, array $configuration = array())
    {
        if (isset($configuration['product']['id_product'])) {
            $this->currentProductId = $configuration['product']['id_product'];
        }

        $products = $this->getViewedProducts();

        if (!empty($products)) {
            return array(
                'products' => $products,
                'version' => _PS_VERSION_,
                'viewedproductsListLink' => $this->context->link->getModuleLink('fmm_recentlyviewedproducts', 'RVProductsCont')
            );
        }

        return false;
    }

    protected function addViewedProduct($idProduct)
    {
        $arr = array();

        if (isset($this->context->cookie->viewed)) {
            $arr = explode(',', $this->context->cookie->viewed);
        }

        if (!in_array($idProduct, $arr)) {
            $arr[] = $idProduct;

            $this->context->cookie->viewed = trim(implode(',', $arr), ',');
        }
    }

    protected function getViewedProductIds()
    {
        $viewedProductsIds = array_reverse(explode(',', $this->context->cookie->viewed));
        if (null !== $this->currentProductId && in_array($this->currentProductId, $viewedProductsIds)) {
            $viewedProductsIds = array_diff($viewedProductsIds, array($this->currentProductId));
        }

        $existingProducts = $this->getExistingProductsIds();
        $viewedProductsIds = array_filter($viewedProductsIds, function ($entry) use ($existingProducts) {
            return in_array($entry, $existingProducts);
        });

        return array_slice($viewedProductsIds, 0, (int) (Configuration::get('FMM_AH_PRODUCTS_VIEWED_NBR')));
    }

    protected function getViewedProducts()
    {
        $productIds = $this->getViewedProductIds();

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
                    if ($this->currentProductId !== $productId) {
                        $products_for_template[] = $presenter->present(
                            $presentationSettings,
                            $assembler->assembleProduct(array('id_product' => $productId)),
                            $this->context->language
                        );
                    }
                }
            }

            return $products_for_template;
        }

        return false;
    }

    /**
     * @return array the list of active product ids.
     */
    private function getExistingProductsIds()
    {
        $existingProductsQuery = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT p.id_product
            FROM ' . _DB_PREFIX_ . 'product p
            WHERE p.active = 1'
        );

        return array_map(function ($entry) {
            return $entry['id_product'];
        }, $existingProductsQuery);
    }

    public function hookActionModuleRegisterHookAfter($params)
    {
        dump($params);exit;
        if ($params['hook_name'] == 'displayMyAccountBlock') {
            $this->_clearCache('*');
        }
    }

    public function hookdisplayHeader($params)
    {
        if (_PS_VERSION_ == '1.7.6.0' or _PS_VERSION_ == '1.7.6') {
            $this->context->controller->registerStylesheet(
                'modules-fmm_recentlyviewedproducts',
                'modules/' . $this->name . '/views/css/presta1760.css'
            );
        } else {
            $this->context->controller->registerStylesheet(
                'modules-fmm_recentlyviewedproducts',
                'modules/' . $this->name . '/views/css/fmm_recentlyviewedproducts.css'
            );
        }


    }

    public function hookDisplayCustomerAccount()
    {
        $products = $this->getViewedProducts();
        if (!empty($products)) {
            $this->context->smarty->assign([
                'viewedproductsListLink' => $this->context->link->getModuleLink('fmm_recentlyviewedproducts', 'RVProductsCont')
            ]);
            return $this->display(__FILE__, 'linkInMyAccountBlock.tpl');
        }
    }

}
