<?php
// mettre le sachet SQLite dans le pot
include (dirname(__FILE__).'/../lib/teipot/Teipot.php');
$pot=new Teipot(dirname(__FILE__).'/critique.sqlite', 'fr');
// est-ce qu’une resssource statique répond ? 
$pot->get();
$themeHref=$pot->baseHref.'../lib/teipot/';
// get components from URI
$divs=$pot->divs();


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <?php echo $divs['head']; ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $themeHref; ?>html.css" />
  </head>
  <body class="<?php if($pot->q) echo 'search'; ?>">
    <header id="header">
      <h1>
        <a href="<?php echo $pot->baseHref; ?>">OBVIL, corpus critique</a>
      </h1>
      <!-- ??
      <menu class="bar">
        <li><a href="?">?</a></li>
      </menu>
      -->
      <form name="q" id="qForm" action="<?php echo $pot->baseHref; ?>"
      style="float:right; margin-right:1em; margin-top:1ex; line-height:100%;" >
        
        <input name="q" id="q" name="search" size="25" accesskey="f" tabindex="1" title="Rechercher dans le corpus [alt-shift-f]" autocomplete="off" placeholder="Rechercher"
value="<?php echo str_replace('"', '&quot;', $pot->q); ?>"/>
        <button name="go">&gt;</button>
        <?php if (false && $bookId) echo '<br/><label>uniquement dans ce livre <input name="bookId" value="',$bookName,'" type="checkbox"/></label>'; ?>
        
      </form>
    </header>
    <div id="center">
      <nav id="breadcrumb">
        <?php 
        echo '<a href="',$pot->baseHref,'">OBVIL, corpus critique</a> » '; 
        echo $divs['breadcrumb']; 
        ?>
      </nav>
      <article id="article">
      <?php

if ($divs['body']) {
  echo $divs['body'];
  // page d’accueil d’un livre et requête, afficher une concordance dans le livre
  if ($pot->q && (!$divs['artName'] || $divs['artName']=='index')) echo $pot->concBook($divs['bookId']);
}
// pas de livre trouvé, proposer la liste
else {
  $pot->bib();
}
      ?>
      </article>
      <aside id="aside">
        <menu>
<?php
// ajouter la recherche de mots aux sous parties
echo $pot->reHref($divs['nav']);
?>
        </menu>
      </aside>
    </div>
    <footer id="footer">
      Prototype d'application TEI pour le corpus critique
    </footer>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Tree.js">//</script>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Sortable.js">//</script>
    <script type="text/javascript"><?php echo $divs['js']; ?></script>  
  </body>
</html>