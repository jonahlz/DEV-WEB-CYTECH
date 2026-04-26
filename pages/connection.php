<?php
    session_start();
    require("../includes/db.php");

    if( isset($_POST["login_ou_email"] ) && isset($_POST["mot_de_passe"]) ){

        $sql = "SELECT * FROM utilisateurs WHERE login = ? OR email = ?";
     
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST["login_ou_email"],
            $_POST["login_ou_email"],
        ]);

        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if($utilisateur &&  password_verify( $_POST["mot_de_passe"] , $utilisateur["mot_de_passe"]) ){
            $_SESSION["utilisateur_connectee"] = $utilisateur;
            header("Location: profiles.php");
        }else{
            print("utilisateur non trouvé");
        }


    }
  
    
    
    
        


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../styles/inscription.css"></link>
    
</head>
<body>

<form action="#" method="POST">

    <h2>Se connecter</h2>

    <label>Nom utilisateur ou Email *</label>
    <input type="text" name="login_ou_email" required>

 
    <label>Mot de passe *</label>
    <input type="password" name="mot_de_passe" required>

    <button type="submit">Se connecter</button>

    <p>Vous n'avez pas de compte ?  cliquez <a href='inscription.php'> ici</a> pour vous inscrire </p>

</form>

</body>
</html>
