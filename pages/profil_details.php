<?php

    require("../includes/db.php");
    require("../includes/functions.php");

    if(isset($_GET['id'])){
        $id_profil = $_GET['id'];
        $sql = "SELECT * FROM utilisateurs where id = ? ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_profil]);

        $user =  $stmt->fetch(PDO::FETCH_ASSOC);
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
    <div id="container_profil_detaille">
        <div>
            <?php
                echo afficherProfildetaille($user);
            ?>
        </div>
    </div>
</body>
</html>