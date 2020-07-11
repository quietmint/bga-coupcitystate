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
   INCOME => array(
      'name' => clienttranslate('Income'),
      'icon' => 'mdi-cash',
      'text1' => clienttranslate('Take ₤1.'),
      'text2' => '',
      'balloonAttempt' => '',
      'logAttempt' => '',
      'logExecute' => clienttranslate('${player_name} takes ₤1 as income.'),
      'target' => false,
      'cost' => 0,
      'amount' => 1,
      'character' => 0,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => null,
   ),
   FOREIGN_AID => array(
      'name' => clienttranslate('Foreign Aid'),
      'icon' => 'mdi-gift',
      'text1' => clienttranslate('Take ₤2.'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will take foreign aid...'),
      'logAttempt' => clienttranslate('${player_name} attempts to take ₤2 as foreign aid...'),
      'logExecute' => clienttranslate('${player_name} takes ₤2 as foreign aid.'),
      'target' => false,
      'cost' => 0,
      'amount' => 2,
      'character' => 0,
      'forbid' => 0,
      'blockers' => array(DUKE),
      'variant' => null,
   ),
   COUP => array(
      'name' => clienttranslate('Coup'),
      'icon' => 'mdi-alert-decagram',
      'text1' => clienttranslate('Pay ₤7.'),
      'text2' => clienttranslate('Choose player to lose influence.'),
      'balloonAttempt' => '',
      'logAttempt' => '',
      'logExecute' => clienttranslate('${player_name} pays ₤7 to launch a coup against ${player_name2}.'),
      'target' => true,
      'cost' => 7,
      'character' => 0,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => null,
   ),
   CONVERT => array(
      'name' => clienttranslate('Convert'),
      'icon' => 'mdi-account-switch-outline',
      'text1' => clienttranslate('Pay almshouse.'),
      'text2' => clienttranslate('Choose player to change faction.'),
      'balloonAttempt' => '',
      'logAttempt' => '',
      'logExecute' => clienttranslate('${player_name} pays ₤${amount} to convert ${player_name2} to the ${faction_name} faction.'),
      'target' => true,
      'cost' => 0,
      'character' => 0,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => array('variantFactions' => 1),
   ),
   EMBEZZLE => array(
      'name' => clienttranslate('Embezzle'),
      'icon' => 'mdi-charity',
      'text1' => clienttranslate('Steal from almshouse.'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will steal from the almshouse...'),
      'logAttempt' => clienttranslate('${player_name} attempts to steal from the almshouse...'),
      'logExecute' => clienttranslate('${player_name} steals ₤${amount} from the almshouse.'),
      'target' => false,
      'cost' => 0,
      'character' => 0,
      'forbid' => DUKE,
      'blockers' => array(),
      'variant' => array('variantFactions' => 1),
   ),
   TAX => array(
      'name' => clienttranslate('Tax'),
      'icon' => 'mdi-sack-percent',
      'text1' => clienttranslate('Take ₤3.'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will collect tax...'),
      'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to take ₤3 as tax...'),
      'logExecute' => clienttranslate('${player_name} takes ₤3 as tax.'),
      'target' => false,
      'cost' => 0,
      'amount' => 3,
      'character' => DUKE,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => null,
   ),
   STEAL => array(
      'name' => clienttranslate('Steal'),
      'icon' => 'mdi-anchor',
      'text1' => clienttranslate('Steal ₤2 from another player.'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will steal from ${player_name2}...'),
      'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to steal from ${player_name2}...'),
      'logExecute' => clienttranslate('${player_name} steals ₤${amount} from ${player_name2}.'),
      'target' => true,
      'cost' => 0,
      'character' => CAPTAIN,
      'forbid' => 0,
      'blockers' => array(CAPTAIN, AMBASSADOR, INQUISITOR),
      'variant' => null,
   ),
   EXCHANGE => array(
      'name' => clienttranslate('Exchange'),
      'icon' => 'mdi-swap-horizontal',
      'text1' => clienttranslate('Exchange 2 cards with the deck.'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will exchange cards...'),
      'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to exchange cards...'),
      'logExecute' => clienttranslate('${player_name} draws ${count} cards from the deck.'),
      'target' => false,
      'cost' => 0,
      'count' => 2,
      'character' => AMBASSADOR,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => array('variantInquisitor' => 0),
   ),
   EXCHANGE1 => array(
      'name' => clienttranslate('Exchange'),
      'icon' => 'mdi-swap-horizontal',
      'text1' => clienttranslate('Exchange 1 card with the deck.'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will exchange cards...'),
      'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to exchange cards...'),
      'logExecute' => clienttranslate('${player_name} draws ${count} cards from the deck.'),
      'target' => false,
      'cost' => 0,
      'count' => 1,
      'character' => INQUISITOR,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => array('variantInquisitor' => 1),
      'stat' => 'action' . EXCHANGE,
   ),
   EXAMINE => array(
      'name' => clienttranslate('Examine'),
      'icon' => 'mdi-incognito',
      'text1' => clienttranslate('Choose player to view card (may force exchange).'),
      'text2' => '',
      'balloonAttempt' => clienttranslate('I will examine ${player_name2}\'s card...'),
      'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to examine ${player_name2}\'s card...'),
      'logExecute' => clienttranslate('${player_name} examines ${player_name2}\'s card.'),
      'target' => true,
      'cost' => 0,
      'count' => 1,
      'character' => INQUISITOR,
      'forbid' => 0,
      'blockers' => array(),
      'variant' => array('variantInquisitor' => 1),
   ),
   ASSASSINATE => array(
      'name' => clienttranslate('Assassinate'),
      'icon' => 'mdi-skull',
      'text1' => clienttranslate('Pay ₤3.'),
      'text2' => clienttranslate('Choose player to lose influence.'),
      'balloonAttempt' => clienttranslate('I will assassinate ${player_name2}...'),
      'logAttempt' => clienttranslate('${player_name} claims the ${card_name} to assassinate ${player_name2}...'),
      'logExecute' => clienttranslate('${player_name} pays ₤3 to assassinate ${player_name2}.'),
      'target' => true,
      'cost' => 3,
      'character' => ASSASSIN,
      'forbid' => 0,
      'blockers' => array(CONTESSA),
      'variant' => null,
   ),
);

$this->characters = array(
   0 => array(
      'name' => null,
      'text' => null,
      'variant' => null,
   ),
   DUKE => array(
      'name' => clienttranslate('Duke'),
      'text' => array(clienttranslate('Take ₤3.'), clienttranslate('Blocks foreign aid.')),
      'variant' => null,
   ),
   ASSASSIN => array(
      'name' => clienttranslate('Assassin'),
      'text' => array(clienttranslate('Pay ₤3.'), clienttranslate('Choose player to lose influence.')),
      'variant' => null,
   ),
   AMBASSADOR => array(
      'name' => clienttranslate('Ambassador'),
      'text' => array(clienttranslate('Exchange 2 cards with the deck.'), clienttranslate('Blocks stealing.')),
      'variant' => array('variantInquisitor' => 0),
   ),
   INQUISITOR => array(
      'name' => clienttranslate('Inquisitor'),
      'text' => array(clienttranslate('Exchange 1 card with the deck.'), clienttranslate('OR'), clienttranslate('Choose player to view card (may force exchange).'), clienttranslate('Blocks stealing.')),
      'variant' => array('variantInquisitor' => 1),
   ),
   CAPTAIN => array(
      'name' => clienttranslate('Captain'),
      'text' => array(clienttranslate('Steal ₤2 from another player.'), clienttranslate('Blocks stealing.')),
      'variant' => null,
   ),
   CONTESSA => array(
      'name' => clienttranslate('Contessa'),
      'text' => array(clienttranslate('Blocks assassination.')),
      'variant' => null,
   ),
);

$this->factions = array(
   1 => array(
      'name' => clienttranslate('Monarchist'),
      'icon' => 'mdi-crown',
   ),
   2 => array(
      'name' => clienttranslate('Populist'),
      'icon' => 'mdi-party-popper',
   ),
);

$this->balloons = array(
   'block'        => clienttranslate('I block ${player_name2} with my ${card_name}'),
   'challenge'    => clienttranslate('I challenge ${player_name2}\'s ${card_name}'),
   'challengeAll' => clienttranslate('I challenge ${player_name2}'),
   'die'          => clienttranslate('I lose the ${card_name}'),
   'discard'      => clienttranslate('I discard ${count} cards'),
   'draw'         => clienttranslate('I draw ${count} cards'),
   'kill'         => clienttranslate('I kill ${player_name2}'),
   'lie'          => clienttranslate('I was bluffing. ${player_name2} challenged correctly.'),
   'truth'        => clienttranslate('I was truthful. ${player_name2} challenged incorrectly.'),
   'wealth'       => clienttranslate('I take ₤${amount}'),
   'convert'      => clienttranslate('I convert to the ${faction_name} faction'),
   'examine'      => clienttranslate('I examine ${player_name2}\'s card'),
   'replace'      => clienttranslate('I discard, shuffle, and replace a card'),
);
