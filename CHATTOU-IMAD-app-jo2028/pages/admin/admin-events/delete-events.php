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

// Vérifiez si l'ID de l'événement est fourni dans l'URL
if (!isset($_GET['id_event'])) {
    $_SESSION['error'] = "ID de l'événement manquant.";
    header("Location: manage-events.php");
    exit();
} else {
    $id_event = filter_input(INPUT_GET, 'id_event', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'événement est un entier valide
    if ($id_event === false) {
        $_SESSION['error'] = "ID de l'événement invalide.";
        header("Location: manage-events.php");
        exit();
    } else {
        try {
            // Vérifier d'abord si l'événement est utilisé dans d'autres tables
            $checkQuery = "SELECT COUNT(*) as count FROM participer WHERE id_event = :param_id_event";
            $checkStatement = $connexion->prepare($checkQuery);
            $checkStatement->bindParam(':param_id_event', $id_event, PDO::PARAM_INT);
            $checkStatement->execute();
            $result = $checkStatement->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Impossible de supprimer cet événement car il est utilisé par " . $result['count'] . " participation(s).";
                header('Location: manage-events.php');
                exit();
            }

            // Récupérer le nom de l'événement pour le message de succès
            $nameQuery = "SELECT nom_event FROM events WHERE id_event = :param_id_event";
            $nameStatement = $connexion->prepare($nameQuery);
            $nameStatement->bindParam(':param_id_event', $id_event, PDO::PARAM_INT);
            $nameStatement->execute();
            $event = $nameStatement->fetch(PDO::FETCH_ASSOC);
            $nom_event = $event['nom_event'] ?? "l'événement";

            // Préparez la requête SQL pour supprimer l'événement
            $sql = "DELETE FROM events WHERE id_event = :param_id_event";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':param_id_event', $id_event, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "L'événement '" . htmlspecialchars($nom_event, ENT_QUOTES, 'UTF-8') . "' a été supprimé avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-events.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'événement : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-events.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d' avoir activé l'option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>