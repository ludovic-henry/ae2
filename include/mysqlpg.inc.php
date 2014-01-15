<?php
class mysqlpg extends mysql {

  function mysqlpg ($type = "ro") {

    $this->mysql('login_petit_geni', 'mdp_petit_geni', 'host', 'base');

  }
}
?>
