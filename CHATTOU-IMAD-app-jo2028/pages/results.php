<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/normalize.css">
    <link rel="stylesheet" href="../css/styles-computer.css">
    <link rel="stylesheet" href="../css/styles-responsive.css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <title>Résultats Officiels - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="./../index.php">Accueil</a></li>
                <li><a href="./sports.php">Sports</a></li>
                <li><a href="./events.php">Calendrier des épreuves</a></li>
                <li><a href="./results.php">Résultats</a></li>
                <li><a href="./login.php">Accès administrateur</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Résultats</h1>

        <?php
        require_once("../database/database.php");

        try {
            // Requête pour récupérer la liste des résultats depuis la base de données
            $query = "SELECT nom_athlete, prenom_athlete, nom_epreuve, resultat 
                      FROM participer 
                      INNER JOIN athlete ON participer.id_athlete = athlete.id_athlete
                      INNER JOIN epreuve ON participer.id_epreuve = epreuve.id_epreuve
                      ORDER BY nom_epreuve ASC";

            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table>";
                echo "<tr><th class='color'>Épreuve</th><th class='color'>Athlète</th><th class='color'>Performance</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['prenom_athlete'] . ' ' . $row['nom_athlete'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['resultat'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun résultat trouvé.</p>";
            }
        } catch (PDOException $e) {
            // Gestion d'erreur améliorée
            echo "<p style='color: red;'>Erreur : Impossible de récupérer les résultats. Veuillez réessayer plus tard.</p>";
            error_log("Erreur PDO : " . $e->getMessage()); // Log de l'erreur dans un fichier serveur pour débogage
        }

        // Définir le niveau d'affichage des erreurs (utile en phase de développement)
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../index.php">Retour Accueil</a>
        </p>

    </main>
    <footer>
        <figure>
            <img src="../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>