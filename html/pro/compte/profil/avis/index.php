<?php
session_start();
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/authentification.php';
$pro = verifyPro();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image" href="/public/images/favicon.png">
    <link rel="stylesheet" href="/styles/input.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/styles/config.js"></script>
    <script type="module" src="/scripts/main.js" defer></script>
    <script src="https://kit.fontawesome.com/d815dd872f.js" crossorigin="anonymous"></script>

    <title>Mes avis - Professionnel - PACT</title>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Inclusion du menu -->
    <div id="menu-pro">
        <?php
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/html/../view/menu-pro.php';
        ?>
    </div>

    <!-- Inclusion du header -->
    <?php
    include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/html/../view/header-pro.php';
    ?>

    <main class="flex flex-col justify-center grow md:w-full mt-0 m-auto max-w-[1280px] p-2">
        <p class="text-h3 p-4">
            <a href="/pro/compte">Mon compte</a>
            >
            <a href="/pro/compte/profil">Profil</a>
            >
            <a href="/pro/compte/profil/avis" class="underline">Avis</a>
        </p>

        <hr class="mb-8">

        <div class="flex justify-between items-center">
            <p class="text-h1">Mes avis</p>

            <a class="cursor-pointer flex items-center gap-2 hover:text-primary duration-100" id="sort-button">
                <i class="text xl fa-solid fa-sort"></i>
                <p>Trier par</p>
            </a>
        </div>

        <div class="hidden relative" id="sort-section">
            <div
                class="absolute top-0 right-0 z-20 self-end bg-white border border-base200 rounded-lg shadow-md max-w-48 p-2 flex flex-col gap-4">
                <a href="<?php echo (isset($_GET['sort']) && $_GET['sort'] === 'date-ascending') ? '/pro/compte/profil/avis' : '?sort=date-ascending'; ?>"
                    class="flex items-center <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date-ascending') ? 'font-bold' : ''; ?> hover:text-primary duration-100">
                    <p>Plus récent au plus ancien</p>
                </a>
                <a href="<?php echo (isset($_GET['sort']) && $_GET['sort'] === 'date-descending') ? '/pro/compte/profil/avis' : '?sort=date-descending'; ?>"
                    class="flex items-center <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date-descending') ? 'font-bold' : ''; ?> hover:text-primary duration-100">
                    <p>Plus ancien au plus récent</p>
                </a>
            </div>
        </div>

        <div class="grow flex flex-col gap-4 mt-4">
            <?php
            // Afficher tous les avis du professionnel
            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/avis_controller.php';
            $avisController = new AvisController();
            $tousMesAvis = $avisController->getAvisByIdPro($pro['id_compte']);

            if (isset($_GET['sort']) && $_GET['sort'] === 'date-ascending') {
                usort($tousMesAvis, function ($a, $b) {
                    return strtotime($a['date_publication']) - strtotime($b['date_publication']);
                });
            } else if (isset($_GET['sort']) && $_GET['sort'] === 'date-descending') {
                usort($tousMesAvis, function ($a, $b) {
                    return strtotime($b['date_publication']) - strtotime($a['date_publication']);
                });
            }

            if ($tousMesAvis) {
                foreach ($tousMesAvis as $avis) {
                    $id_avis = $avis['id_avis'];
                    $id_membre = $avis['id_membre'];
                    ?>

                    <div id="clickable_div_<?php echo $id_avis ?>" class="shadow-lg hover:cursor-pointer">
                        <?php
                        include dirname($_SERVER['DOCUMENT_ROOT']) . '/view/avis_view.php';
                        ?>
                    </div>

                    <script>
                        document.querySelector('#clickable_div_<?php echo $id_avis ?>')?.addEventListener('click', function () {
                            window.location.href = '/scripts/go_to_details.php?id_offre=<?php echo $avis['id_offre'] ?>';
                        });
                    </script>

                    <?php
                }
            } else {
                ?>
                <h1 class="text-h2 font-bold">Aucun avis n'a été publié sur vos offres.</h1>
                <?php
            }
            ?>
        </div>

    </main>

    <!-- FOOTER -->
    <?php
    include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/html/../view/footer-pro.php';
    ?>
</body>

</html>

<script>
    // Fonction pour configurer un bouton qui affiche ou masque une section
    function setupToggle(buttonId, sectionId) {
        const button = document.getElementById(buttonId); // Bouton pour activer/désactiver
        const section = document.getElementById(sectionId); // Section à afficher/masquer

        if (button && section) { // Vérification que les éléments existent
            button.addEventListener('click', function (event) {
                event.preventDefault(); // Empêche le comportement par défaut du lien
                section.classList.toggle('hidden'); // Alterne la visibilité de la section
            });

            // Fermer la section si l'utilisateur clique en dehors
            document.addEventListener('click', function (event) {
                if (!section.contains(event.target) && !button.contains(event.target)) {
                    section.classList.add('hidden'); // Cache la section si clic ailleurs
                }
            });
        }
    }

    // Initialisation du toggle pour le bouton et la section
    setupToggle('sort-button', 'sort-section');
</script>