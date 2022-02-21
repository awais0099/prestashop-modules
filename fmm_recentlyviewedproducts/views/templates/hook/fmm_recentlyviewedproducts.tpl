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

<section id="recentlyviewedproducts" class="featured-products clearfix">
  <p class="text-uppercase h6">{l s='Viewed products' mod='fmm_recentlyviewedproducts'}</p>
  <div class="products">
   
    {foreach from=$products item="product"}
      {include file="catalog/_partials/miniatures/product.tpl" product=$product}
    {/foreach}

    <a href="{$viewedproductsListLink}" class="d-block recentlyviewedproducts-anchor-tag">
      {l s='View all' mod='fmm_recentlyviewedproducts'}
    </a>

  </div>
</section>
