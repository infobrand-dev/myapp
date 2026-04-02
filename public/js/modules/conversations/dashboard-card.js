/******/ (() => { // webpackBootstrap
/*!******************************************************************!*\
  !*** ./app/Modules/Conversations/resources/js/dashboard-card.js ***!
  \******************************************************************/
document.addEventListener('DOMContentLoaded', function () {
  var cards = document.querySelectorAll('[data-conversation-dashboard-card]');
  var configs = document.querySelectorAll('[data-conversation-dashboard-config]');
  cards.forEach(function (card, index) {
    var configEl = configs[index];
    if (!configEl) {
      return;
    }
    var config = {
      openShare: 0,
      claimedShare: 0
    };
    try {
      config = JSON.parse(configEl.textContent || '{}');
    } catch (_) {
      return;
    }
    var openBar = card.querySelector('[data-conversation-open]');
    var claimedBar = card.querySelector('[data-conversation-claimed]');
    requestAnimationFrame(function () {
      if (openBar) {
        openBar.style.width = "".concat(Math.max(0, Math.min(100, Number(config.openShare || 0))), "%");
      }
      if (claimedBar) {
        claimedBar.style.width = "".concat(Math.max(0, Math.min(100, Number(config.claimedShare || 0))), "%");
      }
    });
  });
});
/******/ })()
;