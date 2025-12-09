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

// Vérifiez si l'ID de l'utilisateur est fourni dans l'URL
if (!isset($_GET['id_admin'])) {
    $_SESSION['error'] = "ID de l'utilisateur manquant.";
    header("Location: manage-users.php");
    exit();
} else {
    $id_admin = filter_input(INPUT_GET, 'id_admin', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'utilisateur est un entier valide
    if ($id_admin === false) {
        $_SESSION['error'] = "ID de l'utilisateur invalide.";
        header("Location: manage-users.php");
        exit();
    } else {
        try {
            // Récupérer les informations de l'utilisateur avant suppression
            $queryInfo = "SELECT nom_admin, prenom_admin, login FROM administrateur WHERE id_admin = :param_id_admin";
            $statementInfo = $connexion->prepare($queryInfo);
            $statementInfo->bindParam(':param_id_admin', $id_admin, PDO::PARAM_INT);
            $statementInfo->execute();
            
            $userInfo = $statementInfo->fetch(PDO::FETCH_ASSOC);

            // Empêcher la suppression de son propre compte
            if ($_SESSION['login'] == $userInfo['login']) {
                $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
                header('Location: manage-users.php');
                exit();
            }

            // Préparez la requête SQL pour supprimer l'utilisateur
            $sql = "DELETE FROM ADMINISTRATEUR WHERE id_admin = :param_id_admin";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':param_id_admin', $id_admin, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            if ($userInfo) {
                $_SESSION['success'] = "L'utilisateur " . htmlspecialchars($userInfo['prenom_admin'] . ' ' . $userInfo['nom_admin']) . " a été supprimé avec succès.";
            } else {
                $_SESSION['success'] = "L'utilisateur a été supprimé avec succès.";
            }

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-users.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-users.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d' avoir activé l'option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>