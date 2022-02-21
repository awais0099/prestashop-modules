{**
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
* @author FMM Modules
* @copyright FMM Modules 2021
* @license Single domain
*}
{extends file=$layout}

{block name='content'}
  <section class="featured-products clearfix page-content card card-block" id="my-recentlyviewedproducts-list">
    <h4>{l s='Viewed Products' d='Modules.Viewedproduct.Shop'}</h4>

    <div class="products js-product-list" id="products">
      
        {foreach from=$products item="product"}
          {include file="catalog/_partials/miniatures/product.tpl" product=$product}
        {/foreach}
    </div>
  </section>
{/block}
