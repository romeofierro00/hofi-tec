(function () {
  var root = document.documentElement;
  var btn = document.getElementById('fs');
  var KEY = 'hofi-big';
  function set(on) {
    root.classList.toggle('big', on);
    if (btn) {
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.textContent = on ? 'A−' : 'A+';
      btn.setAttribute('aria-label', on ? 'Schrift kleiner' : 'Schrift grösser');
    }
  }
  var on = false;
  try { on = localStorage.getItem(KEY) === '1'; } catch (e) {}
  set(on);
  if (!btn) return;
  btn.hidden = false;
  btn.addEventListener('click', function () {
    on = !on;
    set(on);
    try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) {}
  });
})();
