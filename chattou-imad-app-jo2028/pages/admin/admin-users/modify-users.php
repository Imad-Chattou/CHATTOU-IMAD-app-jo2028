<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'utilisateur est fourni dans l'URL
if (!isset($_GET['id_admin'])) {
    $_SESSION['error'] = "ID de l'utilisateur manquant.";
    header("Location: manage-users.php");
    exit();
}

$id_admin = filter_input(INPUT_GET, 'id_admin', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'utilisateur est un entier valide
if (!$id_admin && $id_admin !== 0) {
    $_SESSION['error'] = "ID de l'utilisateur invalide.";
    header("Location: manage-users.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'utilisateur pour affichage dans le formulaire
try {
    $queryUser = "SELECT nom_admin, prenom_admin, login FROM administrateur WHERE id_admin = :param_id_admin";
    $statementUser = $connexion->prepare($queryUser);
    $statementUser->bindParam(":param_id_admin", $id_admin, PDO::PARAM_INT);
    $statementUser->execute();

    if ($statementUser->rowCount() > 0) {
        $user = $statementUser->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-users.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_admin = filter_input(INPUT_POST, 'nom_admin', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenom_admin = filter_input(INPUT_POST, 'prenom_admin', FILTER_SANITIZE_SPECIAL_CHARS);
    $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];

    // Vérifiez si les champs obligatoires sont vides
    if (empty($nom_admin) || empty($prenom_admin) || empty($login)) {
        $_SESSION['error'] = "Les champs nom, prénom et login sont obligatoires.";
        header("Location: modify-users.php?id_admin=$id_admin");
        exit();
    }

    try {
        // Vérifiez si le login existe déjà pour un autre utilisateur
        $queryCheck = "SELECT id_admin FROM ADMINISTRATEUR WHERE login = :param_login AND id_admin <> :param_id_admin";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_login", $login, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_id_admin", $id_admin, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le login existe déjà pour un autre utilisateur.";
            header("Location: modify-users.php?id_admin=$id_admin");
            exit();
        }

        // Construction de la requête de mise à jour
        if (!empty($password)) {
            // Si un nouveau mot de passe est fourni
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE ADMINISTRATEUR SET nom_admin = :param_nom_admin, prenom_admin = :param_prenom_admin, login = :param_login, password = :param_password WHERE id_admin = :param_id_admin";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_password", $hashed_password, PDO::PARAM_STR);
        } else {
            // Si aucun nouveau mot de passe n'est fourni
            $query = "UPDATE ADMINISTRATEUR SET nom_admin = :param_nom_admin, prenom_admin = :param_prenom_admin, login = :param_login WHERE id_admin = :param_id_admin";
            $statement = $connexion->prepare($query);
        }

        $statement->bindParam(":param_nom_admin", $nom_admin, PDO::PARAM_STR);
        $statement->bindParam(":param_prenom_admin", $prenom_admin, PDO::PARAM_STR);
        $statement->bindParam(":param_login", $login, PDO::PARAM_STR);
        $statement->bindParam(":param_id_admin", $id_admin, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'utilisateur a été modifié avec succès.";
            header("Location: manage-users.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur.";
            header("Location: modify-users.php?id_admin=$id_admin");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-users.php?id_admin=$id_admin");
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
    <title>Modifier un Utilisateur - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="./admin.php">Gestion Administrateurs</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Utilisateur</h1>

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

        <form action="modify-users.php?id_admin=<?php echo $id_admin; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet utilisateur?')">
            <label for="nom_admin">Nom :</label>
            <input type="text" name="nom_admin" id="nom_admin"
                value="<?php echo htmlspecialchars($user['nom_admin']); ?>" required>

            <label for="prenom_admin">Prénom :</label>
            <input type="text" name="prenom_admin" id="prenom_admin"
                value="<?php echo htmlspecialchars($user['prenom_admin']); ?>" required>

            <label for="login">Login :</label>
            <input type="text" name="login" id="login"
                value="<?php echo htmlspecialchars($user['login']); ?>" required>

            <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer) :</label>
            <input type="password" name="password" id="password">

            <input type="submit" value="Modifier l'Utilisateur">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion des utilisateurs</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>