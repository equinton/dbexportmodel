# DBExportModel

## Présentation

Les bases de données relationnelles stockent l'information en la répartissant dans des tables à deux dimensions. Pour restituer celle-ci, des relations sont créées entre les tables, principalement des relations hiérarchiques (table 1 dépend de la table 2).

Transférer des données d'une base de données à une autre est une opération complexe. La solution la plus simple, mais pas la plus souvent adaptée, consiste à réaliser une sauvegarde complète, puis de la restaurer ensuite. L'autre solution consiste à extraire les données de chaque table dans des fichiers supportant deux dimensions (fichers CSV par exemple), mais là encore, extraire toutes les données nécessaires, pis retrouver l'ensemble de l'information initiale est complexe, en raison des relations entre les tables qui sont difficiles à reconstituer.

La solution proposée ici s'appuie sur l'utilisation d'un format de stockage hiérarchique. Le format JSON a été choisi en raison de sa compacité et de sa facilité d'interfaçage avec les langages actuels de programmation, mais XML aurait pu également être utilisé. 
Le principe retenu est de stocker les objets dans leur entièreté, c'est à dire avec tous les sous-objets ou les objets relatifs qui le composent ou le définissent. 
Ainsi, une commande comprendra non seulement le récapitulatif de la commande (enregistrement de la table commande), la référence du client, mais également toutes les lignes correspondants aux produits, chaque ligne contenant elle-même les références des produits et le code de TVA, informations stockées dans des tables différentes. 
Une fois décrite la structure des données à exporter, il est très facile de générer le fichier JSON correspondant et, en utilisant le même fichier de description, d'importer les objets dans la nouvelle base de données.

### Comment cela fonctionne ?
DBExportModel s'appuie sur une description *métier* du modèle relationnel, en identifiant les relations entre les tables et leur nature. 
Pour une même base de données, plusieurs descriptions peuvent être créées, en fonction de ce que l'on veut extraire comme information. Ainsi, la liste des commandes en cours n'aura pas la même structure que la liste des commandes passées par un client.
Cette description est stockée au format JSON.

Les données sont stockées sous une forme hiérarchique. À partir d'une table, on retrouve dans l'enregistrement JSON :

  * les informations liées (tables *enfant*) ;
  * les informations qui correspondent aux paramètres associés, c'est à dire celles qui sont stockées dans des tables dédiées pour en garantir l'unicité et leur non redondance.

Chaque information *enfant* peut également contenir des enregistrements enfants, des paramètres, etc.

Pour des questions notamment de volumétrie, les données binaires (type bytea) sont stockées dans un fichier (un fichier par enregistrement et par champ binaire).

Pour l'importation, le programme a besoin d'une description des tables traitées (types de champs), qui peut être générée à partir du programme. Elle est stockée au format JSON. Un script SQL de génération des tables correspondantes au modèle peut également être généré à partir de ces deux fichiers (modèle et description des tables). Ce script ne recréera pas la base de données initiale, mais simplement la structure nécessaire pour pouvoir importer les données transférées.
Lors de l'importation, les relations entre les tables sont recréées, en fonction des identifiants générés.

Selon les paramètres définis, le programme pourra mettre à jour des enregistrements pré-existants, ou bien en créer systématiquement des nouveaux.

### Limitations
Le programme a été conçu pour Postgresql. 

Chaque table doit disposer d'une clé primaire numérique, auto-incrémentée ou non. Quand la clé n'est pas auto-incrémentée, certaines précautions sont à prendre lors de la description du modèle. Le support de clés primaires non numériques sera envisagé dans une version future.

Les tables ne doivent posséder que des clés primaires composées d'un seul attribut (sauf cas spécifique des tables porteuses de relations n-n).

## Description des fichiers

### Configuration de la description du modèle

La page html *html/exportModelChange.html* permet de construire la description du modèle. Un enregistrement doit être créé pour chaque table traitée.

Une liste de clés à traiter (au format JSON) peut être utilisée. Dans ce cas de figure, la première table à décrire doit être celle sur laquelle la liste va porter.

#### Description des champs

  * Name of the table (**tableName**): nom de la table dans la base de données
  * Alias of the table (**aliasName**): quand la table figure dans plusieurs relations à la fois, chaque relation doit être décrite avec un alias différent. Une même table peut ainsi être décrite plusieurs fois avec différents alias
  * Primary key (**technicalKey**): clé primaire de la table. Elle doit être renseignée pour toutes les tables, sauf dans le cas des tables porteuses des relations n-n
  * Table empty (**isEmpty**): cet indicateur sera positionné si la table ne doit pas être exportée dans son intégralité, mais renseignée uniquement à partir des informations fournies dans d'autres tables. Cet indicateur s'applique surtout aux tables de paramètres, dès lors que leur contenu n'a pas besoin d'être transféré intégralement. Par exemple, si 5 communes seulement sont citées dans les données transmises, il n'est pas utile de transférer l'ensemble des communes présentes dans la base de données d'origine
  * Business key (**businessKey**): il s'agit du champ qui porte l'information discriminante pour retrouver l'enregistrement. En principe, le contenu de ce champ doit être unique dans la base de données. Si cette information est renseignée, elle est utilisée pour mettre à jour les enregistrements déjà existants. Si elle correspond à la clé primaire, les nouveaux enregistrements auront la valeur de la clé primaire fournie, et non celle générée automatiquement (si la clé primaire est de type *serial*)
  * Foreign key (**parentKey**): l'attribut qui porte la relation vers la table parente
  * List of alias of related tables (**children**: il s'agit des tables qui dépendent de la table courante (tables enfant). Pour chacune, il faut indiquer :
    * Alias of the table (**aliasName**): l'alias de la table correspondante. Une description de cet alias devra être réalisée dans le modèle
    * Strict relation (**isStrict**): cet indicateur est positionné à *true* pour n'autoriser que les enregistrements strictement dépendants de la table courante
  * Parameter tables (**parameters**): les tables de paramètres sont les tables qui sont utilisées pour factoriser des libellés régulièrement employés (liste des communes, des taxons, etc.). Pour chacune, il faut indiquer :
    * Table alias (**aliasName**): le nom de l'alias, décrit par ailleurs
    * Column name in the current table (**fieldName**): nom de la colonne qui sert de support à la relation vers la table de paramètres
  * Table of type n-n (**istablenn**): les tables n-n sont des tables imposées par les bases relationnelles pour faire correspondre deux tables dont chacune peut avoir plusieurs enregistrements vers une autre table. Elles se caractérisent par une relation portée systématiquement vers les deux tables parentes. Le positionnement de l'indicateur à *true* va permettre d'indiquer, dans **tablenn** :
    * Name of the second foreign key (**secondaryParentKey**): le nom de l'attribut portant la relation vers la seconde table
    * Alias of the second table (**tableAlias**): le nom de l'alias de la seconde table.

#### Cas particuliers
##### Tables porteuses de champs binaires
Les données binaires sont stockées dans des fichiers spécifiques. Leur nom est généré à partir du nom de la table, du nom de la colonne, et du nom de la clé métier (businessKey). Cette dernière doit donc impérativement être renseignée.

##### Tables de paramètres
Les enregistrements dans les tables de paramètres peuvent être présents à de multiples emplacements dans le fichier de données. Pour éviter que ceux-ci soient multipliés lors de l'importation, une clé métier (businessKey) doit impérativement être indiquée.

Cette clé métier correspondra à la clé primaire si on souhaite conserver la même numérotation que dans la base de données d'origine.

##### Tables porteuses des relations n-n

Dans ce cas de figure, la clé primaire de la table ne doit pas être renseignée. Si une clé primaire existe dans la table d'origine, elle doit être de type *serial*.

### Configuration de la description de la structure des tables

Pour pouvoir procéder à l'exportation des données binaires, et à l'importation des données binaires ou booléennes, le programme a besoin de connaître les caractéristiques de chaque table exportée.
Ces informations servent également à générer le script de création de la base de données correspondante aux données exportées.

Le fichier, au format JSON, comprend un enregistrement dont le nom est le nom de la table, et qui contient :

  * **attributes** : la liste des colonnes de la table, avec, pour chacune :
    * **attnum** : le numéro d'ordre de l'attribut
    * **field** : le nom de l'attribut
    * **type** : le type de l'attribut. Le script de génération remplace la valeur *int* par *serial* si la table contient une contrainte de type *sequence*
    * **comment** : la description littéraire de l'attribut
    * **notnull** : positionné à 1 si l'attribut ne supporte pas les valeurs nulles
    * **key** : le nom de la contrainte de clé pour la table considérée
  * **description** : la description littéraire de la table
  * **children** : la liste des tables *enfants*, avec pour chacune :
    * **tableName** : le nom de la table 
    * **childKey** : le nom de l'attribut porteur de la relation dans la table enfant (lien vers la clé primaire de la table courante)
 * **parents** : la liste des tables parentes, notamment les tables de paramètres, avec, pour chacune :
     * **tableName** : le nom de la table
     * **parentKey** : le nom de la colonne porteuse de la relation dans la table parente (la clé primaire de la table parente)
     * **fieldName** : l'attribut porteur de la relation vers la table parente
 * **booleanFields** : la liste des champs booléens
 * **binaryFields** : la liste des champs binaires (type *bytea*)

Ces deux dernières informations sont stockées pour faciliter le traitement des exportations/importations.

### Configuration du fichier contenant les clés des enregistrements à traiter

Il est possible de ne réaliser l'exportation que pour un nombre limité d'enregistrements. La liste des clés à traiter doit être renseigné dans un fichier au format JSON, qui ne comprendra que les clés, par exemple : [125,238,1272].

Ces clés seront associées avec *la première table* décrite dans le modèle.

## Utilisation du programme
Le programme a été écrit en PHP, version 7.2 minimum.

### Utilisation en ligne de commande

Le programme peut être utilisé en ligne de commande, sous la forme :
~~~
php dbexportmodel.php --help
~~~
Cette option récapitule l'ensemble des configurations possibles.

#### Configuration de la connexion à la base de données
Le fichier *param.ini.dist* doit être renommé en *param.ini*, et modifié pour configurer la connexion à la base de données. La section [database] contient les informations suivantes :
  * **dsn** : chaîne de connexion à la base de dnnées, contenant le nom du serveur (*host*) et le nom de la base de données (*dbname*). D'autres options peuvent être ajoutées, comme le support du chiffrement (*sslmode=require* par exemple)
  * **login** : login de connexion
  * **passwd** : mot de passe associé
  * **schema** : liste des schémas définis par défaut. Il faut notamment que le schéma *public* figure dans la liste, notamment si des colonnes de type *objets géographiques* existent.

#### fichiers par défaut
Le programme est paramétré pour accepter des noms de fichier par défaut :
  * **dbexportdescription.json** : fichier contenant la description métier de l'export
  * **dbexportstructure.json** : fichier contenant la structure de la base de données (description des tables et des relations entre elles)
  * **dbexportkeys.json** : liste des clés à traiter
  * **dbexportdata.json** : fichier contenant les données exportées
  * **dbcreate.sql** : fichier contenant les commandes SQL permettant de générer les tables correspondant aux données exportées
  * **dbexport** : dossier contenant les fichier binaires générés
  * **dbexport.zip** : si l'option de compression est utilisée, nom du fichier contenant l'ensemble des informations précédentes

#### Déclencher un traitement
Les options suivantes peuvent être utilisées :
  * **--export** : exporte les données
  * **--structure** : crée le fichier *dbexportstructure.json*, contenant la description des tables
  * **--create** : crée le fichier sql permettant de reconstituer la base de données correspondant aux données
  * **--import** : déclenche l'importation des données.

#### L'option --zip
Le programme intègre la possibilité de travailler avec un fichier compressé, qui comprend à la fois la description de l'import et les données (fichiers json et binaires).

Attention : l'utilisation de cette option peut considérablement augmenter le temps de traitement, notamment si de nombreux fichiers binaires sont à traiter.

### Utilisation dans un programme

La plupart des opérations sont réalisées par la classe *ExportModelProcessing*, qui est disponible dans le fichier *lib/exportmodel.class.php*. 

La classe s'appuie sur quelques fonctions qui sont déclarées dans le fichier *lib/functions.php*.

Il est possible de prendre pour modèle les commandes présentes dans le fichier *dbexportmodel.php* pour identifier les principales fonctionalités de la classe.
