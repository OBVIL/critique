<?php


// afficher des items d'une base TEIpub
$sqliteFile=dirname(__FILE__).'/critique.sqlite';
$libDir=dirname(dirname(__FILE__)).'/lib/';
// si ça crie, adapter à la conf serveur
$path='';
if (isset($_SERVER['PATH_INFO'])) {
  $path=ltrim($_SERVER['PATH_INFO'], '/');
}
$q=false;
if (isset($_REQUEST['q']) && $_REQUEST['q']) $q=$_REQUEST['q'];

$baseHref=str_repeat("../", substr_count($path, '/'));
$themeHref=$baseHref.'../lib/theme/';
// javascript en fin de page ?
$js=array();

$pdo=new PDO("sqlite:".$sqliteFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
$branch=explode('/', $path);
if(count($branch)>0) $bookName=$branch[0];
if(count($branch)>1) $artName=$branch[1];


// téléchargement direct dans un autre format
if (!isset($bookName));
else if ($pos=strpos($bookName, '.epub')) {
  $bookName=substr($bookName, 0, $pos);
  $sql =  'SELECT epub FROM book WHERE name= '.$pdo->quote($bookName);
  foreach  ($pdo->query($sql) as $row) {
    header("Content-Type: application/epub+zip");
    echo $row['epub'];
    exit;
  }
  header("HTTP/1.0 404 Not Found");
  exit;
}
else if ($pos=strpos($bookName, '.txt')) {
  $bookName=substr($bookName, 0, $pos);
  $sql =  'SELECT txt FROM book WHERE name= '.$pdo->quote($bookName);
  foreach  ($pdo->query($sql) as $row) {
    header("Content-Type: text/plain;charset=utf-8");
    echo $row['txt'];
    exit;
  }
  header("HTTP/1.0 404 Not Found");
  exit;
}
else if ($pos=strpos($bookName, '.xml')) {
  $bookName=substr($bookName, 0, $pos);
  $sql =  'SELECT tei FROM book WHERE name= '.$pdo->quote($bookName);
  foreach  ($pdo->query($sql) as $row) {
    header("Content-Type: text/xml");
    echo $row['tei'];
    exit;
  }
  header("HTTP/1.0 404 Not Found");
  exit;
}
else if ($pos=strpos($bookName, '.html')) {
  $bookName=substr($bookName, 0, $pos);
  $sql =  'SELECT html FROM book WHERE name= '.$pdo->quote($bookName);
  foreach  ($pdo->query($sql) as $row) {
    header("Content-Type: text/html;charset=utf-8");
    echo $row['html'];
    exit;
  }
  header("HTTP/1.0 404 Not Found");
  exit;
}


// un livre ?
if (isset($bookName)) {
  $sql =  'SELECT id,nav FROM book WHERE name= '.$pdo->quote($bookName);
  foreach  ($pdo->query($sql) as $row) {
    $bookId=$row['id'];
    $bookNav=$row['nav'];
    break;
  }
  if (!isset($artName) || !$artName ) $artName='index';
  
  // recherche dans un livre, ne pas charger de chapitre à afficher
  if ($artName=='index' and $q);
  else if(isset($bookId) and isset($artName)) {
    // perfs ? identifiant plus unique ?
    $sql =  'SELECT id,body,head,breadcrumb FROM article WHERE book='.$bookId.' AND name= '.$pdo->quote($artName);
    foreach  ($pdo->query($sql) as $row) {
      $artBody=$row['body'];
      $artHead=$row['head'];
      $artBreadcrumb=$row['breadcrumb'];
      $artId=$row['id'];
      // pour surligner, récupérer les offsets (il y a correspondance avec le texte brut)
      if ($q) {
        // on croise la table des articles avec la table full-text, la table full-text n'indexe pas certaines colonnes utiles à des tris (date, auteur)
        // sur le fts3 sorbonne il faut d'abord faire le match avant de tester le rowid
        $query=$pdo->prepare('SELECT offsets(search) AS offsets FROM search WHERE   +rowid = '.$artId.' AND text MATCH ?') ;
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
        <a href="<?php echo $baseHref; ?>">OBVIL, corpus critique</a>
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
value="<?php echo str_replace('"', '&quot;', $q); ?>"
style="
    background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAQCAIAAABY/YLgAAAAJUlEQVQIHQXBsQEAAAjDoND/73UWdnerhmHVsDQZJrNWVg3Dqge6bgMe6bejNAAAAABJRU5ErkJggg==');
    background-position: left top;
    background-repeat: repeat-x;
border:1px #FFFFFF solid;;
padding:1px 1ex;
    "/>
        <button name="go">&gt;</button>
      </form>
    </header>
    <div id="center">
      <nav id="breadcrumb">
        <?php 
        echo '<a href="',$baseHref,'">OBVIL, corpus critique</a> » '; 
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
  echo '
<table class="sortable">
  <tr><th>Auteur(s)</th><th>Date</th><th>Titre</th></tr>  
  ';
  $sql =  'SELECT name, byline, created, title FROM book ORDER BY byline, created';
  foreach  ($pdo->query($sql) as $row) {
    // '<th>','<a href="',$baseHref, $row['name'],'/">',$row['name'],'</a></th>',
    echo '<tr>','<td>',$row['byline'],'</td>','<td>',$row['created'],'</td>','<td><a href="',$baseHref, $row['name'],'/">',$row['title'],'</a></td>',
      '<td><a class="epub" href="',$baseHref, $row['name'],'.epub">.epub</a></td>',
      '<td><a class="tei" href="',$baseHref, $row['name'],'.xml">.xml</a></td>',
      '<td><a class="txt" href="',$baseHref, $row['name'],'.txt">.txt</a></td>',
      '<td><a class="html" href="',$baseHref, $row['name'],'.html">.html</a></td>',
      "</th>\n";
  }
  echo "</table>\n";
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
<?php
/** Classe provisoire en cours de mise au point pour exploiter une base SQLite produite par Teipub */
class Teipot {
  // réécrire certains liens dans du html pour transmettre des paramètres de requêt
  static function reHref($html, $q=null) {
    if(!$q && !isset($_REQUEST['q'])) return $html;
    if(!$q) $q=$_REQUEST['q'];
    return preg_replace('@ href="([^"]+)"@', ' href="$1?q='.$q.'#mark1"', $html);
  }

  // découper les phrases dans le texte à partir de la position du terme trouvé
  // c'est du propre, à une phrase par ligne
  static function snip ($text, $offset, $size, $href) {
    // largeur max
    $width=300;
    $snip=array();
    $snip[]='<tr class="snip"><td align="right">';
    $start=$offset-$width;
    $length=$width;
    if($start < 0) {
      $start=0;
      $length=$offset-1;
    }
    if ($length) {
      $left=substr($text, $start, $length);
      // couper au dernier saut de ligne 
      if ($pos=strrpos($left, "\n")) $left=substr($left, $pos);
      // sinon couper au premier espace
      else if ($pos=strpos($left, ' ')) $left=substr($left, $pos-1);
      $snip[]=$left;
    }
    $snip[]='</td><td class="mark"><a href="';
    $snip[]=$href;
    $snip[]='">';
    $snip[]=substr($text, $offset, $size);
    $snip[]="</a></td><td>";
    $start=$offset+$size;
    $length=$width;
    $len=strlen($text);
    if ($start + $length - 1 > $len) $length=$len-$start;
    if($length) {
      $right=substr($text, $start, $length);
      // couper au premier saut de ligne 
      if ($pos=strpos($right, "\n")) $right=substr($right,0, $pos);
      // sinon couper au dernier espace
      else if ($pos=strrpos($right, ' ')) $left=substr($right, 0, $pos);
      $snip[]=$right;
    }
    $snip[]='</td></tr>';
    return implode('', $snip);
  }
}
