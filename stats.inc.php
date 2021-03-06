<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Coup implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * Coup game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.

    !! After modifying this file, you must use 'Reload  statistics configuration' in BGA Studio backoffice
    ('Control Panel' / 'Manage Game' / 'Your Game')

    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be 'int' for integer, 'float' for floating point values, and 'bool' for boolean

    Once you defined your statistics there, you can start using 'initStat', 'setStat' and 'incStat' method
    in your game logic, using statistics names defined below.

    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress

    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players

*/

$stats_type = array(

    // Statistics global to table
    'table' => array(),

    // Statistics existing for each player
    'player' => array(
        'turns' => array(
            'id' => 10,
            'type' => 'int',
            'name' => totranslate('Turns')
        ),
        'wealthIn' => array(
            'id' => 11,
            'type' => 'int',
            'name' => totranslate('Coins collected')
        ),
        'wealthOut' => array(
            'id' => 12,
            'type' => 'int',
            'name' => totranslate('Coins spent')
        ),
        'truth' => array(
            'id' => 13,
            'type' => 'int',
            'name' => totranslate('Truths')
        ),
        'lie' => array(
            'id' => 14,
            'type' => 'int',
            'name' => totranslate('Lies')
        ),
        'honesty' => array(
            'id' => 15,
            'type' => 'int',
            'name' => totranslate('Honesty (%)')
        ),
        'action1' => array(
            'id' => 21,
            'type' => 'int',
            'name' => totranslate('Income')
        ),
        'action2' => array(
            'id' => 22,
            'type' => 'int',
            'name' => totranslate('Foreign Aid')
        ),
        'action3' => array(
            'id' => 23,
            'type' => 'int',
            'name' => totranslate('Coup')
        ),
        'action4' => array(
            'id' => 24,
            'type' => 'int',
            'name' => totranslate('Tax')
        ),
        'action7' => array(
            'id' => 27,
            'type' => 'int',
            'name' => totranslate('Steal')
        ),
        'action6' => array(
            'id' => 26,
            'type' => 'int',
            'name' => totranslate('Exchange')
        ),
        'action5' => array(
            'id' => 25,
            'type' => 'int',
            'name' => totranslate('Assassinate')
        ),
        'action8' => array(
            'id' => 28,
            'type' => 'int',
            'name' => totranslate('Convert')
        ),
        'action9' => array(
            'id' => 29,
            'type' => 'int',
            'name' => totranslate('Embezzle')
        ),
        'action11' => array(
            'id' => 40,
            'type' => 'int',
            'name' => totranslate('Examine')
        ),
        'action12' => array(
            'id' => 41,
            'type' => 'int',
            'name' => totranslate('Cooperate')
        ),
        'blockIssued' => array(
            'id' => 50,
            'type' => 'int',
            'name' => totranslate('Blocks issued')
        ),
        'blockReceived' => array(
            'id' => 59,
            'type' => 'int',
            'name' => totranslate('Blocks received')
        ),
        'challengeIssued' => array(
            'id' => 30,
            'type' => 'int',
            'name' => totranslate('Challenges issued')
        ),
        'challengeReceived' => array(
            'id' => 39,
            'type' => 'int',
            'name' => totranslate('Challenges received')
        ),
        'challengeWin' => array(
            'id' => 31,
            'type' => 'int',
            'name' => totranslate('Challenges won')
        ),
        'challengeLoss' => array(
            'id' => 32,
            'type' => 'int',
            'name' => totranslate('Challenges lost')
        )
    )
);
