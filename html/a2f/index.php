<?php
session_start();

// Connexion avec la bdd
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/connect_to_bdd.php';

// Si déjà connecté, rediriger sur page d'accueil
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/authentification.php';
if (isConnectedAsMember()) {
    header('Location: /');
    exit();
} else if (isConnectedAsPro()) {
    header('Location: /pro');
    exit();
}

// Controllers
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/membre_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/pro_prive_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/pro_public_controller.php';
$membreController = new MembreController();
$proPublicController = new ProPublicController();
$proPriveController = new ProPriveController();

// Savoir s'il y a un compte auquel se connecter via l'id_compte reçu dans $_SESSION
$stmt = $dbh->prepare("SELECT * FROM sae_db._compte WHERE id_compte = :id_compte AND totp_active = TRUE AND secret_totp IS NOT NULL");
$stmt->bindParam(':id_compte', $_SESSION['tmp_id_compte_a2f']);
$stmt->execute();
$user_found = $stmt->fetch(PDO::FETCH_ASSOC);
if (!isset($_SESSION['tmp_id_compte_a2f']) || empty($_SESSION['tmp_id_compte_a2f']) || !$user_found) {
    echo "Aucun compte lié à cette A2F";
}

// Pour générer et comparer les mdp TOTP
require dirname($_SERVER['DOCUMENT_ROOT']) . '/vendor/autoload.php';
use OTPHP\TOTP;

if (empty($_POST)) { ?>

    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- NOS FICHIERS -->
        <link rel="icon" href="/public/images/favicon.png">
        <link rel="stylesheet" href="/styles/style.css">
        <script src="https://kit.fontawesome.com/d815dd872f.js" crossorigin="anonymous"></script>

        <!-- TAILWIND -->
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

        <title>A2F - PACT</title>
    </head>

    <body class="h-screen bg-white p-4 overflow-hidden">
        <div class="h-full flex flex-col items-center justify-center">
            <div class="relative w-full max-w-96 h-fit flex flex-col items-center justify-center sm:w-96 m-auto">
                <!-- Logo de l'application -->
                <a href="/">
                    <img src="/public/icones/logo.svg" alt="Logo de TripEnArvor : Moine macareux" width="108">
                </a>

                <h2 class="mx-auto text-center text-2xl pt-4 my-4">Authentification TOTP</h2>

                <form class="bg-white w-full p-5 border-2 border-secondary" action="/a2f" method="POST">

                    <!-- Message d'information -->
                    <p class="text-sm">Consultez votre application d'authentification OTP pour connaître le TOTP à saisir.</p>

                    <br>

                    <!-- Champ pour le mot de passe -->
                    <label class="text-sm" for="mdp-totp">TOTP</label>
                    <div class="relative">
                        <input class="p-2 pr-12 bg-base100 w-full h-12 mb-1.5" type="password" id="mdp-totp" name="mdp-totp"
                            pattern="^(\d){6}$" title="Saisir votre mot de passe TOTP (6 chiffres)" value="" required>
                        <!-- Icône pour afficher/masquer le mot de passe -->
                        <i class="fa-regular fa-eye fa-lg absolute top-1/2 -translate-y-1/2 right-4 cursor-pointer"
                            id="togglePassword"></i>
                    </div>

                    <span id="error-message" class="error text-rouge-logo text-sm">
                        <?php echo $_SESSION['error_totp'] ?? '' ?>
                    </span>

                    <!-- Bouton de connexion -->
                    <input type="submit" value="Me connecter"
                        class="cursor-pointer w-full text-sm py-2 px-4 rounded-full h-12 my-1.5 bg-secondary text-white inline-flex items-center justify-center border border-transparent focus:scale-[0.97] hover:bg-orange-600 hover:border-orange-600 hover:text-white">

                </form>

            </div>
        </div>

        <script>
            // Récupération de l'élément pour afficher/masquer le mot de passe
            const togglePassword = document.getElementById('togglePassword');
            const mdp = document.getElementById('mdp');

            // Événement pour afficher le mot de passe lorsque l'utilisateur clique sur l'icône
            if (togglePassword) {
                togglePassword.addEventListener('click', function () {
                    if (mdp.type === 'password') {
                        mdp.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        mdp.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                });
            }
        </script>
    </body>

<?php } else {
    // 2ème étape : essayer de se connecter au compte (pro ou membre)
    try {
        // Vérifie si la requête est une soumission de formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Considérer le TOTP actuel et celui d'avant (30 sec en arrière)
            $totp = TOTP::createFromSecret($user_found['secret_totp']);
            $totps_must_have = [$totp->now()];
            array_push($totps_must_have, $totp->at(time() - 30));

            // Vérifier si celui saisi correspond
            if (in_array($_POST['mdp-totp'], $totps_must_have)) {
                // Alors on se connecte (avec le bon rôle)
                if ($membreController->getInfosMembre($_SESSION['tmp_id_compte_a2f'])) {
                    $_SESSION['id_membre'] = $_SESSION['tmp_id_compte_a2f'];
                    unset($_SESSION['tmp_id_compte_a2f'], $_SESSION['error_totp']);
                    header('Location: /');
                } else if ($proPublicController->getInfosProPublic($_SESSION['tmp_id_compte_a2f']) || $proPriveController->getInfosProPrive($_SESSION['tmp_id_compte_a2f'])) {
                    $_SESSION['id_pro'] = $_SESSION['tmp_id_compte_a2f'];
                    unset($_SESSION['tmp_id_compte_a2f'], $_SESSION['error_totp']);
                    header('Location: /pro');
                } else {
                    $_SESSION['error_totp'] = "Connexion impossible : votre identifiant de compte ne corresond à aucun compte dans la base de données";
                    header('Location: /a2f');
                }
            } else {
                $_SESSION['error_totp'] = 'TOTP invalide';
                header('Location: /a2f');
            }
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}