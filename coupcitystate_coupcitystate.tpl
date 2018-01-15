{OVERALL_GAME_HEADER}
<div id="game_wrap">
  <div id="myactions">
    <div class="actionrowhead">{I18N_Actions}:</div>
    <!-- BEGIN action -->
    <div class="action action-{action_id}" data-action="{action_id}">
      <i class="icon-action-{action_id} mdi {icon}"></i>
      <div class="actiondesc">
        <div class="actionhead">
          {name}
          <div class="actionwho">{claimHtml}</div>
        </div>
        {text} {blockHtml}
      </div>
    </div>
    <!-- END action -->
  </div>

  <div id="circle" class="circle-{player_count}">
    <div id="almshouse" class="oncircle hide" data-action="9">
      {I18N_Almshouse} (<span id="almshousecount">₤0</span>)
    </div>
    <div id="deck" class="oncircle" data-action="{action_deck}">
      {I18N_Deck} (<span id="deckcount">0</span>)
      <div id="deckcard" class="card"></div>
    </div>
    <!-- BEGIN player -->
    <div class="oncircle player-{index}">
      <div id="placemat_{player_id}" class="placemat player-{index}" style="color: #{player_color}" data-player="{player_id}">
        <div class="playerhead">
          <div id="balloon_{player_id}" class="balloon"></div>
          <div id="wealth_{player_id}" class="wealth"></div>
          <div id="faction_{player_id}" class="faction-name"></div>
          <div class="player-name">{player_name}</div>
        </div>
        <div id="cards_{player_id}" class="cards"></div>
      </div>
    </div>
    <!-- END player -->
  </div>
</div>
{OVERALL_GAME_FOOTER}