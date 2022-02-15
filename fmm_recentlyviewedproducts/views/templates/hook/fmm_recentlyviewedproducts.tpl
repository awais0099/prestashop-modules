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

<section class="featured-products clearfix" style="background: white;padding-top: 15px;">
  <h2>{l s='Viewed products' d='Modules.Viewedproduct.Shop'}</h2>
  <div class="products">
    {if count($products) > 2}
      {assign var="products" value=array_slice($products, 0, 2)}
        {* {dump( count($products))} *}
      {foreach from=$products item="product"}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product}
      {/foreach}
      <a href="{$viewedproductsListLink}" class="d-block">View all</a>
    {else}
      {foreach from=$products item="product"}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product}
      {/foreach}
    {/if}
  </div>
</section>
