<!DOCTYPE HTML>
<html>
  <head>
    <title>{$pageTitle} | Civvic.ro</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link type="text/css" href="{$wwwRoot}css/main.css?v=1" rel="stylesheet"/>
    <link type="text/css" href="{$wwwRoot}css/smoothness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />	
    <script type="text/javascript" src="{$wwwRoot}js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="{$wwwRoot}js/jquery-ui-1.8.18.custom.min.js"></script>
    <script type="text/javascript" src="{$wwwRoot}js/jquery.ui.datepicker-ro.js"></script>
    <script type="text/javascript" src="{$wwwRoot}js/main.js"></script>
  </head>

  <body>
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

      <h3>Legături externe</h3>

      <ul class="sideMenu">
        <li><a href="http://wiki.civvic.ro/">wiki.civvic.ro</a></li>
      </ul>

      {if $user && $user->admin}
        <h3>Administrare</h3>

        <ul class="sideMenu">
          <li><a href="{$wwwRoot}acte-inexistente">acte inexistente</a></li>
        </ul>
      {/if}
    </div>
    <div id="main">
      <div id="userActions">
        {if $user}
          {$user->getDisplayName()}
          <a href="{$wwwRoot}auth/logout">deconectare</a>
        {else}
          <a id="openidLink" href="{$wwwRoot}auth/login">autentificare cu OpenID</a>
        {/if}
      </div>
      {include file="bits/flashMessage.tpl"}
      <div id="template">
        {include file=$templateName}
      </div>
    </div>
  </body>

</html>
