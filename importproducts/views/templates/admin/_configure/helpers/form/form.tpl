{*
 * EU Cookies policy
 *
 * @author    Grupo Hostienda <info@hostienda.com>
 * @copyright 2017 Grupo Hostienda
 * @license   You are just allowed to modify this copy for your own use. You must not redistribute it. License is permitted for one Prestashop instance only but you can install it on your test instances.
 *}

{extends file="helpers/form/form.tpl"}
{block name="field"}
    {if $input.type == 'button'}
        <div class="col-md-3">
            <a class="btn btn-info" href="{$input.href|escape:'html':'UTF-8'}">
                <i class="icon process-icon-import"></i>
                <b>{l s='Import products' mod='importproducts'}</b>
            </a>
        </div>        
    {else}
        {$smarty.block.parent}
    {/if}
{/block}