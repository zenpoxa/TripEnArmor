<?php
session_start();

if (isset($_GET['id_offre'])) {
    $_SESSION['id_offre'] = $_GET['id_offre'];
    header('Location: /offre');
} else {
    echo 'ERREUR : aucune offre sélectionnée';
}

exit();
