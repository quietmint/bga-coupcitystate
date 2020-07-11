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
 * gameoptions.inc.php
 *
 * Coup game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in coupcitystate.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    101 => array(
        'name' => totranslate('Factions'),
        'values' => array(
            0 => array(
                'name' => totranslate('No')
            ),
            1 => array(
                'name' => totranslate('Yes'),
                'tmdisplay' => totranslate('Factions'),
                'description' => totranslate('Cannot attack teammates while 2 teams exist'),
                'nobeginner' => true
            ),
        ),
        'startcondition' => array(
            1 => array(
                array(
                    'type' => 'minplayers',
                    'value' => 3,
                    'message' => totranslate('Factions requires 3+ players.')
                )
            ),
        ),
    ),

    102 => array(
        'name' => totranslate('Inquisitor'),
        'values' => array(
            0 => array(
                'name' => totranslate('No')
            ),
            1 => array(
                'name' => totranslate('Yes'),
                'tmdisplay' => totranslate('Inquisitor'),
                'description' => totranslate('Inquisitor replaces Ambassador'),
                'nobeginner' => true
            ),
        ),
    ),

    103 => array(
        'name' => totranslate('Diplomat'),
        'values' => array(
            0 => array(
                'name' => totranslate('No')
            ),
            1 => array(
                'name' => totranslate('Yes'),
                'tmdisplay' => totranslate('Diplomat'),
                'description' => totranslate('Diplomat replaces Duke')
            ),
        ),
    ),
);
