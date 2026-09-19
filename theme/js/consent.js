// ========================================
// Cookie consent: layout's own banner, Complianz does the actual consent work
// ========================================
(() => {
  const banner = document.getElementById('cookie');
  if (!banner) return;

  const REVEAL_DELAY = 1000; // как в вёрстке: плашка всплывает не на первой отрисовке

  const api = () => (typeof window.cmplz_set_banner_status === 'function' ? window : null);

  const open = () => banner.classList.add('is-open');
  const close = () => banner.classList.remove('is-open');

  // Complianz помечает свой выбор куки cmplz_banner-status=dismissed.
  const alreadyAnswered = () => document.cookie.split(';').some((c) => c.trim().startsWith('cmplz_banner-status=dismissed'));

  const decide = (accepted) => {
    const cmplz = api();

    if (cmplz) {
      // согласие сохраняет и применяет плагин: он же разблокирует скрипты
      if (accepted) {
        cmplz.cmplz_accept_all();
      } else {
        cmplz.cmplz_deny_all();
      }

      cmplz.cmplz_set_banner_status('dismissed');
    }

    close();
  };

  banner.querySelectorAll('[data-cookie-close]').forEach((button) => {
    button.addEventListener('click', () => decide(button.classList.contains('cookie__accept')));
  });

  // Плагин сам просит показать или спрятать баннер: так работает ссылка
  // «изменить согласие» и отзыв согласия из политики.
  document.addEventListener('cmplz_banner_status', (e) => {
    if ('show' === e.detail) {
      open();
    } else {
      close();
    }
  });

  document.addEventListener('cmplz_revoke', open);

  // Ссылка «Настройки cookie» в подвале — вместо плавающего язычка плагина.
  document.querySelectorAll('[data-consent-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const cmplz = api();

      if (cmplz) {
        cmplz.cmplz_set_banner_status('show'); // плагин попросит показать плашку
      } else {
        open();
      }
    });
  });

  // Скрипт плагина грузится асинхронно, поэтому решение о показе принимаем,
  // когда он готов; без плагина ведём себя как статичная вёрстка.
  const start = () => {
    if (alreadyAnswered()) return;

    setTimeout(open, REVEAL_DELAY);
  };

  if (api()) {
    start();
  } else {
    document.addEventListener('cmplz_before_cookiebanner', start, { once: true });
    // плагин не отозвался — плашка всё равно должна появиться
    setTimeout(() => {
      if (!banner.classList.contains('is-open')) start();
    }, 1500);
  }
})();
