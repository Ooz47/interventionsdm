<?php
$mysqli = new mysqli("localhost", "sc3bpwx1045_idcla", "mcAXz1e(2aj&", "sc3bpwx1045_interventionsde23");
if ($mysqli->connect_errno) {
  die("Échec de connexion : " . $mysqli->connect_error);
}
echo "Connexion OK";
$mysqli->close();
