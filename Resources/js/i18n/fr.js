if(bb) {
    $.extend(bb.i18n, {
        loading: 'Chargement ...',
        save :'Enregistrer',
        cancel:'Annuler'
    });
}

if($.ui.bbMediaImageUpload) {
    $.extend($.ui.bbMediaImageUpload.prototype.i18n, {
        upload_browser_not_supported: 'Votre navigateur ne supporte pas l\'upload de fichier !',
        upload_too_many_file: 'Trop de fichiers !',
        upload_file_too_large: ' est trop volumineuse !',
        upload_only_image_allowed: 'Seules les images sont acceptées !'
    });
}

if($.ui.bbPageBrowser) {
    $.extend($.ui.bbPageBrowser.prototype.i18n, {
        new_node: 'Nouvelle page',
        multiple_selection: 'Sélection multiple',
        create: 'Créer',
        edit: 'Modifier',
        rename: 'Renommer',
        remove: 'Supprimer',
        ccp: 'Couper/Coller',
        cut: 'Couper',
        copy: 'Copier',
        paste: 'Coller',
        flyto: 'Affichage de la page',
        save: 'Sauvegarder',
        close: 'Fermer',
        notice: 'Information',
        error: 'Erreur'
    });
}

if($.ui.bbLinkSelector) {
    $.extend($.ui.bbLinkSelector.prototype.i18n, {
        });
}

if($.ui.bbMediaSelector) {
    $.extend($.ui.bbMediaSelector.prototype.i18n, {
        medias: 'média(s)',
        new_node: 'Nouveau dossier',
        multiple_selection: 'Multiple selection',
        create: 'Créer un dossier',
        rename: 'Modifier le dossier',
        remove: 'Supprimer le dossier',
        create_media: 'Ajouter un média',
        import_media: 'Importer depuis le serveur',
        delete_media: 'Supprimer le média',
        delete_media_confirm: 'Vous êtes sur le point de supprimer un média!<br/>Souhaitez-vous continuer ?',
        save: 'Sauvegarder',
        close: 'Fermer',
        notice: 'Information',
        error: 'Erreur',
        upload_browser_not_supported: 'Votre navigateur ne supporte pas l\'upload de fichier !',
        upload_too_many_file: 'Trop de fichiers !',
        upload_file_too_large: ' est trop volumineuse !',
        upload_only_image_allowed: 'Seules les images sont acceptées !'
    });
}

if($.ui.bbPageSelector) {
    $.extend($.ui.bbPageSelector.prototype.i18n, {
        pages: 'page(s)',
        multiple_selection: 'Multiple selection',
        create: 'Create',
        rename: 'Rename',
        remove: 'Delete'
    });
}

/*contentForm params*/
if(bb.FormBuilder){
  $.extend(bb.FormBuilder.prototype.i18n,{
     noParamsMsg : "Aucun Paramètre disponible"   
  }); 
}