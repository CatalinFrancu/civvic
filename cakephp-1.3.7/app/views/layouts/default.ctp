<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" /> 
    <title>Administrare civvic.ro</title>
    <?php echo $html->css('main'); ?>
  </head>
  <body>
    <div id="header">
      <div id="logo">
        <?php echo $html->link('admin.civvic.ro', '/') ?>
      </div>
      <div id="identity">
        <?php echo $html->image('openid-16x16.png', array('alt' => 'OpenId logo')) ?>
        <?php if ($this->Session->read('user')) {?>
          Utilizator: <?php $user = $this->Session->read('user'); print $user['User']['openid']; ?>
          <?php echo $html->link('deconectare', '/users/logout') ?>
        <?php } else { ?>
          <?php echo $html->link('conectare', '/users/login') ?>
        <?php } ?>
      </div>
      <div class="clearer"></div>
    </div>
    <?php echo $this->Session->flash() ?>
    <div id="mainContainer">
      <?php echo $content_for_layout ?>
    </div>
  </body>
</html>
