{if $mode eq 'ajax'}
    {assign var="extend" value='ajax.tpl'}
{else}
    {assign var="extend" value='page.tpl'}
{/if}
{extends file=$extend}
{block name="content"}
    <div id="mass_change"{if $mode neq 'ajax'} class="center"{else} title="{$page_title}"{/if}>
    <form action="{$form_url}" method="post">
        {if $mode neq 'ajax'}<h2>{$page_title}</h2>{/if}
        {if isset($message)}<p>{$message}</p>{/if}
        <div class="button-container">
    {* Form entries*}
    {include file="forms_types.tpl" masschange=true}
            <input type="submit" id="masschange" class="button" value="{_T string="Edit"}"/>
            <a href="{$cancel_uri}" class="button" id="btncancel">{_T string="Cancel"}</a>
            <input type="hidden" name="confirm" value="1"/>
            {if $mode eq 'ajax'}<input type="hidden" name="ajax" value="true"/>{/if}
            {foreach $data as $key=>$value}
                {if is_array($value)}
                    {foreach $value as $val}
                <input type="hidden" name="{$key}[]" value="{$val}"/>
                    {/foreach}
                {else}
                <input type="hidden" name="{$key}" value="{$value}"/>
                {/if}
            {/foreach}
        </div>
    </form>
    </div>
{/block}
