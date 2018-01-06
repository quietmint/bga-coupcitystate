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
 * coupcitystate.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in coupcitystate_coupcitystate.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH."view/common/game.view.php");

class view_coupcitystate_coupcitystate extends game_view
{
    public function getGameName()
    {
        return 'coupcitystate';
    }

    public function getCharacterName($character_id)
    {
        $character_ref = $this->game->characters[$character_id];
        return '<div class="character-name character-' . $character_id . '">' . $character_ref['name'] . '</div>';
    }

    public function build_page($viewArgs)
    {
        global $g_user;
        $current_player_id = $g_user->get_id();
        $template = self::getGameName() . '_' . self::getGameName();

        // Translations for static text
        $this->tpl['I18N_Actions'] = self::_('Actions on your turn');
        $this->tpl['I18N_Almshouse'] = self::_('Almshouse');
        $this->tpl['I18N_Deck'] = self::_('Deck');

        // Inflate action block
        $this->page->begin_block($template, 'action');
        foreach ($this->game->actions as $action => $action_ref) {
            // Check if variant is enabled
            if ($action_ref['variant'] && !$this->game->getGameStateValue($action_ref['variant'])) {
                continue;
            }

            $args = $action_ref;
            unset($args['blockers']);
            $args['action_id'] = $action;

            $args['blockHtml'] = self::_('Cannot block.');
            if (count($action_ref['blockers']) > 0) {
                $card_name = '';
                foreach ($action_ref['blockers'] as $blocker) {
                    $card_name .= $this->getCharacterName($blocker) . ' & ';
                }
                $card_name = substr($card_name, 0, -3);
                $args['blockHtml'] = self::raw(str_replace('${card_name}', $card_name, self::_('${card_name} can block.')));
            }

            $args['claimHtml'] = '';
            if ($args['character'] > 0) {
                $card_name = $this->getCharacterName($args['character']);
                $args['claimHtml'] = self::raw(str_replace('${card_name}', $card_name, self::_('as ${card_name}')));
            } elseif ($args['forbid'] > 0) {
                $card_name = $this->getCharacterName($action_ref['forbid']);
                $args['claimHtml'] = self::raw(str_replace('${card_name}', $card_name, self::_('not as ${card_name}')));
            } else {
                //$args['claimHtml'] = self::_('as any character');
            }
            $this->page->insert_block('action', $args);
        }

        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $this->tpl['player_count'] = count($players);
        $spectator = !array_key_exists($current_player_id, $players);

        // Compute order starting with current player
        // (for spectators, starting with first player)
        if ($spectator) {
            $id = $this->game->getNextPlayerTable()[0];
        } else {
            $id = intval($current_player_id);
        }
        $player_order = array($id);
        for ($i = 1; $i < $this->tpl['player_count']; $i++) {
            $id = $this->game->getPlayerAfter($id);
            array_push($player_order, $id);
        }

        // Inflate player block
        $this->page->begin_block($template, 'player');
        $index = 0;
        foreach ($player_order as $player_id) {
            $this->page->insert_block('player', array(
                'index' => $index++,
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'player_color' => $players[$player_id]['player_color'],
            ));
        }
    }
}
