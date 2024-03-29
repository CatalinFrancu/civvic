<!DOCTYPE HTML>
<html>
  <head>
    <title>{$pageTitle} | Civvic.ro</title>
    <meta charset="utf-8">
    {foreach from=$cssFiles item=cssFile}
      <link type="text/css" href="{$wwwRoot}css/{$cssFile}" rel="stylesheet"/>
    {/foreach}
    {foreach from=$jsFiles item=jsFile}
      <script src="{$wwwRoot}js/{$jsFile}"></script>
    {/foreach}
  </head>

  <body>
    {if $productionMode && (!$user || !$user->admin)}
      <div id="fb-root"></div>
      <script>facebookInit(document, 'script', 'facebook-jssdk');</script>
    {/if}

    <div id="sidebar">
      <div id="logo">
        <a href="{$wwwRoot}"><img src="{$wwwRoot}img/logo.png" title="Civvic logo"/></a>
      </div>

      <h3>Categorii</h3>

      <ul class="sideMenu">
        {if $user && $user->admin}
          <li><a href="{$wwwRoot}tipuri-acte">tipuri de acte</a></li>
          <li><a href="{$wwwRoot}autori">autori</a></li>
          <li><a href="{$wwwRoot}locuri">locuri</a></li>
        {/if}
        <li><a href="{$wwwRoot}acte">acte</a></li>
        <li><a href="{$wwwRoot}monitoare">monitoare</a></li>
      </ul>

      <h3>Informații</h3>

      <ul class="sideMenu">
        <li><a href="http://wiki.civvic.ro/wiki/Acces_la_codul_surs%C4%83">programatori</a></li>
      </ul>

      <h3>Legături externe</h3>

      <ul class="sideMenu">
        <li><a href="http://wiki.civvic.ro/">wiki.civvic.ro</a></li>
        {if $moArchiveUrl && $user && $user->admin}
          <li><a href="{$moArchiveUrl}">arhiva PDF</a></li>
        {/if}
      </ul>

      {if $user && $user->admin}
        <h3>Administrare</h3>

        <ul class="sideMenu">
          <li><a href="{$wwwRoot}acte-cu-comentarii">acte cu comentarii</a></li>
          <li><a href="{$wwwRoot}acte-inexistente">acte inexistente</a></li>
          <li><a href="{$wwwRoot}acte-fara-numar">acte fără număr</a></li>
          <li><a href="{$wwwRoot}imagini">imagini</a></li>
          <li><a href="{$wwwRoot}texte-ocr">texte OCR</a></li>
          <li class="guide"><a href="http://wiki.civvic.ro/wiki/Ghidul_moderatorului">ghid</a></li>
          <li class="bug"><a href="http://wiki.civvic.ro/wiki/Bugs">buguri</a></li>
        </ul>
      {/if}
    </div>
    <div id="main">
      <div id="userActions">
        {if $user}
          <span class="userName">{$user->getDisplayName()}</span>
          <a href="{$wwwRoot}auth/contul-meu">contul meu</a>
          <a href="{$wwwRoot}auth/logout">deconectare</a>
        {else}
          <a id="openidLink" href="{$wwwRoot}auth/login">autentificare cu OpenID</a>
        {/if}
      </div>
      <div class="clearer"></div>

      {if !$user}
      {*
        <div class="banner">
          <a href="http://hartapoliticii.ro"><img src="{$wwwRoot}img/hp.jpg" title="banner hartapoliticii.ro"/></a>
        </div>
      *}
      {/if}

      {include "bits/flashMessage.tpl"}
      <div id="template">
        {include "$templateName"}
      </div>
    </div>
    <div style="clear: both"></div>

    <footer>
      {if $productionMode && (!$user || !$user->admin)}
        <div class="fb-like" data-href="http://facebook.com/civvic.ro" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div>
      {/if}

      <div id="license">
        Datele Civvic.ro sunt disponibile sub
        <a rel="license" href="http://creativecommons.org/licenses/by-nd/3.0/ro/">Creative Commons Attribution-NoDerivs 3.0 Romania License</a>.
      </div>

    </footer>
  </body>

</html>
