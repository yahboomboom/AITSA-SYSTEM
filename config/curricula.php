<?php

// Master list of curricula (programs) offered for admission. This is the single
// source of truth used by both the public application form (auth/apply.blade.php)
// and the Registrar's admission-slots management page, so a "program_key" here
// always means the same curriculum everywhere in the app.
return [
    // TESDA durations are each program's own nominal training hours per its
    // TESDA Training Regulation — not a calendar length. Actual calendar
    // duration depends on AITSA's own daily schedule for each batch, which
    // isn't modeled yet.
    ['id' => 'bk3',    'name' => 'Bookkeeping NC III',                            'level' => 'TESDA',     'icon' => 'fa-book-bookmark',      'duration' => '292 training hours'],
    ['id' => 'em3',    'name' => 'Events Management NC III',                       'level' => 'TESDA',     'icon' => 'fa-calendar-star',      'duration' => '108 training hours'],
    ['id' => 'fb3',    'name' => 'Food & Beverages NC III',                        'level' => 'TESDA',     'icon' => 'fa-utensils',           'duration' => '230 training hours'],
    ['id' => 'bom',    'name' => 'Business Office Management',                     'level' => 'ASSOCIATE', 'icon' => 'fa-briefcase',          'duration' => '2 years',  'abbr' => 'BoM'],
    ['id' => 'fsm',    'name' => 'Food Service Management',                        'level' => 'ASSOCIATE', 'icon' => 'fa-bowl-food',          'duration' => '2 years',  'abbr' => 'FSM'],
    ['id' => 'bsoa',   'name' => 'Bachelor in Science Office Administration',      'level' => 'BACHELOR',  'icon' => 'fa-landmark-flag',      'duration' => '4 years',  'abbr' => 'BSOA'],
    ['id' => 'btvted', 'name' => 'Bachelor in Technical Vocational Teacher Education', 'level' => 'BACHELOR', 'icon' => 'fa-chalkboard-user', 'duration' => '4 years',  'abbr' => 'BTVTED'],
];