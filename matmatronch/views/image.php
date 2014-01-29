<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
  <html>
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <title>Photo Matmatronch</title>
      <link rel="stylesheet" type="text/css" href="/themes/default/css/site.css" />
    </head>
    <body>
      <center>
        <a href="javascript:window.close()">
          <img src="/data/matmatronch/<?php echo $user_id ?>.jpg" style="margin-bottom: 0.5em; margin-top: 0.5em;">
        </a>

        <br/>

        <?php
          if (isset($citation) && !empty($citation))
            echo "<i>" . $citation . "</i><br/><br/>";
        ?>

        <input type="submit" class="connectsubmit" id="connectsubmit" value="Fermer cette fenetre" OnClick="window.close()"/>
      </center>
  </body>
</html>