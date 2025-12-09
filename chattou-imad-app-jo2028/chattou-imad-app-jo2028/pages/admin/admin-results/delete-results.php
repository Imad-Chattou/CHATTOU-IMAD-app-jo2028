<?php
session_start();
require_once("../../../database/database.php");

// Protection CSRF
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header('Location: ../../../index.php');
        exit();
    }
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Génération du token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si les IDs sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "IDs de l'athlète et de l'épreuve manquants.";
    header("Location: manage-results.php");
    exit();
} else {
    $id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

    // Vérifiez si les IDs sont des entiers valides
    if ($id_athlete === false || $id_epreuve === false) {
        $_SESSION['error'] = "IDs de l'athlète ou de l'épreuve invalides.";
        header("Location: manage-results.php");
        exit();
    } else {
        try {
            // Récupérer les informations du résultat avant suppression
            $queryInfo = "SELECT a.prenom_athlete, a.nom_athlete, e.nom_epreuve 
                          FROM PARTICIPER p
                          JOIN ATHLETE a ON p.id_athlete = a.id_athlete
                          JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve
                          WHERE p.id_athlete = :param_id_athlete AND p.id_epreuve = :param_id_epreuve";
            $statementInfo = $connexion->prepare($queryInfo);
            $statementInfo->bindParam(':param_id_athlete', $id_athlete, PDO::PARAM_INT);
            $statementInfo->bindParam(':param_id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statementInfo->execute();

            $resultInfo = $statementInfo->fetch(PDO::FETCH_ASSOC);

            // Préparez la requête SQL pour supprimer le résultat
            $sql = "DELETE FROM PARTICIPER WHERE id_athlete = :param_id_athlete AND id_epreuve = :param_id_epreuve";
            // Exécutez la requête SQL avec les paramètres
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':param_id_athlete', $id_athlete, PDO::PARAM_INT);
            $statement->bindParam(':param_id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            if ($resultInfo) {
                $_SESSION['success'] = "Le résultat de " . htmlspecialchars($resultInfo['prenom_athlete'] . ' ' . $resultInfo['nom_athlete'], ENT_QUOTES, 'UTF-8') .
                    " pour l'épreuve '" . htmlspecialchars($resultInfo['nom_epreuve'], ENT_QUOTES, 'UTF-8') . "' a été supprimé avec succès.";
            } else {
                $_SESSION['success'] = "Le résultat a été supprimé avec succès.";
            }

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-results.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du résultat : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-results.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d' avoir activé l'option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>