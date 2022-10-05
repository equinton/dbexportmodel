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
            "type": "array",
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
                    "technicalKey": {
                        "title": "Primary key",
                        "type": "string"
                    },
                    "businessKeys": {
                        "title": "Business key",
                        "type": "array",
                        "toolbarSticky": true,
                        "items": {
                            "title": "Name of the column",
                            "type": "string",
                        }
                    },
                    "parentKeys": {
                        "title": "Parent keys",
                        "type": "array",
                        "toolbarSticky": true,
                        "items":{
                            "title":"Name of the column",
                            "type":"string"
                        }
                    },
                    "children": {
                        "toolbarSticky": true,
                        "title": "List of alias of related tables",
                        "type": "array",
                        "items" : {
                            "title": "Name of the related alias",
                            "type": "string"
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
                                    "title": "List of columns used to rely to the parent",
                                    "type":"array",
                                    "items": {
                                        "title":"Name of the column into the current table",
                                        "type":"string"
                                    }
                                }
                            }
                        }
                    },
                    "recursiveField": {
                        "title":"Name of the column used to link to the parent record, in the same table",
                        "type":"string"
                    }
                }
            }
        },
        "options": {

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
