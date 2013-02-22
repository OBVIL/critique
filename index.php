<?php
// afficher des chapitres d'une base TEIpub

$path='';
// si ça crie, adapter à la conf serveur
if (isset($_SERVER['PATH_INFO'])) {
  $path=ltrim($_SERVER['PATH_INFO'], '/');
}
$baseHref=str_repeat("../", substr_count($path, '/'));
if (file_exists($libDir=dirname(dirname(__FILE__)).'/lib/')) $themeHref=$baseHref.'../lib/theme/';
else if (file_exists($libDir=dirname(dirname(dirname(__FILE__)).'/lib/')))$themeHref=$baseHref.'../../lib/theme/';


$sqliteFile=dirname(__FILE__).'/critique.sqlite';
$pdo=new PDO("sqlite:".$sqliteFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
$branch=explode('/', $path);
if(count($branch)>0) $bookName=$branch[0];
if(count($branch)>1) $artName=$branch[1];

// télécharger un livre en epub
if (isset($bookName) && ($pos=strpos($bookName, '.epub'))) {
  $bookName=substr($bookName, 0, $pos);
  $sql =  'SELECT epub FROM book WHERE name= '.$pdo->quote($bookName);
  foreach  ($pdo->query($sql) as $row) {
    header("Content-Type: application/epub+zip");
    echo $row['epub'];
    exit;
  }
  // send 404
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
  if(isset($bookId)) {
    // perfs ? identifiant plus unique ?
    $sql =  'SELECT body,head,path FROM article WHERE book='.$bookId.' AND name= '.$pdo->quote($artName);
    foreach  ($pdo->query($sql) as $row) {
      $artBody=$row['body'];
      $artHead=$row['head'];
      $artPath=$row['path'];
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
  <body>
    <header id="header">
      <h1>
        <a href="<?php echo $baseHref; ?>">OBVIL, corpus critique</a>
      </h1>
      <!-- ??
      <menu class="bar">
        <li><a href="?">?</a></li>
      </menu>
      -->
    </header>
    <div id="center">
      <nav id="breadcrumb">
        <?php 
        echo '<a href="',$baseHref,'">OBVIL, corpus critique</a> » '; 
        if (isset($artPath)) echo $artPath; 
        ?>
      </nav>
      <article id="article">
      <?php 
if (isset($artBody)) {
  echo $artBody;
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
    echo '<tr>','<td>',$row['byline'],'</td>','<td>',$row['created'],'</td>','<td><a href="',$baseHref, $row['name'],'/">',$row['title'],'</a></td>','<td><a class="epub" href="',$baseHref, $row['name'],'.epub">epub</a></td>',"</th>\n";
  }
  echo "</table>\n";
}
      ?>
      </article>
      <aside id="aside">
        <menu>
<?php
// rediriger lien ?
if (isset($bookNav)) echo $bookNav;
?>
        </menu>
      </aside>
    </div>
    <footer id="footer">
      Prototype d'application TEI pour le corpus critique
    </footer>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Tree.js">//</script>
    <script type="text/javascript" src="<?php echo $themeHref; ?>Sortable.js">//</script>
  </body>
</html>
