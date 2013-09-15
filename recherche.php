<?php
header('Content-Type: text/html; charset=UTF-8');
$timeStart = microtime(true);
include (dirname(__FILE__).'/../lib/teipot/Teipot.php');
$pot=new Teipot(dirname(__FILE__).'/critique.sqlite', 'fr');


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Recherche</title>
    <link rel="stylesheet" type="text/css" href="../lib/teipot/html.css"/>
    <link rel="stylesheet" type="text/css" href="../lib/teipot/teipot.css"/>
  </head>
  <body>
    <form action="">
      <input name="q" class="text" value="<?php echo $pot->q; ?>"/>
      <div>de <input name="start" class="year" value="<?php echo $pot->start; ?>"/>à <input class="year" name="end" value="<?php echo $pot->end; ?>"/></div>
      <div>
      <?php 
echo $pot->byList();
      ?>
      </div>
      <button type="reset" onclick="return Form.reset(this.form)">Effacer</button>
      <button type="submit">Rechercher</button>
    </form>

      

    <?php
flush(); // send some content client before searching for docs from params
$timeStart = microtime(true);
$pot->search();
echo "<br/>",(microtime(true) - $timeStart)," s.\n";
$timeStart = microtime(true);
echo $pot->report();
// display a chrono from search results
echo $pot->chrono();
echo "<br/>",(microtime(true) - $timeStart)," s.\n";

// table d’auteurs
$timeStart = microtime(true);
echo $pot->biblio();
echo "<br/>",(microtime(true) - $timeStart)," s.\n";
// concordance
$timeStart = microtime(true);
echo $pot->conc();
echo "<br/>",(microtime(true) - $timeStart)," s.\n";


  ?>
    <script type="text/javascript" src="../lib/teipot/Form.js">//</script>
    <script type="text/javascript" src="../lib/teipot/Sortable.js">//</script>
  </body>
</html>
