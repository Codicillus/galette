<!DOCTYPE html>
<html lang="{$galette_lang}" class="public_page{if $additionnal_html_class} {$additionnal_html_class}{/if}">
    <head>
        {include file='common_header.tpl'}
{if $require_calendar}
        <script type="text/javascript" src="{$galette_base_path}{$jquery_dir}jquery.ui-{$jquery_ui_version}/jquery.ui.datepicker.min.js"></script>
    {if $galette_lang ne 'en'}
        <script type="text/javascript" src="{$galette_base_path}{$jquery_dir}jquery.ui-{$jquery_ui_version}/i18n/jquery.ui.datepicker-{$galette_lang}.js"></script>
    {/if}
{/if}
{* If some additionnals headers should be added from plugins, we load the relevant template file
We have to use a template file, so Smarty will do its work (like replacing variables). *}
{if $headers|@count != 0}
    {foreach from=$headers item=header}
        {include file=$header}
    {/foreach}
{/if}
        {if $head_redirect}{$head_redirect}{/if}
    </head>
    <body>
        {* IE7 and above are no longer supported *}
        <!--[if lt IE 8]>
        <div id="oldie">
            <p>{_T string="Your browser version is way too old and no longer supported in Galette for a while."}</p>
            <p>{_T string="Please update your browser or use an alternative one, like Mozilla Firefox (http://mozilla.org)."}</p>
        </div>
        <![endif]-->
        <header>
            <img src="{urlFor name="logo"}" width="{$logo->getOptimalWidth()}" height="{$logo->getOptimalHeight()}" alt="[ Galette ]" />
            <ul id="langs">
{foreach item=langue from=$languages}
                <li><a href="?pref_lang={$langue->getID()}"><img src="{$galette_base_path}{$langue->getFlag()}" alt="{$langue->getName()}" lang="{$langue->getAbbrev()}" class="flag"/></a></li>
{/foreach}
            </ul>
{if $login->isLogged()}
            <div id="user">
                <a id="userlink" title="{_T string="View your member card"}" href="{$galette_base_path}voir_adherent.php">{$login->loggedInAs(true)}</a>
                <a id="logout" title="{_T string="Log off"}" href="{$galette_base_path}index.php?logout=1">{_T string="Log off"}</a>
            </div>
{/if}
{if $GALETTE_MODE eq 'DEMO'}
        <div id="demo" title="{_T string="This application runs under DEMO mode, all features may not be available."}">
            {_T string="Demonstration"}
        </div>
{/if}
        </header>
        <h1 id="titre">{$page_title}</h1>
        <p id="asso_name">{$preferences->pref_nom}{if $preferences->pref_slogan}&nbsp;: {$preferences->pref_slogan}{/if}</p>
        <nav>
            <a id="backhome" class="button{if $cur_path eq "/" or $cur_path eq '/login'} selected{/if}" href="{$galette_base_path}index.php">{_T string="Home"}</a>
    {if !$login->isLogged()}
        {if $preferences->pref_bool_selfsubscribe eq true}
            <a id="subscribe" class="button{if $cur_path eq "/subscribe"} selected{/if}" href="{urlFor name="subscribe"}">{_T string="Subscribe"}</a>
        {/if}
        {if $pref_mail_method neq constant('Galette\Core\GaletteMail::METHOD_DISABLED')}
            <a id="lostpassword" class="button{if $cur_path eq "/password-lost"} selected{/if}" href="{urlFor name="password-lost"}">{_T string="Lost your password?"}</a>
        {/if}
    {/if}
    {if $preferences->showPublicPages() eq true}
            <a id="memberslist" class="button{if $cur_path eq "/public/members"} selected{/if}" href="{urlFor name="public_members"}" title="{_T string="Members list"}">{_T string="Members list"}</a>
            <a id="trombino" class="button{if $cur_path eq "/public/trombinoscope"} selected{/if}" href="{urlFor name="public_trombinoscope"}" title="{_T string="Trombinoscope"}">{_T string="Trombinoscope"}</a>
    {/if}
        </nav>
        {include file="global_messages.tpl"}
        {block name="content"}{_T string="Public page content"}{/block}
        {include file="footer.tpl"}
    </body>
</html>
