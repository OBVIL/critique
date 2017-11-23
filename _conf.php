<?php
return array(
  "title" => 'Critique littéraire', // Nom du corpus
  "srcdir" => dirname( __FILE__ ), // dossier source depuis lequel exécuter la commande de mise à jour
  "cmdup" => "git pull 2>&1", // commande de mise à jour
  "pass" => "", // Mot de passe à renseigner obligatoirement à l’installation
  "srcglob" => array( "*/*_*.xml" ), // sources XML à publier
  "sqlite" => "critique.sqlite", // nom de la base à générer
  "formats" => "article, toc, epub, kindle, markdown, html, iramuteq ",
);
?>
