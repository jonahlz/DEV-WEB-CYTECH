<?php


    
    function afficherProfil($profil){
        $ajd = new DateTime();
        $age = $ajd->diff( new DateTime($profil['date_naissance']))->y;
        return  "
                <a href='./profil_details?id={$profil['id']}'>
                    <div class='profil'>
                        <h1> {$profil['nom']} ({$profil['login']})</h1>
                        <img src='https://randomuser.me/api/portraits/men/75.jpg' alt=''>
                        <p class='email'>{$profil['email']}</p>
                        <p>{$age} ans</p>
                        <p>Membre depuis {$profil['created_at']}</p>
                    </div>
                </a>
                ";
    }

    function afficherProfildetaille($profil){
            $ajd = new DateTime();
            $age = $ajd->diff( new DateTime($profil['date_naissance']))->y;
        
            return  "
                    <div class='profil'>
                        <h1> {$profil['prenom']} ({$profil['login']})</h1>
                        <img src='https://randomuser.me/api/portraits/men/75.jpg' alt=''>
                        <p class='email'>{$profil['email']}</p>
                        <p>{$age} ans</p>
                        <p>{$profil['role']} </p>
                        <p>sexe : {$profil['sexe']}</p>
                        <p>Membre depuis {$profil['created_at']}</p>
                    </div>
                    ";
    }


    function envoyerEmailDeVerification($a){
        require '../PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
        require '../PHPMailer-master/PHPMailer-master/src/SMTP.php';
        require '../PHPMailer-master/PHPMailer-master/src/Exception.php';


        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;


        $mail = new PHPMailer(true);

        $token = bin2hex(random_bytes(32));
        $lien = "http://localhost/aida/pages/verification.php?token=" . $token;

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            
            $mail->Username   = 'mariebah574@gmail.com';
            $mail->Password   = 'duqj zohc fapz lrhu';
            
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('mariebah574@gmail.com', 'Lumiere');
            $mail->addAddress($a);

            $mail->isHTML(true);
            $mail->Subject = 'Confirm your account';

            $mail->Body = $mail->Body = "
                <div style='font-family: Arial; text-align:center;'>

                    <h2>Welcome Aida </h2>

                    <img src='https://cdn-icons-png.flaticon.com/512/295/295128.png' width='100'>

                    <p>Cliquez ici pour confimer votre compte!</p>

                    <a href='localhost' 
                        style='display:inline-block;
                            padding:12px 20px;
                            background:#28a745;
                            color:white;
                            text-decoration:none;
                            border-radius:5px;'>
                        Confirmer le compte
                    </a>

                    <p style='margin-top:20px;'>Si vous n'avez pas créé ce compte, ignorez simplemet cet email</p>

                </div>
                ";

            $mail->send();
            echo "Email envoyé!";
            
        } catch (Exception $e) {
            echo "Error: {$mail->ErrorInfo}";
        }
    }