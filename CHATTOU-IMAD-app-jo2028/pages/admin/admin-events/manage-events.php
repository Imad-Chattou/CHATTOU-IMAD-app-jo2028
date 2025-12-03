<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

$login = $_SESSION['login'];
$nom_utilisateur = $_SESSION['prenom_admin'];
$prenom_utilisateur = $_SESSION['nom_admin'];

// Fonction pour vérifier le token CSRF
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Token CSRF invalide.');
        }
    }
}

// Générer un token CSRF si ce n'est pas déjà fait
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF
}

// Gestion de la suppression directement dans manage-events.php
if (isset($_GET['delete_id'])) {
    require_once("../../../database/database.php");

    $id_event = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);

    if ($id_event !== false) {
        try {
            // CORRECTION : Utiliser id_epreuve au lieu de Id_epreuve
            $checkQuery = "SELECT COUNT(*) as count FROM participer WHERE id_epreuve = :id_event";
            $checkStatement = $connexion->prepare($checkQuery);
            $checkStatement->bindParam(':id_event', $id_event, PDO::PARAM_INT);
            $checkStatement->execute();
            $result = $checkStatement->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Impossible de supprimer cet événement car il est utilisé par " . $result['count'] . " participant(s).";
            } else {
                // Récupérer le titre de l'événement pour le message de succès
                $nameQuery = "SELECT title FROM events WHERE id_event = :id_event";
                $nameStatement = $connexion->prepare($nameQuery);
                $nameStatement->bindParam(':id_event', $id_event, PDO::PARAM_INT);
                $nameStatement->execute();
                $event = $nameStatement->fetch(PDO::FETCH_ASSOC);
                $title = $event['title'] ?? "l'événement";

                // Supprimer l'événement
                $sql = "DELETE FROM events WHERE id_event = :id_event";
                $statement = $connexion->prepare($sql);
                $statement->bindParam(':id_event', $id_event, PDO::PARAM_INT);
                $statement->execute();

                $_SESSION['success'] = "L'événement '$title' a été supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'événement : " . $e->getMessage();
        }
    }
    header('Location: manage-events.php');
    exit();
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
    <title>Gestion des Événements - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin.php">Accueil Administration</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Gestion des Événements</h1>
        
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

        <div class="action-buttons">
            <button onclick="openAddEventForm()">Ajouter un Événement</button>
        </div>
        
        <!-- Tableau des événements -->
        <?php
        require_once("../../../database/database.php");

        try {
            // Requête pour récupérer la liste des événements depuis la base de données
            $query = "SELECT * FROM events ORDER BY event_date, start_time";
            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table><tr><th>Titre</th><th>Sport</th><th>Date</th><th>Heure Début</th><th>Heure Fin</th><th>Lieu</th><th>Modifier</th><th>Supprimer</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['sport'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['event_date'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['start_time'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['end_time'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['venue'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td><button onclick='openModifyEventForm({$row['id_event']})'>Modifier</button></td>";
                    echo "<td><button onclick='deleteEventConfirmation({$row['id_event']})'>Supprimer</button></td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun événement trouvé.</p>";
            }
        } catch (PDOException $e) {
            echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../admin.php">Accueil administration</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

    <script>
        function openAddEventForm() {
            window.location.href = 'add-events.php';
        }

        function openModifyEventForm(id_event) {
            window.location.href = 'modify-events.php?id_event=' + id_event;
        }

        function deleteEventConfirmation(id_event) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cet événement?")) {
                window.location.href = 'manage-events.php?delete_id=' + id_event;
            }
        }
    </script>
</body>

</html>