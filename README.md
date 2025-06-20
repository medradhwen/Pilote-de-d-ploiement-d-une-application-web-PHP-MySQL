# Pilote de Déploiement d'une Application Web sur Kubernetes

Ce projet déploie une application web simple composée d'un frontend PHP et d'un backend MySQL sur un cluster Kubernetes local géré par Minikube.

Le but est de démontrer un déploiement web de base en respectant les bonnes pratiques de Kubernetes :
- **Haute disponibilité** pour le frontend (3 répliques).
- **Persistance des données** pour le backend (base de données).
- **Gestion de la configuration** et des **secrets**.
- **Exposition** de l'application à l'extérieur du cluster.

---

## Structure du Projet

```
.
├── backend
│   └── init.sql
├── frontend
│   ├── Dockerfile
│   └── index.php
├── frontend-deployment.yaml
├── mysql-deployment.yaml
└── README.md
```

---

## Instructions de Déploiement (Questions 5, 6 et 7)

Suivez ces étapes depuis un terminal à l'intérieur de votre VM Ubuntu.

### 5. Déploiement sur Minikube

#### a. Démarrer Minikube
Assurez-vous que Docker est en cours d'exécution, puis démarrez Minikube.

```bash
# Commande
minikube start --driver=docker
```
**Explication :** Cette commande initialise et démarre votre cluster Kubernetes local en utilisant Docker comme gestionnaire de conteneurs. Si c'est le premier lancement, Minikube téléchargera les images nécessaires.

**Résultat attendu :**
```
😄  minikube v1.25.2 on Ubuntu 20.04
✨  Using the docker driver based on existing profile.
👍  Starting control plane node minikube in cluster minikube
...
🏄  Done! kubectl is now configured to use "minikube" cluster and "default" namespace by default
```

#### b. Construire l'image Docker du Frontend
Pour que le cluster Minikube puisse utiliser une image Docker construite localement (sans la pousser vers un registre externe comme Docker Hub), nous devons nous connecter au daemon Docker interne de Minikube.

```bash
# Commande 1: Connecter le terminal au Docker de Minikube
eval $(minikube -p minikube docker-env)
```
**Explication :** Cette commande cruciale configure les variables d'environnement de votre shell (`DOCKER_HOST`, etc.) pour que votre client `docker` pointe vers le moteur Docker qui tourne *à l'intérieur* de la machine virtuelle Minikube, et non celui de votre VM Ubuntu. Toutes les commandes `docker` suivantes s'exécuteront dans l'environnement de Minikube.

```bash
# Commande 2: Naviguer vers le dossier frontend et construire l'image
cd frontend/
docker build -t mon-app-php:latest .
cd ..
```
**Explication :** Nous nous déplaçons dans le dossier `frontend` qui contient le `Dockerfile`, puis nous lançons la construction de l'image.
- `docker build` : La commande pour construire une image.
- `-t mon-app-php:latest` : Assigne un "tag" (une étiquette) à l'image. Notre `frontend-deployment.yaml` fait référence à ce nom (`mon-app-php`) et tag (`latest`).
- `.` : Indique que le contexte de build (l'emplacement du `Dockerfile` et des fichiers à copier) est le répertoire actuel.

**Résultat attendu :**
```
Sending build context to Docker daemon  4.096kB
Step 1/4 : FROM php:8.2-apache
...
Step 2/4 : RUN docker-php-ext-install mysqli
...
Step 3/4 : COPY index.php /var/www/html/
...
Step 4/4 : EXPOSE 80
...
Successfully built <image_id>
Successfully tagged mon-app-php:latest
```

#### c. Appliquer les manifestes Kubernetes
Nous allons maintenant déployer toutes nos ressources sur le cluster.

```bash
# Commande 1: Créer la ConfigMap pour le script d'initialisation SQL
kubectl create configmap mysql-initdb-config --from-file=backend/init.sql
```
**Explication :** Avant de déployer MySQL, nous devons créer la `ConfigMap` qui contient son script d'initialisation. Le manifeste `mysql-deployment.yaml` s'attend à ce que cette ConfigMap existe pour la monter en tant que volume.

**Résultat attendu :**
```
configmap/mysql-initdb-config created
```

```bash
# Commande 2: Appliquer les manifestes pour MySQL (backend)
kubectl apply -f mysql-deployment.yaml
```
**Explication :** Cette commande demande à Kubernetes de lire le fichier `mysql-deployment.yaml` et de créer ou mettre à jour toutes les ressources qui y sont définies (le Secret, le PersistentVolumeClaim, le Deployment et le Service pour MySQL).

**Résultat attendu :**
```
secret/mysql-secret created
persistentvolumeclaim/mysql-pvc created
deployment.apps/mysql-deployment created
service/mysql-service created
```

```bash
# Commande 3: Appliquer les manifestes pour PHP (frontend)
kubectl apply -f frontend-deployment.yaml
```
**Explication :** De même, cette commande crée le Deployment et le Service pour notre application PHP.

**Résultat attendu :**
```
deployment.apps/frontend-deployment created
service/frontend-service created
```

#### d. Vérifier le déploiement
Attendons quelques instants que les conteneurs démarrent, puis vérifions leur état.

```bash
# Commande
kubectl get pods -w
```
**Explication :** `kubectl get pods` liste tous les pods. L'option `-w` ("watch") maintient la commande active et affiche les changements d'état en temps réel. Attendez que tous les pods soient à l'état `Running`. Vous devriez voir 1 pod MySQL et 3 pods frontend. Appuyez sur `Ctrl+C` pour quitter.

**Résultat attendu :**
```
NAME                                   READY   STATUS    RESTARTS   AGE
frontend-deployment-5d4f87749f-abcde   1/1     Running   0          1m
frontend-deployment-5d4f87749f-fghij   1/1     Running   0          1m
frontend-deployment-5d4f87749f-klmno   1/1     Running   0          1m
mysql-deployment-6c8f58f4c5-pqrst      1/1     Running   0          2m
```

```bash
# Commande
kubectl get services
```
**Explication :** Cette commande liste les services. `mysql-service` aura une IP de type `ClusterIP` (interne au cluster), tandis que `frontend-service` sera de type `LoadBalancer`.

**Résultat attendu :**
```
NAME               TYPE           CLUSTER-IP      EXTERNAL-IP   PORT(S)        AGE
frontend-service   LoadBalancer   10.108.140.40   <pending>     80:31111/TCP   1m
kubernetes         ClusterIP      10.96.0.1       <none>        443/TCP        10m
mysql-service      ClusterIP      10.101.17.108   <none>        3306/TCP       2m
```

### 6. Accéder à l'application

Minikube fournit une commande simple pour ouvrir un tunnel vers le service de type `LoadBalancer`.

```bash
# Commande
minikube service frontend-service
```
**Explication :** Cette commande ouvre automatiquement l'URL de l'application dans le navigateur de votre VM (si elle a une interface graphique). Sur un serveur, elle créera un tunnel et affichera l'URL à utiliser (ex: `http://127.0.0.1:54321`). Vous pouvez ensuite accéder à cette URL depuis votre machine hôte si le réseau de la VM le permet.

**Résultat attendu :**
- Sur Ubuntu Desktop : Le navigateur s'ouvre sur la page de l'application.
- Sur Ubuntu Server :
  ```
  |-----------|------------------|-------------|---------------------------|
  | NAMESPACE |       NAME       | TARGET PORT |            URL            |
  |-----------|------------------|-------------|---------------------------|
  | default   | frontend-service |          80 | http://127.0.0.1:58229      |
  |-----------|------------------|-------------|---------------------------|
  🎉  Opening service default/frontend-service in default browser...
  ```

La page web devrait afficher un message de succès de connexion et le contenu de la table `messages`. Si vous rafraîchissez la page plusieurs fois, vous verrez le nom du pod (`Page servie par le pod : ...`) changer, ce qui démontre que le load balancing fonctionne entre les 3 répliques du frontend.

### 7. Nettoyage

Pour supprimer toutes les ressources créées par ce projet, vous pouvez utiliser les commandes `delete` avec les mêmes fichiers de manifeste.

```bash
# Commande
kubectl delete -f frontend-deployment.yaml
kubectl delete -f mysql-deployment.yaml
kubectl delete configmap mysql-initdb-config
```
**Explication :**
- `kubectl delete -f <fichier.yaml>` supprime toutes les ressources qui ont été créées à partir de ce fichier.
- Nous supprimons ensuite la `ConfigMap` manuellement, car elle a été créée manuellement.
- Le `PersistentVolumeClaim` et le `Secret` sont aussi supprimés car ils sont définis dans `mysql-deployment.yaml`.

**Résultat attendu :**
```
deployment.apps "frontend-deployment" deleted
service "frontend-service" deleted
secret "mysql-secret" deleted
persistentvolumeclaim "mysql-pvc" deleted
deployment.apps "mysql-deployment" deleted
service "mysql-service" deleted
configmap "mysql-initdb-config" deleted
```

Pour arrêter complètement le cluster Minikube :
```bash
minikube stop
``` 