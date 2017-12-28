/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Coup implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * coupcitystate.js
 *
 * Coup user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
        "dojo", "dojo/_base/declare", "dojo/dom-attr", "dojo/dom-style",
        "ebg/core/gamegui",
        "ebg/counter",
        "ebg/stock"
    ],
    function(dojo, declare, domAttr, domStyle) {
        function getRandomInt(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }

        return declare("bgagame.coupcitystate", ebg.core.gamegui, {
            constructor: function() {
                this.cardwidth = 100;
                this.cardheight = 128;

                function argsFilter(args) {
                    if (this.gamedatas) {
                        // Stylize character names
                        var characters = this.gamedatas.characters;
                        for (var character in characters) {
                            var character_ref = characters[character];
                            if (character_ref.name != null) {
                                var styleized = '<div class="character-name" style="background-color: ' + character_ref.color_bright + '">' + character_ref.name + '</div>';
                                if (args.card_name == character_ref.name) {
                                    args.card_name = styleized;
                                }
                                if (args.card_name2 == character_ref.name) {
                                    args.card_name2 = styleized;
                                }
                            }
                        }

                        // Stylize player names
                        for (var player in this.gamedatas.players) {
                            var player_ref = this.gamedatas.players[player];
                            var stylized = '<!--PNS--><span style="font-weight:bold;color:#' + player_ref.color;
                            if (player_ref.color_back) {
                                stylized += ';background-color:#' + player_ref.color_back;
                            }
                            stylized += '">' + player_ref.name + '</span><!--PNE-->';;

                            if (args.player_name == player_ref.name) {
                                args.player_name = stylized;
                            }
                            if (args.player_name2 == player_ref.name) {
                                args.player_name2 = stylized;
                            }
                        }

                        // Recurse through nested arguments
                        for (argname in args) {
                            if (argname != 'i18n' && typeof args[argname] == 'object' &&
                                args[argname] !== null && typeof args[argname].log != 'undefined' && typeof args[argname].args != 'undefined') {
                                args[argname].args = argsFilter(args[argname].args);
                            }
                        }
                    }
                    return args;
                };
                this.notifqueue.playerNameFilterGame = argsFilter.bind(this);
            },

            /*
                setup:

                This method must set up the game user interface according to current game situation specified
                in parameters.

                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)

                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function(gamedatas) {
                // Current player
                dojo.addClass('placemat_' + gamedatas.turn, 'active');

                // Player actions
                if (!this.isSpectator) {
                    dojo.addClass('placemat_' + this.player_id, 'mine');
                    dojo.query('#myactions .action').connect('onclick', this, 'onAct');
                    dojo.query('#deck').connect('onclick', this, 'onAct');

                    var osp = dojo.hitch(this, 'onSelectPlayer');
                    var placemats = document.querySelectorAll('.placemat');
                    for (var i = 1; i < placemats.length; i++) {
                        placemats[i].addEventListener('click', osp, true);
                    }
                }

                this.tableau = {};
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];
                    var handCount = gamedatas.handCounts[player_id] || 0;

                    // Eliminated?
                    if (player.eliminated) {
                        dojo.addClass('placemat_' + player_id, 'eliminated');
                    }

                    // Balloon?
                    if (player.balloon) {
                        this.notif_balloon({
                            args: player.balloon
                        }, true);
                    }

                    // Coin count
                    $('wealth_' + player_id).innerHTML = '₤' + player.wealth;
                    dojo.place('<span id="panel_wealth_' + player_id + '">₤' + player.wealth + ' • </span>', 'player_score_' + player_id, 'before');

                    // Setup my cards
                    var myCards = new ebg.stock();
                    myCards.create(this, $('cards_' + player_id), this.cardwidth, this.cardheight);
                    myCards.image_items_per_row = 1;
                    myCards.apparenceBorderWidth = '2px';
                    myCards.setSelectionMode(0);
                    myCards.onItemCreate = dojo.hitch(this, 'setupCard');
                    for (var i in gamedatas.characters) {
                        myCards.addItemType(i, i, g_gamethemeurl + 'img/cards.jpg', i);
                    }

                    // Add visible cards (dead)
                    for (var i in gamedatas.tableau) {
                        var card = gamedatas.tableau[i];
                        if (card.location_arg == player_id) {
                            myCards.addToStockWithId(+card.type, +card.id);
                            var divId = myCards.getItemDivId(+card.id);
                            dojo.addClass(divId, 'dead');
                            this.removeTooltip(divId);
                        }
                    }

                    // Add secret cards
                    if (player_id == this.player_id) {
                        for (var i in gamedatas.hand) {
                            var card = gamedatas.hand[i];
                            myCards.addToStockWithId(+card.type, +card.id);
                        }
                        dojo.connect(myCards, 'onChangeSelection', this, 'onSelectCard');
                    } else {
                        this.addUnknownCards(myCards, handCount);
                    }

                    // Increase width if we have more than 2 cards
                    var total = myCards.getAllItems().length;
                    if (total > 2) {
                        domStyle.set('cards_' + player_id, 'width', '270px');
                        myCards.setOverlap(50, 0);
                    }
                    this.tableau[player_id] = myCards;
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();
            },

            setupCard: function(card_div, card_type_id, card_id) {
                dojo.addClass(card_div, 'card');
                if (card_type_id > 0) {
                    var character_ref = this.gamedatas.characters[card_type_id];
                    var tip = '<div class="character-name" style="background-color: ' + character_ref.color_bright + '">' + character_ref.name + '</div>' +
                        '<div style="max-width: 200px">' + character_ref.text + ' ' + character_ref.subtext + '</div>';
                    this.addTooltipHtml(card_div.id, tip);
                    dojo.place('<div class="card-name">' + character_ref.name + '</div>', card_div.id);
                }
            },

            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function(stateName, args) {
                console.info('Entering state: ' + stateName, args.args);
                if (stateName == 'playerStart') {
                    var active = this.getActivePlayerId();
                    dojo.query('.placemat.active').removeClass('active');
                    dojo.addClass('placemat_' + active, 'active');
                }

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'playerStart':
                        case 'askChooseCard':
                            this.tableau[this.player_id].setSelectionMode(1);
                            break;

                        case 'askDiscard':
                            this.tableau[this.player_id].setSelectionMode(2);
                            break;
                    }
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function(stateName) {
                console.info('Leaving state: ' + stateName);
                if (!this.isSpectator) {
                    clearInterval(window.passIntervalId);
                    delete window.passIntervalId;
                    if (stateName == 'playerStart') {
                        dojo.query('.placemat.selected').removeClass('selected');
                    }
                    this.tableau[this.player_id].setSelectionMode(0);
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //
            onUpdateActionButtons: function(stateName, args) {
                console.info('Update action buttons: ' + stateName, args);
                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'ask':
                        case 'askBlock':
                            if (!window.passIntervalId && typeof g_replayFrom == 'undefined') {
                                // Auto pass after 10 - 15 seconds
                                clearInterval(window.passIntervalId);
                                window.passIntervalSeconds = getRandomInt(10, 15);
                                window.passIntervalId = setInterval(function() {
                                    var button_no = document.getElementById('button_no');
                                    if (button_no != null) { // tick
                                        button_no.textContent = _('I allow') + ' (' + window.passIntervalSeconds-- + ')';
                                    } else {
                                        window.passIntervalSeconds = 0;
                                    }

                                    if (window.passIntervalSeconds <= 0) { // stop
                                        if (button_no != null) {
                                            console.info('Executing auto-pass timer');
                                            button_no.click();
                                        }
                                        clearInterval(window.passIntervalId);
                                        delete window.passIntervalId;
                                    }
                                }, 1000);
                                console.info('Starting auto-pass timer (' + window.passIntervalSeconds + ' seconds)');
                            }

                            this.addActionButton('button_no', _('I allow') + (window.passIntervalSeconds ? ' (' + window.passIntervalSeconds + ')' : ''), 'onActionNo');
                            if (args != null) {
                                if (args.card_name != null) {
                                    var str = dojo.string.substitute(_('I challenge ${player_name2}\'s ${card_name}'), args);
                                    this.addActionButton('button_yes', str, 'onActionYes');
                                }

                                // Combine public and private block actions
                                var blockers = [];
                                if (args.blockers != null) {
                                    Array.prototype.push.apply(blockers, args.blockers);
                                }
                                if (args._private != null && args._private.blockers != null) {
                                    Array.prototype.push.apply(blockers, args._private.blockers);
                                }
                                for (var i = 0; i < blockers.length; i++) {
                                    var blocker = blockers[i];
                                    var character_ref = this.gamedatas.characters[blocker];
                                    var str = dojo.string.substitute(_('I block with my ${card_name}'), {
                                        card_name: character_ref.name
                                    });
                                    this.addActionButton('button_block' + blocker, str, 'onBlock');
                                }
                            }
                            break;

                        case 'askDiscard':
                            this.addActionButton('button_exchange', _('Discard'), 'onActionDiscard');
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            doAction: function(action, args) {
                if (this.checkAction(action)) {
                    console.info('Taking action: ' + action, args);
                    args = args || {};
                    args.lock = true;
                    this.ajaxcall('/coupcitystate/coupcitystate/' + action + '.html', args, this, function(result) {});
                }
            },

            hasCard: function(stock, id) {
                return stock.getAllItems().some(function(card) {
                    return card.id == id;
                });
            },

            addUnknownCards: function(stock, count, from) {
                if (count > 0) {
                    var unknownCount = stock.getAllItems().filter(function(card) {
                        return card.type == 0;
                    }).length;
                    for (var i = 0; i < count; i++) {
                        stock.addToStockWithId(0, 100 + unknownCount + i, from);
                    }
                }
            },

            removeUnknownCards: function(stock, count, to) {
                if (count > 0) {
                    var removed = 0;
                    stock.getAllItems().reverse().some(function(card) {
                        if (card.type == 0) {
                            stock.removeFromStockById(card.id, to);
                            removed++;
                        }
                        // Stop if we've removed enough cards
                        return removed == count;
                    });
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            onSelectPlayer: function(evt) {
                if (this.isCurrentPlayerActive() && this.checkAction('act', true)) {
                    var select_id = domAttr.get(evt.currentTarget, 'data-player');
                    if (dojo.hasClass(evt.currentTarget, 'selected')) {
                        dojo.stopEvent(evt);
                        dojo.removeClass(evt.currentTarget, 'selected');
                    } else if (!dojo.hasClass(evt.currentTarget, 'eliminated')) {
                        dojo.stopEvent(evt);
                        dojo.query('.placemat').removeClass('selected');
                        dojo.addClass(evt.currentTarget, 'selected');
                        if (this.pendingAction) {
                            this.onAct(this.pendingAction);
                        }
                    }
                }
            },

            onSelectCard: function(htmlId) {
                var selectCards = this.tableau[this.player_id];
                var items = selectCards.getSelectedItems();
                // Unselect dead cards
                items.forEach(function(card) {
                    var isDead = dojo.hasClass(selectCards.getItemDivId(card.id), 'dead');
                    if (isDead) {
                        selectCards.unselectItem(card.id);
                    }
                });
                items = selectCards.getSelectedItems();
                if (items.length > 0) {
                    if (this.checkAction('actionDiscard', true)) {
                        // Allow multi-select, do nothing
                    } else {
                        selectCards.unselectAll();
                        if (this.checkAction('act', true)) {
                            // Clicking your own card performs its action
                            for (var action in this.gamedatas.actions) {
                                var action_ref = this.gamedatas.actions[action];
                                if (action_ref.character == items[0].type) {
                                    this.onAct(+action);
                                    break;
                                }
                            }

                        } else if (this.checkAction('actionChooseCard', true)) {
                            this.doAction('actionChooseCard', {
                                card_id: items[0].id
                            });
                        }
                    }
                }
            },

            onAct: function(evt) {
                delete this.pendingAction;
                if (typeof evt == 'number') {
                    // Happens when clicking your own card
                    var action = evt;
                } else {
                    // Happens when clicking an action button
                    dojo.stopEvent(evt);
                    var button = evt.currentTarget;
                    var action = +domAttr.get(button, 'data-action');
                }

                if (action > 0 && this.checkAction('act')) {
                    var action_ref = this.gamedatas.actions[action];
                    var args = {
                        act: action
                    };

                    // Check wealth
                    var wealth = this.gamedatas.players[this.player_id].wealth;
                    if (wealth >= 10 && action_ref.name != 'Coup') {
                        this.showMessage(_('You must Coup because you have ₤10.'), 'error');
                        return;
                    } else if (wealth < action_ref.cost) {
                        var str = _('You need ₤%d for this action.').replace('%d', action_ref.cost);
                        this.showMessage(str, 'error');
                        return;
                    }

                    // Check target
                    if (action_ref.target) {
                        var target = dojo.query('.placemat.selected')[0];
                        var targetWealth = 0;
                        if (target != null) {
                            args.target = +domAttr.get(target, 'data-player');
                            targetWealth = this.gamedatas.players[args.target].wealth;
                        }
                        if (target == null || (action_ref.name == 'Steal' && targetWealth == 0)) {
                            this.pendingAction = action;
                            var msg = action_ref.name == 'Steal' ? _('Choose an active player with money.') : _('Choose an active player.');
                            this.showMessage(msg, 'info');
                            return;
                        }
                    }

                    this.doAction('act', args);
                }
            },

            onActionDiscard: function(evt) {
                dojo.stopEvent(evt);
                var items = this.tableau[this.player_id].getSelectedItems();
                if (this.checkAction('actionDiscard')) {
                    if (items.length == 2) {
                        var card_ids = items.map(function(item) {
                            return item.id;
                        }).join(';');
                        this.doAction('actionDiscard', {
                            card_ids: card_ids
                        });
                        this.tableau[this.player_id].unselectAll();
                    } else {
                        this.showMessage(_('You must choose 2 active cards to discard.'), 'error');
                        return;
                    }
                } else {
                    this.tableau[this.player_id].unselectAll();
                }
            },

            onActionNo: function(evt) {
                dojo.stopEvent(evt);
                this.doAction('actionNo');
            },

            onActionYes: function(evt) {
                dojo.stopEvent(evt);
                this.doAction('actionYes');
            },

            onBlock: function(evt) {
                dojo.stopEvent(evt);
                this.doAction('actionBlock', {
                    card_type: +evt.currentTarget.id.substr(-1)
                });
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:

                In this method, you associate each of your game notifications with your local method to handle it.

                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your coupcitystate.game.php file.

            */
            setupNotifications: function() {
                dojo.subscribe('tableInfosChanged', this, 'notif_tableInfosChanged');
                dojo.subscribe('score', this, 'notif_score');

                dojo.subscribe('wealthInstant', this, 'notif_wealth');
                dojo.subscribe('wealth', this, 'notif_wealth');
                this.notifqueue.setSynchronous('wealth', 2500);

                dojo.subscribe('balloonInstant', this, 'notif_balloon');
                dojo.subscribe('balloon', this, 'notif_balloon');
                this.notifqueue.setSynchronous('balloon', 2500);

                dojo.subscribe('revealInstant', this, 'notif_reveal');
                dojo.subscribe('reveal', this, 'notif_reveal');
                this.notifqueue.setSynchronous('reveal', 2500);

                dojo.subscribe('discardInstant', this, 'notif_discard');
                dojo.subscribe('discard', this, 'notif_discard');
                this.notifqueue.setSynchronous('discard', 2500);

                dojo.subscribe('drawInstant', this, 'notif_draw');
                dojo.subscribe('draw', this, 'notif_draw');
                this.notifqueue.setSynchronous('draw', 2500);
            },

            notif_tableInfosChanged: function(n) {
                // Detect player elimination
                if (n.args.reload_reason == 'playerElimination') {
                    dojo.addClass('placemat_' + n.args.who_quits, 'eliminated');
                }
            },

            notif_score: function(n) {
                var player_id = n.args.player_id;
                var score = n.args.score || 0;
                this.gamedatas.players[player_id].score = score;
                $('player_score_' + player_id).innerText = score;
            },

            notif_wealth: function(n) {
                var player_id = n.args.player_id;
                var wealth = n.args.wealth || 0;
                this.gamedatas.players[player_id].wealth = wealth;
                $('wealth_' + player_id).innerText = '₤' + wealth;
                $('panel_wealth_' + player_id).innerText = '₤' + wealth + ' • ';

                if (n.args.balloon) {
                    this.notif_balloon(n);
                }
            },

            notif_balloon: function(n, keep) {
                var player_id = n.args.player_id;
                if (player_id) {
                    // Styleize player/character names like notifications
                    n.args = this.notifqueue.playerNameFilterGame(n.args);
                    var isNo = n.args.balloon == 'no';

                    // Clear all balloons
                    if (!keep && !isNo) {
                        dojo.query('.balloon').forEach(dojo.empty);
                    }

                    // New balloon
                    var html = '';
                    if (isNo) {
                        html = '<i class="mdi mdi-thumb-up-outline"></i>';
                    } else if (n.args.balloon != null) {
                        if (n.args.action) {
                            var action_ref = this.gamedatas.actions[n.args.action];
                            if (action_ref != null) {
                                html += '<i class="mdi ' + action_ref.icon + '"></i> ';
                            }
                        }
                        html += dojo.string.substitute(_(n.args.balloon), n.args);
                    }
                    if (html) {
                        $('balloon_' + player_id).innerHTML = html;
                    }
                }
            },

            notif_reveal: function(n) {
                var player_id = n.args.player_id;
                var myCards = this.tableau[player_id];
                if (myCards != null) {
                    for (var i = 0; i < n.args.card_ids.length; i++) {
                        var id = +n.args.card_ids[i];
                        var cardtype = +n.args.card_types[i];
                        if (!this.hasCard(myCards, id)) {
                            this.removeUnknownCards(myCards, 1);
                        }
                        myCards.addToStockWithId(cardtype, id);
                        var divId = myCards.getItemDivId(id);
                        var newClass = 'reveal';
                        if (!n.args.alive) {
                            newClass += ' dead';
                            this.removeTooltip(divId);
                        }
                        dojo.addClass(divId, newClass);
                    }
                }

                if (n.args.balloon) {
                    this.notif_balloon(n);
                }
            },

            notif_discard: function(n) {
                var player_id = n.args.player_id;
                var isMe = this.player_id == player_id;
                var myCards = this.tableau[player_id];
                var card_ids = n.args.card_ids;
                if (card_ids) {
                    // Discard visible cards by ID
                    for (var i = 0; i < card_ids.length; i++) {
                        var id = +card_ids[i];
                        myCards.removeFromStockById(id, 'deck');
                    }
                } else if (!isMe && n.args.count) {
                    // Discard hidden cards
                    this.removeUnknownCards(myCards, n.args.count, 'deck');
                }

                // Remove increased width
                domStyle.set('cards_' + player_id, 'width', null);
                myCards.setOverlap(0, 0);

                if (n.args.deck_count) {
                    $('deckcount').innerText = 'Deck (' + n.args.deck_count + ')';
                }

                if (n.args.balloon) {
                    this.notif_balloon(n);
                }
            },

            notif_draw: function(n) {
                var player_id = n.args.player_id;
                var isMe = this.player_id == player_id;
                var myCards = this.tableau[player_id];
                var cards = n.args.cards;
                if (cards) {
                    // Increase width if we have more than 2 cards
                    var total = myCards.getAllItems().length + cards.length;
                    if (total > 2) {
                        domStyle.set('cards_' + player_id, 'width', '270px');
                        myCards.setOverlap(50, 0);
                    }

                    for (var i = 0; i < cards.length; i++) {
                        var card = n.args.cards[i];
                        myCards.addToStockWithId(+card.type, +card.id, 'deck');
                    }
                } else if (!isMe && n.args.count) {
                    // Increase width if we have more than 2 cards
                    var total = myCards.getAllItems().length + n.args.count;
                    if (total > 2) {
                        domStyle.set('cards_' + player_id, 'width', '270px');
                        myCards.setOverlap(50, 0);
                    }

                    // Draw hidden card(s)
                    this.addUnknownCards(myCards, n.args.count, 'deck');
                }

                if (n.args.deck_count) {
                    $('deckcount').innerText = 'Deck (' + n.args.deck_count + ')';
                }

                if (n.args.balloon) {
                    this.notif_balloon(n);
                }
            },
        });
    });
