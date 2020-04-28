create table "collection" (
"collection_id" serial not null
,"collection_name" character varying not null
,"referent_id" integer
,"allowed_import_flow" boolean
,"allowed_export_flow" boolean
,"public_collection" boolean
,primary key ("collection_id")
);
comment on table "collection" is 'List of all collections into the database';
comment on column "collection"."allowed_import_flow" is 'Allow an external source to update a collection';
comment on column "collection"."allowed_export_flow" is 'Allow interrogation requests from external sources';
comment on column "collection"."public_collection" is 'Set if a collection can be requested without authentication';

create table "referent" (
"referent_id" serial not null
,"referent_name" character varying not null
,"referent_email" character varying
,"address_name" character varying
,"address_line2" character varying
,"address_line3" character varying
,"address_city" character varying
,"address_country" character varying
,"referent_phone" character varying
,primary key ("referent_id")
);
comment on table "referent" is 'Table of sample referents';
comment on column "referent"."referent_name" is 'Name, firstname-lastname or department name';
comment on column "referent"."referent_email" is 'Email for contact';
comment on column "referent"."address_name" is 'Name for postal address';
comment on column "referent"."address_line2" is 'second line in postal address';
comment on column "referent"."address_line3" is 'third line in postal address';
comment on column "referent"."address_city" is 'ZIPCode and City in postal address';
comment on column "referent"."address_country" is 'Country in postal address';
comment on column "referent"."referent_phone" is 'Contact phone';

create table "sample" (
"sample_id" serial not null
,"uid" integer not null
,"collection_id" integer not null
,"sample_type_id" integer not null
,"sample_creation_date" timestamp without time zone not null
,"sampling_date" timestamp without time zone
,"parent_sample_id" integer
,"multiple_value" double precision
,"sampling_place_id" integer
,"dbuid_origin" character varying
,"metadata" json
,"expiration_date" timestamp without time zone
,"campaign_id" integer
,primary key ("sample_id")
);
comment on table "sample" is 'Table of samples';
comment on column "sample"."sample_creation_date" is 'Creation date of the record in the database';
comment on column "sample"."sampling_date" is 'Creation date of the physical sample or date of sampling';
comment on column "sample"."dbuid_origin" is 'Reference used in the original database, under the form db:uid. Used for read the labels created in others instances';
comment on column "sample"."metadata" is 'Metadata associated with the sample, in JSON format';
comment on column "sample"."expiration_date" is 'Date of expiration of the sample. After this date, the sample is not usable';

create table "sample_type" (
"sample_type_id" serial not null
,"sample_type_name" character varying not null
,"container_type_id" integer
,"multiple_type_id" integer
,"multiple_unit" character varying
,"metadata_id" integer
,"operation_id" integer
,"identifier_generator_js" character varying
,"sample_type_description" character varying
,primary key ("sample_type_id")
);
comment on table "sample_type" is 'Table of the types of samples';
comment on column "sample_type"."sample_type_name" is 'Name of the type';
comment on column "sample_type"."multiple_unit" is 'Name of the unit used  to qualify the number of sub-samples (ml, number, g, etc.)';
comment on column "sample_type"."identifier_generator_js" is 'Javascript function code used to automaticaly generate a working identifier from the intered information';
comment on column "sample_type"."sample_type_description" is 'Description of the type of sample';

create table "multiple_type" (
"multiple_type_id" serial not null
,"multiple_type_name" character varying not null
,primary key ("multiple_type_id")
);
comment on table "multiple_type" is 'Table of categories of potential sub-sampling (unit, quantity, percentage, etc.)';

create table "object" (
"uid" serial not null
,"identifier" character varying
,"wgs84_x" double precision
,"wgs84_y" double precision
,"object_status_id" integer
,"referent_id" integer
,"change_date" timestamp without time zone not null
,"uuid" uuid not null
,"trashed" boolean
,"location_accuracy" double precision
,"geom" geography(Point,4326)
,primary key ("uid")
);
comment on table "object" is 'Table of objects';
comment on column "object"."uid" is 'Unique identifier in the database of all objects';
comment on column "object"."identifier" is 'Main working identifier';
comment on column "object"."wgs84_x" is 'GPS longitude, in decimal form';
comment on column "object"."wgs84_y" is 'GPS latitude, in decimal form';
comment on column "object"."change_date" is 'Technical date of changement of the object';
comment on column "object"."uuid" is 'UUID of the object';
comment on column "object"."trashed" is 'If the object is trashed before completly destroyed ?';
comment on column "object"."location_accuracy" is 'Location accuracy of the object, in meters';
comment on column "object"."geom" is 'Geographic point generate from wgs84_x and wgs84_y';

create table "sampling_place" (
"sampling_place_id" serial not null
,"sampling_place_name" character varying not null
,"collection_id" integer
,"sampling_place_code" character varying
,"sampling_place_x" double precision
,"sampling_place_y" double precision
,primary key ("sampling_place_id")
);
comment on table "sampling_place" is 'Table of sampling places';
comment on column "sampling_place"."sampling_place_name" is 'Name of the sampling place';
comment on column "sampling_place"."collection_id" is 'Collection of rattachment';
comment on column "sampling_place"."sampling_place_code" is 'Working code of the station';
comment on column "sampling_place"."sampling_place_x" is 'Longitude of the station, in WGS84';
comment on column "sampling_place"."sampling_place_y" is 'Latitude of the station, in WGS84';

create table "object_status" (
"object_status_id" serial not null
,"object_status_name" character varying not null
,primary key ("object_status_id")
);
comment on table "object_status" is 'Table of types of status';

create table "document" (
"document_id" serial not null
,"uid" integer
,"mime_type_id" integer not null
,"document_import_date" timestamp without time zone not null
,"document_name" character varying not null
,"document_description" character varying
,"data" bytea
,"thumbnail" bytea
,"size" integer
,"document_creation_date" timestamp without time zone
,"campaign_id" integer
,primary key ("document_id")
);
comment on table "document" is 'Numeric docs associated to an objet or a campaign';
comment on column "document"."document_import_date" is 'Import date into the database';
comment on column "document"."document_name" is 'Original name';
comment on column "document"."document_description" is 'Description';
comment on column "document"."data" is 'Binary content (object imported)';
comment on column "document"."thumbnail" is 'Thumbnail in PNG format ( only for pdf, jpg or png docs)';
comment on column "document"."size" is 'Size of downloaded file';
comment on column "document"."document_creation_date" is 'Create date of the document (date of photo shooting, for example)';

create table "mime_type" (
"mime_type_id" serial not null
,"extension" character varying not null
,"content_type" character varying not null
,primary key ("mime_type_id")
);
comment on table "mime_type" is 'Mime types of imported files';
comment on column "mime_type"."extension" is 'File extension';
comment on column "mime_type"."content_type" is 'Official mime type';

create table "object_identifier" (
"object_identifier_id" serial not null
,"uid" integer not null
,"identifier_type_id" integer not null
,"object_identifier_value" character varying not null
,primary key ("object_identifier_id")
);
comment on table "object_identifier" is 'Table of complementary identifiers';
comment on column "object_identifier"."object_identifier_value" is 'Identifier value';

create table "identifier_type" (
"identifier_type_id" serial not null
,"identifier_type_name" character varying not null
,"identifier_type_code" character varying not null
,"used_for_search" boolean not null
,primary key ("identifier_type_id")
);
comment on table "identifier_type" is 'Table of identifier types';
comment on column "identifier_type"."identifier_type_name" is 'Textual name of the identifier';
comment on column "identifier_type"."identifier_type_code" is 'Identifier code, used in the labels';
comment on column "identifier_type"."used_for_search" is 'Is the identifier usable for barcode searches?';

ALTER TABLE "sample" ADD CONSTRAINT sample_has_parent_collection
FOREIGN KEY ("collection_id") REFERENCES "collection"("collection_id");
ALTER TABLE "collection" ADD CONSTRAINT collection_has_parent_referent
FOREIGN KEY ("referent_id") REFERENCES "referent"("referent_id");
ALTER TABLE "sample" ADD CONSTRAINT sample_has_parent_sample_type
FOREIGN KEY ("sample_type_id") REFERENCES "sample_type"("sample_type_id");
ALTER TABLE "sample" ADD CONSTRAINT sample_has_parent_object
FOREIGN KEY ("uid") REFERENCES "object"("uid");
ALTER TABLE "sample" ADD CONSTRAINT sample_has_parent_sampling_place
FOREIGN KEY ("sampling_place_id") REFERENCES "sampling_place"("sampling_place_id");
ALTER TABLE "sample_type" ADD CONSTRAINT sample_type_has_parent_multiple_type
FOREIGN KEY ("multiple_type_id") REFERENCES "multiple_type"("multiple_type_id");
ALTER TABLE "document" ADD CONSTRAINT document_has_parent_object
FOREIGN KEY ("uid") REFERENCES "object"("uid");
ALTER TABLE "object_identifier" ADD CONSTRAINT object_identifier_has_parent_object
FOREIGN KEY ("uid") REFERENCES "object"("uid");
ALTER TABLE "object" ADD CONSTRAINT object_has_parent_referent
FOREIGN KEY ("referent_id") REFERENCES "referent"("referent_id");
ALTER TABLE "object" ADD CONSTRAINT object_has_parent_object_status
FOREIGN KEY ("object_status_id") REFERENCES "object_status"("object_status_id");
ALTER TABLE "sampling_place" ADD CONSTRAINT sampling_place_has_parent_collection
FOREIGN KEY ("collection_id") REFERENCES "collection"("collection_id");
ALTER TABLE "document" ADD CONSTRAINT document_has_parent_mime_type
FOREIGN KEY ("mime_type_id") REFERENCES "mime_type"("mime_type_id");
ALTER TABLE "object_identifier" ADD CONSTRAINT object_identifier_has_parent_identifier_type
FOREIGN KEY ("identifier_type_id") REFERENCES "identifier_type"("identifier_type_id");
