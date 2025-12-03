<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'événement est fourni dans l'URL
if (!isset($_GET['id_event'])) {
    $_SESSION['error'] = "ID de l'événement manquant.";
    header("Location: manage-events.php");
    exit();
}

$id_event = filter_input(INPUT_GET, 'id_event', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'événement est un entier valide
if (!$id_event && $id_event !== 0) {
    $_SESSION['error'] = "ID de l'événement invalide.";
    header("Location: manage-events.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'événement pour affichage dans le formulaire
try {
    $queryEvent = "SELECT title, sport, event_date, start_time, end_time, venue, description FROM events WHERE id_event = :param_id_event";
    $statementEvent = $connexion->prepare($queryEvent);
    $statementEvent->bindParam(":param_id_event", $id_event, PDO::PARAM_INT);
    $statementEvent->execute();

    if ($statementEvent->rowCount() > 0) {
        $event = $statementEvent->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Événement non trouvé.";
        header("Location: manage-events.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-events.php");
    exit();
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

    // Vérifiez si les champs obligatoires sont vides
    if (empty($title) || empty($sport) || empty($event_date) || empty($start_time) || empty($end_time) || empty($venue)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: modify-events.php?id_event=$id_event");
        exit();
    }

    if ($start_time > $end_time) {
        $_SESSION['error'] = "L'heure de fin ne peut pas être avant l'heure de début.";
        header("Location: modify-events.php?id_event=$id_event");
        exit();
    }

    try {
        // Vérifiez si l'événement existe déjà
        $queryCheck = "SELECT id_event FROM events WHERE title = :param_title AND event_date = :param_event_date AND id_event <> :param_id_event";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_title", $title, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_event_date", $event_date);
        $statementCheck->bindParam(":param_id_event", $id_event, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'événement existe déjà.";
            header("Location: modify-events.php?id_event=$id_event");
            exit();
        }

        // Requête pour mettre à jour l'événement
        $query = "UPDATE events SET title = :param_title, sport = :param_sport, event_date = :param_event_date, 
                 start_time = :param_start_time, end_time = :param_end_time, venue = :param_venue, description = :param_description 
                 WHERE id_event = :param_id_event";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":param_title", $title, PDO::PARAM_STR);
        $statement->bindParam(":param_sport", $sport, PDO::PARAM_STR);
        $statement->bindParam(":param_event_date", $event_date);
        $statement->bindParam(":param_start_time", $start_time);
        $statement->bindParam(":param_end_time", $end_time);
        $statement->bindParam(":param_venue", $venue, PDO::PARAM_STR);
        $statement->bindParam(":param_description", $description, PDO::PARAM_STR);
        $statement->bindParam(":param_id_event", $id_event, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'événement a été modifié avec succès.";
            header("Location: manage-events.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'événement.";
            header("Location: modify-events.php?id_event=$id_event");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-events.php?id_event=$id_event");
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
    <title>Modifier un Événement - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Événement</h1>

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

        <form action="modify-events.php?id_event=<?php echo $id_event; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet événement?')">
            <label for="title">Titre de l'événement :</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event['title']); ?>"
                required>

            <label for="sport">Sport :</label>
            <input type="text" name="sport" id="sport" value="<?php echo htmlspecialchars($event['sport']); ?>"
                list="sports-list" required>
            <datalist id="sports-list">
                <?php foreach ($sports as $sport): ?>
                    <option value="<?= htmlspecialchars($sport) ?>">
                    <?php endforeach; ?>
            </datalist>

            <label for="event_date">Date :</label>
            <input type="date" name="event_date" id="event_date" value="<?php echo $event['event_date']; ?>" required>

            <label for="start_time">Heure de début :</label>
            <input type="time" name="start_time" id="start_time" value="<?php echo $event['start_time']; ?>" required>

            <label for="end_time">Heure de fin :</label>
            <input type="time" name="end_time" id="end_time" value="<?php echo $event['end_time']; ?>" required>

            <label for="venue">Lieu :</label>
            <input type="text" name="venue" id="venue" value="<?php echo htmlspecialchars($event['venue']); ?>"
                required>

            <label for="description">Description :</label>
            <textarea name="description" id="description"
                rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>

            <input type="submit" value="Modifier l'Événement">
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