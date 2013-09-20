<?php
// prendre le pot
include (dirname(__FILE__).'/../lib/teipot/Teipot.php');
// mettre le sachet SQLite dans le pot
$pot=new Teipot(dirname(__FILE__).'/critique.sqlite', 'fr');
// est-ce qu’un fichier statique en base est attendu pour ce chemin ? Si oui, envoyer. 
$pot->get($pot->path);
// Si un document correspond à ce chemin, charger un tableau avec différents composants (body, head, breadcrumb…)
$doc=$pot->doc($pot->path);
// chemin css, js ; baseHref est le nombre de '../' utile pour revenir en racine du site
$themeHref=$pot->baseHref.'../lib/teipot/';


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
      <nav id="breadcrumb">
        <?php 
        echo '<a href="',$pot->baseHref,'">OBVIL, corpus critique</a> » ';
        // nous avons un livre, glisser aussi les liens de téléchargement
        echo $doc['breadcrumb']; 
        ?>
      </nav>
      <article id="article">
      <?php

if ($doc['body']) {
  echo $doc['body'];
  // page d’accueil d’un livre avec recherche plein texte, afficher une concordance
  if ($pot->q && (!$doc['artName'] || $doc['artName']=='index')) echo $pot->concBook($doc['bookId']);
}
// pas de livre demandé, montrer un rapport général
else {
  // charger des résultats en mémoire
  $pot->search();
  // nombre de résultats
  echo $pot->report();
  // présentation chronologique des résultats
  echo $pot->chrono();
  // présentation bibliographique des résultats
  echo $pot->biblio();
  echo '<a name="conc"/>';
  // concordance s’il y a recherche plein texte
  echo $pot->conc();
}
      ?>
      </article>
      <aside id="aside">
        <p> </p>
          <?php
// livre
if ($doc['bookId']) {
  echo "\n<nav>";
  // liens de téléchargements
  echo "\n".'<div class="downloads"><small>Télécharger :</small> '.$doc['downloads'].'</div>';
  // auteur, titre, date
  if ($doc['byline']) $doc['byline']=$doc['byline'].'<br/>';
  echo "\n".'<header><a href="'.$pot->baseHref.$doc['bookName'].'/">'.$doc['byline'].$doc['title'].' ('.$doc['end'].')</a></header>';
  // rechercher dans ce livre
  echo '
  <form>
    <small>Rechercher dans ce livre</small><br/>
    <input name="q" id="q" onclick="this.select()" name="search" size="25" title="Rechercher dans ce livre" value="'. str_replace('"', '&quot;', $pot->q) .'"/>
    <button name="go">&gt;</button>
  </form>
  ';
  // table des matières
  echo $doc['toc'];
  echo "\n</nav>";
}
// accueil ? formulaire de recherche général
else {
  echo'
    <h1>Rechercher dans la collection</h1>
    <form action="">
      <input name="q" class="text" value="'.str_replace('"', '&quot;', $pot->q).'"/>
      <div><label>Dates,</label> de <input name="start" class="year" value="'.$pot->start.'"/>à <input class="year" name="end" value="'. $pot->end .'"/></div>
      <div><label>Auteurs :</label>'.$pot->byList().'</div>
      <button type="reset" onclick="return Form.reset(this.form)">Effacer</button>
      <button type="submit">Rechercher</button>
    </form>
  ';
}
?>
      </aside>
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
