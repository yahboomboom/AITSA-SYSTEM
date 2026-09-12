<?php

// Master list of curricula (programs) offered for admission. This is the single
// source of truth used by both the public application form (auth/apply.blade.php)
// and the Registrar's admission-slots management page, so a "program_key" here
// always means the same curriculum everywhere in the app.
//
// program_code must match the `code` column of the `programs` table exactly —
// it's what gets stored on the student's `major` column once they're admitted
// (see AuthController::processApplication), and User::program() resolves a
// student's Program by looking up that code. The 'name' above is only ever a
// display label and does NOT match `programs.name` verbatim for every row, so
// it can't be used for that lookup.
return [
    // TESDA durations are each program's own nominal training hours per its
    // TESDA Training Regulation — not a calendar length. Actual calendar
    // duration depends on AITSA's own daily schedule for each batch, which
    // isn't modeled yet.
    ['id' => 'bk3',    'name' => 'Bookkeeping NC III',                            'level' => 'TESDA',     'icon' => 'fa-book-bookmark',      'duration' => '292 training hours', 'program_code' => 'BKNC3'],
    ['id' => 'em3',    'name' => 'Events Management NC III',                       'level' => 'TESDA',     'icon' => 'fa-calendar-star',      'duration' => '108 training hours', 'program_code' => 'EMNC3'],
    ['id' => 'fb3',    'name' => 'Food & Beverages NC III',                        'level' => 'TESDA',     'icon' => 'fa-utensils',           'duration' => '230 training hours', 'program_code' => 'FBNC3'],
    ['id' => 'bom',    'name' => 'Business Office Management',                     'level' => 'ASSOCIATE', 'icon' => 'fa-briefcase',          'duration' => '2 years',  'abbr' => 'BoM',    'program_code' => 'BOM'],
    ['id' => 'fsm',    'name' => 'Food Service Management',                        'level' => 'ASSOCIATE', 'icon' => 'fa-bowl-food',          'duration' => '2 years',  'abbr' => 'FSM',    'program_code' => 'FSM'],
    ['id' => 'bsoa',   'name' => 'Bachelor in Science Office Administration',      'level' => 'BACHELOR',  'icon' => 'fa-landmark-flag',      'duration' => '4 years',  'abbr' => 'BSOA',   'program_code' => 'BSOA'],
    ['id' => 'btvted', 'name' => 'Bachelor in Technical Vocational Teacher Education', 'level' => 'BACHELOR', 'icon' => 'fa-chalkboard-user', 'duration' => '4 years',  'abbr' => 'BTVTED', 'program_code' => 'BTVTED'],
];