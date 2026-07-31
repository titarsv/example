'use strict';

performance.mark("vendors initialization");

// Depends
require('./bootstrap');

// Modules
let Forms = require('./components/forms');
let Slider = require('./components/slider');
let Popup = require('./components/popup');
require('../../node_modules/sumoselect/jquery.sumoselect.min');
require('../../node_modules/jquery-validation/dist/jquery.validate.min');
require('../../node_modules/mark.js/dist/jquery.mark.min');

// Are you ready?
$(function () {
    new Forms();
    new Slider();
    new Popup();

    // fixed header

    var header = $('.header'),
        scrollPrev = 0;

    $(window).scroll(function () {
        var scrolled = $(window).scrollTop();

        if (scrolled > 200 && scrolled) {
            header.addClass('fixed');
            $('body').addClass('fixed-header');
        } else {
            header.removeClass('fixed');
            $('body').removeClass('fixed-header');
        }
        scrollPrev = scrolled;
    })

    // mega menu

    $('.mega-menu-btn').on('click', function () {
        const $menu = $('.mega-menu');
        const isOpen = $menu.hasClass('active');

        if (!isOpen) {
            $(this).addClass('active');
            $menu.addClass('active').removeClass('closing');
        } else {
            $(this).removeClass('active');
            $menu.addClass('closing');
            setTimeout(() => {
                $menu.removeClass('active closing');
            }, 600);
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.mega-menu, .mega-menu-btn').length) {
            $('.mega-menu-btn').removeClass('active');
            $('.mega-menu').removeClass('active closing');
        }
    });

    // header-search

    (function ($) {
        const $body = $('body');
        const $btnOpen = $('.header-search__btn');

        const OPEN_CLASS = 'is-open';
        const CLOSING_CLASS = 'is-closing';

        // Initialize each search wrapper separately
        $('.header-search__wrapper').each(function () {
            const $wrap = $(this);
            const $form = $wrap.find('.header-search__form');
            const $input = $form.find('input[type="search"]');
            const $btnClear = $form.find('.search-close');
            const $results = $form.find('.search-results');
            const $list = $results.children('ul');
            const $items = $list.children('li');
            const $viewAll = $results.find('.view-all');
            const $nothing = $results.find('.nothing-found');
            const $noTxt = $nothing.find('p span');

            let closingTimer = null;

            // helpers
            const open = () => {
                clearTimeout(closingTimer);
                $wrap.addClass(OPEN_CLASS).removeClass(CLOSING_CLASS).attr('aria-hidden', 'false');
                $body.addClass('open-search-bar');
                $('.mega-menu').removeClass('active');
                setTimeout(() => $input.trigger('focus'), 150);
            };

            const close = () => {
                clearTimeout(closingTimer);
                $wrap.addClass(CLOSING_CLASS).attr('aria-hidden', 'true');
                closingTimer = setTimeout(() => {
                    $wrap.removeClass(`${OPEN_CLASS} ${CLOSING_CLASS} show-results has-text`);
                    $body.removeClass('open-search-bar open-search-results show-mobile-overlay');
                    $results.removeClass('highlighting-results');
                    // also hide panel bits
                    $viewAll.hide();
                    $nothing.hide();
                }, 600);
            };

            const showResultsPanel = () => {
                $wrap.addClass('show-results');
                $body.addClass('open-search-results show-mobile-overlay');
            };

            const hideResultsPanel = () => {
                $wrap.removeClass('show-results');
                $body.removeClass('open-search-results show-mobile-overlay');
                $results.removeClass('highlighting-results');
                $viewAll.hide();
                $nothing.hide();
            };

            // search-close: clear AND close
            $btnClear.on('click', function () {
                $input.val('');
                $noTxt.text('');
                // reset all rows visible
                $items.show();
                // clear highlights
                $list.unmark();
                hideResultsPanel();
                close(); // fully close the panel
            });

            // cache each row's searchable text (title span)
            const rowData = $items.map(function () {
                const $li = $(this);
                const $title = $li.find('.search-results-item__info > span').first();
                return {
                    $li,
                    $title,
                    text: ($title.text() || '').toLowerCase()
                };
            }).get();

            // debounce
            const debounce = (fn, wait = 200) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(null, args), wait);
                };
            };

            // filtering + highlighting
            const handleInput = debounce(function () {
                // cancel any pending close() timer so it can't remove classes after we set them
                clearTimeout(closingTimer);

                const q = $input.val().trim();
                const qLower = q.toLowerCase();
                $noTxt.text(q);
                $wrap.toggleClass('has-text', q.length > 0);

                // If less than 3 chars: hide results and reset list visibility
                if (q.length < 3) {
                    hideResultsPanel();
                    $items.show();
                    $list.unmark();
                    return;
                }

                // filter rows by title contains
                let matchCount = 0;
                rowData.forEach(({$li, text}) => {
                    const isMatch = text.includes(qLower);
                    $li.toggle(isMatch);
                    if (isMatch) matchCount++;
                });

                // show/hide panels
                showResultsPanel();

                if (matchCount > 0) {
                    $viewAll.css('display', 'flex');
                    $nothing.hide();
                } else {
                    $viewAll.hide();
                    $nothing.css('display', 'block');
                }

                // highlighting only on visible matches
                $list.unmark({
                    done() {
                        if (matchCount > 0) {
                            // mark on visible <li> content only
                            $items.filter(':visible').each(function () {
                                $(this).mark(q, {separateWordSearch: true});
                            });
                            $results.addClass('highlighting-results');
                        } else {
                            $results.removeClass('highlighting-results');
                        }
                    }
                });
            }, 120);

            $input.on('input', handleInput);

            // prevent submit on Enter when empty, close on Esc
            $input.on('keydown', function (e) {
                if (e.key === 'Enter' && !$input.val().trim()) e.preventDefault();
                if (e.key === 'Escape') close();
            });

            // click outside closes
            $(document).on('pointerdown', function (e) {
                if (!$wrap.is(e.target) && $wrap.has(e.target).length === 0 && $wrap.hasClass(OPEN_CLASS)) {
                    close();
                }
            });
            $wrap.on('pointerdown', function (e) {
                e.stopPropagation();
            });

            // Store methods for button to access
            $wrap.data('searchMethods', { open, close });
        });

        // open/close toggle (only for desktop button)
        $btnOpen.on('click', function () {
            const $desktopWrap = $('.header-search__wrapper').not('.header-search-mobile__wrapper');
            const methods = $desktopWrap.data('searchMethods');
            if (methods) {
                $desktopWrap.hasClass(OPEN_CLASS) ? methods.close() : methods.open();
            }
        });

    })(jQuery);

    // slider refresh

    const relayout = debounce(() => {
        $('.slick-slider').each(function () {
            // or: $(this).slick('refresh');
            $(this).slick('setPosition');
        });
    }, 120);

    $(window).on('resize.slickfix orientationchange.slickfix', relayout);

    function debounce(fn, wait) {
        let t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn, wait);
        };
    }

    // custom cursor

    const pointer = document.getElementById('custom-pointer');
    const targetElements = document.querySelectorAll('.custom-cursor');

    if (pointer && targetElements.length > 0) {
        const movePointer = (e) => {
            requestAnimationFrame(() => {
                pointer.style.left = `${e.clientX}px`;
                pointer.style.top = `${e.clientY}px`;
            });
        };
        document.addEventListener('mousemove', movePointer);
        targetElements.forEach(area => {
            area.addEventListener('mouseenter', () => {
                pointer.style.opacity = '1';
            });
            area.addEventListener('mouseleave', () => {
                pointer.style.opacity = '0';
            });
        });
    }

    // main links screen

    const links = document.querySelectorAll('.main-categories__list a[data-link]');
    let activeImage = null;
    const imageOffsetX = 90; // смещение вправо
    const imageOffsetY = -170; // смещение вниз

    // Двигаем активную картинку за курсором
    const moveActiveImage = (e) => {
        if (activeImage && activeImage.style) {
            requestAnimationFrame(() => {
                if (activeImage && activeImage.style) {
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;
                    const rect = activeImage.getBoundingClientRect();
                    const imgWidth = rect.width || 0;
                    const imgHeight = rect.height || 0;

                    let targetX = e.clientX + imageOffsetX;
                    let targetY = e.clientY + imageOffsetY;

                    // Проверка правой границы
                    if (targetX + imgWidth > viewportWidth - 40) {
                        targetX = viewportWidth - imgWidth - 40;
                    }

                    // Проверка левой границы
                    if (targetX < 40) {
                        targetX = 40;
                    }

                    // Проверка нижней границы
                    if (targetY + imgHeight > viewportHeight - 200) {
                        targetY = viewportHeight - imgHeight - 200;
                    }

                    // Проверка верхней границы
                    if (targetY < 10) {
                        targetY = 10;
                    }

                    activeImage.style.left = `${targetX + window.scrollX}px`;
                    activeImage.style.top = `${targetY + window.scrollY}px`;
                }
            });
        }
    };

    // Iterate over each link to add event listeners
    links.forEach(link => {

        // Event listener for when the mouse enters the link area
        link.addEventListener('mouseenter', () => {
            // Get the value of the 'data-link' attribute (e.g., "top_sellers")
            const linkValue = link.dataset.link;

            // Find the corresponding image container using the data value
            const imageToShow = document.querySelector(`.main-categories__img[data-image="${linkValue}"]`);

            // If a matching image container is found, add the 'active' class to it
            if (imageToShow) {
                // Сначала останавливаем предыдущее отслеживание если оно было
                if (activeImage) {
                    document.removeEventListener('mousemove', moveActiveImage);
                }
                activeImage = imageToShow;
                imageToShow.classList.add('active');
                // Начинаем отслеживать движение мыши
                document.addEventListener('mousemove', moveActiveImage);
            }
        });

        // Event listener for when the mouse leaves the link area
        link.addEventListener('mouseleave', () => {
            // Get the value of the 'data-link' attribute
            const linkValue = link.dataset.link;

            // Find the corresponding image container to hide it
            const imageToHide = document.querySelector(`.main-categories__img[data-image="${linkValue}"]`);

            // If found, remove the 'active' class
            if (imageToHide) {
                imageToHide.classList.remove('active');
                // Останавливаем отслеживание движения мыши
                document.removeEventListener('mousemove', moveActiveImage);
                activeImage = null;
            }
        });
    });

    // chips animation

    const chips_section = document.querySelector('.advantages-main');
    if (chips_section) {

        let started = false;

        // Animation timing constants
        const FADE_DURATION_MS = 400;
        const WAIT_DURATION_MS = 300;
        const staggerDelay = 150;
        // Duration of the bounce animation
        const BOUNCE_DURATION_MS = 1300;

        function startChipsAnimation() {
            if (started) return;
            started = true;

            // Desired order for staggering the animation
            const order = [
                'advantage5', 'advantage6', 'advantage7', 'advantage4',
                'advantage8', 'advantage1', 'advantage3', 'advantage2'
            ];

            const chips = order
                .map(cls => chips_section.querySelector('.advantages-list .' + cls))
                .filter(Boolean);

            // Lock initial layout (Force reflow)
            chips.forEach(chip => {
                chip.offsetHeight;
            });

            // Calculate the total time until the LAST chip completes its FADE IN.
            const lastChipIndex = chips.length - 1;
            const timeUntilLastFadeStarts = lastChipIndex * staggerDelay;
            const totalFadeTime = timeUntilLastFadeStarts + FADE_DURATION_MS;

            // The time when the FIRST chip should begin the bounce sequence
            const fallStartMasterDelay = totalFadeTime + WAIT_DURATION_MS;


            // 1. Start all chips' FADE IN / SCALE animation with stagger.
            chips.forEach((chip, i) => {
                const chipStartDelay = i * staggerDelay;

                setTimeout(() => {
                    chip.classList.add('is-fading');
                }, chipStartDelay);
            });

            // 2. Start the BOUNCE for ALL chips with the same initial stagger delay
            // after the master delay has elapsed.
            setTimeout(() => {
                chips.forEach((chip, i) => {
                    const bounceStaggerDelay = i * staggerDelay;

                    setTimeout(() => {
                        // Remove fading class before starting bounce
                        chip.classList.remove('is-fading');

                        // Add the bouncing class to start the gravity fall.
                        chip.classList.add('is-bouncing');

                        // Ensure the chip remains in its final, visible, resting state.
                        setTimeout(() => {
                            // 1. Set the final style (translateY(0), scale(1), opacity(1)) as inline styles
                            // to override the initial styles defined in the CSS class.
                            chip.style.transform = 'translateY(0) scale(1)';
                            chip.style.opacity = '1';

                            // 2. Now safely remove the animation class.
                            chip.classList.remove('is-bouncing');
                        }, BOUNCE_DURATION_MS);

                    }, bounceStaggerDelay);
                });

            }, fallStartMasterDelay);
        }

        // Start when at least 30% of the section is visible
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                const e = entries[0];
                // Check if element is intersecting AND at least 30% is visible
                if (e.isIntersecting && e.intersectionRatio >= 0.3) {
                    io.disconnect();
                    startChipsAnimation();
                }
            }, {
                threshold: [0, 0.3, 1]
            });
            io.observe(chips_section);
        } else {
            // Fallback for older browsers
            startChipsAnimation();
        }

        // custom slider controls

        function updateControlsEmptyState() {
            $('.custom-slider__controls').each(function () {
                const $controls = $(this);
                const hasDots = $controls.find('.slick-dots:visible').length > 0; // anywhere inside
                $controls.toggleClass('empty', !hasDots);
            });
        }

        // Simple debounce
        function debounce(fn, wait = 150) {
            let t;
            return function (...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        // Run now (in case script loads after Slick)
        updateControlsEmptyState();

        // Run on resize/orientation change
        $(window).on('resize orientationchange', debounce(updateControlsEmptyState, 150));

        // Also re-check when Slick changes its dots (init, reInit, breakpoint, etc.)
        $(document).on(
            'init reInit afterChange breakpoint setPosition',
            '.slick-slider',
            updateControlsEmptyState
        );
    }

    // accordion

    $('.accordion-item__head').on('click', function () {
        $(this).closest('.accordion-item').toggleClass('active').siblings().removeClass('active');
        $(this).next('.accordion-item__body').slideToggle().closest('.accordion-item').siblings().find('.accordion-item__body').slideUp();
    });

    // footer logo

    const footers = document.querySelectorAll('.footer');

    const DELAY_MS = 1000; // adjust delay as needed

    const addAnimated = (footerEl) => {
        const logo = footerEl.querySelector('.footer-logo');
        if (logo) logo.classList.add('animated');
    };

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio >= 0.3) {
                    setTimeout(() => addAnimated(entry.target), DELAY_MS); // ← delay added
                    obs.unobserve(entry.target); // one-shot
                }
            });
        }, {threshold: [0, 0.3, 1]});

        footers.forEach(f => io.observe(f));
    } else {
        // Fallback for old browsers
        const is30Visible = (el) => {
            const r = el.getBoundingClientRect();
            const vh = window.innerHeight || document.documentElement.clientHeight;
            const visible = Math.min(r.bottom, vh) - Math.max(r.top, 0);
            return visible > 0 && visible / Math.max(1, r.height) >= 0.3;
        };

        const onScroll = () => {
            footers.forEach(f => {
                if (is30Visible(f) && !f.dataset.animScheduled) {
                    f.dataset.animScheduled = '1';
                    setTimeout(() => addAnimated(f), DELAY_MS); // ← delay added
                }
            });
        };

        window.addEventListener('scroll', onScroll, {passive: true});
        window.addEventListener('resize', onScroll);
        onScroll();
    }

    // footer menu

    const mobileBreakpoint = 575;
    const allMenus = $('.footer-menu');
    const allTitles = $('.footer-menu__title');

    // Function to check screen size and set initial state
    let isDesktop = $(window).width() >= mobileBreakpoint;

    function checkScreenSize() {
        const newIsDesktop = $(window).width() >= mobileBreakpoint;

        if (newIsDesktop) {
            // --- DESKTOP STATE (>= 575px) ---

            // Only execute cleanup if we just transitioned FROM mobile TO desktop
            if (!isDesktop) {
                // Ensure all titles have the active class for icon consistency (even though the icon is hidden by CSS)
                allTitles.addClass('active');
                // The CSS media query handles display: block !important, so no slideDown needed.
            }
            isDesktop = true;

        } else {
            // --- MOBILE STATE (< 575px) ---

            // Explicitly set all menus to display: none immediately to start clean
            allMenus.css('display', 'none');
            allTitles.removeClass('active');

            // 1. Ensure the first menu is open and active
            const firstTitle = allTitles.eq(0);
            const firstMenu = allMenus.eq(0);

            // Open the first menu (Shop)
            firstTitle.addClass('active');
            firstMenu.css('display', 'block'); // Use raw CSS property for instant display

            // 2. Ensure the remaining menus are closed and inactive (redundant but safe)
            allTitles.slice(1).removeClass('active');
            allMenus.slice(1).css('display', 'none');

            isDesktop = false;
        }
    }

    allTitles.off('click').on('click', function () {
        // Only allow click function on mobile
        if ($(window).width() < mobileBreakpoint) {
            const $this = $(this);
            const menu = $this.next('.footer-menu');

            if ($this.data('menu-id') === 1 && $this.hasClass('active')) {
                return;
            }

            if (menu.is(':hidden')) {
                allTitles.not($this).removeClass('active').next('.footer-menu').slideUp(300);
            }

            $this.toggleClass('active');
            menu.slideToggle(300);
        }
    });

    checkScreenSize();
    $(window).on('resize', checkScreenSize);



    // blog categories

    $('.blog-categories__title').on('click', function () {
        $(this).closest('.blog-categories').toggleClass('active');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.blog-categories').length) {
            $('.blog-categories').removeClass('active');
        }
    });

    $('.blog-categories__title-mob').on('click', function () {
        $('html').addClass('blog-categories-open');
        $('.blog-categories__mob').addClass('active');
    });

    $('.blog-categories__close').on('click', function () {
        $('html').removeClass('blog-categories-open');
        $('.blog-categories__mob').removeClass('active');
    });

    // product amount

    $('.product-amount li').on('click', function () {
        $(this).addClass('current').siblings().removeClass('current');
    });

    // product sticky buttons

    const btns = document.querySelector('.product-page__info-buttons');
    if (btns) {
        btns.classList.add('sticky');
    }

    // effects

    let uid = 0;
    const NS = 'http://www.w3.org/2000/svg';

    function makeRect(attrs) {
        const el = document.createElementNS(NS, 'rect');
        for (const k in attrs) el.setAttribute(k, attrs[k]);
        return el;
    }

    function makeEl(name, attrs = {}) {
        const el = document.createElementNS(NS, name);
        for (const k in attrs) el.setAttribute(k, attrs[k]);
        return el;
    }

    window.buildIndicator = function(host) {
        // Config via data-* (defaults)
        const percent = Math.max(0, Math.min(100, parseFloat(host.dataset.percent) || 0));
        const h = parseFloat(host.dataset.height) || 20; // px
        const bw = parseFloat(host.dataset.bar) || 4;  // bar width px
        const gap = parseFloat(host.dataset.gap) || 2;  // gap px
        const baseColor = host.dataset.base || '#D9D9D9';
        const fillColor = host.dataset.fill || '#7FE3C1';

        const id = 'ind-' + (++uid);

        // SVG sized by CSS; no viewBox -> pattern units act in px (stays 4px bars at any width)
        const svg = makeEl('svg', {width: '100%', height: h});
        const defs = makeEl('defs');

        // Base (grey) pattern
        const patGrey = makeEl('pattern', {
            id: 'barsGrey-' + id,
            patternUnits: 'userSpaceOnUse',
            width: bw + gap,
            height: h
        });
        patGrey.appendChild(makeRect({x: 0, y: 0, width: bw, height: h, rx: 1, fill: baseColor}));

        // Fill (green) pattern
        const patGreen = makeEl('pattern', {
            id: 'barsGreen-' + id,
            patternUnits: 'userSpaceOnUse',
            width: bw + gap,
            height: h
        });
        patGreen.appendChild(makeRect({x: 0, y: 0, width: bw, height: h, rx: 1, fill: fillColor}));

        defs.appendChild(patGrey);
        defs.appendChild(patGreen);
        svg.appendChild(defs);

        // Base layer fills full width
        svg.appendChild(makeRect({
            x: 0, y: 0, width: '100%', height: h, fill: `url(#${patGrey.id})`
        }));

        // Fill layer width = percent%
        const fillRect = makeRect({
            x: 0, y: 0, width: percent + '%', height: h, fill: `url(#${patGreen.id})`
        });
        svg.appendChild(fillRect);

        host.replaceChildren(svg);

        // Optional API to update later: host._setPercent(65)
        host._setPercent = (p) => {
            const v = Math.max(0, Math.min(100, +p || 0));
            fillRect.setAttribute('width', v + '%');
        };
    }

    // Init all
    document.querySelectorAll('.svg-indicator').forEach(window.buildIndicator);

    // video

    const PAUSE_OVERLAY_DELAY = 450; // ms — delay before showing overlays on pause

    // Load YT IFrame API once
    function loadYT() {
        return new Promise(resolve => {
            if (window.YT && YT.Player) return resolve();
            const s = document.createElement('script');
            s.src = 'https://www.youtube.com/iframe_api';
            s.async = true;
            document.head.appendChild(s);
            const prev = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                prev && prev();
                resolve();
            };
        });
    }

    // Helpers
    function ytIdFrom(src) {
        try {
            const u = new URL(src, location.href);
            if (u.hostname.includes('youtu.be')) return u.pathname.slice(1);
            const m = u.pathname.match(/\/embed\/([^/?#]+)/);
            return (m && m[1]) || u.searchParams.get('v');
        } catch {
            return null;
        }
    }

    function ensureJsApi(iframe) {
        const u = new URL(iframe.src, location.href);
        u.searchParams.set('enablejsapi', '1');
        u.searchParams.set('playsinline', '1');
        u.searchParams.set('origin', location.origin);
        iframe.src = u.toString();
        iframe.setAttribute('allow', (iframe.getAttribute('allow') || '') + '; autoplay');
    }

    function initBlock(block) {
        const iframe = block.querySelector('iframe');
        if (!iframe) return;

        // Create the <img> inside .video-preview if missing
        const preview = block.querySelector('.video-preview');
        if (preview && !preview.querySelector('img')) {
            const img = document.createElement('img');
            img.alt = '';
            preview.appendChild(img);
        }

        // Set thumbnail if none provided
        const img = preview ? preview.querySelector('img') : null;
        if (img && !img.getAttribute('src')) {
            const id = ytIdFrom(iframe.src);
            if (id) {
                const max = `https://i.ytimg.com/vi/${id}/maxresdefault.jpg`;
                const hq = `https://i.ytimg.com/vi/${id}/hqdefault.jpg`;
                img.src = max;
                img.onerror = () => {
                    if (img.src !== hq) img.src = hq;
                };
            }
        }

        ensureJsApi(iframe);

        const showOverlays = () => block.classList.remove('is-playing');
        const hideOverlays = () => block.classList.add('is-playing');

        let player;
        let showTimer = null;
        const scheduleShow = () => {
            clearTimeout(showTimer);
            showTimer = setTimeout(showOverlays, PAUSE_OVERLAY_DELAY);
        };
        const cancelShow = () => {
            clearTimeout(showTimer);
            showTimer = null;
        };

        loadYT().then(() => {
            player = new YT.Player(iframe, {
                events: {
                    onStateChange(e) {
                        switch (e.data) {
                            case YT.PlayerState.PLAYING:
                                cancelShow();
                                hideOverlays();
                                break;
                            case YT.PlayerState.BUFFERING:
                                // happens during scrubbing; keep overlays hidden
                                cancelShow();
                                hideOverlays();
                                break;
                            case YT.PlayerState.PAUSED:
                                // only show if it stays paused for a bit (prevents flicker on scrub)
                                scheduleShow();
                                break;
                            case YT.PlayerState.ENDED:
                                cancelShow();
                                showOverlays();
                                break;
                            case YT.PlayerState.CUED:
                            case YT.PlayerState.UNSTARTED:
                                cancelShow();
                                showOverlays();
                                break;
                        }
                    }
                }
            });
            block._ytPlayer = player;
        });

        // Click overlay/button to start
        const start = () => {
            cancelShow();
            hideOverlays(); // instant feedback
            if (player && player.playVideo) player.playVideo();
            else {
                try {
                    const u = new URL(iframe.src, location.href);
                    u.searchParams.set('autoplay', '1');
                    u.searchParams.set('mute', '1');
                    u.searchParams.set('playsinline', '1');
                    iframe.src = u.toString();
                } catch {
                }
            }
        };

        [block, preview, block.querySelector('.video-btn')].forEach(el => {
            if (!el) return;
            el.addEventListener('click', (ev) => {
                if (!block.classList.contains('is-playing')) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    start();
                }
            });
        });
    }

    function init() {
        document.querySelectorAll('.product-video').forEach(initBlock);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    // video tag

    function initNativeVideo(block) {
        const video = block.querySelector('video');
        const preview = block.querySelector('.video-preview');
        if (!video || !preview) return;

        // ensure <img> exists inside .video-preview (does NOT set src here)
        let img = preview.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = '';
            preview.appendChild(img);
        }

        // Start with overlays visible (only hide after actual play)
        block.classList.remove('is-playing');

        let started = false;
        let showTimer = null;
        const show = () => block.classList.remove('is-playing');
        const hide = () => block.classList.add('is-playing');
        const scheduleShow = () => {
            clearTimeout(showTimer);
            showTimer = setTimeout(show, PAUSE_OVERLAY_DELAY);
        };
        const cancelShow = () => {
            clearTimeout(showTimer);
            showTimer = null;
        };

        // Events
        video.addEventListener('play', () => {
            started = true;
            cancelShow();
            hide();
        });
        video.addEventListener('playing', () => {
            started = true;
            cancelShow();
            hide();
        });
        video.addEventListener('pause', () => {
            started ? scheduleShow() : show();
        });
        video.addEventListener('ended', () => {
            started = false;
            cancelShow();
            show();
        });

        // Keep overlays hidden during scrubbing/buffering only after user started
        ['waiting', 'seeking', 'stalled', 'suspend'].forEach(ev =>
            video.addEventListener(ev, () => {
                if (started) {
                    cancelShow();
                    hide();
                }
            })
        );
        ['canplay', 'canplaythrough', 'seeked'].forEach(ev =>
            video.addEventListener(ev, () => {
                if (started && !video.paused && !video.ended) hide();
            })
        );

        // Click overlay/button to start
        const start = () => {
            started = true;
            cancelShow();
            hide();
            video.play().catch(() => {
            });
        };
        [preview, block.querySelector('.video-btn')].forEach(el => {
            if (!el) return;
            el.addEventListener('click', (e) => {
                if (!block.classList.contains('is-playing')) {
                    e.preventDefault();
                    e.stopPropagation();
                    start();
                }
            });
        });
        block._nativeVideo = video;
    }

    function initAllNative() {
        document.querySelectorAll('.product-video').forEach(block => {
            if (block.querySelector('video')) initNativeVideo(block);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllNative);
    } else {
        initAllNative();
    }

    const USE_RANDOM_FRAME = false;
    const FIRST_FRAME_TIME = 0.08;  // safer than 0
    const JPEG_QUALITY = 0.9;
    const TIMEOUT_MS = 4000;

    function ensurePreviewImg(container) {
        let img = container.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = '';
            container.appendChild(img);
        }
        return img;
    }

    async function captureFrameFromUrl(url, timeSec) {
        const ghost = document.createElement('video');
        ghost.preload = 'metadata';
        ghost.crossOrigin = 'anonymous'; // we only set it on the ghost element
        ghost.muted = true;
        ghost.src = url;

        // metadata
        await new Promise((res, rej) => {
            const to = setTimeout(() => rej(new Error('metadata timeout')), TIMEOUT_MS);
            ghost.addEventListener('loadedmetadata', () => {
                clearTimeout(to);
                res();
            }, {once: true});
            ghost.addEventListener('error', () => {
                clearTimeout(to);
                rej(new Error('load error'));
            }, {once: true});
        });

        const t = Math.min(Math.max(timeSec, 0), ghost.duration || timeSec);

        // seek
        await new Promise((res, rej) => {
            const to = setTimeout(() => rej(new Error('seek timeout')), TIMEOUT_MS);
            ghost.addEventListener('seeked', () => {
                clearTimeout(to);
                res();
            }, {once: true});
            try {
                ghost.currentTime = t;
            } catch (e) {
                clearTimeout(to);
                rej(e);
            }
        });

        // draw
        const w = ghost.videoWidth || 640;
        const h = ghost.videoHeight || 360;
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(ghost, 0, 0, w, h);

        // may throw if CORS taints canvas
        return canvas.toDataURL('image/jpeg', JPEG_QUALITY);
    }

    async function initPosterCapture(block) {
        const video = block.querySelector('video');
        const preview = block.querySelector('.video-preview');
        if (!video || !preview) return;

        const img = ensurePreviewImg(preview);

        // If you already provided a poster or an img src, keep it.
        const providedPoster =
            video.getAttribute('poster') ||
            (img.getAttribute('src') || '') ||
            video.dataset.poster ||
            block.dataset.poster || '';

        if (providedPoster) {
            if (!img.src) img.src = providedPoster;
            return;
        }

        // Use <source> src (do not touch the actual <video> element)
        const source = video.querySelector('source');
        const url = (source && source.getAttribute('src')) || video.currentSrc || video.src;
        if (!url) return;

        const targetTime = USE_RANDOM_FRAME ? 0.5 : FIRST_FRAME_TIME; // pick a time; not using video.duration here to avoid probing the real <video>

        try {
            const dataUrl = await captureFrameFromUrl(url, targetTime);
            img.src = dataUrl; // preview only
        } catch (e) {
            // CORS blocked: set your fallback here if desired
            // img.src = 'https://your-cdn.example.com/fallback.jpg';
        }
    }

    function initAllPosters() {
        document.querySelectorAll('.product-video').forEach(initPosterCapture);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllPosters);
    } else {
        initAllPosters();
    }

    $('.video-slider').on('beforeChange', function(event, slick, currentSlide, nextSlide) {

        // Find all video blocks
        const videoBlocks = document.querySelectorAll('.product-video');

        videoBlocks.forEach(block => {
            // 1. Stop YouTube Video if it exists
            if (block._ytPlayer && typeof block._ytPlayer.pauseVideo === 'function') {
                block._ytPlayer.pauseVideo();
            }

            // 2. Stop Native Video if it exists
            if (block._nativeVideo) {
                block._nativeVideo.pause();
                // Optional: block._nativeVideo.currentTime = 0; // Reset to start
            }

            // 3. Reset the UI overlays (defined in your original script)
            block.classList.remove('is-playing');
        });
    });

    // categories scroll

    function initializeScrollCategories() {
        $('.catalog-categories__list').each(function () {
            var $this = $(this),
                $wrapper = $this.closest('.catalog-categories'),
                $la = $wrapper.find('.arrow-left'),
                $ra = $wrapper.find('.arrow-right');

            function calculateDimensions() {
                var cw = $this.innerWidth(), // Container width
                    tw = $this.find('ul').outerWidth(), // Total width of tabs
                    tr = Math.max(tw - cw, 0); // Maximum scrollable distance to the right
                return {cw, tw, tr};
            }

            function updateArrows() {
                var {tr} = calculateDimensions();
                var scrollLeft = $this.scrollLeft();
                var scrollWidth = $this.get(0).scrollWidth; // Total scrollable element width
                var clientWidth = $this.get(0).clientWidth; // Visible width of the scrollable element
                var atRightEnd = scrollWidth - Math.floor(scrollLeft) - clientWidth <= 1; // Allow a tolerance of 1px

                $la.toggleClass('active', scrollLeft > 0);
                $ra.toggleClass('active', !atRightEnd);
            }

            function getAdjustedScrollWidth() {
                var {cw} = calculateDimensions();
                var arrowWidth = $la.outerWidth() + $ra.outerWidth(); // Combined width of both arrows
                return window.innerWidth < 768 ? cw - arrowWidth : cw;
            }

            function bindArrowEvents() {
                $la.off('click').on('click', function () {
                    var adjustedCw = getAdjustedScrollWidth(),
                        leftPos = Math.max($this.scrollLeft() - adjustedCw, 0);
                    $this.animate({scrollLeft: leftPos}, 300, updateArrows);
                });

                $ra.off('click').on('click', function () {
                    var {tr} = calculateDimensions();
                    var adjustedCw = getAdjustedScrollWidth(),
                        rightPos = Math.min($this.scrollLeft() + adjustedCw, tr);
                    $this.animate({scrollLeft: rightPos}, 300, updateArrows);
                });
            }

            updateArrows(); // Initial call to set arrows' state
            bindArrowEvents();
            $this.off('scroll').on('scroll', updateArrows);
        });
    }

    function debounce(func, wait, immediate) {
        var timeout;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            }, wait);
            if (immediate && !timeout) func.apply(context, args);
        };
    }

    // Debounce and reinitialize on resize and orientation change
    $(window).off('resize scrollTabs').on('resize scrollTabs', debounce(initializeScrollCategories, 250));

    initializeScrollCategories();

    // catalog filters open/close

    $('.catalog-filters__btn').on('click', function () {
        $('.catalog-filters').addClass('active')
    });

    $('.catalog-filters__close').on('click', function () {
        $('.catalog-filters').removeClass('active')
    });

    // catalog toggler

    $('.catalog-filter__toggler').on('click', function () {
        $(this).toggleClass('active')
    });

    // catalog filter

    $(document).on('click', '.catalog-filter__btn', function (e) {
        e.stopPropagation();
        const $wrap = $(this).closest('.catalog-filter__wrapper');
        const isActive = $wrap.hasClass('active');
        $('.catalog-filter__wrapper.active').not($wrap).removeClass('active');
        $wrap.toggleClass('active', !isActive);
    });

    $(document).on('click', '.catalog-filter__wrapper', function (e) {
        e.stopPropagation();
    });

    $(document).on('click', function () {
        $('.catalog-filter__wrapper').removeClass('active');
    });

    function updateResultsState($wrap) {
        const $results = $wrap.find('.catalog-filters__results').first();
        const hasChips = $results.find('.catalog-filter__clear').length > 0;
        $results.toggleClass('show', hasChips);
    }

    // $(document).on('click', '.catalog-filter', function (e) {
    //     e.preventDefault();
    //     const $t = $(this);
    //     const val = String($t.data('filter') || '').trim();
    //     if (!val) return;
    //     const $wrap = $t.closest('.catalog-filters');
    //     const $result = $wrap.find('.catalog-filters__results');
    //     const sel = `.catalog-filter__clear[data-clear="${val}"]`;
    //     const $pill = $result.find(sel);
    //     const nowActive = $t.toggleClass('active').hasClass('active');
    //     if (nowActive) {
    //         if (!$pill.length) {
    //             $result.append(
    //                 `<span class="catalog-filter__clear" data-clear="${val}">
    //                   ${val}
    //                   <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
    //                     <path d="M12 4L4 12" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    //                     <path d="M4 4L12 12" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    //                   </svg>
    //                 </span>`
    //             );
    //         }
    //     } else {
    //         $pill.remove();
    //     }
    //
    //     updateResultsState($wrap);
    // });

    // clear

    $(document).on('click', '.catalog-filter__clear', function () {
        const val = $(this).data('clear');
        const $wrap = $(this).closest('.catalog-filters');
        $(this).remove();
        $wrap.find('.catalog-filter').filter(function () {
            return $(this).data('filter') === val;
        }).removeClass('active');
        updateResultsState($wrap);
    });

    // clear all

    $(document).on('click', '.catalog-filter__clear-all', function () {
        const $wrap = $(this).closest('.catalog-filters');
        $wrap.find('.catalog-filter__clear').remove();
        $wrap.find('.catalog-filter.active').removeClass('active');
        updateResultsState($wrap);
    });

    $('.catalog-filters__results').each(function () {
        updateResultsState($(this).closest('.catalog-filters'));
    });

    // sort

    $(document).on('click', '.catalog-sort', function () {
        $(this).addClass('current').siblings().removeClass('current').closest('.catalog-filter__wrapper').removeClass('active');
    });

    // mobile filters

    $('.catalog-filters__mob-btn').on('click', function () {
        $('html').addClass('catalog-filters-open');
        $('.catalog-filters__mob-main').addClass('active');
    });

    $('.catalog-filters__mob-close, .catalog-filters__overlay').on('click', function () {
        $('html').removeClass('catalog-filters-open');
        $('.catalog-filters__mob-main').removeClass('active');
    });

    $('.catalog-filter__block-head').on('click', function () {
        const $el = $(this);
        $el.next('.catalog-filter__block-body').slideToggle()
            .closest('.catalog-filter__block').toggleClass('active');
    });

    // tabs faq

    $(document).on('click', '.page-faq .faq-tabs li', function () {
        const $tab = $(this);
        const key = $tab.data('tab');
        const $items = $('.page-faq .faq-list .accordion-item');

        $tab.addClass('active').siblings().removeClass('active');

        let $visible;
        if ($tab.hasClass('all')) {
            $items.removeClass('hide');
            $visible = $items;
        } else {
            $items.addClass('hide');
            $visible = $items.filter((_, el) => $(el).data('content') === key)
                .removeClass('hide');
        }

        if ($visible.length) {
            const $first = $visible.first();

            $first.addClass('active')
                .siblings('.accordion-item').removeClass('active');

            $first.find('.accordion-item__body').stop(true, true).show();
            $first.siblings('.accordion-item')
                .find('.accordion-item__body').stop(true, true).hide();
        }
    });

    // cart update

    function getCartPopup($from) {
        return ($from && $from.length ? $from.closest('.cart-popup') : $('.cart-popup')).first();
    }

    function readQty($input) {
        const n = parseInt(String($input.val()).replace(/[^\d]/g, ''), 10);
        return isNaN(n) || n < 1 ? 1 : n;
    }

    // function updateCartUI($scope) {
    //     const $popup = ($scope && $scope.length ? $scope.closest('.cart-popup') : $('.cart-popup')).first();
    //     if (!$popup.length) return;
    //
    //     let total = 0;
    //     $popup.find('.cart-item').each(function () {
    //         const $qty = $(this).find('.cart-item__counter .value');
    //         const n = parseInt(String($qty.val()).replace(/[^\d]/g, ''), 10);
    //         total += isNaN(n) || n < 1 ? 1 : n;
    //     });
    //
    //     const rows = $popup.find('.cart-item').length;
    //     $popup.toggleClass('empty', rows === 0);
    //
    //     $popup.find('.cart-head .amount').text(total);
    //     $('.header-cart__btn .amount').text(total);
    // }

    // $(function () {
    //     updateCartUI($('.cart-popup'));
    // });

    // open/close cart

    // $(document).on('click', '.header-cart__btn', function () {
    //     $('html').addClass('cart-open');
    //     $('.cart-overlay, .cart-popup').addClass('active');
    //     // updateCartUI($('.cart-popup'));
    // });

    $(document).on('click', '.cart-close, .cart-overlay', function () {
        $('html').removeClass('cart-open');
        $('.cart-overlay, .cart-popup').removeClass('active');
    });

    // delete item

    $(document).on('click', '.cart-item__del', function (e) {
        e.preventDefault();
        const $popup = getCartPopup($(this));
        $(this).closest('.cart-item').remove();
        // updateCartUI($popup);
    });

    // qty controls

    // $(document).on('click', '.cart-item__counter .plus', function () {
    //     const $input = $(this).siblings('input.value');
    //     $input.val(readQty($input) + 1).trigger('input');
    // });
    //
    // $(document).on('click', '.cart-item__counter .minus', function () {
    //     const $input = $(this).siblings('input.value');
    //     const next = Math.max(1, readQty($input) - 1);
    //     $input.val(next).trigger('input');
    // });

    $(document).on('input change', '.cart-item__counter input.value', function () {
        const clean = readQty($(this));
        $(this).val(clean);
        // updateCartUI($(this));
    });

    // promocode

    $(document).on('click', '.cart-promo__plus', function () {
        $(this).toggleClass('active');
        $('.cart-promo__body').slideToggle();
    });

    $('.promo-wrapper').each(function () {
        var promo_wrapper = $(this),
            promo_input = promo_wrapper.find('.input'),
            promo_btn = promo_wrapper.find('.js_apply_promocode'),
            promo_error = promo_wrapper.find('.promo-error');

        promo_error.hide();

        function checkInput() {
            const val = promo_input.val().trim();
            if (val.length > 0) {
                promo_btn.prop('disabled', false);
            } else {
                promo_btn.prop('disabled', true);
            }
        }
        checkInput();

        promo_input.on('input', function() {
            checkInput();
            promo_error.fadeOut();
            $(this).removeClass('error');
        });

        promo_input.on('input', function() {
            promo_error.fadeOut();
            $(this).removeClass('error');
            promo_btn.prop('disabled', false).html(promo_btn.attr('data-default'));
        });
    });

    // page "jumps" fix

    (function () {
        const setSBW = () => {
            const sbw = window.innerWidth - document.documentElement.clientWidth;
            document.documentElement.style.setProperty('--sbw', sbw + 'px');
        };
        setSBW();
        document.documentElement.classList.add('no-gap');
        window.addEventListener('resize', setSBW, {passive: true});
    })();

    // stars rating

    (function ($) {
        function paint($wrap, val) {
            $wrap.find('.star').each(function () {
                const v = +$(this).data('value');
                $(this).toggleClass('is-on', v <= val);
            });
        }

        function setValue($wrap, val) {
            const target = $wrap.data('target');
            $(target).val(val);
            paint($wrap, val);
        }

        $('.rating').each(function () {
            const $wrap = $(this);
            const target = $wrap.data('target');
            const current = parseInt($(target).val(), 10) || 0;
            paint($wrap, current);
        });

        $(document).on('click', '.rating .star', function () {
            const $main_wrap = $(this).closest('.rating-wrapper');
            const $wrap = $(this).closest('.rating');
            const val = +$(this).data('value');
            setValue($wrap, val);
            $main_wrap.removeClass('err').find('label.error, .validate-error').remove();
        });

        $(document).on('mouseenter', '.rating .star', function () {
            const $wrap = $(this).closest('.rating');
            paint($wrap, +$(this).data('value'));
        }).on('mouseleave', '.rating', function () {
            const target = $(this).data('target');
            const current = parseInt($(target).val(), 10) || 0;
            paint($(this), current);
        });
    })(jQuery);

    // legal age popup

    setTimeout(function () {
        if (localStorage.getItem('popup_time') === null || parseInt(localStorage.getItem('popup_time')) < Math.floor(Date.now())) {
            if (!localStorage.getItem('accepted')) {
                /*$.magnificPopup.open(
                    {
                        items: {
                            src: '#legal-age-popup'
                        },
                        type: 'inline',
                        closeOnBgClick: false,
                        enableEscapeKey: false
                    }
                );*/
            }
            $('.legal-age__popup-confirm').on('click', function (e) {
                //localStorage.setItem('popup_time', Math.floor(Date.now()) + 604800000); // 1 week
                $(this).closest('.popup').find('.mfp-close').trigger('click');
                e.preventDefault();
            });
            $('.legal-age__popup-exit').on('click', function (e) {
                window.location.href = 'http://www.google.com';
                e.preventDefault();
            });
        }
    }, 500);


    // mobile menu

    $('.mobile-btn').on('click', function () {
        $(this).toggleClass('active');
        $('.mobile-menu').toggleClass('active');
        $('html').toggleClass('open-mobile-menu');
        $('body').toggleClass('open-mobile-menu').removeClass('search-shown open-search-results');
        $('.header-search__btn').removeClass('active');
        $('.header-search__wrapper').removeClass('focus');

        // reset search state
        $('.header-search__wrapper').each(function () {
            const $wrap = $(this);
            const $form = $wrap.find('.header-search__form');
            const $input = $form.find('input[type="search"]');
            const $results = $form.find('.search-results');
            const $list = $results.children('ul');
            const $items = $list.children('li');
            const $noTxt = $results.find('.nothing-found p span');

            $input.val('');
            $noTxt.text('');
            $items.show();
            $list.unmark();
            $wrap.removeClass('is-open is-closing show-results has-text')
                 .removeAttr('aria-hidden');
            $('body').removeClass('open-search-bar open-search-results show-mobile-overlay');
            $results.removeClass('highlighting-results');
            $results.find('.view-all').hide();
            $results.find('.nothing-found').hide();
        });
    });

    $('.mobile-menu__bottom span.btn').on('click', function () {
        $('.mobile-btn').removeClass('active');
        $('.mobile-menu').removeClass('active');
        $('html').removeClass('open-mobile-menu');
        $('body').removeClass('open-mobile-menu').removeClass('search-shown open-search-results');
        $('.header-search__btn').removeClass('active');
        $('.header-search__wrapper').removeClass('focus');
    });

    const $menuList = $('.mobile-menu__list');

    $menuList.on('click', '.menu-item-has-children > span > i', function () {
        const $submenu = $(this).parent().next('div[class*="mobile-menu__lvl"]');
        if ($submenu.length > 0) {
            const level = $submenu.attr('class').match(/lvl(\d+)/)[1];
            $submenu.addClass('show');
            $('body').addClass('mm-lvl' + level);
        }
    });

    $menuList.on('click', '.menu-back > i', function () {
        const $submenu = $(this).closest('div[class*="mobile-menu__lvl"]');
        if ($submenu.length > 0) {
            const level = $submenu.attr('class').match(/lvl(\d+)/)[1];
            $submenu.removeClass('show');
            $('body').removeClass('mm-lvl' + level);
        }
    });

    $('.validate-form').each(function () {
        var validate_form = $(this);
        validate_form.validate({
            rules: {
                email: {
                    email: true
                }
            },
            highlight: function (element) {
                $(element).parent().addClass('err');
            },
            unhighlight: function (element) {
                $(element).parent().removeClass('err');
            }
        });
    });

    // terms checkbox in checkout

    function syncTermsChecker() {
        const $terms = $('#terms');
        const $wrap = $terms.closest('.checkbox-wrapper');
        const $checker = $wrap.find('[name="terms_checker"]');
        if ($terms.is(':checked')) {
            $wrap.removeClass('err');
            $wrap.find('label.error').remove();
            $checker.val('terms checkbox checked');
        } else {
            $checker.val('');
        }
    }

    $(document).on('change', '#terms', syncTermsChecker);

    $(syncTermsChecker);

    // select

    $('.select').SumoSelect({
        forceCustomRendering: true
    });

    $('.search-select').on('sumo:opening', function () {
        $(this).closest('.input-wrapper').addClass('open');
    });
    $('.search-select').on('sumo:closing', function () {
        $(this).closest('.input-wrapper').removeClass('open');
    });

    $('.select, .search-select').change(function () {
        var val = $(this).val();
        if (!$(this).val() == '') {
            $(this).closest('.input-wrapper').removeClass('error err').addClass('active').find('.input').val(val);
            $(this).closest('.input-wrapper').find('label.error').remove();
            $(this).closest('.input-wrapper').find('.validate-error').remove();
        }
    });

    $(document).on('change', '.select-cripto', function () {
        if ($(this).val() === 'BTC') {
            $(this).closest('.checkout-payment__item').find('.payment-method__text').slideDown();
        } else {
            $(this).closest('.checkout-payment__item').find('.payment-method__text').slideUp();
        }
    });

    $('.checkout-payment__item input[type="radio"]').on('click', function () {
        const $item = $(this).closest('.checkout-payment__item');
        if ($item.hasClass('checked')) {
            return;
        }
        $('.checkout-payment__item').removeClass('checked');
        $('.checkout-payment__inner').slideUp();
        $item.addClass('checked');
        $item.find('.checkout-payment__inner').slideDown();
    });

    $('.checkout-promo .checkout-promo__head').on('click', function () {
        $('.checkout-promo__body').slideDown(400, function () {
            $('.checkout-promo__body .input').focus();
        });
        $('.checkout-promo .checkout-promo__head').slideUp();
    });

    $('.checkout-promo .checkout-promo__cancel').on('click', function () {
        $('.checkout-promo__body').slideUp();
        $('.checkout-promo .checkout-promo__head').slideDown();
    });

    /*var $coupon_input = $('.checkout-promo__body .input');
    var $coupon_button = $('.checkout-promo__body .btn');
    $coupon_input.on('input', function () {
        $coupon_button.toggleClass('disable', $(this).val().trim() === '');
    }).trigger('input');*/

    // street dropdown

    $('.dropdown-wrapper').each(function() {
        const $wrapper = $(this);
        const $input = $wrapper.find('.input');
        const $dropdown = $wrapper.find('.dropdown-list');
        const $placeholder = $dropdown.find('.placeholder');
        const $list = $dropdown.find('ul');
        const $items = $list.find('li');

        $input.on('focus', function() {
            $dropdown.addClass('is-active');
            toggleView($(this).val());
        });

        $input.on('input', function() {
            const val = $(this).val().toLowerCase().trim();
            toggleView(val);

            let matchCount = 0;
            $items.each(function() {
                const text = $(this).text().toLowerCase();
                if (text.includes(val) && val !== '') {
                    $(this).show();
                    matchCount++;
                } else {
                    $(this).hide();
                }
            });

            if (matchCount === 0 && val !== '') {
                $list.hide();
            } else if (val !== '') {
                $list.show();
            }
        });

        $items.on('click', function() {
            $input.val($(this).text());
            $dropdown.removeClass('is-active');
            $input.valid && $input.valid();
        });

        function toggleView(val) {
            if (val.length === 0) {
                $placeholder.show();
                $list.hide();
            } else {
                $placeholder.hide();
                $list.show();
            }
        }

        $(document).on('click', function(e) {
            if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                $dropdown.removeClass('is-active');
            }
        });
    });

    // product counter

    $('.product-counter input').each(function () {
        var input = $(this);
        var val = parseInt(input.val());
        var minus = $(this).parent().find('.minus');
        if (val > 1) {
            minus.removeClass('disabled');
        } else {
            minus.addClass('disabled');
        }
    });

    $(document).on('click', '.plus', function () {
        var input = $(this).parent().find('input');
        var minus = $(this).parent().find('.minus');
        var val = parseInt(input.val());
        input.val(parseInt(input.val()) + 1).change();
        if (val > 0) {
            minus.removeClass('disabled');
        } else {
            minus.addClass('disabled');
        }
    });

    $(document).on('click', '.minus', function () {
        var input = $(this).parent().find('input');
        var val = parseInt(input.val());
        if (val > 1) {
            val--;
        }
        if (val > 1) {
            $(this).removeClass('disabled');
        } else {
            $(this).addClass('disabled');
        }
        input.val(val).change();
    });

    // copy
    $('.cryptocheckout-left__inputs-item i').css('cursor', 'pointer').on('click', function() {
        const $parent = $(this).parent();
        const $input = $parent.find('input');
        const $okMessage = $parent.find('span');
        if (!$input.length || !$okMessage.length) return;
        const textToCopy = $input.val();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showOkMessage($okMessage);
            }).catch(function(err) {
                copyFallback($input, $okMessage);
            });
        } else {
            copyFallback($input, $okMessage);
        }
    });

    function copyFallback($input, $okMessage) {
        $input.focus();
        $input.select();
        $input[0].setSelectionRange(0, 99999);

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showOkMessage($okMessage);
            }
        } catch (err) {
            console.error('Error:', err);
        }
        window.getSelection().removeAllRanges();
    }

    function showOkMessage($element) {
        $element.css({
            'opacity': '1',
            'pointer-events': 'auto'
        });
        setTimeout(function() {
            $element.css({
                'opacity': '0',
                'pointer-events': 'none'
            });
        }, 2000);
    }

    // variation hover
    var $productVariationItems = $('.product-variation__item');

    if ($productVariationItems.length > 0) {
        $productVariationItems.each(function () {
            var $item = $(this);
            var $tooltip = $item.find('.product-variation__item-tooltip');

            $item.on('mouseenter', function (e) {
                $tooltip.css({
                    opacity: '1',
                    scale: '1'
                });
                $(document).on('mousemove', followCursor);
            });

            $item.on('mouseleave', function (e) {
                $tooltip.css({
                    opacity: '0',
                    scale: '0.8'
                });
                $(document).off('mousemove', followCursor);
            });

            function followCursor(event) {
                $tooltip.css({
                    left: event.clientX + 18 + 'px',
                    top: event.clientY + 18 + 'px'
                });
            }
        });
    }

    // magnific popup
    $.extend(true, $.magnificPopup.defaults, {
        fixedContentPos: true,
        fixedBgPos: true
    });
});

performance.mark("larchik initialization");
require('./custom.js');
performance.measure("larchik initialization");
