var passwordRevealButtons = document.querySelectorAll('[data-password-reveal]');

for (var i = 0; i < passwordRevealButtons.length; i += 1) {
  (function (button) {
  var input = document.getElementById(button.getAttribute('aria-controls'));
  if (!input) {
    return;
  }

  var reveal = function () {
    input.type = 'text';
    button.classList.add('is-revealing');
    button.setAttribute('aria-pressed', 'true');
  };
  var conceal = function () {
    input.type = 'password';
    button.classList.remove('is-revealing');
    button.setAttribute('aria-pressed', 'false');
  };

  button.addEventListener('mouseenter', reveal);
  button.addEventListener('mouseleave', conceal);
  button.addEventListener('touchstart', reveal, { passive: true });
  button.addEventListener('touchend', conceal);
  button.addEventListener('touchcancel', conceal);
  button.addEventListener('keydown', function (event) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      reveal();
    }
  });
  button.addEventListener('keyup', function (event) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      conceal();
    }
  });
  button.addEventListener('blur', conceal);
  button.addEventListener('click', function (event) {
    event.preventDefault();
  });
  }(passwordRevealButtons[i]));
}
