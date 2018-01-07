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
    100 => array(
        'name' => totranslate('Rounds to win'),
        'values' => array(
            1 => array(
                'name' => '1'
            ),
            2 => array(
                'name' => '2',
                'tmdisplay' => totranslate('2 rounds to win')
            ),
            3 => array(
                'name' => '3',
                'tmdisplay' => totranslate('3 rounds to win')
            ),
            4 => array(
                'name' => '4',
                'tmdisplay' => totranslate('4 rounds to win')
            ),
            5 => array(
                'name' => '5',
                'tmdisplay' => totranslate('5 rounds to win')
            ),
        )
    ),

    101 => array(
        'name' => totranslate('Factions'),
        'values' => array(
            0 => array(
                'name' => totranslate('No')
            ),
            1 => array(
                'name' => totranslate('Yes'),
                'tmdisplay' => totranslate('Factions'),
                'nobeginner' => true
            )
        )
    )
);
