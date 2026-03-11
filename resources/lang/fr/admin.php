<?php

declare(strict_types=1);

return [
    // General
    'dashboard' => 'Tableau de bord',
    'welcome' => 'Bienvenue',
    'home' => 'Accueil',
    'logout' => 'Déconnexion',
    'profile' => 'Profil',
    'settings' => 'Paramètres',
    'search' => 'Rechercher...',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'confirm' => 'Confirmer',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'view' => 'Voir',
    'details' => 'Détails',
    'create' => 'Créer',
    'actions' => 'Actions',
    'status' => 'Statut',
    'date' => 'Date',
    'loading' => 'Chargement...',
    'no_data' => 'Aucune donnée disponible',
    'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer?',
    'delete_warning' => 'Cette action est irréversible.',

    // Navigation
    'nav' => [
        'main_menu' => 'Menu Principal',
        'content' => 'Contenu',
        'media' => 'Médias',
        'users' => 'Utilisateurs',
        'settings' => 'Paramètres',
        'hadith' => 'Hadith',
    ],

    // Dashboard
    'dashboard_title' => 'Tableau de bord principal',
    'dashboard_subtitle' => 'Aperçu du système d\'encyclopédie islamique',
    'statistics' => 'Statistiques',
    'recent_activity' => 'Activité récente',
    'quick_actions' => 'Actions rapides',
    'view_all' => 'Voir tout',

    // Statistics Cards
    'total' => 'Total',
    'published' => 'Publié',
    'draft' => 'Brouillon',
    'archived' => 'Archivé',
    'verified' => 'Vérifié',
    'unverified' => 'Non vérifié',

    // Modules
    'articles' => 'Articles',
    'article' => 'Article',
    'articles_count' => '{0} Aucun article|{1} :count article|[2,*] :count articles',
    'new_article' => 'Nouvel article',
    'manage_articles' => 'Gérer les articles',

    'hadith' => 'Hadith',
    'hadiths' => 'Hadiths',
    'hadith_count' => '{0} Aucun hadith|{1} :count hadith|[2,*] :count hadiths',
    'new_hadith' => 'Nouveau hadith',
    'manage_hadith' => 'Gérer les hadiths',
    'hadith_books' => 'Livres de hadith',

    'media_programs' => 'Programmes médias',
    'media_program' => 'Programme média',
    'programs_count' => '{0} Aucun programme|{1} :count programme|[2,*] :count programmes',
    'new_program' => 'Nouveau programme',
    'manage_programs' => 'Gérer les programmes',
    'episodes' => 'Épisodes',

    'quran' => 'Coran',
    'quran_surahs' => 'Sourates du Coran',
    'quran_verses' => 'Versets du Coran',
    'verses_count' => '{0} Aucun verset|{1} :count verset|[2,*] :count versets',
    'manage_quran' => 'Gérer le Coran',

    'sahaba' => 'Compagnons',
    'sahabi' => 'Compagnon',
    'sahaba_count' => '{0} Aucun compagnon|{1} :count compagnon|[2,*] :count compagnons',
    'new_sahabi' => 'Nouveau compagnon',
    'manage_sahaba' => 'Gérer les compagnons',
    'companions' => 'Nobles compagnons',

    'seerah' => 'Seerah',
    'seerah_events' => 'Événements Seerah',
    'seerah_event' => 'Événement Seerah',
    'events_count' => '{0} Aucun événement|{1} :count événement|[2,*] :count événements',
    'new_event' => 'Nouvel événement',
    'manage_seerah' => 'Gérer la Seerah',
    'timeline' => 'Chronologie',
    'blog'     => 'Blog',
    'media'    => 'Médias',
    'type'     => 'Type',

    'genealogy' => 'Généalogie',
    'family_tree' => 'Arbre généalogique du Prophète ﷺ',
    'manage_genealogy' => 'Gérer la généalogie',
    'people' => 'Personnes',
    'relationships' => 'Relations',

    'users' => 'Utilisateurs',
    'user' => 'Utilisateur',
    'users_count' => '{0} Aucun utilisateur|{1} :count utilisateur|[2,*] :count utilisateurs',
    'new_user' => 'Nouvel utilisateur',
    'manage_users' => 'Gérer les utilisateurs',
    'user_management' => 'Gestion des utilisateurs',

    'roles' => 'Rôles',
    'role' => 'Rôle',
    'permissions' => 'Permissions',
    'manage_roles' => 'Gérer les rôles',
    'assign_roles' => 'Attribuer des rôles',

    // Categories & Tags
    'categories' => 'Catégories',
    'category' => 'Catégorie',
    'tags' => 'Étiquettes',
    'tag' => 'Étiquette',
    'name' => 'Nom',
    'total_categories' => 'Total des Catégories',

    // Forms
    'title' => 'Titre',
    'title_ar' => 'Titre (Arabe)',
    'title_en' => 'Titre (Anglais)',
    'title_fr' => 'Titre (Français)',
    'title_tr' => 'Titre (Turc)',
    'title_ur' => 'Titre (Ourdou)',
    'content' => 'Contenu',
    'description' => 'Description',
    'tag_name' => 'Nom de l\'étiquette',
    'category_name' => 'Nom de la catégorie',
    'slug' => 'Slug',

    // Category Form Labels
    'category_information'     => 'Informations sur la catégorie',
    'parent_category'          => 'Catégorie parente',
    'root_level'               => '-- Niveau racine --',
    'leave_empty_root_category'=> 'Laisser vide pour la catégorie racine',
    'icon'                     => 'Icône',
    'color'                    => 'Couleur',
    'featured_image'           => 'Image mise en avant',
    'display_order'            => 'Ordre d\'affichage',
    'lower_numbers_first'      => 'Les chiffres inférieurs apparaissent en premier',
    'active_status'            => 'Statut actif',
    'save_category'            => 'Enregistrer la catégorie',
    'save_and_close'           => 'Enregistrer et fermer',
    'save_and_new'             => 'Enregistrer et nouveau',
    'delete_category'          => 'Supprimer la catégorie',
    'delete_category_warning'  => 'Êtes-vous sûr de vouloir supprimer cette catégorie ? Toutes les sous-catégories seront également supprimées.',
    'seo_settings'             => 'Paramètres SEO',
    'seo_settings_desc'        => 'Optimiser pour les moteurs de recherche',
    'meta_title'               => 'Titre Meta',
    'meta_description'         => 'Description Meta',
    'seo_title_placeholder'    => 'Titre SEO (max 60 caractères)',
    'seo_desc_placeholder'     => 'Description SEO (max 160 caractères)',
    'url_friendly_hint'        => 'URL conviviale (minuscules, tirets uniquement)',
    'brief_description'        => 'Brève description...',
    'enter_name_in'            => 'Entrez le nom en :lang',
    'excerpt' => 'Extrait',
    'image' => 'Image',
    'video' => 'Vidéo',
    'author' => 'Auteur',
    'created_at' => 'Créé le',
    'updated_at' => 'Mis à jour le',

    // Messages
    'success' => 'Succès!',
    'error' => 'Erreur!',
    'created_successfully' => 'Créé avec succès',
    'updated_successfully' => 'Mis à jour avec succès',
    'deleted_successfully' => 'Supprimé avec succès',
    'error_occurred' => 'Une erreur est survenue. Veuillez réessayer.',
    'items_published' => 'éléments publiés avec succès',
    'operation_failed' => 'Opération échouée',

    // Pagination
    'showing' => 'Affichage',
    'to' => 'à',
    'of' => 'sur',
    'results' => 'résultats',
    'previous' => 'Précédent',
    'next' => 'Suivant',

    // Authentication
    'login' => 'Connexion',
    'email' => 'Email',
    'password' => 'Mot de passe',
    'remember_me' => 'Se souvenir de moi',
    'forgot_password' => 'Mot de passe oublié?',
    'login_subtitle' => 'Entrez vos identifiants pour accéder au tableau de bord',
    'admin_access_only' => 'Ce portail est réservé aux administrateurs',
    'unauthorized' => 'Non autorisé',
    'no_admin_access' => 'Vous n\'avez pas d\'accès administrateur',

    // Time
    'just_now' => 'À l\'instant',
    'minutes_ago' => 'Il y a :count minute|Il y a :count minutes',
    'hours_ago' => 'Il y a :count heure|Il y a :count heures',
    'days_ago' => 'Il y a :count jour|Il y a :count jours',
    'weeks_ago' => 'Il y a :count semaine|Il y a :count semaines',
    'months_ago' => 'Il y a :count mois',
    'years_ago' => 'Il y a :count an|Il y a :count ans',

    // Charts
    'content_growth' => 'Croissance du contenu',
    'popular_content' => 'Contenu populaire',
    'views' => 'Vues',
    'favorites' => 'Favoris',

    // Activity Types
    'activity_types' => [
        'article' => 'Article',
        'hadith' => 'Hadith',
        'media_program' => 'Programme média',
        'seerah_event' => 'Événement Seerah',
        'sahabi' => 'Compagnon',
    ],

    // Status Types
    'status_types' => [
        'published' => 'Publié',
        'draft' => 'Brouillon',
        'archived' => 'Archivé',
        'pending' => 'En attente de révision',
    ],

    // Bulk Actions
    'bulk_actions' => 'Actions groupées',
    'select_all' => 'Tout sélectionner',
    'bulk_delete' => 'Supprimer la sélection',
    'bulk_publish' => 'Publier la sélection',
    'bulk_archive' => 'Archiver la sélection',
    'items_selected' => ':count élément sélectionné|:count éléments sélectionnés',

    // Filters
    'filter' => 'Filtrer',
    'filters' => 'Filtres',
    'sort_by' => 'Trier par',
    'filter_by_status' => 'Filtrer par statut',
    'filter_by_category' => 'Filtrer par catégorie',
    'all' => 'Tout',
    'reset_filters' => 'Réinitialiser',

    // Dark/Light Mode
    'theme' => 'Thème',
    'light_mode' => 'Mode clair',
    'dark_mode' => 'Mode sombre',
    'toggle_theme' => 'Changer de thème',

    // Breadcrumbs
    'breadcrumb' => [
        'home' => 'Accueil',
        'dashboard' => 'Tableau de bord',
    ],

    // Hadith Sub-modules
    'hadith_collections' => 'Collections',
    'collection' => 'Collection',
    'hadith_narrators' => 'Narrateurs',
    'hadith_scholars' => 'Scholars',
    'hadith_themes' => 'Thèmes',
    'hadith_grading' => 'Niveaux de classement',

    // Hadith Management
    'add_hadith' => 'Ajouter un hadith',
    'add_new_hadith' => 'Ajouter un nouveau hadith',
    'create_new_hadith' => 'Créer un nouveau hadith dans le système',
    'hadith_information' => 'Informations sur le hadith',
    'manage_hadiths' => 'Gérer les hadiths',
    'hadith_number' => 'Numéro du hadith',
    'select_book' => 'Sélectionnez un livre',
    'enter_hadith_number' => 'Entrez le numéro du hadith',
    'narrator' => 'Narrateur',
    'select_narrator' => 'Sélectionnez un narrateur',
    'grading' => 'Classement',
    'select_grading' => 'Sélectionnez le niveau de classement',
    'companion_id' => 'ID du compagnon',
    'enter_companion_id' => 'Entrez l\'ID du compagnon',
    'create_hadith' => 'Créer un hadith',
    'back' => 'Retour',
    'edit_hadith' => 'Modifier le hadith',
    'save_changes' => 'Enregistrer les modifications',
    'hadith_details' => 'Détails du hadith',
    'basic_information' => 'Informations de base',
    'book' => 'Livre',
    'metadata' => 'Métadonnées',
    'id' => 'ID',
    'total_hadiths' => 'Total des hadiths',
    'current_page' => 'Page actuelle',
    'search_hadith_number' => 'Rechercher par numéro de hadith...',
    'no_hadiths_found' => 'Aucun hadith trouvé',
    'translations' => 'Traductions',
    'confirm_delete_hadith' => 'Êtes-vous sûr de vouloir supprimer ce hadith?',
    'ungraded' => 'Non encore noté',
    'delete_hadith' => 'Supprimer le hadith',

    // Additional Modules
    'series' => 'Séries',
    'activity_logs' => 'Journaux d\'activité',
    'scheduler' => 'Planificateur',

    // Data Table
    'data_table' => 'Tableau de données',
    'records_found' => 'résultats trouvés',
    'export' => 'Exporter',
    'export_csv' => 'Exporter CSV',
    'export_excel' => 'Exporter Excel',
    'export_pdf' => 'Exporter PDF',
    'selected' => 'sélectionné',
    'rows_per_page' => 'Lignes par page',
    'no_data_message' => 'Aucune donnée à afficher',
    'clear' => 'Effacer',
    'publish' => 'Publier',
    'trash' => 'Corbeille',
    'view_details' => 'Voir les détails',
    'search_placeholder' => 'Commencez à taper pour rechercher...',

    // Filter Sidebar
    'advanced_filters' => 'Filtres avancés',
    'filters_active'   => 'filtres actifs',
    'clear_all'        => 'Tout effacer',
    'date_range'       => 'Plage de dates',
    'from'             => 'De',
    'no_results'       => 'Aucun résultat trouvé',
    'special_filters'  => 'Filtres spéciaux',
    'featured_only'    => 'En vedette seulement',
    'pinned_only'      => 'Épinglés seulement',
    'reset_all'        => 'Tout réinitialiser',
    'apply_filters'    => 'Appliquer les filtres',

    // Category Not Found / Error Messages
    'category_not_found'     => 'La catégorie demandée est introuvable.',
    'category_delete_failed' => 'Échec de la suppression de la catégorie. Veuillez réessayer.',

    // Tag Not Found / Error Messages
    'tag_not_found'    => 'Le tag demandé est introuvable.',
    'tag_delete_failed' => 'Échec de la suppression du tag. Veuillez réessayer.',

    // Narrator Not Found / Error Messages
    'narrator_not_found'     => 'Le narrateur demandé est introuvable.',
    'narrator_delete_failed' => 'Échec de la suppression du narrateur. Veuillez réessayer.',
    'narrator_has_hadiths'   => 'Impossible de supprimer un narrateur lié à des hadiths.',

    // Series Not Found / Error Messages
    'series_not_found'     => 'La série demandée est introuvable.',
    'series_delete_failed' => 'Échec de la suppression de la série. Veuillez réessayer.',

    // Category CRUD Messages
    'category_created'                => 'Catégorie créée avec succès.',
    'category_updated'                => 'Catégorie mise à jour avec succès.',
    'category_deleted'                => 'Catégorie supprimée avec succès.',
    'category_restored'               => 'Catégorie restaurée avec succès.',
    'category_duplicated'             => 'Catégorie dupliquée avec succès.',
    'category_duplicated_edit'        => 'Catégorie dupliquée avec succès. Vous pouvez maintenant modifier la copie.',
    'categories_reordered'            => 'Catégories réorganisées avec succès.',
    'category_operation_failed'       => 'Opération sur la catégorie échouée. Veuillez réessayer.',
    'category_has_children'           => 'Impossible de supprimer une catégorie ayant des sous-catégories. Veuillez d\'abord supprimer les enfants.',
    'category_has_articles'           => 'Impossible de supprimer une catégorie avec :count article(s). Veuillez d\'abord réaffecter les articles.',
    'categories_bulk_deleted'         => ':deleted catégorie(s) supprimée(s) avec succès.',
    'categories_bulk_deleted_partial' => ':deleted catégorie(s) supprimée(s) avec succès (:failed échec).',

    // Tag CRUD Messages
    'tag_created'          => 'Étiquette créée avec succès.',
    'tag_updated'          => 'Étiquette mise à jour avec succès.',
    'tag_deleted'          => 'Étiquette supprimée avec succès.',
    'tag_duplicate_exists' => 'Une étiquette similaire existe déjà.',
    'tags_merged'          => 'Étiquettes fusionnées avec succès. :count article(s) déplacé(s).',
    'tags_bulk_deleted'    => ':count étiquette(s) supprimée(s) avec succès.',

    // Series CRUD Messages
    'series_created'            => 'Série créée avec succès.',
    'series_updated'            => 'Série mise à jour avec succès.',
    'series_deleted'            => 'Série supprimée avec succès.',
    'series_articles_reordered' => 'Articles réorganisés avec succès.',
    'series_bulk_deleted'       => ':count série(s) supprimée(s) avec succès.',

    // Reciter CRUD Messages
    'reciter_created'                => 'Récitateur créé avec succès.',
    'reciter_updated'                => 'Récitateur mis à jour avec succès.',
    'reciter_deleted'                => 'Récitateur supprimé avec succès.',
    'reciter_create_failed'          => 'Échec de la création du récitateur. Veuillez réessayer.',
    'reciter_update_failed'          => 'Échec de la mise à jour du récitateur. Veuillez réessayer.',
    'reciter_delete_failed'          => 'Échec de la suppression du récitateur. Veuillez réessayer.',
    'reciters_reordered'             => 'Récitateurs réorganisés avec succès.',
    'reciters_reorder_failed'        => 'Échec de la réorganisation des récitateurs. Veuillez réessayer.',
    'reciters_bulk_deleted'          => ':count récitateur(s) supprimé(s) avec succès.',
    'reciters_bulk_delete_failed'    => 'Échec de la suppression des récitateurs. Veuillez réessayer.',
    'reciter_featured_toggled'       => 'Statut en vedette mis à jour avec succès.',
    'reciter_featured_toggle_failed' => 'Échec de la mise à jour du statut en vedette. Veuillez réessayer.',
    'reciter_search_failed'          => 'Échec de la recherche de récitateurs. Veuillez réessayer.',

    // Locale Switch Messages
    'locale_unsupported' => 'La langue sélectionnée n\'est pas prise en charge.',
    'locale_changed'     => 'Langue changée avec succès.',

    // Narrator CRUD Messages
    'narrator_fetched'             => 'Narrateurs récupérés avec succès.',
    'narrator_created'             => 'Narrateur créé avec succès.',
    'narrator_updated'             => 'Narrateur mis à jour avec succès.',
    'narrator_deleted'             => 'Narrateur supprimé avec succès.',
    'narrator_restored'            => 'Narrateur restauré avec succès.',
    'narrator_bulk_deleted'        => ':count narrateur(s) supprimé(s) avec succès.',
    'narrator_statistics_fetched'  => 'Statistiques du narrateur récupérées avec succès.',

    // Scholar CRUD Messages
    'scholar_fetched'       => 'Savants récupérés avec succès.',
    'scholar_created'       => 'Savant créé avec succès.',
    'scholar_create_failed' => 'Échec de la création du savant. Veuillez réessayer.',
    'scholar_updated'       => 'Savant mis à jour avec succès.',
    'scholar_update_failed' => 'Échec de la mise à jour du savant. Veuillez réessayer.',
    'scholar_deleted'       => 'Savant supprimé avec succès.',
    'scholar_delete_failed' => 'Échec de la suppression du savant. Veuillez réessayer.',
    'scholar_bulk_deleted'  => ':count savant(s) supprimé(s) avec succès.',

    // Grading Level Messages
    'grading_levels_fetched'           => 'Niveaux de classement récupérés avec succès.',
    'grading_level_not_found'          => 'Niveau de classement introuvable.',
    'grading_level_fetched'            => 'Niveau de classement récupéré avec succès.',
    'grading_level_statistics_fetched' => 'Statistiques du niveau de classement récupérées avec succès.',

    // Theme CRUD Messages
    'theme_fetched'       => 'Thèmes récupérés avec succès.',
    'theme_created'       => 'Thème créé avec succès.',
    'theme_create_failed' => 'Échec de la création du thème. Veuillez réessayer.',
    'theme_updated'       => 'Thème mis à jour avec succès.',
    'theme_update_failed' => 'Échec de la mise à jour du thème. Veuillez réessayer.',
    'theme_has_content'   => 'Impossible de supprimer un thème lié à du contenu.',
    'theme_deleted'       => 'Thème supprimé avec succès.',
    'theme_delete_failed' => 'Échec de la suppression du thème. Veuillez réessayer.',
    'theme_bulk_deleted'  => ':count thème(s) supprimé(s) avec succès.',
];
