<?php


/** 
 Deals with an sqlite base produced by Teipub.php
 Make it dynamic, we can imagine to have multiple bases to link on
 */
class Teipot {
  /** sqlite File  */
  private $sqliteFile;
  /** sqlite link */
  public $pdo;
  /** requested path */
  public $path;
  /** ../ */
  public $baseHref;
  /** Content-Type header */
  static $mime=array(
      "css"  => 'text/css; charset=UTF-8',
      "epub" => 'application/epub+zip',
      "html" => 'text/html; charset=UTF-8',
      "jpg"  => 'image/jpeg',
      "png"  => 'image/png',
      "xml"  => 'text/xml',
    );
  /** Generated messages */
  private static $msg=array(
    "authors"=>array("en"=>"Author(s)","fr"=>"Auteur(s)"),
    "date"=>array("en"=>"Date","fr"=>"Date"),
    "title"=>array("en"=>"Title","fr"=>"Titre"),
  );
  /** Default lang for messages */
  public $lang="en";
  
  /** Constructor, link on the base */
  public function __construct($sqliteFile, $lang="en", $path="") {
    if (!$path && isset($_SERVER['PATH_INFO'])) {
      $this->path=ltrim($_SERVER['PATH_INFO'], '/');
    }
    $this->baseHref=str_repeat("../", substr_count($this->path, '/'));
    if ($lang) {
      $this->lang=$lang;
      setlocale(LC_ALL, $lang.'.UTF-8');
    }
    $this->sqliteFile = $sqliteFile;
    $this->pdo=new PDO("sqlite:".$sqliteFile);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
  }
  /** Display a message */
  public function msg($key, $lang=false, $arg=array()) {
    if(!$lang) $lang=$this->lang;
    if (isset(self::$msg[$key]) && isset(self::$msg[$key][$lang])) $key=self::$msg[$key][$lang];
    if (!count($arg)) echo $key;
    else if (count($arg) > 2 ) printf($key, $arg[0], $arg[1], $arg[2]);
    else if (count($arg) > 1 ) printf($key, $arg[0], $arg[1]);
    else if (count($arg) > 0 ) printf($key, $arg[0]);
  }
  /** serve a static file */
  public function get($href=null) {
    if(!$href) $href=$this->path;
    if (!$href) return false;
    $query=$this->pdo->prepare('SELECT type, cont FROM file WHERE href = ? ') ;
    $query->execute(array($href));
    list($type, $cont)=$query->fetch();
    if (!$cont) return false;
    if (!$type) {
      $ext=pathinfo($dest, PATHINFO_EXTENSION);
      if (isset($mime[$ext])) $type=$mime[$ext];
    }
    if ($type) header("Content-Type: ".$type);
    echo $cont;
    exit;
  }
  /** Output table of all books */
  public function bib() {
    $query=$this->pdo->prepare('SELECT href, name FROM file WHERE book = ? ') ;
    echo '
  <table class="sortable">
    <tr><th>',$this->msg('authors'),'</th><th>',$this->msg('date'),'</th><th>',$this->msg('title'),'</th></tr>  
    ';
    $sql =  'SELECT id, name, byline, created, title FROM book ORDER BY byline, created';
    
    foreach  ($this->pdo->query($sql) as $row) {
      // '<th>','<a href="',$this->baseHref, $row['name'],'/">',$row['name'],'</a></th>',
      echo "\n<tr>",'<td class="byline">',$row['byline'],'</td>','<td class="created">',$row['created'],'</td>','<td class="title"><a href="',$this->baseHref, $row['name'],'/">',$row['title'],'</a></td>';
      $sep="\n".'<td class="formats" nowrap="nowrap">';
      $query->execute(array($row['id']));
      while($link=$query->fetch()) {
        echo $sep,'<a href="',$this->baseHref,$link['href'],'">',$link['name'],'</a>';
        $sep=", ";
      }
      if ($sep==", ")echo"</td>";
      echo "</tr>";
    }
    echo "</table>\n";
  }
  
  
  /** rewrite HTML links to add query links */
  static function reHref($html, $q=null) {
    if(!$q && !isset($_REQUEST['q'])) return $html;
    if(!$q) $q=$_REQUEST['q'];
    return preg_replace('@ href="([^"]+)"@', ' href="$1?q='.$q.'#mark1"', $html);
  }

  /** Résultats de recherche */
  function q($q, $bookId=null) {
    $docMax=100;
    // recherche juste dans un livre
    if(isset($bookId)) {
      $query=$this->pdo->prepare('SELECT COUNT(*) AS rowcount FROM search, article WHERE article.book=? AND search.rowid=article.rowid AND search.text MATCH ?');
      $query->execute(array($bookId, $q));
      list($rowcount)=$query->fetch();
      $query=$this->pdo->prepare('SELECT article.breadcrumb, article.name, article.href, search.text, offsets(search) AS offsets FROM search, article WHERE article.book=? AND search.rowid=article.rowid AND search.text MATCH ? LIMIT ?; ') ;
      $query->execute(array($bookId, $q, $docMax));
    }
    else {
      $query=$this->pdo->prepare('SELECT COUNT(*) AS rowcount FROM search WHERE text MATCH ? ') ;
      $query->execute(array($q));
      list($rowcount)=$query->fetch();
      // on croise la table des articles avec la table full-text, la table full-text n'indexe pas certaines colonnes utiles à des tris (date, auteur)
      $query=$this->pdo->prepare('SELECT article.breadcrumb, article.name, article.href, search.text, offsets(search) AS offsets FROM search, article WHERE text MATCH ? AND search.rowid=article.rowid LIMIT ?; ') ;
      $query->execute(array($q, $docMax));
    }
    $docnum=1;
    echo "\n",'<table width="100%" class="search">';
    if(!$rowcount) echo "\n",'<caption>Pas de résultats pour la recherche : ',$q,'</caption>';
    // TODO, join quote expressions 
    else { 
      if($rowcount > $docMax) echo "\n",'<caption>',$rowcount,' textes trouvés, affichage limité à ',$docMax,'. Votre recherche : ',$q,'</caption>';
      else echo "\n",'<caption>',$rowcount,' textes trouvés. Votre recherche : ',$q,'</caption>';
      while ($row=$query->fetch()) {
        $offsets=explode(' ',$row['offsets']);
        $count=count($offsets);
        $occ=floor($count/4);
        $more="";
        $occDisp=$occ;
        $occMax=50;
        if ($occ > $occMax) {
          $more='<tr><td/><td colspan="3" align="right">Plus de '.$occMax.' occurrences dans ce texte ('.$occ.'), vous les retrouvez toutes surlignées dans le <a href="'.$this->baseHref.$row['href'].'?q='.$q.'#mark1">texte complet</a></td></tr>';
          $count=$occMax*4;
          $occDisp=$occMax.'/'.$occ;
        }
        if($this->baseHref=='') $row['breadcrumb']=preg_replace('@"../@', '"', $row['breadcrumb']);
        echo "\n",'<tr><th class="num">',$docnum,'</th><th colspan="3">',Teipot::reHref($row['breadcrumb']),' (',$occDisp,' occ.)</td></tr>';
        // echo '<tr><td colspan="3"><pre style="white-space:pre-wrap; font-family:serif; ">',$row['text'],'</pre></td></tr>';
        $mark=1;
        for ($i=0; $i<$count;$i=$i+4) {
          echo '<tr class="snip"><td class="num">',$docnum,'.',$mark,'</td>',Teipot::snip($row['text'], $offsets[$i+2], $offsets[$i+3], $this->baseHref.$row['href'].'?q='.$q.'#mark'.$mark),"</tr>\n";
          $mark++;
        }
        $docnum++;
        echo $more;
      }
    }
    echo "\n</table>";
  }
  
  /**
   * Snip a sentence in plain-text according to a byte offset
   * Dependant of a formated text with one sentence by line
   */
  static function snip ($text, $offset, $size, $href) {
    // largeur max
    $width=300;
    $snip=array();
    $snip[]='<td align="right">';
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
    $snip[]='</td>';
    return implode('', $snip);
  }
}
?>