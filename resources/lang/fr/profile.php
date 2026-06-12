<?php

declare(strict_types=1);

return [
    // General
    'profile' => 'Profil',
    'my_profile' => 'Mon profil',
    'welcome_back' => 'Bon retour',
    'member_since' => 'Membre depuis',
    'edit_profile' => 'Modifier le profil',

    // Tabs
    'tab_dashboard' => 'Tableau de bord',
    'tab_bookmarks' => 'Favoris',
    'tab_progress' => 'Progression',
    'tab_activity' => 'Activité',
    'tab_settings' => 'Paramètres',

    // Stats
    'stats_title' => 'Mes statistiques',
    'engagement_score' => "Score d'engagement",
    'engagement_level' => "Niveau d'engagement",
    'streak_days' => 'Jours de série',
    'current_streak' => 'Série actuelle',
    'total_interactions' => 'Interactions totales',
    'total_views' => 'Vues',
    'total_bookmarks' => 'Favoris',
    'total_likes' => "J'aime",
    'total_shares' => 'Partages',
    'time_spent' => 'Temps passé',
    'days' => 'jours',

    // Recent activity
    'recent_activity' => 'Activité récente',
    'no_activity_title' => "Pas encore d'activité",
    'no_activity_message' => 'Parcourez les articles, hadiths et le Coran et votre activité apparaîtra ici.',
    'activity_viewed' => 'A consulté',
    'activity_bookmarked' => 'A enregistré',
    'activity_liked' => 'A aimé',
    'activity_completed' => 'A terminé',
    'activity_on' => 'le',

    // Progress
    'progress_title' => 'Progression de lecture',
    'no_progress_title' => 'Aucune progression enregistrée',
    'no_progress_message' => 'Commencez à lire et nous suivrons votre progression automatiquement.',
    'completion_rate' => "Taux d'achèvement",
    'longest_streak' => 'Plus longue série',

    // Bookmarks
    'bookmarks_title' => 'Mes favoris',
    'bookmarks_subtitle' => 'Tout ce que vous avez enregistré, au même endroit.',
    'view_all_bookmarks' => 'Voir tous les favoris',
    'no_bookmarks_title' => 'Aucun favori pour le moment',
    'no_bookmarks_message' => 'Enregistrez des articles, hadiths, versets et événements pour les retrouver ici.',
    'browse_content' => 'Parcourir le contenu',
    'bookmark_untitled' => 'Élément sans titre',
    'bookmark_unavailable' => 'Contenu indisponible',
    'open' => 'Ouvrir',
    'remove' => 'Retirer',
    'saved_on' => 'Enregistré le',

    // Bookmark filters / types
    'filter_all' => 'Tout',
    'type_article' => 'Articles',
    'type_hadith' => 'Hadiths',
    'type_quran' => 'Versets',
    'type_seerah' => 'Événements de la Sîra',
    'type_media' => 'Programmes médias',
    'type_unknown' => 'Inconnu',

    // Bookmark display references
    'hadith_number' => 'Hadith n° :number',
    'verse_reference' => 'Verset :key',

    // Bookmark action messages
    'bookmark_added' => 'Ajouté à vos favoris',
    'bookmark_removed' => 'Retiré de vos favoris',
    'bookmark_exists' => 'Déjà dans vos favoris',
    'bookmark_not_found' => 'Élément introuvable dans vos favoris',
    'bookmark_content_missing' => "Le contenu demandé n'existe pas",
    'bookmark_failed' => "Impossible d'effectuer l'action, veuillez réessayer",
    'login_to_bookmark' => 'Connectez-vous pour enregistrer ce contenu',

    // Settings
    'settings_title' => 'Paramètres du compte',
    'account_settings' => 'Détails du compte',
    'account_settings_hint' => 'Mettez à jour votre nom, e-mail et langue préférée.',
    'name' => 'Nom',
    'email' => 'E-mail',
    'language' => 'Langue préférée',
    'save_changes' => 'Enregistrer les modifications',
    'saving' => 'Enregistrement...',
    'updated' => 'Votre profil a été mis à jour avec succès',

    'password_settings' => 'Changer le mot de passe',
    'password_settings_hint' => 'Utilisez un mot de passe fort que vous ne réutilisez pas ailleurs.',
    'current_password' => 'Mot de passe actuel',
    'new_password' => 'Nouveau mot de passe',
    'confirm_password' => 'Confirmer le nouveau mot de passe',
    'update_password' => 'Mettre à jour le mot de passe',
    'password_updated' => 'Votre mot de passe a été mis à jour avec succès',

    'data_settings' => 'Mes données',
    'download_my_data' => 'Télécharger mes données',
    'download_my_data_hint' => 'Obtenez une copie de vos données d\'engagement.',

    // States
    'loading' => 'Chargement...',
    'error_title' => 'Une erreur est survenue',
    'error_message' => 'Impossible de charger ces données, veuillez réessayer plus tard.',
    'retry' => 'Réessayer',

    // Avatar
    'change_avatar' => 'Changer la photo de profil',
    'avatar_updated' => 'Votre photo de profil a été mise à jour avec succès',
    'avatar_invalid' => 'Veuillez choisir une image JPG, PNG ou WebP de 2 Mo maximum.',
    'avatar_dimensions' => 'L\'image doit mesurer entre 64×64 et 4096×4096 pixels.',
    'avatar_failed' => 'Impossible d\'enregistrer votre photo, veuillez réessayer.',

    // Email verification
    'email_unverified_banner' => 'Votre adresse e-mail n\'est pas encore vérifiée. Consultez votre boîte de réception ou renvoyez le lien.',
    'resend_verification' => 'Renvoyer le lien de vérification',

    // Account deletion
    'delete_account' => 'Supprimer le compte',
    'delete_account_hint' => 'Supprimez définitivement votre compte, vos signets et votre activité. Cette action est irréversible.',
    'delete_account_confirm_title' => 'Supprimer votre compte ?',
    'delete_account_confirm_message' => 'Cette action supprime définitivement votre compte et toutes vos données. Elle est irréversible.',
    'delete_type_to_confirm' => 'Saisissez « :word » pour confirmer',
    'delete_confirm_word' => 'SUPPRIMER',
    'delete_password_label' => 'Votre mot de passe',
    'delete_password_mismatch' => 'Le mot de passe saisi est incorrect.',
    'delete_account_cta' => 'Supprimer définitivement',
    'cancel' => 'Annuler',
    'account_deleted' => 'Votre compte a été supprimé. Vous serez toujours le bienvenu parmi nous.',
];
