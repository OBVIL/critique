<?php
// prendre le pot
include (dirname(__FILE__).'/../lib/teipot/Teipot.php');
// mettre le sachet SQLite dans le pot
$pot=new Teipot(dirname(__FILE__).'/critique.sqlite', 'fr');
// est-ce qu’un fichier statique (ex: epub) est attendu pour ce chemin ? 
// Si oui, l’envoyer maintenant depuis la base avant d’avoir écrit la moindre ligne
$pot->file($pot->path);
// chemin css, js ; baseHref est le nombre de '../' utile pour revenir en racine du site
$themeHref=$pot->baseHref.'../lib/teipot/';
// Si un document correspond à ce chemin, charger un tableau avec différents composants (body, head, breadcrumb…)
$doc=$pot->doc($pot->path);


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <?php echo $doc['head']; ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $themeHref; ?>html.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $themeHref; ?>teipot.css" />
  </head>
  <body>
    <header id="header">
      <h1>
        <a href="<?php echo $pot->baseHref; ?>?">OBVIL, corpus critique</a>
      </h1>
      
    </header>
    <div id="center">
    <?php
// pas de body trouvé, charger des résultats en mémoire
if (!$doc['body']) {
  $timeStart=microtime(true);
  $pot->search();
}
    ?>
      
      <?php 
      echo '<nav><a href="',$pot->baseHref,'">OBVIL, corpus critique</a> » ';
      echo $doc['breadcrumb'];
      echo '</nav>';
      // liens de téléchargements
      if ($doc['downloads']) echo "\n".'<nav class="downloads"><small>Télécharger :</small> '.$doc['downloads'].'</nav>';
      ?>
      <aside id="aside">
        <p> </p>
          <?php
// les concordances peuvent être très lourdes, placer la nav sans attendre
// livre
if ($doc['bookId']) {
  echo "\n<nav>";
  // auteur, titre, date
  if ($doc['byline']) $doc['byline']=$doc['byline'].'<br/>';
  echo "\n".'<header><a href="'.$pot->baseHref.$doc['bookName'].'/">'.$doc['byline'].$doc['title'].' ('.$doc['end'].')</a></header>';
  // rechercher dans ce livre
  echo '
  <form action=".#conc">
    <small>Rechercher dans ce livre</small><br/>
    <input name="q" id="q" onclick="this.select()" class="search" size="20" title="Rechercher dans ce livre" value="'. str_replace('"', '&quot;', $pot->q) .'"/>
    <input type="submit" name="go" value="&gt;"/>
  </form>
  ';
  // table des matières
  echo $doc['toc'];
  echo "\n</nav>";
}
// accueil ? formulaire de recherche général
else {
  echo'
    <form action="">
      <input name="q" class="text" placeholder="Rechercher" value="'.str_replace('"', '&quot;', $pot->q).'"/>
      <div><label>De <input placeholder="année" name="start" class="year" value="'.$pot->start.'"/></label> <label>à <input class="year" placeholder="année" name="end" value="'. $pot->end .'"/></label></div>
      '.$pot->bylist().'
      <button type="reset" onclick="return Form.reset(this.form)">Effacer</button>
      <button type="submit">Rechercher</button>
    </form>
  ';
}
?>
      </aside>
      <article id="article">
      <?php

if ($doc['body']) {
  echo $doc['body'];
  // page d’accueil d’un livre avec recherche plein texte, afficher une concordance
  if ($pot->q && (!$doc['artName'] || $doc['artName']=='index')) echo $pot->concBook($doc['bookId']);
}
// pas de livre demandé, montrer un rapport général
else {
  // nombre de résultats
  echo $pot->report();
  // présentation chronologique des résultats
  echo $pot->chrono();
  // présentation bibliographique des résultats
  echo $pot->biblio();
  // concordance s’il y a recherche plein texte
  echo $pot->conc();
}
      ?>
        <p> </p>
      </article>
    </div>
    <footer id="footer">
      Prototype d'application TEI pour le corpus critique
    </footer>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Tree.js">//</script>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Form.js">//</script>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Sortable.js">//</script>
    <script type="text/javascript"><?php echo $doc['js']; ?></script>  
  </body>
</html>
