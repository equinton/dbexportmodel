# DBExportModel

## Présentation

Exporter des données depuis une base de données relationnelle vers une autre est toujours un processus délicat, surtout si celles-ci sont hébergées dans plusieurs tables. DBExportModel permet de sauvegarder des données dans un fichier au format JSON, pour pouvoir ensuite les réimporter dans une autre base de données.

### Comment cela fonctionne ?
DBExportModel s'appuie sur une description du modèle relationnel, en identifiant les relations entre les tables et leur nature. Cette description est stockée au format JSON.

Les données sont stockées sous une forme hiérarchique. À partir d'une table, on retrouve dans l'enregistrement JSON :

  * les enregistrements enfants
  * les enregistrements qui correspondent aux paramètres associés

Chaque enregistrement enfant peut également contenir des enregistrements enfants, des paramètres, etc.

Pour des questions notamment de volumétrie, les données binaires (type bytea) sont stockées dans un fichier (un fichier par enregistrement et par champ binaire).

Pour l'importation, le programme a besoin d'une description des tables traitées (types de champs), qui peut être générée à partir du programme. Elle est stockée au format JSON. Un script SQL de génération des tables correspondantes au modèle peut également être généré à partir de ces deux fichiers (modèle et description des tables). 
Lors de l'importation, les relations entre les tables sont recréées, en fonction des identifiants générés.

Selon les paramètres définis, le programme pourra mettre à jour des enregistrements pré-existants, ou bien en créer systématiquement des nouveaux.

### Limitations
Le programme a été conçu pour Postgresql. 

Chaque table doit disposer d'une clé primaire numérique, auto-incrémentée ou non. Dans ce dernier cas, certaines précautions sont à prendre lors de la description du modèle.

Les tables ne doivent posséder que des clés primaires composées d'un seul attribut (sauf cas spécifique des tables porteuses de relations n-n).

## Configuration de la description du modèle

La page html *html/exportModelChange.html* permet de construire la description du modèle. Un enregistrement doit être créé pour chaque table traitée.

Une liste de clés à traiter (au format JSON) peut être utilisée. Dans ce cas de figure, la première table à décrire doit être celle sur laquelle la liste va porter.

### Description des champs

  * Name of the table (tableName): nom de la table dans la base de données
  * Alias of the table (aliasName): quand la table figure dans plusieurs relations à la fois, chaque relation doit être décrite avec un alias différent. Une même table peut ainsi être décrite plusieurs fois avec différents alias
  * Primary key (technicalKey): clé primaire de la table. Elle doit être renseignée pour toutes les tables, sauf dans le cas des tables porteuses des relations n-n
  * Table empty (): cet indicateur sera positionné si la table ne doit pas être exportée dans son intégralité, mais renseignée uniquement à partir des informations fournies dans d'autres tables. Cet indicateur s'applique surtout aux tables de paramètres, dès lors que leur contenu n'a pas besoin d'être transféré intégralement. Par exemple, si 5 communes seulement sont citées dans les données transmises, il n'est pas utile de transférer l'ensemble des communes présentes dans la base de données d'origine
  * Business key (): il s'agit du champ qui porte l'information discriminante pour retrouver l'enregistrement. En principe, le contenu de ce champ doit être unique dans la base de données. Si cette information est renseignée, elle est utilisée pour mettre à jour les enregistrements déjà existants. Si elle correspond à la clé primaire, les nouveaux enregistrements auront la valeur de la clé primaire fournie, et non celle générée automatiquement (si la clé primaire est de type *serial*)
  * Foreign key (): l'attribut qui porte la relation vers la table parente
  * List of alias of related tables: il s'agit des tables qui dépendent de la table courante (tables enfant). Pour chacune, il faut indiquer :
    * Alias of the table (): l'alias de la table correspondante. Une description de cet alias devra être réalisée dans le modèle
    * Strict relation (): cet indicateur est positionné pour n'autoriser que les enregistrements strictement dépendants de la table courante
  * Parameter tables (): les tables de paramètres sont les tables qui sont utilisées pour factoriser des libellés régulièrement employés (liste des communes, des taxons, etc.). Pour chacune, il faut indiquer :
    * Table alias (): le nom de l'alias, décrit par ailleurs
    * Column name in the current table (): nom de la colonne qui sert de support à la relation vers la table de paramètres
  * Table of type n-n (): les tables n-n sont des tables imposées par les bases relationnelles pour faire correspondre deux tables dont chacune peut avoir plusieurs enregistrements vers une autre table. Elles se caractérisent par une relation portée systématiquement vers les deux tables parentes. Le positionnement de l'indicateur va permettre d'indiquer :
    * Name of the second foreign key (): le nom de l'attribut portant la relation vers la seconde table
    * Alias of the second table (): le nom de l'alias de la seconde table

### Cas particuliers
#### Tables porteuses de champs binaires
Les données binaires sont stockées dans des fichiers spécifiques. Leur nom est généré à partir du nom de la table, du nom de la colonne, et du nom de la clé métier (businessKey). Cette dernière doit donc impérativement être renseignée.

#### Tables de paramètres
Les enregistrements dans les tables de paramètres peuvent être présents à de multiples emplacements dans le fichier de données Pour éviter que ceux-ci soient multipliés lors de l'importation, une clé métier (businessKey) doit impérativement être indiquée.

Cette clé métier correspondra à la clé primaire si on souhaite conserver la même numérotation que dans la base de données d'origine.

#### Tables porteuses des relations n-n

Dans ce cas de figure, la clé primaire de la table ne doit pas être renseignée. Si une clé primaire existe dans la table d'origine, elle doit être de type *serial*.

## Configuration de la description de la structure des tables


## Configuration du fichier contenant les clés des enregistrements à traiter

## Utilisation du programme


