import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import {
    Clock3,
    ListFilter,
    Activity,
    ArrowLeft,
    ArrowRight,
    BriefcaseBusiness,
    Building2,
    Cable,
    Check,
    Contact,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    CircleHelp,
    CircleMinus,
    ClipboardList,
    CreditCard,
    Eye,
    FileText,
    Headphones,
    House,
    Images,
    Image,
    Info,
    KeyRound,
    LayoutDashboard,
    LockKeyhole,
    LogIn,
    LogOut,
    Mail,
    Map,
    MapPin,
    MapPinHouse,
    MapPinned,
    Menu,
    Moon,
    Pencil,
    Phone,
    Plus,
    RadioTower,
    Router,
    Save,
    Search,
    SearchCheck,
    Send,
    Settings,
    Settings2,
    Shield,
    ShieldCheck,
    ShieldUser,
    Sun,
    Ticket,
    TicketPlus,
    Trash2,
    TriangleAlert,
    User,
    UserCheck,
    UserCog,
    UserPlus,
    Users,
    UsersRound,
    UserX,
    Wifi,
    WifiOff,
    Wrench,
    X,
    createIcons,
    History,
} from 'lucide';

const icons = {
    Clock3,
    ListFilter,
    Activity,
    ArrowLeft,
    ArrowRight,
    BriefcaseBusiness,
    Building2,
    Cable,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    CircleHelp,
    CircleMinus,
    ClipboardList,
    Contact,
    CreditCard,
    Eye,
    FileText,
    Headphones,
    House,
    Image,
    Images,
    Info,
    KeyRound,
    LayoutDashboard,
    LockKeyhole,
    LogIn,
    LogOut,
    Mail,
    Map,
    MapPin,
    MapPinHouse,
    MapPinned,
    Menu,
    Moon,
    Pencil,
    Phone,
    Plus,
    RadioTower,
    Router,
    Save,
    Search,
    SearchCheck,
    Send,
    Settings,
    Settings2,
    Shield,
    ShieldCheck,
    ShieldUser,
    Sun,
    Ticket,
    TicketPlus,
    Trash2,
    TriangleAlert,
    User,
    UserCheck,
    UserCog,
    UserPlus,
    Users,
    UsersRound,
    UserX,
    Wifi,
    WifiOff,
    Wrench,
    X,
    History,
};

document.addEventListener('DOMContentLoaded', () => {
    initIcons();
    initAdminSidebar();
    initHeroContentFields();
    initTheme();
    initPageLoading();
    initPublicMobileDrawer();
    initDesktopDropdowns();
    initHeroDeleteModal();
    initUserAccountModals();
    initUserManagementFilters();
    initHeroCarousel();
    initCoverageMap();
});

// Icons
function initIcons() {
    createIcons({ icons });
}

// Admin sidebar
function initAdminSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openButton = document.getElementById('sidebar-open');
    const closeButton = document.getElementById('sidebar-close');

    if (!sidebar || !overlay || !openButton || !closeButton) {
        return;
    }

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openButton.setAttribute('aria-expanded', 'true');
    };

    const closeSidebar = () => {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        openButton.setAttribute('aria-expanded', 'false');
    };

    openButton.addEventListener('click', openSidebar);
    closeButton.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
}

// Hero slide form fields
function initHeroContentFields() {
    const heroContentType = document.querySelector('[data-hero-content-type]');

    if (!heroContentType) {
        return;
    }

    const textFields = document.querySelectorAll('[data-hero-text-field]');
    const ctaFields = document.querySelector('[data-hero-cta-fields]');
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const ctaTextInput = document.getElementById('cta_text');
    const ctaUrlInput = document.getElementById('cta_url');

    const updateHeroFields = () => {
        const type = heroContentType.value;
        const showText = type === 'image_text' || type === 'image_text_cta';
        const showCta = type === 'image_text_cta';

        textFields.forEach((field) => {
            field.classList.toggle('hidden', !showText);
        });

        if (ctaFields) {
            ctaFields.classList.toggle('hidden', !showCta);
        }

        if (titleInput) {
            titleInput.required = showText;
        }

        if (descriptionInput) {
            descriptionInput.required = showText;
        }

        if (ctaTextInput) {
            ctaTextInput.required = showCta;
        }

        if (ctaUrlInput) {
            ctaUrlInput.required = showCta;
        }
    };

    updateHeroFields();
    heroContentType.addEventListener('change', updateHeroFields);
}

// Light and dark theme
function initTheme() {
    const themeToggles = document.querySelectorAll('[data-theme-toggle]');
    const themeSunIcons = document.querySelectorAll('[data-theme-sun-icon]');
    const themeMoonIcons = document.querySelectorAll('[data-theme-moon-icon]');

    const updateThemeToggles = () => {
        const isDark = document.documentElement.classList.contains('dark');

        themeSunIcons.forEach((icon) => {
            icon.classList.toggle('hidden', !isDark);
        });

        themeMoonIcons.forEach((icon) => {
            icon.classList.toggle('hidden', isDark);
        });

        themeToggles.forEach((button) => {
            const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
        });
    };

    updateThemeToggles();

    themeToggles.forEach((button) => {
        button.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('rincomm-theme', isDark ? 'dark' : 'light');
            updateThemeToggles();
        });
    });
}

// Page loading and duplicate-submit protection
function initPageLoading() {
    const pageLoader = document.getElementById('page-loader');
    const pageLoaderBar = document.getElementById('page-loader-bar');
    let loaderTimeout = null;

    const showPageLoader = () => {
        if (!pageLoader || !pageLoaderBar) {
            return;
        }

        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
        }

        pageLoader.classList.remove('hidden');
        pageLoaderBar.style.width = '12%';

        requestAnimationFrame(() => {
            pageLoaderBar.style.width = '70%';
        });
    };

    const hidePageLoader = () => {
        if (!pageLoader || !pageLoaderBar) {
            return;
        }

        pageLoaderBar.style.width = '100%';

        loaderTimeout = setTimeout(() => {
            pageLoader.classList.add('hidden');
            pageLoaderBar.style.width = '0%';
        }, 200);
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link) {
            return;
        }

        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        if (link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const href = link.getAttribute('href');

        if (!href || href === '#' || href.startsWith('javascript:')) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        if (
            url.pathname === window.location.pathname &&
            url.search === window.location.search &&
            url.hash
        ) {
            return;
        }

        showPageLoader();
    });

    document.querySelectorAll('form[data-lock-submit]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            const submitButton = form.querySelector(
                'button[type="submit"], input[type="submit"]'
            );

            if (submitButton) {
                submitButton.setAttribute('aria-disabled', 'true');
                submitButton.classList.add(
                    'pointer-events-none',
                    'cursor-not-allowed',
                    'opacity-60'
                );

                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.dataset.originalContent = submitButton.innerHTML;

                    const loadingText =
                        submitButton.dataset.loadingText || 'Processing...';

                    submitButton.innerHTML = `
                        <span class="inline-flex items-center justify-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                                aria-hidden="true"
                            ></span>
                            <span>${loadingText}</span>
                        </span>
                    `;
                }

                requestAnimationFrame(() => {
                    submitButton.disabled = true;
                });
            }

            showPageLoader();
        });
    });

    window.addEventListener('pageshow', () => {
        hidePageLoader();

        document.querySelectorAll('form[data-lock-submit]').forEach((form) => {
            form.dataset.submitting = 'false';

            const submitButton = form.querySelector(
                'button[type="submit"], input[type="submit"]'
            );

            if (!submitButton) {
                return;
            }

            submitButton.disabled = false;
            submitButton.removeAttribute('aria-disabled');
            submitButton.classList.remove(
                'pointer-events-none',
                'cursor-not-allowed',
                'opacity-60'
            );

            if (
                submitButton instanceof HTMLButtonElement &&
                submitButton.dataset.originalContent
            ) {
                submitButton.innerHTML = submitButton.dataset.originalContent;
            }
        });
    });
}

// Public mobile navigation
function initPublicMobileDrawer() {
    const openButton = document.getElementById('public-drawer-open');
    const closeButton = document.getElementById('public-drawer-close');
    const drawer = document.getElementById('public-mobile-drawer');
    const overlay = document.getElementById('public-drawer-overlay');

    if (!openButton || !closeButton || !drawer || !overlay) {
        return;
    }

    const breakpoint = 1024;

    const closeDrawer = () => {
        drawer.classList.remove('translate-x-0', 'pointer-events-auto');
        drawer.classList.add('translate-x-full', 'pointer-events-none');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        openButton.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
    };

    const openDrawer = () => {
        if (window.innerWidth >= breakpoint) {
            closeDrawer();
            return;
        }

        drawer.classList.remove('translate-x-full', 'pointer-events-none');
        drawer.classList.add('translate-x-0', 'pointer-events-auto');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openButton.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
    };

    const dropdownButtons = document.querySelectorAll('[data-mobile-dropdown]');

    dropdownButtons.forEach((button) => {
        button.setAttribute('aria-expanded', 'false');

        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.mobileDropdown);

            if (!target) {
                return;
            }

            const isOpen = button.getAttribute('aria-expanded') === 'true';

            dropdownButtons.forEach((otherButton) => {
                if (otherButton === button) {
                    return;
                }

                const otherTarget = document.getElementById(
                    otherButton.dataset.mobileDropdown
                );

                if (otherTarget) {
                    otherTarget.classList.add('hidden');
                }

                otherButton.setAttribute('aria-expanded', 'false');
                otherButton.querySelector('svg')?.classList.remove('rotate-180');
            });

            target.classList.toggle('hidden', isOpen);
            button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            button.querySelector('svg')?.classList.toggle('rotate-180', !isOpen);
        });
    });

    closeDrawer();

    openButton.addEventListener('click', openDrawer);
    closeButton.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    document.querySelectorAll('.public-drawer-link').forEach((link) => {
        link.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= breakpoint) {
            closeDrawer();
        }
    });
}

// Public desktop dropdowns
function initDesktopDropdowns() {
    const buttons = document.querySelectorAll('[data-desktop-dropdown]');

    if (buttons.length === 0) {
        return;
    }

    const closeDropdowns = (exceptButton = null) => {
        buttons.forEach((button) => {
            if (button === exceptButton) {
                return;
            }

            const target = document.getElementById(button.dataset.desktopDropdown);

            if (!target) {
                return;
            }

            target.classList.add('invisible', 'opacity-0', 'translate-y-1');
            target.classList.remove('visible', 'opacity-100', 'translate-y-0');
            button.setAttribute('aria-expanded', 'false');
        });
    };

    buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
            if (window.innerWidth < 1024) {
                return;
            }

            event.stopPropagation();

            const target = document.getElementById(button.dataset.desktopDropdown);

            if (!target) {
                return;
            }

            const isOpen = button.getAttribute('aria-expanded') === 'true';
            closeDropdowns(button);

            target.classList.toggle('invisible', isOpen);
            target.classList.toggle('opacity-0', isOpen);
            target.classList.toggle('translate-y-1', isOpen);
            target.classList.toggle('visible', !isOpen);
            target.classList.toggle('opacity-100', !isOpen);
            target.classList.toggle('translate-y-0', !isOpen);
            button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
    });

    document.addEventListener('click', () => closeDropdowns());

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDropdowns();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth < 1024) {
            closeDropdowns();
        }
    });
}

// Hero slide delete confirmation
function initHeroDeleteModal() {
    const modal = document.getElementById('hero-slide-delete-modal');
    const overlay = document.getElementById('hero-slide-delete-overlay');
    const cancelButton = document.getElementById('hero-slide-delete-cancel');
    const form = document.getElementById('hero-slide-delete-form');
    const title = document.getElementById('hero-slide-delete-title');
    const deleteButtons = document.querySelectorAll('[data-delete-slide]');

    if (!modal || !form || deleteButtons.length === 0) {
        return;
    }

    const closeModal = () => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openModal = (button) => {
        form.action = button.dataset.deleteUrl;

        if (title) {
            title.textContent = button.dataset.deleteTitle || 'this hero slide';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    deleteButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    cancelButton?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

// User account activation and deactivation confirmations
function initUserAccountModals() {
    const deactivate = createUserAccountModal('deactivate');
    const activate = createUserAccountModal('activate');

    document.addEventListener('click', (event) => {
        const deactivateButton = event.target.closest('[data-user-deactivate]');

        if (deactivateButton && deactivate) {
            deactivate.open(deactivateButton);
            return;
        }

        const activateButton = event.target.closest('[data-user-activate]');

        if (activateButton && activate) {
            activate.open(activateButton);
        }
    });
}

function createUserAccountModal(action) {
    const modal = document.getElementById(`user-${action}-modal`);
    const overlay = document.getElementById(`user-${action}-overlay`);
    const cancelButton = document.getElementById(`user-${action}-cancel`);
    const form = document.getElementById(`user-${action}-form`);
    const name = document.getElementById(`user-${action}-name`);

    if (!modal || !form) {
        return null;
    }

    const close = () => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const open = (button) => {
        form.action = button.dataset.userUrl;

        if (name) {
            name.textContent = button.dataset.userName || 'this user';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        cancelButton?.focus();
    };

    cancelButton?.addEventListener('click', close);
    overlay?.addEventListener('click', close);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });

    return { open, close };
}

// User management search, filters, and pagination
function initUserManagementFilters() {
    const form = document.querySelector('[data-user-filters]');
    const results = document.querySelector('[data-user-results]');

    if (!form || !results) {
        return;
    }

    const searchInput = form.querySelector('[data-user-search]');
    const filterSelects = form.querySelectorAll('[data-user-filter]');
    let searchTimer = null;
    let requestController = null;

    const buildFilterUrl = () => {
        const url = new URL(form.action, window.location.origin);
        const formData = new FormData(form);

        formData.forEach((value, key) => {
            const cleanValue = String(value).trim();

            if (cleanValue !== '') {
                url.searchParams.set(key, cleanValue);
            }
        });

        return url;
    };

    const loadResults = async (url, historyMode = 'replace') => {
        requestController?.abort();
        requestController = new AbortController();
        results.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error('Unable to load user results.');
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextResults = nextDocument.querySelector('[data-user-results]');

            if (!nextResults) {
                window.location.href = url.toString();
                return;
            }

            results.innerHTML = nextResults.innerHTML;
            createIcons({ icons });

            if (historyMode === 'push') {
                window.history.pushState({}, '', url);
            } else if (historyMode === 'replace') {
                window.history.replaceState({}, '', url);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            window.location.href = url.toString();
        } finally {
            results.setAttribute('aria-busy', 'false');
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            searchTimer = setTimeout(() => {
                loadResults(buildFilterUrl());
            }, 400);
        });
    }

    filterSelects.forEach((select) => {
        select.addEventListener('change', () => {
            if (searchTimer) {
                clearTimeout(searchTimer);
                searchTimer = null;
            }

            loadResults(buildFilterUrl());
        });
    });

    results.addEventListener('click', (event) => {
        const paginationLink = event.target.closest('a[href]');

        if (!paginationLink || !results.contains(paginationLink)) {
            return;
        }

        const url = new URL(paginationLink.href);

        if (!url.searchParams.has('page')) {
            return;
        }

        event.preventDefault();
        loadResults(url, 'push');
    });
}

// Public hero carousel
function initHeroCarousel() {
    const carousel = document.querySelector('[data-hero-carousel]');

    if (!carousel) {
        return;
    }

    const slides = Array.from(carousel.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-hero-dot]'));
    const previousButton = carousel.querySelector('[data-hero-previous]');
    const nextButton = carousel.querySelector('[data-hero-next]');

    if (slides.length <= 1) {
        return;
    }

    const autoplayDelay = 7000;
    let currentIndex = 0;
    let autoplayTimer = null;

    const showSlide = (index) => {
        if (index < 0) {
            index = slides.length - 1;
        } else if (index >= slides.length) {
            index = 0;
        }

        currentIndex = index;

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === currentIndex;

            slide.classList.toggle('opacity-100', isActive);
            slide.classList.toggle('z-10', isActive);
            slide.classList.toggle('pointer-events-none', !isActive);
            slide.classList.toggle('opacity-0', !isActive);
            slide.classList.toggle('z-0', !isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        dots.forEach((dot, dotIndex) => {
            const isActive = dotIndex === currentIndex;

            dot.classList.toggle('w-7', isActive);
            dot.classList.toggle('bg-white', isActive);
            dot.classList.toggle('w-2.5', !isActive);
            dot.classList.toggle('bg-white/50', !isActive);
            dot.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    };

    const stopAutoplay = () => {
        if (!autoplayTimer) {
            return;
        }

        clearInterval(autoplayTimer);
        autoplayTimer = null;
    };

    const startAutoplay = () => {
        stopAutoplay();
        autoplayTimer = setInterval(() => showSlide(currentIndex + 1), autoplayDelay);
    };

    const restartAutoplay = () => {
        stopAutoplay();
        startAutoplay();
    };

    nextButton?.addEventListener('click', () => {
        showSlide(currentIndex + 1);
        restartAutoplay();
    });

    previousButton?.addEventListener('click', () => {
        showSlide(currentIndex - 1);
        restartAutoplay();
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const index = Number(dot.dataset.heroDot);

            if (Number.isNaN(index)) {
                return;
            }

            showSlide(index);
            restartAutoplay();
        });
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoplay();
        } else {
            startAutoplay();
        }
    });

    showSlide(0);
    startAutoplay();
}

// Public service coverage map
function initCoverageMap() {
    const mapElement = document.getElementById('coverage-map');
    const mapDataElement = document.getElementById('coverage-map-data');

    if (!mapElement || !mapDataElement) {
        return;
    }

    let coverageAreas = [];

    try {
        coverageAreas = JSON.parse(mapDataElement.textContent || '[]');
    } catch (error) {
        console.error('Unable to read coverage map data.', error);
    }

    const defaultCenter = [15.6689, 120.5803];
    const defaultZoom = 13;

    const map = L.map(mapElement, {
        zoomControl: true,
        scrollWheelZoom: false,
    }).setView(defaultCenter, defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const plottedPoints = [];

    coverageAreas.forEach((area) => {
        const latitude = Number(area.latitude);
        const longitude = Number(area.longitude);

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return;
        }

        const isServiceable = Boolean(area.is_serviceable);
        const marker = L.circleMarker([latitude, longitude], {
            radius: 8,
            weight: 3,
            color: isServiceable ? '#047857' : '#525252',
            fillColor: isServiceable ? '#10b981' : '#a3a3a3',
            fillOpacity: 0.9,
        });

        const location = [
            area.barangay,
            area.city_municipality,
            area.province,
        ]
            .filter(Boolean)
            .join(', ');

        const statusText = isServiceable ? 'Serviceable' : 'Not Serviceable';

        marker.bindPopup(`
            <div style="min-width: 180px">
                <strong>${escapeMapText(location)}</strong><br>
                <span>${escapeMapText(statusText)}</span>
            </div>
        `);

        marker.addTo(map);
        plottedPoints.push([latitude, longitude]);
    });

    if (plottedPoints.length === 1) {
        map.setView(plottedPoints[0], 15);
    } else if (plottedPoints.length > 1) {
        map.fitBounds(plottedPoints, {
            padding: [30, 30],
            maxZoom: 15,
        });
    }

    window.setTimeout(() => {
        map.invalidateSize();
    }, 100);
}

function escapeMapText(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
