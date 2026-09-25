/**
 * ============================================================
 * FILE GUIDE — site.js
 * Site JS
 *
 * Purpose:
 *     Inisialisasi global halaman admin: dropdown, tooltip, theme switch, misc UI.
 *
 * Important:
 *     - Dimuat via addJs() oleh controller Builtin terkait; bergantung
 *       global base_url/current_url dari layout dan shared functions.js.
 *     - Jangan mengubah selector/endpoint tanpa cek view & controller
 *       terkait (lihat FILE GUIDE controller module yang sama).
 * ============================================================
 */

/**
* Written by: IT-IPI
* Year		: Last Oktober 2023
* Company : Indopasifik Indahtama
*/

jQuery(function () {
	const $body = $('body');
	const initCriticalUI = function() {
		$body.addClass('theme-ready');

		$(document)
			.on('mouseenter', '.has-children', function() {
				$(this).children('ul').stop(true, true).fadeIn('fast');
			})
			.on('mouseleave', '.has-children', function() {
				$(this).children('ul').stop(true, true).fadeOut('fast');
			})
			.on('click', '.has-children', function() {
				var $this = $(this);
				$(this).next().stop(true, true).slideToggle('fast', function() {
					$this.parent().toggleClass('tree-open');
				});
				return false;
			})
			.on('click', '#mobile-menu-btn', function() {
				$body.toggleClass('mobile-menu-show');
				Cookies.set('nsd_adm_mobile', $body.hasClass('mobile-menu-show') ? '1' : '0');
				return false;
			})
			.on('mouseenter', '.sidebar-guide', function() {
				$body.addClass('show-sidebar');
			})
			.on('mouseleave', '.sidebar', function() {
				$body.removeClass('show-sidebar');
			})
			.on('click', '#mobile-menu-btn-right', function() {
				$('header').toggleClass('mobile-right-menu-show');
				return false;
			})
			.on('keyup', '.number-only', function() {
				this.value = this.value.replace(/\D/i, '');
			});

		$('form').each(function() {
			var $form = $(this);
			if (!$form.hasClass('form-shell')) {
				$form.addClass('form-shell');
			}
		});
	};

	const initDeferredUI = function() {
		bootbox.setDefaults({
			animate: false,
			centerVertical : true
		});

		$('table').on('click', '[data-action="delete-data"]', function(e){
			e.preventDefault();
			var $this =  $(this)
				, $form = $this.parents('form:eq(0)');
			bootbox.confirm({
				message: $this.attr('data-delete-title'),
				callback: function(confirmed) {
					if (confirmed) {
						$form.submit();
					}
				},
				centerVertical: true
			});
		});

		if ($.fn.overlayScrollbars && $('.sidebar').length) {
			$('.sidebar').overlayScrollbars({scrollbars : {autoHide: 'leave', autoHideDelay: 100} });
		}

		if ($.fn.dataTable) {
			$.extend($.fn.dataTable.defaults, {
				"language": {
					"processing": '<span><span class="spinner-border text-secondary" role="status"></span></span>',
				}
			});
		}
	};

	initCriticalUI();
	if ('requestIdleCallback' in window) {
		requestIdleCallback(initDeferredUI, { timeout: 1200 });
	} else {
		setTimeout(initDeferredUI, 250);
	}

	(function initMobileMenu() {
		var media = window.matchMedia('(max-width: 992px)');
		var fabBtn = document.getElementById('mobileMenuFab');
		var overlay = document.getElementById('mobileMenuOverlay');
		var backdrop = document.getElementById('mobileMenuBackdrop');
		var backBtn = document.getElementById('mobileMenuBack');
		var closeBtn = document.getElementById('mobileMenuClose');
		var titleEl = document.getElementById('mobileMenuTitle');
		var gridEl = document.getElementById('mobileMenuGrid');
		var bodyEl = document.getElementById('mobileMenuBody');
		var sourceLists = document.querySelectorAll('.sidebar-group .sidebar-menu > ul');

		if (!fabBtn || !overlay || !gridEl || !sourceLists.length) {
			return;
		}

		var rootItems = [];
		var navStack = [];

		function getText(el) {
			if (!el) return '';
			return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
		}

		function getIconHtml(anchor) {
			var icon = anchor ? (anchor.querySelector('.sidebar-menu-icon') || anchor.querySelector('i')) : null;
			return icon ? icon.outerHTML : '<i class="fas fa-cube"></i>';
		}

		function normalizeUrl(url) {
			return (url || '').replace(/\/+$/, '');
		}

		function extractItem(li) {
			if (!li || li.tagName !== 'LI') return null;
			var anchor = li.querySelector(':scope > a');
			if (!anchor) return null;

			var textEl = anchor.querySelector('.text');
			var title = textEl ? getText(textEl) : getText(anchor);
			var badgeEl = li.querySelector(':scope > .menu-baru');
			var badgeHtml = badgeEl ? badgeEl.outerHTML : '';
			var iconHtml = getIconHtml(anchor);
			var href = anchor.getAttribute('href') || '#';
			var submenu = li.querySelector(':scope > ul.submenu') || li.querySelector(':scope > ul');
			var children = [];

			if (submenu) {
				var childLis = Array.prototype.filter.call(submenu.children, function(child) {
					return child.tagName === 'LI';
				});
				children = childLis.map(extractItem).filter(Boolean);
			}

			var isActive = li.classList.contains('highlight') ||
				anchor.classList.contains('active') ||
				anchor.hasAttribute('aria-current') ||
				!!li.querySelector(':scope > a.active, :scope > .submenu a.active');

			return {
				id: li.dataset.mobileNavId || ('m-nav-' + Math.random().toString(36).slice(2, 9)),
				title: title,
				href: href,
				iconHtml: iconHtml,
				badgeHtml: badgeHtml,
				hasChildren: children.length > 0,
				children: children,
				active: isActive
			};
		}

		function buildRootItems() {
			rootItems = [];
			var currentUrl = normalizeUrl(window.location.href);

			Array.prototype.forEach.call(sourceLists, function(list) {
				Array.prototype.forEach.call(list.children, function(li) {
					var item = extractItem(li);
					if (!item) return;

					if (!item.hasChildren && normalizeUrl(item.href) === currentUrl) {
						item.active = true;
					}
					rootItems.push(item);
				});
			});
		}

		function isMenuOpen() {
			return overlay.classList.contains('is-open');
		}

		function renderCurrentLevel() {
			var currentFrame = navStack[navStack.length - 1];
			if (!currentFrame) {
				closeMenu();
				return;
			}

			if (titleEl) {
				titleEl.textContent = currentFrame.title || 'Menu';
			}

			if (backBtn) {
				if (navStack.length > 1) {
					backBtn.classList.remove('is-hidden');
				} else {
					backBtn.classList.add('is-hidden');
				}
			}

			gridEl.innerHTML = '';
			var items = currentFrame.items || [];

			items.forEach(function(item) {
				var card;
				if (item.hasChildren) {
					card = document.createElement('button');
					card.type = 'button';
					card.className = 'mobile-menu-card' + (item.active ? ' is-active' : '');
					card.setAttribute('aria-label', item.title + ' (Submenu)');
					card.innerHTML =
						item.badgeHtml +
						'<div class="mobile-menu-card-icon">' + item.iconHtml + '</div>' +
						'<span class="mobile-menu-card-label">' + item.title + '</span>' +
						'<span class="mobile-menu-card-badge-child" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>';

					card.addEventListener('click', function() {
						navStack.push({
							title: item.title,
							items: item.children
						});
						if (bodyEl) bodyEl.scrollTop = 0;
						renderCurrentLevel();
					});
				} else {
					card = document.createElement('a');
					card.className = 'mobile-menu-card' + (item.active ? ' is-active' : '');
					card.href = item.href;
					card.setAttribute('aria-label', item.title);
					card.innerHTML =
						item.badgeHtml +
						'<div class="mobile-menu-card-icon">' + item.iconHtml + '</div>' +
						'<span class="mobile-menu-card-label">' + item.title + '</span>';

					card.addEventListener('click', function() {
						closeMenu();
					});
				}

				gridEl.appendChild(card);
			});
		}

		function openMenu() {
			buildRootItems();
			navStack = [{
				title: 'Menu',
				items: rootItems
			}];
			renderCurrentLevel();

			overlay.classList.add('is-open');
			overlay.setAttribute('aria-hidden', 'false');
			fabBtn.setAttribute('aria-expanded', 'true');
			document.documentElement.classList.add('mobile-menu-open');
			document.body.classList.add('mobile-menu-open');
			if (bodyEl) bodyEl.scrollTop = 0;
		}

		function closeMenu() {
			overlay.classList.remove('is-open');
			overlay.setAttribute('aria-hidden', 'true');
			fabBtn.setAttribute('aria-expanded', 'false');
			document.documentElement.classList.remove('mobile-menu-open');
			document.body.classList.remove('mobile-menu-open');
			navStack = [{
				title: 'Menu',
				items: rootItems
			}];
		}

		function handleBack() {
			if (navStack.length > 1) {
				navStack.pop();
				if (bodyEl) bodyEl.scrollTop = 0;
				renderCurrentLevel();
			}
		}

		fabBtn.addEventListener('click', function() {
			if (isMenuOpen()) {
				closeMenu();
			} else {
				openMenu();
			}
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', closeMenu);
		}

		if (backdrop) {
			backdrop.addEventListener('click', closeMenu);
		}

		if (backBtn) {
			backBtn.addEventListener('click', handleBack);
		}

		document.addEventListener('keydown', function(e) {
			if (!isMenuOpen()) return;
			if (e.key === 'Escape') {
				closeMenu();
			}
		});

		function onMediaChange() {
			if (!media.matches && isMenuOpen()) {
				closeMenu();
			}
		}

		if (typeof media.addEventListener === 'function') {
			media.addEventListener('change', onMediaChange);
		} else if (typeof media.addListener === 'function') {
			media.addListener(onMediaChange);
		}

		buildRootItems();
	})();
});
