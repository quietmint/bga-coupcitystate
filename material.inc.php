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
       'icon' => 'mdi-cash',
       'text' => clienttranslate('Take ₤1.'),
       'subtext' => '',
       'balloonAttempt' => '',
       'logAttempt' => '',
       'logExecute' => clienttranslate('${player_name} takes ₤1 as income.'),
       'target' => false,
       'cost' => 0,
       'character' => 0,
       'blockers' => array(),
    ),
    2 => array(
       'name' => clienttranslate('Foreign Aid'),
       'icon' => 'mdi-gift',
       'text' => clienttranslate('Take ₤2.'),
       'subtext' => '',
       'balloonAttempt' => clienttranslate('I will take foreign aid...'),
       'logAttempt' => clienttranslate('${player_name} attempts to take ₤2 as foreign aid...'),
       'logExecute' => clienttranslate('${player_name} takes ₤2 as foreign aid.'),
       'target' => false,
       'cost' => 0,
       'character' => 0,
       'blockers' => array(1),
    ),
    3 => array(
       'name' => clienttranslate('Coup'),
       'icon' => 'mdi-alert-decagram',
       'text' => clienttranslate('Pay ₤7.'),
       'subtext' => clienttranslate('Choose player to lose influence.'),
       'balloonAttempt' => '',
       'logAttempt' => '',
       'logExecute' => clienttranslate('${player_name} pays ₤7 to launch a coup against ${player_name2}.'),
       'target' => true,
       'cost' => 7,
       'character' => 0,
       'blockers' => array(),
    ),
    4 => array(
       'name' => clienttranslate('Tax'),
       'icon' => 'mdi-crown',
       'text' => clienttranslate('Take ₤3.'),
       'subtext' => '',
       'balloonAttempt' => clienttranslate('I will collect tax...'),
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to take ₤3 as tax...'),
       'logExecute' => clienttranslate('${player_name} takes ₤3 as tax.'),
       'target' => false,
       'cost' => 0,
       'character' => 1,
       'blockers' => array(),
    ),
    7 => array(
       'name' => clienttranslate('Steal'),
       'icon' => 'mdi-anchor',
       'text' => clienttranslate('Steal ₤2 from another player.'),
       'subtext' => '',
       'balloonAttempt' => clienttranslate('I will steal from ${player_name2}...'),
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to steal from ${player_name2}...'),
       'logExecute' => clienttranslate('${player_name} steals ₤${amount} from ${player_name2}.'),
       'target' => true,
       'cost' => 0,
       'character' => 4,
       'blockers' => array(3, 4),
    ),
    6 => array(
       'name' => clienttranslate('Exchange'),
       'icon' => 'mdi-swap-horizontal',
       'text' => clienttranslate('Exchange 2 cards with the deck.'),
       'subtext' => '',
       'balloonAttempt' => clienttranslate('I will exchange cards...'),
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to exchange cards...'),
       'logExecute' => clienttranslate('${player_name} draws ${count} cards from the deck.'),
       'target' => false,
       'cost' => 0,
       'character' => 3,
       'blockers' => array(),
    ),
    5 => array(
       'name' => clienttranslate('Assassinate'),
       'icon' => 'mdi-skull',
       'text' => clienttranslate('Pay ₤3.'),
       'subtext' => clienttranslate('Choose player to lose influence.'),
       'balloonAttempt' => clienttranslate('I will assassinate ${player_name2}...'),
       'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to assassinate ${player_name2}...'),
       'logExecute' => clienttranslate('${player_name} pays ₤3 to assassinate ${player_name2}.'),
       'target' => true,
       'cost' => 3,
       'character' => 2,
       'blockers' => array(5),
    )
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
        'text' => clienttranslate('Take ₤3.'),
        'subtext' => clienttranslate('Blocks foreign aid.'),
        'color' => '#50235f',
        'color_bright' => '#9C27B0',
    ),
    2 => array(
        'name' => clienttranslate('Assassin'),
        'text' => clienttranslate('Pay ₤3.'),
        'subtext' => clienttranslate('Choose player to lose influence.'),
        'color' => '#5f380c',
        'color_bright' => '#795548',
    ),
    3 => array(
        'name' => clienttranslate('Ambassador'),
        'text' => clienttranslate('Exchange 2 cards with the deck.'),
        'subtext' => clienttranslate('Blocks stealing.'),
        'color' => '#038213',
        'color_bright' => '#689F38',
    ),
    4 => array(
        'name' => clienttranslate('Captain'),
        'text' => clienttranslate('Steal ₤2 from another player.'),
        'subtext' => clienttranslate('Blocks stealing.'),
        'color' => '#303090',
        'color_bright' => '#2962FF',
    ),
    5 => array(
        'name' => clienttranslate('Contessa'),
        'text' => '',
        'subtext' => clienttranslate('Blocks assassination.'),
        'color' => '#980003',
        'color_bright' => '#D50000',
    ),
);

$this->balloons = array(
    'block'     => clienttranslate('I block ${player_name2} with my ${card_name}'),
    'challenge' => clienttranslate('I challenge ${player_name2}\'s ${card_name}'),
    'die'       => clienttranslate('I lose the ${card_name}'),
    'die2'      => clienttranslate('I lose the ${card_name} and the ${card_name2}'),
    'discard'   => clienttranslate('I discard ${count} cards'),
    'draw'      => clienttranslate('I draw ${count} cards'),
    'kill'      => clienttranslate('I kill ${player_name2}'),
    'lie'       => clienttranslate('I was bluffing. ${player_name2} challenged correctly.'),
    'truth'     => clienttranslate('I was truthful. ${player_name2} challenged incorrectly.'),
    'wealth'    => clienttranslate('I take ₤${amount}'),
);

$this->reasonText = array(
    1 => clienttranslate('challenged to reveal the ${card_name}'),
    2 => clienttranslate('killed by losing the challenge'),
    3 => clienttranslate('killed by ${player_name2}'),
    4 => clienttranslate('killed by losing the challenge and by ${player_name2}')
);
