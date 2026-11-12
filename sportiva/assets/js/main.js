/**
 * SPORTIVA - tema betiği
 * Mobil menü, PT sekmeleri, salon dolu saat listesi, saat doğrulaması, yukarı çık düğmesi.
 * Bağlantılar normal <a href> olarak çalışır; betik hiçbir bağlantıyı engellemez.
 */
(function () {
	'use strict';

	var body = document.body;

	function reduceMotion() {
		return document.documentElement.classList.contains('a11y-motion') || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	/* ---- Mobil menü ---- */
	var toggle = document.querySelector('.nav-toggle');
	var nav = document.getElementById('primary-nav');

	function setMenu(open) {
		if (!toggle) { return; }
		if (open) {
			// Menü, sabit başlığın hemen altından başlar (üstteki erişilebilirlik çubuğu görünür olsa da).
			var hdr = document.getElementById('site-header');
			if (hdr) { document.documentElement.style.setProperty('--nav-top', Math.max(0, Math.round(hdr.getBoundingClientRect().bottom)) + 'px'); }
		}
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		body.classList.toggle('nav-open', open);
	}

	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			setMenu(toggle.getAttribute('aria-expanded') !== 'true');
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && body.classList.contains('nav-open')) {
				setMenu(false);
				toggle.focus();
			}
		});
		window.addEventListener('resize', function () {
			if (window.innerWidth > 1100 && !root.classList.contains('nav-collapsed')) { setMenu(false); }
		});
	}

	/* ---- Erişilebilirlik ayarları (yazı boyutu, kontrast, bağlantı altı çizgisi, hareket) ---- */
	var root = document.documentElement;
	var a11yToggle = document.querySelector('[data-a11y-toggle]');
	var a11yPanel = document.getElementById('a11y-panel');
	var a11yStatus = document.querySelector('[data-a11y-status]');
	var A11Y_KEY = 'sportiva_a11y';

	function a11yRead() {
		try { return JSON.parse(window.localStorage.getItem(A11Y_KEY) || '{}') || {}; } catch (err) { return {}; }
	}
	function a11yWrite(s) {
		try { window.localStorage.setItem(A11Y_KEY, JSON.stringify(s)); } catch (err) { /* depolama kapalıysa ayar bu sayfada geçerli kalır */ }
	}
	var a11yState = a11yRead();

	function a11yApply(announce) {
		root.classList.remove('a11y-fs-1', 'a11y-fs-2');
		if (a11yState.font === 1 || a11yState.font === 2) { root.classList.add('a11y-fs-' + a11yState.font); }
		['contrast', 'links', 'motion'].forEach(function (k) { root.classList.toggle('a11y-' + k, !!a11yState[k]); });
		document.querySelectorAll('[data-a11y-font]').forEach(function (b) {
			b.setAttribute('aria-pressed', String(Number(b.getAttribute('data-a11y-font')) === (a11yState.font || 0)));
		});
		document.querySelectorAll('[data-a11y-flag]').forEach(function (b) {
			b.setAttribute('aria-pressed', String(!!a11yState[b.getAttribute('data-a11y-flag')]));
		});
		if (announce && a11yStatus) { a11yStatus.textContent = announce; }
		if (typeof checkNav === 'function') { checkNav(); }
	}

	function a11ySetOpen(open) {
		if (!a11yToggle || !a11yPanel) { return; }
		a11yToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		a11yPanel.hidden = !open;
		if (open) {
			var first = a11yPanel.querySelector('button');
			if (first) { first.focus(); }
		}
	}

	if (a11yToggle && a11yPanel) {
		a11yToggle.addEventListener('click', function () { a11ySetOpen(a11yPanel.hidden); });
		a11yPanel.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) { return; }
			if (b.hasAttribute('data-a11y-font')) {
				a11yState.font = Number(b.getAttribute('data-a11y-font'));
				a11yApply('Yazı boyutu: ' + b.textContent.trim());
			} else if (b.hasAttribute('data-a11y-flag')) {
				var k = b.getAttribute('data-a11y-flag');
				a11yState[k] = !a11yState[k];
				a11yApply(b.textContent.trim() + (a11yState[k] ? ' açıldı' : ' kapatıldı'));
			} else if (b.hasAttribute('data-a11y-reset')) {
				a11yState = {};
				a11yApply('Erişilebilirlik ayarları sıfırlandı');
			} else if (b.hasAttribute('data-a11y-close')) {
				a11ySetOpen(false);
				a11yToggle.focus();
				return;
			} else {
				return;
			}
			a11yWrite(a11yState);
		});
		a11yPanel.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { a11ySetOpen(false); a11yToggle.focus(); }
		});
	}

	/* ---- Üst menü: masaüstünde sığmıyorsa (uzun menü veya büyük yazı) hamburger menüye geç ---- */
	var headerInner = document.querySelector('.header__inner');
	var checkNav = function () {
		if (!headerInner || !nav) { return; }
		root.classList.remove('nav-collapsed');
		if (window.matchMedia('(max-width: 1100px)').matches) { return; }
		if (headerInner.scrollWidth > headerInner.clientWidth + 1 || nav.scrollHeight > nav.clientHeight + 4) {
			root.classList.add('nav-collapsed');
		}
	};
	checkNav();
	window.addEventListener('resize', checkNav);
	if (document.fonts && document.fonts.ready) { document.fonts.ready.then(checkNav); }
	a11yApply('');

	/* ---- Yukarı çık ---- */
	var toTop = document.querySelector('.to-top');
	if (toTop) {
		window.addEventListener('scroll', function () {
			toTop.classList.toggle('is-visible', (window.pageYOffset || document.documentElement.scrollTop) > 700);
		}, { passive: true });
		toTop.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: reduceMotion() ? 'auto' : 'smooth' });
		});
	}

	/* ---- PT sekmeleri (JS yoksa tüm paneller görünür kalır) ---- */
	var tabsRoot = document.querySelector('[data-tabs]');
	if (tabsRoot) {
		var tabs = tabsRoot.querySelectorAll('[data-tab]');
		var panels = tabsRoot.querySelectorAll('[data-panel]');

		var show = function (id) {
			tabs.forEach(function (t) {
				var on = t.getAttribute('data-tab') === id;
				t.classList.toggle('is-active', on);
				if (on) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
			});
			panels.forEach(function (p) {
				p.classList.toggle('is-active', p.getAttribute('data-panel') === id);
			});
		};

		tabsRoot.classList.add('js-tabs');
		var initial = (window.location.hash || '').replace('#', '');
		show(initial === 'grup' || initial === 'paketler' ? initial : 'bireysel');

		tabs.forEach(function (t) {
			t.addEventListener('click', function (e) {
				e.preventDefault();
				show(t.getAttribute('data-tab'));
			});
		});
	}

	/* ---- Rezervasyon: salon kartları ile salon seçim alanını eşleştir ---- */
	var picks = document.querySelectorAll('[data-salon-picks] .pick');
	var salonSelect = document.querySelector('[data-form="reservation"] [name="salon_id"]');

	function markPicks(id) {
		picks.forEach(function (p) {
			var on = String(p.getAttribute('data-salon-id')) === String(id);
			p.classList.toggle('is-selected', on);
			if (on) { p.setAttribute('aria-current', 'true'); } else { p.removeAttribute('aria-current'); }
		});
	}

	if (picks.length && salonSelect) {
		picks.forEach(function (p) {
			p.addEventListener('click', function (e) {
				e.preventDefault();
				salonSelect.value = p.getAttribute('data-salon-id');
				salonSelect.dispatchEvent(new Event('change', { bubbles: true }));
				markPicks(salonSelect.value);
				var target = document.getElementById('rezervasyon-formu');
				if (target && window.innerWidth <= 960) { target.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'start' }); }
			});
		});
		salonSelect.addEventListener('change', function () { markPicks(salonSelect.value); });
	}

	/* ---- Rezervasyon: seçilen salon ve gün için dolu (onaylı) saatler ---- */
	var form = document.querySelector('[data-form="reservation"]');
	if (form && window.sportivaData) {
		var salon = form.querySelector('[name="salon_id"]');
		var date = form.querySelector('[name="tarih"]');
		var busy = form.querySelector('[data-busy]');

		var loadBusy = function () {
			if (!salon || !date || !busy || !salon.value || !date.value) { return; }
			var url = window.sportivaData.ajaxUrl + '?action=sportiva_busy&salon=' + encodeURIComponent(salon.value) + '&tarih=' + encodeURIComponent(date.value);
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) { return r.text(); })
				.then(function (html) { busy.innerHTML = html; })
				.catch(function () { /* sessizce geç */ });
		};

		if (salon) { salon.addEventListener('change', loadBusy); }
		if (date) { date.addEventListener('change', loadBusy); }

		/* Başlangıç < bitiş denetimi (sunucu da ayrıca kontrol eder) */
		var start = form.querySelector('[name="bas"]');
		var end = form.querySelector('[name="bit"]');
		var check = function () {
			if (start && end && start.value && end.value && start.value >= end.value) {
				end.setCustomValidity('Bitiş saati başlangıç saatinden sonra olmalıdır.');
			} else if (end) {
				end.setCustomValidity('');
			}
		};
		if (start) { start.addEventListener('change', check); }
		if (end) { end.addEventListener('change', check); }
	}

	/* ---- PT randevu: paket ve eğitim türü uyumu (sunucu da ayrıca kontrol eder) ---- */
	var ptTur = document.getElementById('pt-tur');
	var ptPaket = document.getElementById('pt-paket');
	if (ptTur && ptPaket) {
		ptPaket.addEventListener('change', function () {
			var opt = ptPaket.options[ptPaket.selectedIndex];
			var t = opt ? opt.getAttribute('data-tur') : '';
			if (t && ptTur.value !== t) { ptTur.value = t; }
		});
		ptTur.addEventListener('change', function () {
			var opt = ptPaket.options[ptPaket.selectedIndex];
			var t = opt ? opt.getAttribute('data-tur') : '';
			if (t && ptTur.value && t !== ptTur.value) { ptPaket.value = '0'; }
		});
	}

	/* ---- IBAN kopyala ---- */
	document.querySelectorAll('[data-copy]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var text = btn.getAttribute('data-copy');
			var status = btn.parentNode.querySelector('[data-copy-status]');
			var done = function (ok) {
				if (status) { status.textContent = ok ? 'IBAN kopyalandı' : 'Kopyalanamadı; IBAN numarasını seçip kopyalayın'; }
				btn.textContent = ok ? 'Kopyalandı' : 'Kopyala';
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
			} else { done(false); }
		});
	});

	/* ---- Fotoğraf galerisi (ana fotoğraf, küçük önizlemeler, önceki/sonraki, büyütme) ---- */
	var lightbox = null;

	function getLightbox() {
		if (lightbox) { return lightbox; }
		var el = document.createElement('div');
		el.className = 'lightbox';
		el.hidden = true;
		el.setAttribute('role', 'dialog');
		el.setAttribute('aria-modal', 'true');
		el.setAttribute('aria-label', 'Fotoğraf görüntüleyici');
		el.innerHTML = '<button type="button" class="lightbox__close" aria-label="Kapat">&times;</button>' +
			'<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="Önceki fotoğraf">&#8249;</button>' +
			'<figure class="lightbox__fig"><img class="lightbox__img" alt=""><figcaption class="lightbox__cap"></figcaption></figure>' +
			'<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="Sonraki fotoğraf">&#8250;</button>';
		document.body.appendChild(el);

		var state = { items: [], index: 0, opener: null };
		var img = el.querySelector('.lightbox__img');
		var cap = el.querySelector('.lightbox__cap');
		var prev = el.querySelector('.lightbox__nav--prev');
		var next = el.querySelector('.lightbox__nav--next');
		var close = el.querySelector('.lightbox__close');

		function render() {
			var it = state.items[state.index];
			img.src = it.full;
			img.alt = it.alt;
			cap.textContent = state.items.length > 1 ? (state.index + 1) + ' / ' + state.items.length : '';
			prev.hidden = next.hidden = state.items.length < 2;
		}
		function step(d) {
			state.index = (state.index + d + state.items.length) % state.items.length;
			render();
		}
		function hide() {
			el.hidden = true;
			body.classList.remove('lightbox-open');
			if (state.opener) { state.opener.focus(); }
		}

		prev.addEventListener('click', function () { step(-1); });
		next.addEventListener('click', function () { step(1); });
		close.addEventListener('click', hide);
		el.addEventListener('click', function (e) { if (e.target === el) { hide(); } });
		document.addEventListener('keydown', function (e) {
			if (el.hidden) { return; }
			if (e.key === 'Escape') { hide(); }
			else if (e.key === 'ArrowLeft' && state.items.length > 1) { step(-1); }
			else if (e.key === 'ArrowRight' && state.items.length > 1) { step(1); }
			else if (e.key === 'Tab') {
				// Klavye odağı büyütme penceresinin içinde kalır.
				var f = [close, prev, next].filter(function (b) { return !b.hidden; });
				var i = f.indexOf(document.activeElement);
				if (e.shiftKey) {
					if (i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
				} else if (i === f.length - 1 || i === -1) {
					e.preventDefault(); f[0].focus();
				}
			}
		});

		lightbox = {
			open: function (items, index, opener) {
				state.items = items;
				state.index = index;
				state.opener = opener;
				render();
				el.hidden = false;
				body.classList.add('lightbox-open');
				close.focus();
			}
		};
		return lightbox;
	}

	document.querySelectorAll('[data-gallery]').forEach(function (root) {
		var items = [];
		try { items = JSON.parse(root.getAttribute('data-items') || '[]'); } catch (err) { items = []; }
		if (!items.length) { return; }

		var img = root.querySelector('.gallery__img');
		var main = root.querySelector('[data-gallery-zoom]');
		var count = root.querySelector('[data-gallery-count]');
		var thumbs = root.querySelectorAll('.gallery__thumb');
		var idx = 0;

		function show(i) {
			idx = (i + items.length) % items.length;
			img.src = items[idx].large;
			img.alt = items[idx].alt;
			main.href = items[idx].full;
			if (count) { count.textContent = (idx + 1) + ' / ' + items.length; }
			thumbs.forEach(function (t, k) {
				var on = k === idx;
				t.classList.toggle('is-active', on);
				if (on) {
					t.setAttribute('aria-current', 'true');
					var list = t.parentNode.parentNode;
					list.scrollLeft = t.offsetLeft - (list.clientWidth - t.offsetWidth) / 2;
				} else {
					t.removeAttribute('aria-current');
				}
			});
		}

		var prevBtn = root.querySelector('[data-gallery-prev]');
		var nextBtn = root.querySelector('[data-gallery-next]');
		if (prevBtn) { prevBtn.addEventListener('click', function () { show(idx - 1); }); }
		if (nextBtn) { nextBtn.addEventListener('click', function () { show(idx + 1); }); }

		thumbs.forEach(function (t, k) {
			t.addEventListener('click', function (e) { e.preventDefault(); show(k); });
		});

		main.addEventListener('click', function (e) {
			e.preventDefault();
			getLightbox().open(items, idx, main);
		});
	});

	/* ---- Erişilebilirlik: form sonucu mesajına odaklan (ekran okuyucu duyurur) ---- */
	var notice = document.querySelector('.form-card .notice, .account__body .notice');
	if (notice) { notice.focus(); }
})();

/* SPORTIVA 3.0: başlık gölgesi, kaydırınca beliren içerik */
(function () {
	'use strict';

	function motionOff() {
		return document.documentElement.classList.contains('a11y-motion') || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	var header = document.getElementById('site-header');
	if (header) {
		var onScroll = function () { header.classList.toggle('is-scrolled', window.pageYOffset > 12); };
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	if (motionOff() || !('IntersectionObserver' in window)) { return; }

	/* Yalnızca ekranın altında kalan öğeler gizlenir; JS çalışmazsa veya öğe zaten görünüyorsa hiçbir şey saklanmaz. */
	var selector = '.section-head, .card, .feature, .package, .salon-tile, .step, .pick, .home-packages__title, .member__text, .calendar__day';
	var items = Array.prototype.slice.call(document.querySelectorAll(selector));
	var fold = window.innerHeight;
	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) { return; }
			entry.target.classList.add('is-in');
			observer.unobserve(entry.target);
			/* Geçiş bitince sınıflar kaldırılır; böylece kartların kendi hover hareketi engellenmez. */
			setTimeout(function () { entry.target.classList.remove('reveal', 'is-in'); entry.target.style.removeProperty('--reveal-delay'); }, 1300);
		});
	}, { rootMargin: '0px 0px -8% 0px', threshold: .08 });

	items.forEach(function (el) {
		if (el.getBoundingClientRect().top < fold) { return; }
		var index = el.parentElement ? Array.prototype.indexOf.call(el.parentElement.children, el) : 0;
		el.style.setProperty('--reveal-delay', Math.min(index, 4) * 0.08 + 's');
		el.classList.add('reveal');
		observer.observe(el);
	});

	/* Odak, gizli bir öğeye gelirse o öğe hemen gösterilir (klavye kullanıcıları için). */
	document.addEventListener('focusin', function (e) {
		var hidden = e.target.closest ? e.target.closest('.reveal') : null;
		if (hidden) { hidden.classList.add('is-in'); }
	});

})();

/* PT randevu formu: seçime göre seans ücreti; tek branşlı antrenörde spor dalı sabitlenir (sunucu da aynı kuralı uygular). */
(function () {
	'use strict';
	var line = document.querySelector('[data-pt-fee]');
	var tur = document.getElementById('pt-tur');
	var brans = document.getElementById('pt-brans');
	var trainer = document.getElementById('pt-antrenor');
	if (!line || !tur || !brans || !trainer) { return; }
	var fees = JSON.parse(line.getAttribute('data-fees') || '{}');

	function showFee() {
		var fee = fees[tur.value] && fees[tur.value][brans.value];
		if (fee) {
			line.textContent = 'Seans ücreti: ' + fee;
		} else if (tur.value && brans.value) {
			line.textContent = 'Bu seçimin ücreti için tesisimizle iletişime geçin.';
		} else {
			line.textContent = 'Eğitim türü ve spor dalını seçince seans ücreti burada görünür.';
		}
	}
	function syncBranch() {
		var opt = trainer.options[trainer.selectedIndex];
		var only = opt ? opt.getAttribute('data-brans') : '';
		Array.prototype.forEach.call(brans.options, function (o) {
			o.disabled = !!(only && o.value && o.value !== only);
		});
		if (only) { brans.value = only; }
		showFee();
	}
	tur.addEventListener('change', showFee);
	brans.addEventListener('change', showFee);
	trainer.addEventListener('change', syncBranch);
	syncBranch();
})();
