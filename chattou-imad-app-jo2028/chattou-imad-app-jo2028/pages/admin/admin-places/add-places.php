<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_lieu = filter_input(INPUT_POST, 'nom_lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $adresse_lieu = filter_input(INPUT_POST, 'adresse_lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $cp_lieu = filter_input(INPUT_POST, 'cp_lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $ville_lieu = filter_input(INPUT_POST, 'ville_lieu', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-places.php");
        exit();
    }

    // Vérifiez si les champs sont vides
    if (empty($nom_lieu) || empty($adresse_lieu) || empty($cp_lieu) || empty($ville_lieu)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: add-places.php");
        exit();
    }

    try {
        // Vérifiez si le lieu existe déjà - CORRECTION: LIEU en majuscules
        $queryCheck = "SELECT id_lieu FROM LIEU WHERE nom_lieu = :param_nom_lieu";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_nom_lieu", $nom_lieu, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le lieu existe déjà.";
            header("Location: add-places.php");
            exit();
        } else {
            // Requête pour ajouter un lieu
            $query = "INSERT INTO LIEU (nom_lieu, adresse_lieu, cp_lieu, ville_lieu) 
                     VALUES (:param_nom_lieu, :param_adresse_lieu, :param_cp_lieu, :param_ville_lieu)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_nom_lieu", $nom_lieu, PDO::PARAM_STR);
            $statement->bindParam(":param_adresse_lieu", $adresse_lieu, PDO::PARAM_STR);
            $statement->bindParam(":param_cp_lieu", $cp_lieu, PDO::PARAM_STR);
            $statement->bindParam(":param_ville_lieu", $ville_lieu, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "Le lieu a été ajouté avec succès.";
                header("Location: manage-places.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du lieu.";
                header("Location: add-places.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: add-places.php");
        exit();
    }
}

// Afficher les erreurs en PHP
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Ajouter un Lieu - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="./manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Lieu</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="add-places.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ce lieu ?')">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <label for="nom_lieu">Nom du lieu :</label>
            <input type="text" name="nom_lieu" id="nom_lieu" required>

            <label for="adresse_lieu">Adresse :</label>
            <input type="text" name="adresse_lieu" id="adresse_lieu" required>

            <label for="cp_lieu">Code Postal :</label>
            <input type="text" name="cp_lieu" id="cp_lieu" required>

            <label for="ville_lieu">Ville :</label>
            <input type="text" name="ville_lieu" id="ville_lieu" required>

            <input type="submit" value="Ajouter le Lieu">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-places.php">Retour à la gestion des lieux</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>