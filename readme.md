# DBExportModel
Creator : Eric Quinton © 2020 for INRAE

Released with MIT license

## Presentation

Relational databases store information by dividing it into two-dimensional tables. To retrieve this information, relationships are created between the tables, mainly hierarchical relationships (table 2 is dependent on table 1).

Transferring data from one database to another is a complex operation. The simplest, but often not the most suitable solution, is to make a full backup and then restore it afterwards. The other solution is to extract the data from each table into files supporting two dimensions (CSV files for example). 
This remains a complex operation, especially to retrieve all the useful information scattered in several tables, and then to reconstruct them afterwards.

The solution proposed here is based on the use of a hierarchical storage format. The JSON format was chosen because of its compactness and ease of interfacing with current programming languages, but XML could also have been used. 
The principle retained is to store objects in their entirety, i.e. with all the sub-objects or relative objects that compose or define them. 
For example, in a commercial management, an *order* will include not only the summary of the order (record present in the *order* table), the customer reference, but also all the lines corresponding to the products, each line itself containing the product references and the VAT code used, information stored in different tables. 

Once the structure of the data to be exported has been described, it is very easy to generate the corresponding JSON file and, using the same description file, to import the objects into the new database.

### How does it work?
DBExportModel is based on a *business* description of the relational model, identifying the relationships between tables and their nature. 
For the same database, several descriptions can be created, depending on what you want to extract as information. Thus, the list of open orders will not have the same structure as the list of orders placed by a customer.
This description is stored in JSON format.

The data is stored in a hierarchical form. Starting from a table, the JSON record contains the following information:

  * Related information (*child* tables);
  * the information that corresponds to the associated parameters, i.e. the information stored in dedicated tables, created to ensure uniqueness and non-redundancy.

Each *child* information can also contain child records, parameters, etc.

For volumetric issues in particular, binary data (type *bytea*) are stored in a file (one file per record and per binary field).

For import, the program needs a description of the processed tables (field types), which can be generated from the program. It is stored in JSON format. A SQL script for generating the tables corresponding to the model can also be generated from these two files (model and table description). This script will not recreate the initial database, but simply the structure needed to import the transferred data.
During the import, the relationships between the tables are recreated, according to the generated identifiers.

Depending on the parameters defined, the program will be able to update pre-existing records, or systematically create new ones.

### Limitations
The program was designed for Postgresql. 

Each table must have a primary numeric key, auto-incrementing or not. When the key is not auto-incrementing, certain precautions should be taken when describing the model. The support of non-numeric primary keys will be considered in a future version.

Tables must have only primary keys composed of a single attribute (except in the specific case of tables carrying n-n relations).

## Description of the files

### Setting up the model description

The html page *html/exportModelChange.html* is used to build the model description. A record must be created for each table processed.

A list of keys to be processed (in JSON format) can be used. In this case, the first table to be described must be the one on which the list will be based.

#### Field description

  * Name of the table (**tableName**): name of the table in the database.
  * Alias of the table (**aliasName**): when the table is in several relationships at once, each relationship must be described with a different alias. The same table can thus be described several times with different aliases.
  * Primary key (**technicalKey**): Primary key of the table. It must be filled in for all tables, except for tables with n-n relationships.
  * Table empty (**isEmpty**): this flag will be set if the table is not to be exported in its entirety, but only filled in with information from other tables. This flag applies especially to parameter tables, since their contents do not need to be transferred in their entirety. For example, if only 5 communes are mentioned in the transmitted data, it is not useful to transfer all the communes present in the original database.
  * Business key (**businessKey**): this is the field that carries the discriminating information to retrieve the record. In principle, the content of this field must be unique in the database. If this information is filled in, it is used to update already existing records. If it matches the primary key, new records will have the value of the primary key provided, not the automatically generated one (if the primary key is of type *serial*).
  * Foreign key (**parentKey**): the attribute that carries the relationship to the parent table.
  * List of alias of related tables (**children**: these are the tables that depend on the current table (child tables). For each one, you must indicate :
    * Alias of the table (**aliasName**): the alias of the corresponding table. A description of this alias must be made in the model
    * Strict relation (**isStrict**): this flag is set to *true* to allow only the strictly dependent records of the current table.
  * Parameter tables (**parameters**): parameter tables are the tables that are used to factorize regularly used labels (list of communes, taxa, etc.). For each one, you have to indicate :
    * Alias table (**aliasName**): the name of the alias, which is also described
    * Column name in the current table (**fieldName**): name of the column that supports the relationship to the parameter table.
  * Table of type n-n (**istablenn**): n-n tables are tables imposed by relational databases to match two tables, each of which may have multiple records to another table. They are characterized by a relationship systematically carried to the two parent tables. Positioning the indicator at *true* will allow you to indicate, in **tablenn** :
    * Name of the second foreign key (**secondaryParentKey**): the name of the attribute bearing the relationship to the second table.
    * Alias of the second table (**tableAlias**): the name of the alias of the second table.

## Special cases
### Binary Field Carrier Tables
Binary data is stored in specific files. Their name is generated from the table name, the column name, and the name of the business key. The latter must therefore be filled in.

### Parameter tables
Records in the parameter tables can be present in multiple locations in the data file. To prevent them from being multiplied during import, a business key must be specified.

This business key will correspond to the primary key if you wish to keep the same numbering as in the original database.

### Tables carrying the n-n relationships

In this case, the primary key of the table must not be filled. If a primary key exists in the original table, it must have the type *serial*.

### Setting up the description of the table structure

To be able to export binary data and import binary or Boolean data, the program needs to know the characteristics of each exported table.
This information is also used to generate the database creation script for the exported data.

The file, in JSON format, contains a record whose name is the name of the table, and which contains :

  * ** attributes**: the list of table columns, with, for each one:
    * **attnum**: the sequence number of the attribute
    * **field** : the name of the attribute
    * **type**: the type of the attribute. The generation script replaces the value *int* by *serial* if the table contains a constraint of type *sequence*.
    * **comment**: the literary description of the attribute
    * **notnull** : set to 1 if the attribute does not support null values
    * **key** : the name of the key constraint for the table in question
  * **description**: the literary description of the table
  * **children** : the list of *children* tables, with for each one :
    * **tableName** : the name of the table 
    * **childKey**: the name of the attribute carrying the relationship in the child table (link to the primary key of the current table)
 * **parents**: the list of parent tables, including parameter tables, with, for each :
     * **tableName** : the name of the table
     * **parentKey**: the name of the column carrying the relationship in the parent table (the primary key of the parent table)
     * **fieldName** : the attribute carrying the relation to the parent table
 * **booleanFields** : the list of boolean fields
 * **binaryFields**: the list of binary fields (type *bytea*)

The latter two pieces of information are stored to facilitate export/import processing.

### Configuration of the file containing the keys of the records to be processed

It is possible to export only a limited number of records. The list of keys to be processed must be filled in a file in JSON format, which will only include the keys, for example : [125,238,1272].

These keys will be associated with *the first table* described in the template.

## Using the program
The program was written in PHP, version 7.2 minimum.

### Command line use

The program can be used on the command line as :
~~~
php dbexportmodel.php --help
~~~
This option summarizes all possible configurations.

#### Configuration of the connection to the database
The *param.ini.dist* file must be renamed to *param.ini*, and modified to configure the database connection. The [database] section contains the following information:

  * **dsn**: database connection string, containing the server name (*host*) and the database name (*dbname*). Other options can be added, such as encryption support (*sslmode=require* for example).
  * **login** : login
  * **passwd** : associated password
  * **schema** : list of default schemas. The *public* schema must be included in the list, especially if columns of type *geographic objects* exist.

#### default files
The program is set to accept default file names :

  * **dbexportdescription.json**: file containing the business description of the export
  * **dbexportstructure.json** : file containing the database structure (description of the tables and the relations between them)
  * **dbexportkeys.json** : list of keys to process
  * **dbexportdata.json** : file containing the exported data
  * **dbcreate.sql**: file containing the SQL commands to generate the tables corresponding to the exported data.
  * **dbexport**: folder containing the generated binary files
  * **dbexport.zip** : if the compression option is used, name of the file containing all the above information

#### Trigger a treatment
The following options can be used :

  * **--export**: exports data
  * **--structure** : creates the file *dbexportstructure.json*, containing the description of the tables
  * **--create**: creates the sql file allowing to reconstitute the database corresponding to the data
  * **--import**: Triggers data import.

#### The --zip option
The program includes the possibility to work with a compressed file, which includes both the import description and the data (json and binary files).

Warning: using this option can considerably increase the processing time, especially if many binary files are to be processed.

### Use in a program

Most of the operations are performed by the *ExportModelProcessing* class, which is available in the *lib/exportmodel.class.php* file. 

The class relies on a few functions that are declared in the *lib/functions.php* file.

It is possible to take as a model the commands present in the *dbexportmodel.php* file to identify the main functions of the class.

The description of the functions has been generated with Doxygen, and is available in *lib/html/index.html*.


Translated in English from readme-fr.md with [https://www.deepl.com/translator](https://www.deepl.com/translator)