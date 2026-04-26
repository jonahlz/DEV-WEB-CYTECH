<?php
    // $login_user = $_POST["login"];
    require("../includes/db.php");
    require("../includes/functions.php");
    if(
        isset($_POST["login"]                ) && 
        isset($_POST["nom"]                  ) &&
        isset($_POST["prenom"]               ) && 
        isset($_POST["email"]                ) && 
        isset($_POST["mot_de_passe"]         ) &&
        isset($_POST["role"]                 ) &&
        isset($_POST["confirm_mot_de_passe"] ) && 
        isset($_POST["sexe"]                 ) && 
        isset($_POST["date_naissance"]    ) 
        ){

            if($_POST["mot_de_passe"] != $_POST["confirm_mot_de_passe"] ){
                echo "les mots de passes ne sont pas compatibles";
                return;
            }

            $stmt = $pdo->prepare("INSERT INTO utilisateurs (login, nom, prenom, email, mot_de_passe, sexe, date_naissance) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $_POST['login'],
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['email'],
                password_hash( $mot_de_passe, PASSWORD_DEFAULT) ,
                $_POST['sexe'],
                $_POST['date_naissance']
            ]);
                    
        }else{
            echo "no";
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

    <h2>Créer un compte</h2>

    <label>Nom utilisateur *</label>
    <input type="text" name="login" >

    <label>Nom *</label>
    <input type="text" name="nom" >

    <label>Prénom *</label>
    <input type="text" name="prenom" >

    <label>Email *</label>
    <input type="email" name="email" >

    <label>Mot de passe *</label>
    <input type="password" name="mot_de_passe" >

    <label>Confirmer Mot de passe *</label>
    <input type="password" name="confirm_mot_de_passe" >

    <label>Rôle</label>
    <select name="role">
        <option value="visiteur">Visiteur</option>
        <option value="membre" selected>Membre</option>
    </select>

    <label>Sexe *</label>
    <select name="sexe">
        <option value="">--Choisir--</option>
        <option value="M">Homme</option>
        <option value="F">Femme</option>
        <option value="Autre">Autre</option>
    </select>

    <label>Date de naissance *</label>
    <input type="date" name="date_naissance">

    <!-- <label>Type de membre *</label>
    <input type="text" name="type_membre" value="habitant"> -->
        

    <button type="submit">S'inscrire</button>

    <p>Vous avez deja un compte ?  cliquez <a href='connection.php'> ici </a> </p>
</form>

</body>
</html>
