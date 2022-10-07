/**
 * Generate the AlpacaJs Form
 * @param {string} id: id of the form
 * @param {string} dataId: id of the field which contains data
 * @param {json} data: data of the form
 */
function patternForm(id, dataId, data = "") {
    $("#"+id).alpaca("destroy");
    $("#"+id).alpaca({
        "data": data,
        "view": "bootstrap-edit-horizontal",
        "schema": {
            "title":"Model description",
            "type":"object",
            "properties": {
                "version": {
                    "title":"Version of the pattern",
                    "type":"string",
                    "default":"v2.0",
                    "readonly":true
                },
                "databaseType": {
                    "title":"Type of database",
                    "type":"string",
                    "enum":["postgresql","mysql"],
                    "default":"postgresql",
                    "required":true
                },
                "aliases" :{
                    "type": "array",
                    "title":"List of alias tables",
                    "items": {
                        "title":"Description of a table",
                        "type": "object",
                        "properties": {
                            "tableName": {
                                "title": "Name of the table",
                                "type": "string",
                                "required": true
                            },
                            "tableAlias": {
                                "title": "Alias of the table",
                                "type": "string",
                                "required": true
                            },
                            "schemaName": {
                                "title": "Name of the schema where the table is",
                                "type": "string",
                            },
                            "primaryKeys": {
                                "title": "Primary keys",
                                "type": "array",
                                "items": {
                                    "title": "Name of the primary key column",
                                    "type":"string"
                                }
                            },
                            "businessKeys": {
                                "title": "Business key",
                                "type": "array",
                                "toolbarSticky": true,
                                "items": {
                                    "title": "Name of the identifying column",
                                    "type": "string",
                                }
                            },
                            "parentKeys": {
                                "title": "Parent keys",
                                "type": "array",
                                "toolbarSticky": true,
                                "items":{
                                    "title":"Name of the columnn that contains the relationship",
                                    "type":"string"
                                }
                            },
                            "children": {
                                "toolbarSticky": true,
                                "title": "List of alias of related tables",
                                "type": "array",
                                "items" : {
                                    "type":"object",
                                    "properties": {
                                        "aliasName": {
                                            "title":"Child table alias",
                                            "type":"string"
                                        },
                                        "foreignKeys" : {
                                            "title":"List of foreign keys",
                                            "type":"array",
                                            "items": {
                                                "title":"Name of the foreign key",
                                                "type":"string"
                                            }
                                        }
                                    }
                                }
                            },
                            "parents": {
                                "title":"Liste of parents alias",
                                "type":"array",
                                "items": {
                                    "type":"object",
                                    "properties": {
                                        "aliasName": {
                                            "title": "Table alias",
                                            "type": "string"
                                        },
                                        "foreignKeys":{
                                            "title": "List of foreign keys",
                                            "type":"array",
                                            "items": {
                                                "title":"Name of foreign key",
                                                "type":"string"
                                            }
                                        }
                                    }
                                }
                            },
                        }
                    }
                }
            }
        },
        "options": {
            "databaseType": {

            }
        },
        "postRender": function (control) {
            var value = control.getValue();
            $("#"+dataId).val(JSON.stringify(value, null, null));
            control.on("mouseout", function () {
                var value = control.getValue();
                $("#"+dataId).val(JSON.stringify(value, null, null));
            });
            control.on("change", function () {
                var value = control.getValue();
                $("#"+dataId).val(JSON.stringify(value, null, null));
            });
        }
    })
}
