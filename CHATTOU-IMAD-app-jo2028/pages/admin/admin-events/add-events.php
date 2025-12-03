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
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $sport = filter_input(INPUT_POST, 'sport', FILTER_SANITIZE_SPECIAL_CHARS);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue = filter_input(INPUT_POST, 'venue', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-events.php");
        exit();
    }

    // Vérifiez si les champs obligatoires sont vides
    if (empty($title) || empty($sport) || empty($event_date) || empty($start_time) || empty($end_time) || empty($venue)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: add-events.php");
        exit();
    }

    if ($start_time > $end_time) {
        $_SESSION['error'] = "L'heure de fin ne peut pas être avant l'heure de début.";
        header("Location: add-events.php");
        exit();
    }

    try {
        // Vérifiez si l'événement existe déjà
        $queryCheck = "SELECT id_event FROM events WHERE title = :param_title AND event_date = :param_event_date";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_title", $title, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_event_date", $event_date);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Un événement avec ce titre existe déjà à cette date.";
            header("Location: add-events.php");
            exit();
        } else {
            // Requête pour ajouter un événement
            $query = "INSERT INTO events (title, sport, event_date, start_time, end_time, venue, description) 
                     VALUES (:param_title, :param_sport, :param_event_date, :param_start_time, :param_end_time, :param_venue, :param_description)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_title", $title, PDO::PARAM_STR);
            $statement->bindParam(":param_sport", $sport, PDO::PARAM_STR);
            $statement->bindParam(":param_event_date", $event_date);
            $statement->bindParam(":param_start_time", $start_time);
            $statement->bindParam(":param_end_time", $end_time);
            $statement->bindParam(":param_venue", $venue, PDO::PARAM_STR);
            $statement->bindParam(":param_description", $description, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'événement a été ajouté avec succès.";
                header("Location: manage-events.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'événement.";
                header("Location: add-events.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-events.php");
        exit();
    }
}

// Récupérer la liste des sports pour les suggestions
try {
    $querySports = "SELECT nom_sport FROM sport ORDER BY nom_sport";
    $statementSports = $connexion->prepare($querySports);
    $statementSports->execute();
    $sports = $statementSports->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $sports = [];
}

// Afficher les erreurs en PHP (fonctionne à condition d' avoir activé l'option en local)
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
    <title>Ajouter un Événement - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Événement</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="add-events.php" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet événement ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="title">Titre de l'événement :</label>
            <input type="text" name="title" id="title" required>

            <label for="sport">Sport :</label>
            <input type="text" name="sport" id="sport" list="sports-list" required>
            <datalist id="sports-list">
                <?php foreach ($sports as $sport): ?>
                    <option value="<?= htmlspecialchars($sport, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
            </datalist>

            <label for="event_date">Date :</label>
            <input type="date" name="event_date" id="event_date" required>

            <label for="start_time">Heure de début :</label>
            <input type="time" name="start_time" id="start_time" required>

            <label for="end_time">Heure de fin :</label>
            <input type="time" name="end_time" id="end_time" required>

            <label for="venue">Lieu :</label>
            <input type="text" name="venue" id="venue" required>

            <label for="description">Description :</label>
            <textarea name="description" id="description" rows="4"></textarea>

            <input type="submit" value="Ajouter l'Événement">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion des événements</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>