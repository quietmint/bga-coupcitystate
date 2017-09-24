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
 * material.inc.php
 *
 * Coup game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->actions = array(
    1 => array(
       'name' => clienttranslate('Income'),
       'text' => clienttranslate('Take 1 coin.'),
       'subtext' => '',
       'logAttempt' => '',
       'logExecute' => clienttranslate('${player_name} takes 1 coin as income.'),
       'target' => false,
       'cost' => 0,
       'character' => 0,
       'blockers' => array(),
    ),
    2 => array(
       'name' => clienttranslate('Foreign Aid'),
       'text' => clienttranslate('Take 2 coins.'),
       'subtext' => '',
       'logAttempt' => clienttranslate('${player_name} attempts to take 2 coins as foreign aid...'),
       'logExecute' => clienttranslate('${player_name} takes 2 coins as foreign aid.'),
       'target' => false,
       'cost' => 0,
       'character' => 0,
       'blockers' => array(1),
    ),
    3 => array(
       'name' => clienttranslate('Coup'),
       'text' => clienttranslate('Pay 7 coins.'),
       'subtext' => clienttranslate('Choose player to lose influence.'),
       'logAttempt' => '',
       'logExecute' => clienttranslate('${player_name} pays 7 coins to launch a coup against ${player_name2}.'),
       'target' => true,
       'cost' => 7,
       'character' => 0,
       'blockers' => array(),
    ),
    4 => array(
       'name' => clienttranslate('Tax'),
       'text' => clienttranslate('Take 3 coins.'),
       'subtext' => '',
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to take 3 coins as tax...'),
       'logExecute' => clienttranslate('${player_name} takes 3 coins as tax.'),
       'target' => false,
       'cost' => 0,
       'character' => 1,
       'blockers' => array(),
    ),
    5 => array(
       'name' => clienttranslate('Assassinate'),
       'text' => clienttranslate('Pay 3 coins.'),
       'subtext' => clienttranslate('Choose player to lose influence.'),
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to assassinate ${player_name2}...'),
       'logExecute' => clienttranslate('${player_name} pays 3 coins to assassinate ${player_name2}.'),
       'target' => true,
       'cost' => 3,
       'character' => 2,
       'blockers' => array(5),
    ),
    6 => array(
       'name' => clienttranslate('Exchange'),
       'text' => clienttranslate('Exchange cards with the deck.'),
       'subtext' => '',
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to exchange cards...'),
       'logExecute' => clienttranslate('${player_name} draws 2 cards from the deck.'),
       'target' => false,
       'cost' => 0,
       'character' => 3,
       'blockers' => array(),
    ),
    7 => array(
       'name' => clienttranslate('Steal'),
       'text' => clienttranslate('Steal 2 coins from another player.'),
       'subtext' => '',
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to steal from ${player_name2}...'),
       'logExecute' => clienttranslate('${player_name} steals ${amount} coin(s) from ${player_name2}.'),
       'target' => true,
       'cost' => 0,
       'character' => 4,
       'blockers' => array(3, 4),
    ),
);

$this->characters = array(
    0 => array(
        'name' => null,
        'text' => null,
        'color' => null,
        'color_bright' => null,
    ),
    1 => array(
        'name' => clienttranslate('Duke'),
        'text' => clienttranslate('Take 3 coins.'),
        'subtext' => clienttranslate('Blocks Foreign Aid.'),
        'color' => '#50235f',
        'color_bright' => '#9C27B0',
    ),
    2 => array(
        'name' => clienttranslate('Assassin'),
        'text' => clienttranslate('Pay 3 coins.'),
        'subtext' => clienttranslate('Choose player to lose influence.'),
        'color' => '#5f380c',
        'color_bright' => '#795548',
    ),
    3 => array(
        'name' => clienttranslate('Ambassador'),
        'text' => clienttranslate('Exchange cards with the deck.'),
        'subtext' => clienttranslate('Blocks Captain.'),
        'color' => '#038213',
        'color_bright' => '#689F38',
    ),
    4 => array(
        'name' => clienttranslate('Captain'),
        'text' => clienttranslate('Steal 2 coins from another player.'),
        'subtext' => clienttranslate('Blocks Captain.'),
        'color' => '#303090',
        'color_bright' => '#2962FF',
    ),
    5 => array(
        'name' => clienttranslate('Contessa'),
        'text' => '',
        'subtext' => clienttranslate('Blocks Assassin.'),
        'color' => '#980003',
        'color_bright' => '#D50000',
    ),
);

$this->reasonText = array(
    1 => clienttranslate('challenged to reveal the ${card_name}'),
    2 => clienttranslate('killed by losing the challenge'),
    3 => clienttranslate('killed by ${player_name2}'),
    4 => clienttranslate('killed by losing the challenge and by ${player_name2}'),
);
