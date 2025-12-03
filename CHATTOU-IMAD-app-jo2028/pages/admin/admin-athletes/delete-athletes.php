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

// Vérifiez si l'ID de l'athlète est fourni dans l'URL
if (!isset($_GET['id_athlete'])) {
    $_SESSION['error'] = "ID de l'athlète manquant.";
    header("Location: manage-athletes.php");
    exit();
} else {
    $id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'athlète est un entier valide
    if ($id_athlete === false) {
        $_SESSION['error'] = "ID de l'athlète invalide.";
        header("Location: manage-athletes.php");
        exit();
    } else {
        try {
            // Vérifier d'abord si l'athlète est utilisé dans d'autres tables
            $checkQuery = "SELECT COUNT(*) as count FROM participer WHERE id_athlete = :param_id_athlete";
            $checkStatement = $connexion->prepare($checkQuery);
            $checkStatement->bindParam(':param_id_athlete', $id_athlete, PDO::PARAM_INT);
            $checkStatement->execute();
            $result = $checkStatement->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Impossible de supprimer cet athlète car il participe à " . $result['count'] . " épreuve(s).";
                header('Location: manage-athletes.php');
                exit();
            }

            // Récupérer le nom de l'athlète pour le message de succès
            $nameQuery = "SELECT nom_athlete, prenom_athlete FROM athlete WHERE id_athlete = :param_id_athlete";
            $nameStatement = $connexion->prepare($nameQuery);
            $nameStatement->bindParam(':param_id_athlete', $id_athlete, PDO::PARAM_INT);
            $nameStatement->execute();
            $athlete = $nameStatement->fetch(PDO::FETCH_ASSOC);
            $nom_complet = $athlete['prenom_athlete'] . ' ' . $athlete['nom_athlete'] ?? "l'athlète";

            // Préparez la requête SQL pour supprimer l'athlète
            $sql = "DELETE FROM athlete WHERE id_athlete = :param_id_athlete";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':param_id_athlete', $id_athlete, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "L'athlète '" . htmlspecialchars($nom_complet, ENT_QUOTES, 'UTF-8') . "' a été supprimé avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-athletes.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'athlète : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-athletes.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d' avoir activé l'option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>