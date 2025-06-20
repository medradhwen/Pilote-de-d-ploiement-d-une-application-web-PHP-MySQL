<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pilote de Déploiement Kubernetes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f4; color: #333; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0056b3; }
        .pod-info { font-size: 0.9em; color: #666; text-align: right; }
        .status { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .status.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>Pilote de Déploiement d'Application Web sur Kubernetes</h1>
    <p>Cette page tente de se connecter à une base de données MySQL pour afficher un message.</p>

    <?php
    // Afficher le nom du pod qui sert la requête
    $pod_hostname = gethostname();
    echo "<div class='pod-info'>Page servie par le pod : <strong>" . htmlspecialchars($pod_hostname) . "</strong></div>";

    // Récupérer les informations de connexion depuis les variables d'environnement
    // Ces variables sont définies dans le manifeste de déploiement Kubernetes (frontend-deployment.yaml)
    $db_host = getenv('DB_HOST');       // Le nom du service Kubernetes pour MySQL
    $db_user = getenv('DB_USER');       // L'utilisateur de la DB
    $db_pass = getenv('DB_PASSWORD');   // Le mot de passe (injecté via un Secret)
    $db_name = getenv('DB_NAME');       // Le nom de la base de données

    // Établir la connexion à la base de données
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Vérifier la connexion
    if ($conn->connect_error) {
        echo "<div class='status error'>";
        echo "<strong>Échec de la connexion à la base de données.</strong><br>";
        echo "Erreur : " . $conn->connect_error;
        echo "</div>";
    } else {
        echo "<div class='status success'>";
        echo "<strong>Connexion à la base de données (" . htmlspecialchars($db_name) . "@" . htmlspecialchars($db_host) . ") réussie !</strong>";
        echo "</div>";

        // Exécuter une requête SELECT
        $sql = "SELECT id, content, created_at FROM messages";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "<h2>Résultat de la requête <code>SELECT * FROM messages</code> :</h2>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Contenu du message</th><th>Date de création</th></tr>";
            // Afficher les données de chaque ligne
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . htmlspecialchars($row["content"]) . "</td>";
                echo "<td>" . $row["created_at"] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucun message trouvé dans la table 'messages' ou erreur de requête.</p>";
        }

        // Fermer la connexion
        $conn->close();
    }
    ?>

</div>

</body>
</html> 