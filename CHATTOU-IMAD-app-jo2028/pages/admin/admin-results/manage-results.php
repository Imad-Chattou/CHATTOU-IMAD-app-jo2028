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

require_once("../../../database/database.php");

// LISTER TOUS LES RÉSULTATS
try {
    $query = "SELECT p.*, a.nom_athlete, a.prenom_athlete, e.nom_epreuve 
              FROM PARTICIPER p
              INNER JOIN ATHLETE a ON p.id_athlete = a.id_athlete
              INNER JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve
              ORDER BY e.nom_epreuve, a.nom_athlete";

    $statement = $connexion->prepare($query);
    $statement->execute();
    $resultats = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $resultats = [];
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
    <title>Gestion des Résultats - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Gestion des Résultats</h1>
        
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

        <div class="action-buttons">
            <button onclick="openAddResultForm()">Ajouter un Résultat</button>
        </div>

        <!-- Tableau des résultats -->
        <?php
        if (!empty($resultats)) {
            echo "<table>
                    <tr>
                        <th>Épreuve</th>
                        <th>Athlète</th>
                        <th>Résultat</th>
                        <th>Modifier</th>
                        <th>Supprimer</th>
                    </tr>";

            foreach ($resultats as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['nom_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['prenom_athlete'] . ' ' . $row['nom_athlete'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['resultat'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td><button onclick='openModifyResultForm({$row['id_athlete']}, {$row['id_epreuve']})'>Modifier</button></td>";
                echo "<td><button onclick='deleteResultConfirmation({$row['id_athlete']}, {$row['id_epreuve']})'>Supprimer</button></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucun résultat trouvé.</p>";
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
        function openAddResultForm() {
            window.location.href = 'add-results.php';
        }

        function openModifyResultForm(id_athlete, id_epreuve) {
            window.location.href = 'modify-results.php?id_athlete=' + id_athlete + '&id_epreuve=' + id_epreuve;
        }

        function deleteResultConfirmation(id_athlete, id_epreuve) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce résultat?")) {
                window.location.href = 'delete-results.php?id_athlete=' + id_athlete + '&id_epreuve=' + id_epreuve;
            }
        }
    </script>
</body>
</html>