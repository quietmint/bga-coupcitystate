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
 * coupcitystate.action.php
 *
 * Coup main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/coupcitystate/coupcitystate/myAction.html", ...)
 *
 */


class action_coupcitystate extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = 'common_notifwindow';
            $this->viewArgs['table'] = self::getArg('table', AT_posint, true);
        } else {
            $this->view = 'coupcitystate_coupcitystate';
            self::trace('Complete reinitialization of board game');
        }
    }

    public function act()
    {
        self::setAjaxMode();
        $action = self::getArg('act', AT_posint, true);
        $target = self::getArg('target', AT_posint);
        $this->game->act($action, $target);
        self::ajaxResponse();
    }

    public function actionNo()
    {
        self::setAjaxMode();
        $this->game->actionNo();
        self::ajaxResponse();
    }

    public function actionYes()
    {
        self::setAjaxMode();
        $this->game->actionYes();
        self::ajaxResponse();
    }

    public function actionBlock()
    {
        self::setAjaxMode();
        $card_type = self::getArg('card_type', AT_posint, true);
        $this->game->actionBlock($card_type);
        self::ajaxResponse();
    }

    public function actionChooseCard()
    {
        self::setAjaxMode();
        $card_id = self::getArg('card_id', AT_posint, true);
        $this->game->actionChooseCard($card_id);
        self::ajaxResponse();
    }

    public function actionDiscard()
    {
        self::setAjaxMode();
        $str = self::getArg('card_ids', AT_numberlist, true);
        if ($str == '') {
            $card_ids = array();
        } else {
            $card_ids = explode(';', $str);
        }
        $this->game->actionDiscard($card_ids);
        self::ajaxResponse();
    }

    public function actionExamineKeep()
    {
        self::setAjaxMode();
        $this->game->actionExamineKeep();
        self::ajaxResponse();
    }

    public function actionExamineExchange()
    {
        self::setAjaxMode();
        $this->game->actionExamineExchange();
        self::ajaxResponse();
    }
}
