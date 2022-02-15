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
  <section class="featured-products clearfix page-content card card-block">
    <h2>{l s='Viewed products' d='Modules.Viewedproduct.Shop'}</h2>
    <div class="products js-product-list" id="products" style="margin-top: 20px;">
      
        {foreach from=$products item="product"}
          {include file="catalog/_partials/miniatures/product.tpl" product=$product}
        {/foreach}
    </div>
  </section>
{/block}
