<?php
    session_start();
    require("../includes/db.php");
    require("../includes/functions.php");


    $sql = "SELECT * from utilisateurs";
    $stmt = $pdo->query($sql);
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $utilisateur_connectee = null;
    if(isset($_SESSION["utilisateur_connectee"])){
        $utilisateur_connectee = $_SESSION["utilisateur_connectee"];
    }




    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../styles/profil.css">
</head>
<body>

    <div id="profil_connecte">
        <h2> utilisateur connecté </h2>
        <?php
            if($utilisateur_connectee){
                echo afficherProfil($utilisateur_connectee);
            }
        ?>
    </div>

    <div id="container_profils">
        <h2>autres utilisateurs</h2>
        <?php 
            for($i=0; $i<sizeof($utilisateurs); $i++){
                echo afficherProfil($utilisateurs[$i]);
            }
        ?>
    </div>
</body>
</html>
