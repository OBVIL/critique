<?php
// prendre le temps
$time_start = microtime(true);
// mettre le sachet SQLite dans le pot
include ('Teipot.php');
$pot=new Teipot(dirname(__FILE__).'/critique.sqlite', 'fr');
// est-ce qu’une resssource statique répond ? Si non, passer à la suite
$pot->get();

$q=false;
if (isset($_REQUEST['q']) && $_REQUEST['q']) $q=$_REQUEST['q'];
$themeHref=$pot->baseHref.'theme/';
// javascript en fin de page ?
$js=array();
// analyse URI
$branch=explode('/', $pot->path);
if(count($branch)>0) $bookName=$branch[0];
if(count($branch)>1) $artName=$branch[1];

// un livre ?
if (isset($bookName)) {
  $sql =  'SELECT id,nav FROM book WHERE name= '.$pot->pdo->quote($bookName);
  foreach  ($pot->pdo->query($sql) as $row) {
    $bookId=$row['id'];
    $bookNav=$row['nav'];
    break;
  }
  if (!isset($artName) || !$artName ) $artName='index';
  
  // recherche dans un livre, ne pas charger de chapitre à afficher
  if ($artName=='index' and $q);
  else if(isset($bookId) and isset($artName)) {
    // perfs ? identifiant plus unique ?
    $sql =  'SELECT id,body,head,breadcrumb FROM article WHERE book='.$bookId.' AND name= '.$pot->pdo->quote($artName);
    foreach  ($pot->pdo->query($sql) as $row) {
      $artBody=$row['body'];
      $artHead=$row['head'];
      $artBreadcrumb=$row['breadcrumb'];
      $artId=$row['id'];
      // pour surligner, récupérer les offsets (il y a correspondance avec le texte brut)
      if ($q) {
        // on croise la table des articles avec la table full-text, la table full-text n'indexe pas certaines colonnes utiles à des tris (date, auteur)
        // sur le fts3 sorbonne il faut d'abord faire le match avant de tester le rowid
        $query=$pot->pdo->prepare('SELECT offsets(search) AS offsets FROM search WHERE   +rowid = '.$artId.' AND text MATCH ?') ;
        $query->execute(array($q ));
        // TODO, join quote expressions
        $mark=1;
        $html=array();
        while ($row=$query->fetch()) {
          $offsets=explode(' ',$row['offsets']);
          $count=count($offsets);
          $pointer=0;
          for ($i=0; $i<$count; $i=$i+4) {
            $html[]=substr($artBody, $pointer, $offsets[$i+2]-$pointer);
            
            $html[]='<mark id="mark'.$mark.'">';
            
            $dest=$mark-1;
            if($dest<1) $dest=floor($count/4);
            $html[]='<a href="#mark'.$dest.'" class="prev"></a>';
            $html[]=substr($artBody, $offsets[$i+2], $offsets[$i+3]);
            $dest=$mark+1;
            if($i>$count-5) $dest=1;
            $html[]='<a href="#mark'.$dest.'" class="next"></a>';
            
            $html[]='</mark>';

            $pointer=$offsets[$i+2]+$offsets[$i+3];
            $mark++;
          }
          $html[]=substr($artBody, $pointer);
          $artBody=implode('', $html);
          $js[]='if (!location.hash) location.hash = "#mark1";';
          break;
        }
      }
      // on ne prend que le premier ?
      break;
    }
  }
}

?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <?php if (isset($artHead)) echo $artHead; ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $themeHref; ?>html.css" />
  </head>
  <body class="<?php if($q) echo 'search'; ?>">
    <header id="header">
      <h1>
        <a href="<?php echo $pot->baseHref; ?>">OBVIL, corpus critique</a>
      </h1>
      <!-- ??
      <menu class="bar">
        <li><a href="?">?</a></li>
      </menu>
      -->
      <form name="q" id="qForm" 
      style="float:right; margin-right:1em; margin-top:1em;
      " >
        <input name="q" id="q" name="search" size="25" accesskey="f" tabindex="1" title="Rechercher dans le corpus [alt-shift-f]" autocomplete="off" placeholder="Rechercher"
value="<?php echo str_replace('"', '&quot;', $q); ?>"/>
        <button name="go">&gt;</button>
      </form>
    </header>
    <div id="center">
      <nav id="breadcrumb">
        <?php 
        echo '<a href="',$pot->baseHref,'">OBVIL, corpus critique</a> » '; 
        if (isset($artBreadcrumb)) echo $artBreadcrumb; 
        ?>
      </nav>
      <article id="article">
      <?php

if (isset($artBody)) {
  echo $artBody;
}
// recherche, peut être réduite au livre courant (plus haut)
else if ($q) {
  // recherche juste dans un livre
  if(isset($bookId)) {
    $query=$pdo->prepare('SELECT article.breadcrumb, article.name, article.href, search.text, offsets(search) AS offsets FROM search, article WHERE article.book=? AND search.rowid=article.rowid AND search.text MATCH ? LIMIT 100; ') ;
    $query->execute(array($bookId, $q));
  }
  else {
    // on croise la table des articles avec la table full-text, la table full-text n'indexe pas certaines colonnes utiles à des tris (date, auteur)
    $query=$pdo->prepare('SELECT article.breadcrumb, article.name, article.href, search.text, offsets(search) AS offsets FROM search, article WHERE text MATCH ? AND search.rowid=article.rowid LIMIT 100; ') ;
    $query->execute(array($q));
  }
  $start=1;
  echo "\n",'<table class="search">';

  // TODO, join quote expressions 
  while ($row=$query->fetch()) {
    if($baseHref=='') $row['breadcrumb']=preg_replace('@"../@', '"', $row['breadcrumb']);
    echo "\n",'<tr><th colspan="3"><small>',$start++,'.</small> ',Teipot::reHref($row['breadcrumb']),'</td></tr>';
    $offsets=explode(' ',$row['offsets']);
    $count=count($offsets);
    // echo '<tr><td colspan="3"><pre style="white-space:pre-wrap; font-family:serif; ">',$row['text'],'</pre></td></tr>';
    $mark=1;
    for ($i=0; $i<$count;$i=$i+4) {
      echo Teipot::snip($row['text'], $offsets[$i+2], $offsets[$i+3], $baseHref.$row['href'].'?q='.$q.'#mark'.$mark),"\n";
      $mark++;
    }
  }
  echo "\n</table>";
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
// rediriger lien ?
if (isset($bookNav)) echo Teipot::reHref($bookNav);
?>
        </menu>
      </aside>
    </div>
    <footer id="footer">
      Prototype d'application TEI pour le corpus critique
    </footer>
    <script type="text/javascript"><?php echo implode("\n", $js); ?></script>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Tree.js">//</script>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Sortable.js">//</script>
  </body>
</html>