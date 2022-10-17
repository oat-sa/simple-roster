-   [Introduction](#Introduction)
-   [Conditions préalables](#Conditions-préalables)
-   [Étapes de l'installation](#Étapes-de-linstallation)
-   [Variables d'environnement](#Variables-denvironnement)
-   [Configuration globale de LTI](#Configuration-globale-de-LTI)
-   [LTI 1.1.1](#LTI-111)
    -   [Stratégie d'équilibrage de la charge LTI](#Stratégie-déquilibrage-de-la-charge-LTI)
-   [LTI 1.3.0](#LTI-130)
-   [Commandes CLI](#Commandes-CLI)
    -   [Ingestion de données](#Ingestion-de-données)
        -   [Ingestion de l'instance LTI](#Ingestion-de-linstance-LTI)
        -   [Ingestion de postes](#Ingestion-de-postes)
        -   [Ingestion des assignations](#Ingestion-des-assignations)
        -   [Ingestion par les utilisateurs](#Ingestion-par-les-utilisateurs)
    -   [Réchauffement du cache](#Réchauffement-du-cache)
        -   [Réchauffement du cache des postes](#Réchauffement-du-cache-des-postes)
        -   [Réchauffement du cache de l'instance LTI](#Réchauffement-du-cache-de-linstance-LTI)
        -   [Réchauffement du cache de l'utilisateur](#Réchauffement-du-cache-de-lutilisateur)
    -   [Collecteur d'ordures - Assignations](#Collecteur-dordures---Assignations)
    -   [Modifier les dates des postes](#Modifier-les-dates-des-postes)
    -   [Modifier l'état des postes](#Modifier-létat-des-postes)
        -   [Principaux arguments](#Principaux-arguments)

Introduction
============

> Service back-end REST visant à imiter une version simplifiée de la spécification OneRoster IMS.

`OneRoster` répond aux besoins des établissements scolaires en matière d'échange sécurisé et fiable d'informations sur les listes, les supports de cours et les notes entre systèmes. OneRoster prend en charge le mode d'échange .csv couramment utilisé et le dernier mode de services web en temps réel connu sous le nom de REST.

Pour en savoir plus sur `OneRoster`, veuillez consulter la spécification officielle à [IMS Global](https://www.imsglobal.org/activity/onerosterlis).

Conditions préalables
=====================

*   [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

*   [Docker](https://docs.docker.com/get-docker/) >= 19.03

*   [Docker-compose](https://docs.docker.com/compose/install/) >= 1.25


Si vous souhaitez installer cette application directement sur un serveur sans docker et docker-compose, assurez-vous que vous avez installé PHP >= 7.3 et composer >= 1.9.

Assurez-vous que votre utilisateur a tous les droits pour exécuter toutes les commandes docker.

Cette documentation a été testée sur macOS Catalina : version 10.15.4 et Ubuntu 18.04.4 LTS. Il est recommandé de choisir le même système d'exploitation si possible.

Étapes de l'installation
========================

Tout d'abord, vous devez cloner ce projet :

Assurez-vous d'utiliser l'url correcte du dépôt git.

```
git clone git@educstat4.adc.education.fr:cisad/depp-oat/simple-roster.git && cd simple-roster/
```
Ensuite, faites un checkout sur la branche. Veuillez remplacer `＜branch-name＞` par la branche correcte.

```
git checkout ＜branch-name＞
```
L'application est fournie avec un environnement de développement conteneurisé intégré.	
Veuillez mettre à jour votre fichier `.env.docker` dans le répertoire racine de l'application avec les paramètres de Composer tels que le chemin d'accès à vos informations d'identification GitHub `COMPOSER_HOME` et `COMPOSER_AUTH` .	
Cela devrait ressembler à quelque chose comme ça :	
```
COMPOSER_AUTH={"github-oauth":{"github.com":"your token here"}}
COMPOSER_HOME=~/.composer`
```

veuillez mettre à jour `CORS_ALLOW_ORIGIN` dans `.env.dockerenv.docker`  au cas où il serait appelé par un service externe.	
L'environnement est préconfiguré avec le fichier `.env.docker` il ne vous reste donc plus qu'à configurer les conteneurs :	
```
docker-compose up -d
```	
L'application utilise des jetons JWT pour l'authentification de l'API. Vous devez générer votre paire de clés privée/publique pour que cela fonctionne.	
Pour générer la clé privée :	
```
docker container exec -it simple-roster-phpfpm openssl genpkey -aes-256-cbc -algorithm RSA -pass pass:devpassphrase -out config/secrets/docker/jwt_private.pem
```
Pour générer la clé publique :	
```
docker container exec -it simple-roster-phpfpm openssl pkey -in config/secrets/docker/jwt_private.pem -passin pass:devpassphrase -out config/secrets/docker/jwt_public.pem -pubout
```
et installer les dépendances du code

```
docker-compose exec simple-roster-phpfpm composer install 
```
et créer le schéma de la base de données :	
```
docker-compose exec simple-roster-phpfpm ./bin/console doctrine:schema:create
```
L'application sera disponible soit à l'adresse `http://simple-roster.docker.localhost/api/v1` soit à l'adresse`https://localhost:8004`

Veuillez vous assurer que le port 8004 est libre d'être utilisé (il n'est pas bloqué ou occupé).

Variables d'environnement
=========================

|     |     |
| --- | --- |
| **Variable** | **Description** |
| **APP\_ENV** | Environnement d'application, `dev`, `prod`, `docker` ou `test` \[par défaut: `prod`\] |
| **APP\_DEBUG** | Mode de débogage de l'application, \[par défaut:: `false`\] |
| **APP\_SECRET** | Secret de l'application |
| **APP\_API\_KEY** | Application API Key |
| **APP\_ROUTE\_PREFIX** | Préfixe de route de l'application \[par défaut: `/api` \] |
| **DATABASE\_URL** | URL de la base de données |
| **JWT\_SECRET\_KEY** | Chemin vers la clé privée RSA pour le flux d'authentification JWT |
| **JWT\_PUBLIC\_KEY** | Chemin vers la clé publique RSA pour le flux d'authentification JWT |
| **JWT\_PASSPHRASE** | Phrase de passe pour la paire de clés JWT |
| **JWT\_ACCESS\_TOKEN\_TTL** | TTL pour le jeton d'accès JWT en secondes |
| **JWT\_REFRESH\_TOKEN\_TTL** | TTL pour le jeton de rafraîchissement JWT en secondes |
| **REDIS\_DOCTRINE\_CACHE\_HOST** | Hôte Redis pour le stockage du cache de la doctrine |
| **REDIS\_DOCTRINE\_CACHE\_PORT** | Port Redis pour le stockage du cache de la doctrine |
| **REDIS\_JWT\_CACHE\_HOST** | Hôte Redis pour le stockage du cache JWT |
| **REDIS\_JWT\_CACHE\_PORT** | Portage Redis pour le stockage du cache JWT |
| **CACHE\_TTL\_GET\_USER\_WITH\_ASSIGNMENTS** | Cache TTL (en secondes) pour la mise en cache des utilisateurs individuels avec des affectations. |
| **CACHE\_TTL\_LTI\_INSTANCES** | Cache TTL (en secondes) pour la mise en cache de la collection entière d'instances LTI |
| **CACHE\_TTL\_LINE\_ITEM** | TTL du cache (en secondes) pour la mise en cache d'éléments de ligne individuels |
| **MESSENGER\_TRANSPORT\_DSN** | DSN de transport Messenger pour le réchauffement asynchrone du cache |
| **WEBHOOK\_BASIC\_AUTH\_USERNAME** | Nom d'utilisateur pour l'authentification de base du webhook |
| **WEBHOOK\_BASIC\_AUTH\_PASSWORD** | Mot de passe d'authentification de base pour le webhook |
| **CORS\_ALLOW\_ORIGIN** | Origine CORS autorisée |
| **ASSIGNMENT\_STATE\_INTERVAL\_THRESHOLD** | Seuil pour la collecte de déchets d'affectation \[par défaut: `P1D`\] |

Configuration globale de LTI
============================

Les variables d'environnement suivantes sont des configurations agnostiques de la version de LTI :

|     |     |
| --- | --- |
| **Variable** | **Description** |
| **LTI\_VERSION** | Versions supportées: `1.1.1`, `1.3.0`\[ défaut: `1.1.1` \] |
| **LTI\_LAUNCH\_PRESENTATION\_RETURN\_URL** | Lien de retour LTI après avoir terminé la mission. Exemple : `https://test-taker-portal.com/index.html` \] |
| **LTI\_LAUNCH\_PRESENTATION\_LOCALE** | Définit la localisation de l'instance TAO \[par défaut : `en-EN`\] |
| **LTI\_OUTCOME\_XML\_NAMESPACE** | Définit l'espace de nom XML du résultat de la LTI \[Valeur recommandée : http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms\_v1p0 \] |

LTI 1.1.1
=========

Configurez les variables d'environnement en fonction de l'outil que vous intégrez à l'application.

|     |     |
| --- | --- |
| **Variable** | **Description** |
| **LTI\_INSTANCE\_LOAD\_BALANCING\_STRATEGY** | Stratégie d'équilibrage de la charge LTI. Valeurs possibles : `username`, `userGroupId`\] |

Une fois les variables d'environnement configurées, vous devez créer vos instances LTI à l'aide de la commande LTI instance ingester.

#### Stratégie d'équilibrage de la charge LTI

Il existe deux stratégies d'équilibrage de la charge qui peuvent être appliquées. Elles sont configurables via la variable d'environnement`LTI_INSTANCE_LOAD_BALANCING_STRATEGY` dans le fichier `.env` .

|     |     |
| --- | --- |
| **Strategy** | **Description** |
| **username** | Stratégie basée sur le nom d'utilisateur (par défaut) |
| **userGroupId** | Stratégie basée sur l'ID du groupe d'utilisateurs |

**Note:** Pour appliquer la stratégie `userGroupId` , les utilisateurs doivent être ingérés avec la colonne `groupId` spécifiée, sinon l'ingestion échouera.

**Note 2:** Le paramètre de requête `contextId`  LTI est automatiquement ajusté en fonction de la stratégie d'équilibrage de charge active.

|     |     |
| --- | --- |
| **Stratégie** | **Valeur du paramètre de requête LTI** `contextId` |
| **username** | ID du  `LineItem` de la dernière affectation de l'utilisateur |
| **userGroupId** | `groupId` pour le `User` |

LTI 1.3.0
=========

Le LTI 1.3.0 a été créé pour résoudre certaines failles de sécurité de ses versions précédentes. Avec sa création, toutes les autres versions antérieures sont désormais considérées comme dépréciées. La spécification complète de LTI 1.3 peut être consultée à l'adresse suivante [IMS Global](http://www.imsglobal.org/spec/lti/v1p3/) .

Configurez les variables d'environnement en fonction de l'outil que vous intégrez à l'application.

|     |     |
| --- | --- |
| **Variable** | **Description** |
| **LTI1P3\_SERVICE\_ENCRYPTION\_KEY** | Clé utilisée pour la signature de sécurité |
| **LTI1P3\_REGISTRATION\_ID** | ID utilisé pour trouver l'enregistrement configuré |
| **LTI1P3\_PLATFORM\_AUDIENCE** | Audience de la plate-forme |
| **LTI1P3\_PLATFORM\_OIDC\_AUTHENTICATION\_URL** | URL d'authentification OIDC de la plate-forme |
| **LTI1P3\_PLATFORM\_OAUTH2\_ACCESS\_TOKEN\_URL** | URL de génération de jeton d'accès OAUTH2 de la plateforme |
| **LTI1P3\_TOOL\_AUDIENCE** | Audience de l'outil |
| **LTI1P3\_TOOL\_OIDC\_INITIATION\_URL** | URL de lancement de l'outil OIDC |
| **LTI1P3\_TOOL\_LAUNCH\_URL** | URL de lancement de l'outil |
| **LTI1P3\_TOOL\_CLIENT\_ID** | Tool Client Id |
| **LTI1P3\_TOOL\_JWKS\_URL** | URL de l'outil JWKS (JSON Web Key Sets) |

Créez votre paire de clés en cours d'exécution :

```
openssl genrsa -out config/secrets/private.key
openssl rsa -in config/secrets/private.key -outform PEM -pubout -out config/secrets/public.key
```

Configurez les paramètres sur `config/packages/lti1p3.yaml`.

*   Configurer la section `keychain`

*   Définir les paramètres de la `platform`

*   Définir les paramètres de `tool`

*   Ajouter un `registration`


Commandes CLI
=============

Pour toutes les commandes ci-dessous, vous pouvez trouver des exemples CSV dans le dossier **tests/Resources**.

Ingestion de données
--------------------

Les commandes d'ingestion sont responsables de l'ingestion des données `lti-instance`, `line-item`, `user` et `assignment`.

**Principaux arguments:**

|     |     |
| --- | --- |
| **Option** | **Description** |
| path | Chemin local pour ingérer les données à partir de |

**Options principales :**

|     |     |
| --- | --- |
| **Option** | **Description** |
| \-d, --delimiter | Délimiteur CSV \[par défaut : `,`\] |
| \-f, --force | Pour impliquer des modifications réelles de la base de données ou non \[par défaut : `false`\] |
| \-b, --batch | Taille du lot \[par défaut : `1000`\] |

### Ingestion de l'instance LTI

**Usage:**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:lti-instance <path> [--storage=local] [--delimiter=,] [--batch=1000]
```

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm ./bin/console roster:ingest:lti-instance -h
```

**Examples:**

**Ingestion des lti-instances à partir d'un fichier CSV local ::**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:lti-instance /path/to/file.csv --force
```

This is the **CSV** example:

```
label,ltiLink,ltiKey,ltiSecret
infra_1,http://infra_1.com,key1,secret1
infra_2,http://infra_2.com,key2,secret2
```

Voici l'exemple du **CSV**:

|     |     |
| --- | --- |
| **Field** | **Description** |
| **label** | L'étiquette de l'instance lti |
| **ltiLink** | L'URL du système de livraison |
| **ltiKey** | Votre clé de consommateur OAuth |
| **ltiSecret** | Votre secret de consommateur OAuth |

### Ingestion de postes

**Usage:**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:line-item <path> [--storage=local] [--delimiter=,] [--batch=1000]
```

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm ./bin/console roster:ingest:line-item -h
```

**Examples:**

**Ingérer des postes de ligne à partir d'un fichier CSV local :**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest roster:ingest:line-item /path/to/file.csv --force
```

Voici l'exemple du **CSV**:

```
uri,label,slug,startTimestamp,endTimestamp,maxAttempts
https://localhost:8002/ontologies/tao.rdf#i5e907210766ba35588c15f61dee6785,Évaluation sixième - Français,21EVA6_FR,1546682400,1546713000,1
https://localhost:8002/ontologies/tao.rdf#i5e907210766ba35588c15f61dee6784,Évaluation sixième - Mathématiques,21EVA6_MA,1546682400,1546713000,2
```

Ce sont les champs décrits sur le **CSV**:

|     |     |
| --- | --- |
| **Column** | **Description** |
| **uri** | URI de livraison du poste. |
| **label** | Étiquette de l'élément de ligne. |
| **slug** | Identifiant unique (identifiant externe) du poste. |
| **startTimestamp** | Date de début du poste en tant que [Unix epoch timestamp](https://www.epochconverter.com/clock). |
| **endTimestamp** | Date de fin du poste en tant que [Unix epoch timestamp](https://www.epochconverter.com/clock). |
| **maxAttempts** | Nombre maximum de tentatives de prise de test disponibles (0 = tentatives infinies). |

### Ingestion par les utilisateurs

**Usage:**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:user <path> [--storage=local] [--delimiter=,] [--batch=1000]
```

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm ./bin/console roster:ingest:user -h
```

**Examples:**

**Ingestion des utilisateurs à partir d'un fichier CSV local:**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:user /path/to/file.csv --force
```

Voici l'exemple **CSV** :

```
username,password,groupId
user_1,password_1,group_1
user_2,password_2,group_2
user_3,password_3,group_2
user_4,password_4,group_3
```

Ce sont les champs décrits sur le **CSV**:

|     |     |
| --- | --- |
| **Column** | **Description** |
| **username** | Identifiant unique de l'utilisateur. |
| **password** | Mot de passe simple de l'utilisateur (sera encodé lors de l'ingestion). |
| **groupId** | Pour le regroupement logique des utilisateurs (facultatif, dépend de la stratégie d'équilibrage de charge LTI choisie.) |

### Ingestion des assignations

**Usage:**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:assignment <path> [--storage=local] [--delimiter=,] [--batch=1000]
```

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm ./bin/console roster:ingest:assignment -h
```

**Examples:**

**Acquisition des assignations à partir d'un fichier CSV local :**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:ingest:assignment /path/to/file.csv --force
```

Voici l'exemple du **CSV**:

```
username,lineItemSlug
user_1,21EVA6_FR
user_2,21EVA6_FR
user_3,21EVA6_MA
user_4,21EVA6_MA
```

Ce sont les champs décrits sur le **CSV**:

|     |     |
| --- | --- |
| **Column** | **Description** |
| **username** | Identifiant unique de l'utilisateur. |
| **lineItemSlug** | Slug (identifiant externe) de l'article de ligne auquel l'affectation créée sera liée. |


Réchauffement du cache
----------------------

Ces commandes sont responsables du rafraîchissement du cache des résultats de Doctrine.

Actuellement nous utilisons les résultats en cache pour les entités `LineItem`, `LtiInstance` et `User`.

### Réchauffement du cache des postes

**Utilisation**:

```
docker-compose exec simple-roster-phpfpm \
./bin/console  roster:cache-warmup:line-item
```

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
$ ./bin/console roster:cache-warmup:line-item -h
```

### Réchauffement du cache de l'instance LTI

**Utilisation :**

```
docker-compose exec simple-roster-phpfpm \
./bin/console  roster:cache-warmup:lti-instance
```

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
./bin/console roster:cache-warmup:lti-instance -h
```

### Réchauffement du cache de l'utilisateur

Utilisation:

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:cache-warmup:user [--usernames] [--line-item-slugs] [--batch=1000] [--modulo] [--remainder]
```

**Options principales**

|     |     |
| --- | --- |
| Option | Description |
| \-u, --usernames | Liste de noms d'utilisateurs séparés par des virgules pour le réchauffement du cache. |
| \-l, --line-item-slugs | Liste d'éléments de ligne séparés par des virgules pour définir la portée du réchauffement du cache. |
| \-b, --batch | Nombre d'entrées de cache à traiter par lot \[par défaut : `1000`\] |

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
./bin/console roster:cache-warmup:user -h
```


**Examples**

Réchauffement de toutes les entrées du cache des résultats par lots de 10.000 :

```
./bin/console roster:cache-warmup:user --batch=10000
```

Réchauffement des entrées du cache des résultats pour des utilisateurs spécifiques :

```
./bin/console roster:cache-warmup:user --usernames=user1,user2,user3,user4
```

Réchauffement des entrées de la mémoire cache des résultats pour des postes spécifiques :

```
./bin/console roster:cache-warmup:user --line-item-slugs=slug1,slug2,slug3
```

Collecteur d'ordures - Assignations
-----------------------------------

`AssignmentGarbageCollectorCommand` iest responsable de la transition de toutes les affectations collées de l'état  `started` à l'état `completed`.

Le seuil d'intervalle provient de la variable d'environnement ASSIGNMENT\_STATE\_INTERVAL\_THRESHOLD. La valeur par défaut est \[P1D\] (= 1 jour).

**Usage**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:garbage-collector:assignment [--force]
```

Options principales :

|     |     |
| --- | --- |
| **Option** | **Description** |
| *   b, --batch-size | Nombre d'affectations à traiter par lot \[par défaut : `1000`\] |
| *   f, --force | Pour impliquer des modifications réelles de la base de données ou non \[par défaut :`false`\] |

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:garbage-collector:assignment -h
```

Modifier les dates des postes
-----------------------------

`LineItemChangeDatesCommand` permet à l'utilisateur de définir une date de début et de fin pour les postes de ligne.

**Usage**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:modify-entity:line-item:change-dates -i <Line Item ID(s)> -s <date> -e <date>
./bin/console roster:modify-entity:line-item:change-dates -u <Line Item Slug(s)> -s <date> -e <date>
```

**Options principales :**

|     |     |
| --- | --- |
| **Option** | **Description** |
| \-i, --line-item-ids | Liste d'identifiants de postes séparés par des virgules. |
| \-s, --line-item-slugs | Liste séparée par des virgules des éléments de la ligne. |
| \--start-date | Définissez la date de début pour le(s) poste(s) spécifié(s). Format attendu : 2020-01-01T00:00:00+0000. Si elle n'est pas renseignée, elle sera annulée. |
| \--end-date | Définissez la date de fin pour le(s) poste(s) spécifié(s). Format attendu : 2020-01-01T00:00:00+0000. Si elle n'est pas renseignée, elle sera annulée. |
| \-f, --force | Si elle n'est pas utilisée, aucune modification ne sera apportée à la base de données (Dry Run). |

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:modify-entity:line-item:change-dates -h
```

**Examples**

Mise à jour des dates des postes individuels à l'aide des ID

```
./bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 --start-date 2020-01-01T00:00:00+0000 --end-date 2020-01-05T00:00:00+0000 --force
```

Mise à jour des dates des postes individuels à l'aide de Slugs

```
./bin/console roster:modify-entity:line-item:change-dates -s slug1,slug2,slug3 --start-date 2020-01-01T00:00:00+0000 --end-date 2020-01-05T00:00:00+0000 --force
```

Mise à jour des dates dans un fuseau horaire différent (UTC+1)

```
./bin/console roster:modify-entity:line-item:change-dates -s slug1,slug2,slug3 --start-date 2020-01-01T00:00:00+0100 --end-date 2020-01-05T00:00:00+0100 --force
```

Annulation des dates d'une ligne poste par poste

```
//Nullifying start and end dates
./bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 -f

//Nullifying only start date
./bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 --end-date 2020-01-05T00:00:00+0000 --force

//Nullifying only end date
./bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 --start-date 2020-01-01T00:00:00+0000 --force
```

> REMARQUE : Il n'est pas nécessaire de réchauffer le cache manuellement pour les postes concernés lorsque vous exécutez cette commande, cela est fait automatiquement.

Modifier l'état des postes
--------------------------

`LineItemChangeStateCommand` est responsable de l'activation ou de la désactivation des `Line Items` après l'ingestion.

**Usage**

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:modify-entity:line-item:change-state <toggle> <query-field> <query-value>
```

### Principaux arguments

|     |     |
| --- | --- |
| **Argument** | **Description** |
| **toggle** | Deux valeurs acceptées "activate" pour activer un poste. "Désactiver" pour désactiver un poste. |
| **query-field** | Comment voulez-vous interroger les postes que vous voulez activer/désactiver ? Les paramètres acceptés sont :`id`, `slug`, `uri` |
| **query-value** | La valeur qui doit correspondre en fonction du champ de la requête. Il peut s'agir d'une seule valeur ou d'une liste de valeurs séparées par un espace. Exemple : si le champ de la requête est "slug" et que la valeur de la requête est "test1 test2", tous les articles dont le slug est égal à test1 ou test2 seront mis à jour. |

**Autres options**

Pour la liste complète des options, veuillez vous référer à l'option d'aide :

```
docker-compose exec simple-roster-phpfpm \
./bin/console roster:modify-entity:line-item:change-state -h
```

**Examples**

Activation d'un poste par le slug du poste

```
$ ./bin/console roster:modify-entity:line-item:change-state activate slug my-line-item
```

Activation d'un poste de ligne par plusieurs slugs:

```
$ ./bin/console roster:modify-entity:line-item:change-state activate slug my-line-item1 my-line-item2
```

Activation d'un poste par son identifiant

```
$ ./bin/console roster:modify-entity:line-item:change-state activate id 4
```

Activation d'un poste par l'uri du poste

```
$ ./bin/console roster:modify-entity:line-item:change-state activate uri https://i.o#i5fb54d6ecd
```

Désactivation d'un poste de ligne par le slug du poste de ligne

```
$ ./bin/console roster:modify-entity:line-item:change-state deactivate slug my-line-item
```

Désactivation d'un poste par plusieurs tirettes

```
$ ./bin/console roster:modify-entity:line-item:change-state deactivate slug my-line-item1 my-line-item2
```

Désactivation d'un poste par l'identifiant du poste

```
$ ./bin/console roster:modify-entity:line-item:change-state deactivate id 4
```

Désactivation d'un poste par uri de poste

```
$ ./bin/console roster:modify-entity:line-item:change-state deactivate uri https://i.o#i5fb54d6ecd
```

> **REMARQUE** : Il n'est pas nécessaire de réchauffer le cache manuellement pour les postes concernés lorsque vous exécutez cette commande, cela est fait automatiquement.