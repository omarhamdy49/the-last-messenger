<?php

declare(strict_types=1);

return [
    // SEO / page meta
    'page_title' => 'The Noble Companions',
    'page_description' => 'Explore the lives, virtues, and heroic stands of the Companions of the Messenger of Allah ﷺ.',

    // Hero
    'hero_title' => 'The Noble Companions',
    'hero_subtitle' => 'Men who were true to their covenant with Allah and carried Islam to the world',

    // Stats
    'stat_total' => 'Companions',
    'stat_featured' => 'Featured',
    'stat_narrators' => 'Hadith Narrators',
    'stat_male' => 'Men',
    'stat_female' => 'Women',

    // Search & filters
    'search_placeholder' => 'Search for a companion by name...',
    'filter_all' => 'All',
    'filter_featured' => 'The Nearest',
    'filter_ansar' => 'Ansar',
    'filter_muhajir' => 'Muhajirun',
    'companions_unit' => 'companions',

    // States
    'empty_title' => 'No companions found',
    'empty_message' => 'We found no matching results. Try adjusting your search or resetting the filters.',
    'loading' => 'Loading companions...',

    // Single page sections
    'profile_card' => 'Profile Card',
    'section_story' => 'Biography',
    'section_virtues' => 'Virtues',
    'section_narrations' => 'Narrations',
    'section_martyrdom' => 'Martyrdom',
    'section_timeline' => 'Timeline',
    'section_related' => 'Related',

    // Info card labels
    'full_name' => 'Full Name',
    'kunya' => 'Kunya',
    'laqab' => 'Title',
    'tribe' => 'Tribe',
    'clan' => 'Clan',
    'birth_place' => 'Place of Birth',
    'death_place' => 'Place of Death',
    'burial_place' => 'Place of Burial',
    'conversion' => 'Conversion',
    'conversion_order' => 'Order of Conversion',
    'age_at_death' => 'Age at Death',
    'death_year' => 'Year of death',
    'roles' => 'Roles',
    'battles' => 'Battles & Expeditions',
    'hadith_narrated' => 'Hadiths Narrated',
    'year_hijri' => 'AH',
    'years_unit' => 'years',
    'order_unit' => 'Order',

    // Related
    'related_companions' => 'Related Companions',
    'related_article' => 'Related Article',
    'view_profile' => 'View Profile',

    // Actions
    'bookmark' => 'Save to Favorites',
    'bookmarked' => 'Saved',
    'share' => 'Share Page',
    'link_copied' => 'Link copied',
    'show_more' => 'Read more',
    'show_less' => 'Show less',

    // Misc
    'no_biography' => 'No biography has been added for this companion yet.',

    'errors' => [
        'self_parent' => 'A person cannot be their own parent.',
        'ancestor_cycle' => 'Cannot assign this parent: :name already appears in their ancestry.',
        'self_relationship' => 'A person cannot be related to themselves.',
        'parent_has_children' => 'This person is recorded as another person\'s parent.',
        'relationship_exists' => 'That relationship is already recorded between these two people.',
        'not_found' => 'Not found',
        'query_too_short' => 'Enter at least two characters.',
        'name_required' => 'Provide at least an Arabic or an English name.',
    ],
    'relations' => 'Relations',
    'tree' => 'Family tree',
    'unpublished' => 'Unpublished',
    'published' => 'Published',
    'all_roles' => 'All roles',
    'apply' => 'Apply',
    'none_found' => 'No results',
    'add' => 'Add companion',
    'edit' => 'Edit',
    'save' => 'Save',
    'saved' => 'Saved successfully',
    'deleted' => 'Deleted',
    'bio' => 'Biography',
    'slug' => 'Slug',
    'birth_year' => 'Birth year',
    'hijri' => 'AH',
    'before_hijra' => 'before Hijra',
    'status' => 'Status',
    'actions' => 'Actions',
    'hide_from_tree' => 'Hide from tree',
    'tree_display_order' => 'Display order',
    'ancestors' => 'Ancestors',
    'descendants' => 'Descendants',
    'no_ancestors' => 'No ancestors recorded',
    'no_descendants' => 'No descendants recorded',
    'depth_capped' => 'The tree depth limit was reached.',
    'father' => 'Father',
    'mother' => 'Mother',
    'picker_placeholder' => 'Type at least two characters',
    'admin_subtitle' => 'Manage companions, roles and the family tree',

    // Sidebar / page title for the unified person identity.
    'title' => 'Islamic Figures',

    // Admin form: labels and section headings.
    'name' => 'Name',
    'gender' => 'Gender',
    'content' => 'Full biography',
    'generation' => 'Generation',
    'generation_hint' => 'e.g. companion, successor, successor of the successors',
    'thumb' => 'Image path',
    'thumb_hint' => 'A single image path or URL',
    'featured_in_role' => 'Featured in this role',
    'section_identity' => 'Identity & names',
    'section_identity_hint' => 'A name in Arabic or in English is enough',
    'section_dates' => 'Birth & death',
    'section_roles' => 'Islamic roles',
    'section_roles_hint' => 'One person carries every role that applies — never a duplicate record',
    'section_lineage' => 'Lineage',
    'section_publishing' => 'Publishing & display',
    'create_title' => 'Add an Islamic figure',
    'edit_title' => 'Edit: :name',
    'back_to_list' => 'Back to the list',
    'no_roles_seeded' => 'No roles exist yet. Run the IslamicRoleSeeder first.',
    'view_public' => 'View on the site',

    // Admin: role-scoped list headers. `:role` is the role name.
    'admin_role_title' => 'Manage :role',
    'admin_role_subtitle' => 'Everyone carrying the :role role in the encyclopedia',
    'admin_all_title' => 'Manage Islamic Figures',
    'admin_all_subtitle' => 'One record per figure, with their Islamic roles and lineage links',
    'role_scope' => 'Role scope',
    'add_person' => 'Add a figure',
    'add_in_role' => 'Add a :role',

    // Admin: the person-picker modal (father / mother selection).
    'picker_modal' => [
        'subtitle' => 'The most recently added people — search and filter to narrow down',
        'search_placeholder' => 'Search by name or kunya...',
        'empty' => 'No matching people',
        'showing' => 'Showing',
        'choose' => 'Choose',
        'change' => 'Change',
        'close' => 'Close',
        'previous' => 'Previous',
        'next' => 'Next',
        'none_selected' => 'Nothing selected',
    ],

    // Admin: genealogy control (descent + non-descent edges).
    'genealogy' => [
        'title' => 'Genealogy control',
        'subtitle' => 'Ancestors, descendants and relations — drafts included',
        'person_title' => 'Lineage of :name',
        'person_subtitle' => 'Parents, tree placement and family relations',
        'explorer' => 'Tree explorer',
        'root' => 'Tree root',
        'change_root' => 'Change root',
        'root_hint' => 'Type at least two characters to root the tree at a person',
        'depth' => 'Generations',
        'apply' => 'Show',
        'ancestors' => 'Ancestors (upward)',
        'descendants' => 'Descendants (downward)',
        'no_ancestors' => 'No ancestors recorded for this person',
        'no_descendants' => 'No descendants recorded for this person',
        'depth_capped' => 'The requested limit (:depth generations) was reached; the lineage may go deeper than shown.',
        'generation' => 'Generation :depth',
        'manage' => 'Manage lineage',
        'open_profile' => 'Full profile',
        'no_people' => 'There are no figures in the system yet.',

        // Stats
        'stat_people' => 'Total figures',
        'stat_linked' => 'With a parent',
        'stat_roots' => 'Without parents',
        'stat_edges' => 'Relations',
        'stat_hidden' => 'Hidden from the tree',

        // Parents / placement
        'parents' => 'Parents',
        'parents_hint' => 'A descendant cannot be assigned as a parent — that is rejected to keep the tree renderable',
        'placement' => 'Placement in the tree',
        'children' => 'Recorded children',
        'children_hint' => 'A child is recorded from their own lineage page, by naming this person as their parent',
        'no_children' => 'No children recorded',
        'clear_parent' => 'Clear',

        // Relationships
        'relationships' => 'Family relations',
        'relationships_hint' => 'Parent and child are managed by the parent fields above, never recorded here',
        'add_relationship' => 'Add a relation',
        'edit_relationship' => 'Edit relation',
        'relationship_type' => 'Relation type',
        'related_person' => 'The other person',
        'notes' => 'Notes',
        'sort_order' => 'Display order',
        'hidden_from_tree' => 'Hidden from the public tree',
        'reversed_hint' => 'Stored from the other side',
        'no_relationships' => 'No family relations recorded',
        'stored_as' => 'Stored as',
    ],
    'relationship_added' => 'Relation added',
    'relationship_deleted' => 'Relation deleted',
    'no_people_in_role' => 'Nobody carries this role yet',

    // Admin: Islamic roles management page.
    'roles_page' => [
        'title' => 'Islamic Roles',
        'subtitle' => 'The capacities assigned to figures: companion, narrator, scholar, grader, compiler',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'slug' => 'Slug',
        'people' => 'People',
        'published' => 'Published',
        'featured' => 'Featured',
        'view_people' => 'View people',
        'total_people' => 'Total figures',
        'roles_count' => 'Roles',
        'unassigned' => 'No role',
        'unassigned_hint' => 'Figures with no Islamic role assigned yet',
        'empty' => 'No roles found. Run the IslamicRoleSeeder to create them.',
        'fixed_notice' => 'Roles are fixed in the system because their slugs are referenced in code, so they are not added or deleted here.',
    ],

    // Admin: extended profile. Keys match MuslimProfile::fields() — a new
    // field needs a line added here and nowhere else.
    'profile_groups' => [
        'identity' => 'Extended identity',
        'birth' => 'Birth detail',
        'death' => 'Death detail',
        'mission' => 'Start of the mission',
    ],
    'profile' => [
        'full_name' => 'Full name',
        'tribe' => 'Tribe',
        'birth_day' => 'Birth day and month',
        'birth_region' => 'Region',
        'birth_year_ce' => 'Birth year (CE)',
        'death_day' => 'Death day and month',
        'death_place' => 'Place of death',
        'death_detail' => 'Death detail',
        'death_year_ce' => 'Death year (CE)',
        'mission_place' => 'Place revelation began',
        'mission_age' => 'Age at the mission',
        'mission_year_ce' => 'Mission year (CE)',
        'mission_detail' => 'Mission detail',
    ],

    'record_meta' => 'Record data',
    'section_overview' => 'Overview',

    'before_hijra_short' => 'BH',

    'unsaved_changes' => 'You have unsaved changes',
    'no_changes' => 'No changes',
    'saving' => 'Saving...',
];
