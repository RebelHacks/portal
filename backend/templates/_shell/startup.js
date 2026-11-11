// Inlined in <head> before anything renders, so the page never flashes the wrong skin.
(function () {
  var choice = null;
  try { choice = new URLSearchParams(location.search).get('skin') || localStorage.getItem('skin'); } catch (e) {}
  if (!choice || choice === 'auto') {
    choice = matchMedia('(prefers-color-scheme: dark)').matches ? 'neon' : 'day';
  }
  document.documentElement.dataset.skin = choice;
})();
