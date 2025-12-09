<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du pays est fourni dans l'URL
if (!isset($_GET['id_pays'])) {
    $_SESSION['error'] = "ID du pays manquant.";
    header("Location: manage-countries.php");
    exit();
} else {
    $id_pays = filter_input(INPUT_GET, 'id_pays', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID du pays est un entier valide
    if ($id_pays === false) {
        $_SESSION['error'] = "ID du pays invalide.";
        header("Location: manage-countries.php");
        exit();
    } else {
        try {
            // Vérifier d'abord si le pays est utilisé par des athlètes
            $checkQuery = "SELECT COUNT(*) as count FROM ATHLETE WHERE id_pays = :id_pays";
            $checkStatement = $connexion->prepare($checkQuery);
            $checkStatement->bindParam(':id_pays', $id_pays, PDO::PARAM_INT);
            $checkStatement->execute();
            $result = $checkStatement->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Impossible de supprimer ce pays car il est utilisé par " . $result['count'] . " athlète(s).";
                header('Location: manage-countries.php');
                exit();
            }

            // Récupérer le nom du pays pour le message de succès
            $nameQuery = "SELECT nom_pays FROM PAYS WHERE id_pays = :id_pays";
            $nameStatement = $connexion->prepare($nameQuery);
            $nameStatement->bindParam(':id_pays', $id_pays, PDO::PARAM_INT);
            $nameStatement->execute();
            $pays = $nameStatement->fetch(PDO::FETCH_ASSOC);
            $nom_pays = $pays['nom_pays'] ?? 'le pays';

            // Préparez la requête SQL pour supprimer le pays
            $sql = "DELETE FROM PAYS WHERE id_pays = :id_pays";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_pays', $id_pays, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "Le pays '$nom_pays' a été supprimé avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-countries.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du pays : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-countries.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d'avoir activé l'option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>