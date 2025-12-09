<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du lieu est fourni dans l'URL
if (!isset($_GET['id_lieu'])) {
    $_SESSION['error'] = "ID du lieu manquant.";
    header("Location: manage-places.php");
    exit();
}

$id_lieu = filter_input(INPUT_GET, 'id_lieu', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du lieu est un entier valide
if (!$id_lieu && $id_lieu !== 0) {
    $_SESSION['error'] = "ID du lieu invalide.";
    header("Location: manage-places.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du lieu pour affichage dans le formulaire
try {
    $queryLieu = "SELECT nom_lieu, adresse_lieu, cp_lieu, ville_lieu FROM LIEU WHERE id_lieu = :param_id_lieu";
    $statementLieu = $connexion->prepare($queryLieu);
    $statementLieu->bindParam(":param_id_lieu", $id_lieu, PDO::PARAM_INT);
    $statementLieu->execute();

    if ($statementLieu->rowCount() > 0) {
        $lieu = $statementLieu->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Lieu non trouvé.";
        header("Location: manage-places.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-places.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_lieu = filter_input(INPUT_POST, 'nom_lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $adresse_lieu = filter_input(INPUT_POST, 'adresse_lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $cp_lieu = filter_input(INPUT_POST, 'cp_lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $ville_lieu = filter_input(INPUT_POST, 'ville_lieu', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si les champs sont vides
    if (empty($nom_lieu) || empty($adresse_lieu) || empty($cp_lieu) || empty($ville_lieu)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: modify-places.php?id_lieu=$id_lieu");
        exit();
    }

    try {
        // Vérifiez si le lieu existe déjà
        $queryCheck = "SELECT id_lieu FROM LIEU WHERE nom_lieu = :param_nom_lieu AND id_lieu <> :param_id_lieu";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_nom_lieu", $nom_lieu, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_id_lieu", $id_lieu, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le lieu existe déjà.";
            header("Location: modify-places.php?id_lieu=$id_lieu");
            exit();
        }

        // Requête pour mettre à jour le lieu
        $query = "UPDATE LIEU SET nom_lieu = :param_nom_lieu, adresse_lieu = :param_adresse_lieu, 
                  cp_lieu = :param_cp_lieu, ville_lieu = :param_ville_lieu 
                  WHERE id_lieu = :param_id_lieu";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":param_nom_lieu", $nom_lieu, PDO::PARAM_STR);
        $statement->bindParam(":param_adresse_lieu", $adresse_lieu, PDO::PARAM_STR);
        $statement->bindParam(":param_cp_lieu", $cp_lieu, PDO::PARAM_STR);
        $statement->bindParam(":param_ville_lieu", $ville_lieu, PDO::PARAM_STR);
        $statement->bindParam(":param_id_lieu", $id_lieu, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le lieu a été modifié avec succès.";
            header("Location: manage-places.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du lieu.";
            header("Location: modify-places.php?id_lieu=$id_lieu");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-places.php?id_lieu=$id_lieu");
        exit();
    }
}
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
    <title>Modifier un Lieu - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Lieu</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
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

        <form action="modify-places.php?id_lieu=<?php echo $id_lieu; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce lieu?')">
            <label for="nom_lieu">Nom du lieu :</label>
            <input type="text" name="nom_lieu" id="nom_lieu" value="<?php echo htmlspecialchars($lieu['nom_lieu']); ?>"
                required>

            <label for="adresse_lieu">Adresse :</label>
            <input type="text" name="adresse_lieu" id="adresse_lieu"
                value="<?php echo htmlspecialchars($lieu['adresse_lieu']); ?>" required>

            <label for="cp_lieu">Code Postal :</label>
            <input type="text" name="cp_lieu" id="cp_lieu" value="<?php echo htmlspecialchars($lieu['cp_lieu']); ?>"
                required>

            <label for="ville_lieu">Ville :</label>
            <input type="text" name="ville_lieu" id="ville_lieu"
                value="<?php echo htmlspecialchars($lieu['ville_lieu']); ?>" required>

            <input type="submit" value="Modifier le Lieu">
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