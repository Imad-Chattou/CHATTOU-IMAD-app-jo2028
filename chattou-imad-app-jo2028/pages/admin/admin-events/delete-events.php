<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'événement est fourni dans l'URL
if (!isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'événement manquant.";
    header("Location: manage-events.php");
    exit();
}

$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_SANITIZE_NUMBER_INT);

// Vérification CSRF (optionnel pour les GET, mais recommandé pour les DELETE via POST)
// Si vous voulez plus de sécurité, changez la suppression en POST

try {
    // Préparez la requête SQL pour supprimer l'événement
    $sql = "DELETE FROM EPREUVE WHERE id_epreuve = :param_id_epreuve";
    $statement = $connexion->prepare($sql);
    $statement->bindParam(':param_id_epreuve', $id_epreuve, PDO::PARAM_INT);
    
    if ($statement->execute()) {
        $_SESSION['success'] = "L'événement a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'événement.";
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Redirigez vers la page de gestion des événements
header('Location: manage-events.php');
exit();

// Afficher les erreurs en PHP
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>