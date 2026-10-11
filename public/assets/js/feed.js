/* Home feature cards: a spotlight, tilt and parallax that follow the mouse (see feed.css "hover VFX"). */
(function () {
  var cards = document.querySelectorAll('.feature-square');
  if (!cards.length) return;
  var fine = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  var calm = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  cards.forEach(function (card) {
    // decorative layers: glow, running border, light sweep, sparkles
    var fx = document.createElement('span');
    fx.className = 'fx';
    fx.setAttribute('aria-hidden', 'true');
    fx.innerHTML = '<i class="fx-glow"></i><i class="fx-border"></i><i class="fx-shine"></i><i class="fx-parts"><b></b><b></b><b></b><b></b><b></b><b></b><b></b><b></b></i>';
    card.insertBefore(fx, card.firstChild);
    if (!fine || calm) return;

    var frame = 0, ev = null;
    var paint = function () {
      frame = 0;
      var r = card.getBoundingClientRect();
      var x = (ev.clientX - r.left) / r.width, y = (ev.clientY - r.top) / r.height;
      card.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
      card.style.setProperty('--my', (y * 100).toFixed(1) + '%');
      card.style.setProperty('--ry', ((x - 0.5) * 8).toFixed(2) + 'deg');
      card.style.setProperty('--rx', ((0.5 - y) * 7).toFixed(2) + 'deg');
      card.style.setProperty('--px', ((x - 0.5) * 14).toFixed(1) + 'px');
      card.style.setProperty('--py', ((y - 0.5) * 10).toFixed(1) + 'px');
    };
    card.addEventListener('pointermove', function (e) {
      if (e.pointerType !== 'mouse') return;
      ev = e;
      if (!frame) frame = requestAnimationFrame(paint);
    });
    card.addEventListener('pointerleave', function () {
      ['--rx', '--ry', '--px', '--py'].forEach(function (k) { card.style.setProperty(k, k.indexOf('p') === 2 ? '0px' : '0deg'); });
    });
  });
})();
