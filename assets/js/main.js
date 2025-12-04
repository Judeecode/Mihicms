/**
 * Main JavaScript for MiHi Entertainment Website
 * Handles smooth scrolling, animations, lazy loading, and interactions
 */

(function() {
    'use strict';

    // Define global functions IMMEDIATELY (before DOM ready) so they're available for onclick handlers
    // Brochure Modal Functions (global for onclick handlers)
    window.openBrochureModal = function() {
        console.log('%c=== CMS: openBrochureModal called ===', 'color: blue; font-size: 16px; font-weight: bold;');
        console.log('CMS: window.brochurePDFPath:', window.brochurePDFPath);
        console.log('CMS: typeof window.brochurePDFPath:', typeof window.brochurePDFPath);
        const modal = document.getElementById('brochureModal');
        const iframe = document.getElementById('brochureIframe');
        const loader = document.getElementById('brochureLoader');

        if (!modal || !iframe || !loader) {
            console.error('CMS: Missing elements - modal:', !!modal, 'iframe:', !!iframe, 'loader:', !!loader);
            return;
        }

        console.log('CMS: Opening brochure modal');
        modal.classList.remove('hidden');
        loader.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Load brochure PDF from CMS if available, otherwise use default
        loadBrochurePDF(iframe, loader).catch(function(error) {
            console.error('CMS: Error in loadBrochurePDF:', error);
        });
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initSmoothScrolling();
        initLazyLoading();
        initScrollAnimations();
        initKeyboardNavigation();
        initFormValidation();
        initScrollPerformance();
        initHorizontalScrolling();
    }

    /**
     * Smooth scrolling for anchor links
     */
    function initSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#contact') {
                    // Allow default behavior for contact links
                    return;
                }
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Lazy load images with Intersection Observer
     */
    function initLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        if (!images.length) return;

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Scroll-triggered animations using Intersection Observer
     */
    function initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all sections
        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });
    }

    /**
     * Keyboard navigation improvements
     */
    function initKeyboardNavigation() {
        document.addEventListener('keydown', function (e) {
            // Press 'Escape' to close modals or return to top
            if (e.key === 'Escape') {
                const modal = document.getElementById('brochureModal');
                if (modal && !modal.classList.contains('hidden')) {
                    if (typeof closeBrochureModal === 'function') {
                        closeBrochureModal();
                    }
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        });
    }

    /**
     * Form validation with visual feedback
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form');
        if (!forms.length) return;

        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                const inputs = form.querySelectorAll('input[required], textarea[required]');
                let isValid = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('border-red-500', 'shake');
                        setTimeout(() => input.classList.remove('shake'), 500);
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Performance: Optimize scroll events with requestAnimationFrame
     */
    function initScrollPerformance() {
        let scrollTimeout;
        window.addEventListener('scroll', function () {
            if (scrollTimeout) {
                window.cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = window.requestAnimationFrame(function () {
                // Scroll-based animations here
            });
        }, { passive: true });
    }

    /**
     * Horizontal scrolling for service cards
     */
    function initHorizontalScrolling() {
        const scrollContainer = document.querySelector('.scroll-container');
        if (!scrollContainer) return;

        // Horizontal mouse wheel scrolling
        scrollContainer.addEventListener('wheel', function(e) {
            if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                e.preventDefault();
                scrollContainer.scrollLeft += e.deltaY;
            }
        }, { passive: false });

        // Touch/drag scrolling
        let isDown = false;
        let startX;
        let scrollLeft;

        scrollContainer.addEventListener('mousedown', (e) => {
            isDown = true;
            scrollContainer.style.cursor = 'grabbing';
            startX = e.pageX - scrollContainer.offsetLeft;
            scrollLeft = scrollContainer.scrollLeft;
        });

        scrollContainer.addEventListener('mouseleave', () => {
            isDown = false;
            scrollContainer.style.cursor = 'grab';
        });

        scrollContainer.addEventListener('mouseup', () => {
            isDown = false;
            scrollContainer.style.cursor = 'grab';
        });

        scrollContainer.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - scrollContainer.offsetLeft;
            const walk = (x - startX) * 2;
            scrollContainer.scrollLeft = scrollLeft - walk;
        });

        scrollContainer.style.cursor = 'grab';

        // Arrow button functionality
        const scrollLeftBtn = document.getElementById('scrollLeftBtn');
        const scrollRightBtn = document.getElementById('scrollRightBtn');

        function updateArrowButtons() {
            if (!scrollContainer) return;
            const { scrollLeft, scrollWidth, clientWidth } = scrollContainer;
            const maxScroll = scrollWidth - clientWidth;

            if (scrollLeftBtn) {
                scrollLeftBtn.disabled = scrollLeft <= 0;
                scrollLeftBtn.setAttribute('aria-disabled', scrollLeft <= 0);
            }

            if (scrollRightBtn) {
                scrollRightBtn.disabled = scrollLeft >= maxScroll - 1;
                scrollRightBtn.setAttribute('aria-disabled', scrollLeft >= maxScroll - 1);
            }
        }

        scrollContainer.addEventListener('scroll', updateArrowButtons);
        updateArrowButtons();

        if (scrollLeftBtn) {
            scrollLeftBtn.addEventListener('click', () => {
                const cardWidth = 320 + 24;
                scrollContainer.scrollBy({
                    left: -cardWidth,
                    behavior: 'smooth'
                });
            });
        }

        if (scrollRightBtn) {
            scrollRightBtn.addEventListener('click', () => {
                const cardWidth = 320 + 24;
                scrollContainer.scrollBy({
                    left: cardWidth,
                    behavior: 'smooth'
                });
            });
        }
    }

    
    // Function to load brochure PDF from CMS using flipbook
    async function loadBrochurePDF(iframe, loader) {
        console.log('CMS: loadBrochurePDF called');
        // Always use test.html flipbook file
        // It will handle loading the PDF from CMS or URL parameter
        const timestamp = new Date().getTime();
        let flipbookUrl = 'flipbook/examples/test.html?t=' + timestamp;
        console.log('CMS: Initial flipbook URL:', flipbookUrl);
        
        let pdfPath = null;
        
        // If PDF path is available, use it
        if (window.brochurePDFPath) {
            pdfPath = window.brochurePDFPath;
            console.log('CMS: Found brochurePDFPath:', pdfPath);
        } else {
            console.log('CMS: No window.brochurePDFPath, fetching from API...');
            // Try to fetch from API and WAIT for it
            try {
                const response = await fetch('api/get_content.php?page=index');
                console.log('CMS: API response status:', response.status);
                if (response.ok) {
                    const content = await response.json();
                    console.log('CMS: API content keys:', Object.keys(content));
                    if (content['brochure-pdf']) {
                        const pdfData = content['brochure-pdf'];
                        console.log('CMS: PDF data from API:', pdfData);
                        pdfPath = pdfData.content;
                        console.log('CMS: Raw PDF path:', pdfPath);
                        
                        // Parse PDF path (format: "PDF:path")
                        if (pdfPath && pdfPath.indexOf(':') !== -1) {
                            const parts = pdfPath.split(':');
                            pdfPath = parts.slice(1).join(':');
                            console.log('CMS: Parsed PDF path:', pdfPath);
                        }
                    } else {
                        console.log('CMS: No brochure-pdf in API response');
                    }
                } else {
                    console.error('CMS: API response not OK');
                }
            } catch (error) {
                console.error('CMS: Error loading brochure PDF:', error);
            }
        }
        
        // If we have a PDF path, add it to the URL
        if (pdfPath && pdfPath.trim() !== '') {
            console.log('CMS: Processing PDF path for URL:', pdfPath);
            // Fix path - remove leading slash if present (will be handled by flipbook)
            pdfPath = pdfPath.replace(/\\/g, '/');
            if (pdfPath.startsWith('/')) {
                pdfPath = pdfPath.substring(1);
            }
            // Encode the PDF path for URL
            const encodedPath = encodeURIComponent(pdfPath);
            flipbookUrl += '&pdf=' + encodedPath;
            console.log('CMS: Loading brochure with PDF:', pdfPath);
            console.log('CMS: Encoded PDF path:', encodedPath);
            console.log('CMS: Full flipbook URL:', flipbookUrl);
        } else {
            console.warn('CMS: No PDF path available! pdfPath value:', pdfPath);
            console.warn('CMS: window.brochurePDFPath:', window.brochurePDFPath);
            console.log('CMS: Using default PDF (no parameter in URL)');
        }
        
        // Load the dynamic flipbook AFTER we have the PDF path (if available)
        console.log('%cCMS: Final flipbook URL being loaded:', 'color: green; font-weight: bold;', flipbookUrl);
        console.log('CMS: Setting iframe.src to:', flipbookUrl);
        iframe.src = flipbookUrl;
        
        // Hide loader when flipbook loads (handled by postMessage from flipbook)
        // Also set timeout fallback
        const loadTimeout = setTimeout(function() {
            console.log('CMS: Flipbook load timeout - hiding loader');
            if (loader) loader.classList.add('hidden');
        }, 5000);
        
        // Listen for flipbook loaded message
        const messageHandler = function(event) {
            console.log('CMS: Received message from iframe:', event.data);
            if (event.data === 'flipbookLoaded') {
                console.log('CMS: Flipbook loaded successfully');
                clearTimeout(loadTimeout);
                if (loader) loader.classList.add('hidden');
                window.removeEventListener('message', messageHandler);
            }
        };
        window.addEventListener('message', messageHandler);
        
        // Fallback error handler
        iframe.onerror = function() {
            console.error('CMS: Error loading flipbook, using default');
            clearTimeout(loadTimeout);
            // Fallback to default
            const timestamp = new Date().getTime();
            iframe.src = 'flipbook/examples/test.html?t=' + timestamp;
            if (loader) loader.classList.add('hidden');
            window.removeEventListener('message', messageHandler);
        };
        
        // Also handle onload for debugging
        iframe.onload = function() {
            console.log('CMS: Iframe loaded (may still be loading content)');
        };
    }

    window.closeBrochureModal = function() {
        const modal = document.getElementById('brochureModal');
        const iframe = document.getElementById('brochureIframe');
        const loader = document.getElementById('brochureLoader');

        if (!modal || !iframe || !loader) return;

        modal.classList.add('hidden');
        loader.classList.add('hidden');
        document.body.style.overflow = '';

        iframe.src = 'about:blank';
    };

    // Listen for flipbook loaded message from iframe
    window.addEventListener('message', function(event) {
        if (event.data === 'flipbookLoaded') {
            const loader = document.getElementById('brochureLoader');
            if (loader) {
                loader.classList.add('hidden');
            }
        }
    });

    // Prevent modal content clicks from closing modal
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('brochureModal');
        if (modal) {
            const modalContent = modal.querySelector('.relative.w-full');
            if (modalContent) {
                modalContent.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
        }
    });
})();
