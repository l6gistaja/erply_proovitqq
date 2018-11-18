<html>
  <head>
    <title>Erply proovitöö</title>
    <meta http-equiv=Content-Type content="text/html; charset=UTF-8">
    <style></style>
  </head>
  <body>
<?php
  
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    session_start();
    include 'dependencies.php';
    $em = new ErplyManager(true);
    $em->process();

?>
    <form method="post">
    <input type="text" placeholder="Product name" name="name" value="<?= $em->response['data']['name'] ?>"/>
    <input type="submit" name="actionAddProdViaApi" value="Add via API"/>
    <input type="submit" name="actionAddProdViaRabbit" value="Add via Rabbit"/>
    <input type="submit" name="actionShowLog" value="View Rabbit Log"/>
    <input type="submit" name="actionDeleteLog" value="Delete Rabbit Log"/>
    <input type="submit" name="actionGetProductGroups" value="View product groups"/>
    <input type="submit" name="actionEndSession" value="End session"/>
    </form>
    <strong style="color:red"><?= $em->response['msg'] ?></strong>
<?php 

    if(isset($em->response['log'])) {
        print "<table border='1' cellpadding='5' style='border-collapse: collapse'><tr><th>Product</th><th>Status</th><th>Finished</th></tr>\n";
        foreach($em->response['log'] as $r) {
            print "<tr><td>".$r['name']."</td><td>"
                .ErplyManager::getErrorText($r, 'Saved')
                ."</td><td>".date('Y-m-d H:i:s',$r['t1'])."</td>\n";
        }
        print "<tr><th>Product</th><th>Status</th><th>Finished</th></tr></table>\n";
    
    }

    if($em->debug) {
        print "<hr/><strong>Debug Data</strong><pre>";
        print_r($em->response['debug']);
        print '</pre><br/><a href="https://learn-api.erply.com/error-codes" target="_blank">See error codes</a>; <strong>Session ID</strong>: '.$em->getSessionId();
    }

?>
  </body>
</html>
