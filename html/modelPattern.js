
function patternForm(data) {
    $("#alpacaPattern").alpaca({
        "data": data,
        "view": "bootstrap-edit-horizontal",
        "schema": {
            "title":"Description du modèle",
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "tableName": {
                        "title": "Nom de la table",
                        "type": "string",
                        "required": true
                    },
                    "tableAlias": {
                        "title": "Alias de la table (si elle dépend de plusieurs parents)",
                        "type": "string"
                    },
                    "technicalKey": {
                        "title": "Clé primaire",
                        "type": "string"
                    },
                    "isEmpty": {
                        "title":"Table fournie vide (table de paramètres renseignée par les valeurs  fournies dans les autres enregistrements) ?",
                        "type": "boolean",
                        "default" : false
                    },
                    "businessKey": {
                        "title": "Clé métier",
                        "type": "string",
                    },
                    "parentKey": {
                        "title": "Nom de la clé étrangère (table parente)",
                        "type": "string"
                    },
                    "istable11": {
                        "title": "Relation de type 1-1 avec le parent (clé partagée)",
                        "type": "boolean",
                        "default": false
                    },
                    "booleanFields": {
                        "title": "Liste des champs de type booléen",
                        "type": "array",
                        "items": {
                            "type": "string"
                        }
                    },
                    "children": {
                        "title": "Liste des alias des tables liées",
                        "type": "array",
                        "items" : {
                            "type": "object",
                            "properties": {
                                "aliasName": {
                                    "title":"Alias de la table",
                                    "type":"string"
                                },
                                "isStrict": {
                                    "title":"Relation stricte (les enregistrements enfants sont totalement dépendants de l'enregistrement courant) ?",
                                    "type":"boolean",
                                    "default":true
                                }
                            }
                        }
                    },
                    "parameters": {
                        "title":"Liste des tables de paramètres associées",
                        "type":"array",
                        "items": {
                            "type":"object",
                            "properties": {
                                "aliasName": {
                                    "title": "Alias de la table",
                                    "type": "string"
                                },
                                "fieldName":{
                                    "title":"Nom de la colonne dans la table courante",
                                    "type":"string"
                                }
                            }
                        }
                    },
                    "istablenn":{
                        "title": "Table de type n-n",
                        "type":"boolean",
                        "default":false
                    },
                    "tablenn":{
                        "type":"object",
                        "properties": {
                            "secondaryParentKey": {
                                "title": "Nom de la seconde clé étrangère",
                                "type": "string"
                            },
                            "tableAlias": {
                                "title": "Alias de la seconde table",
                                "type": "string"
                            }
                        },
                        "dependencies":"istablenn"
                    }
                }
            },
            "dependencies": {
                "istablenn": ["tablenn"]
            }
        },
        "options": {
            "fields":{
                "tablenn": {
                    "dependencies": {
                        "istablenn": true
                    }
                }
            }
        },
        "postRender": function (control) {
            var value = control.getValue();
            $("#pattern").val(JSON.stringify(value, null, null));
            control.on("mouseout", function () {
                var value = control.getValue();
                $("#pattern").val(JSON.stringify(value, null, null));
            });
            control.on("change", function () {
                var value = control.getValue();
                $("#pattern").val(JSON.stringify(value, null, null));
            });
        }
    })
}
