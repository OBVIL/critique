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
flush(); // send some content now, search query maybe long
$pot->search();
$timeStart = microtime(true);
// line of results
echo $pot->report();
// chrono from search results
echo $pot->chrono();
// biblio from search results
echo $pot->biblio();
// concordance from search results
echo $pot->conc();


  ?>
    <script type="text/javascript" src="../lib/teipot/Form.js">//</script>
    <script type="text/javascript" src="../lib/teipot/Sortable.js">//</script>
  </body>
</html>
