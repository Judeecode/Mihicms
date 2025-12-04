// CMS Content Loader
// This script loads content from the database and updates elements with data-cms-id attributes

(function() {
    'use strict';

    // Load content from API
    async function loadContent() {
        try {
            const response = await fetch('api/get_content.php?page=index');
            if (!response.ok) {
                console.warn('CMS: Could not load content from API. Using default content.');
                return;
            }
            
            const content = await response.json();
            
            // Handle brochure PDF
            if (content['brochure-pdf']) {
                const pdfData = content['brochure-pdf'];
                console.log('CMS: Brochure PDF data from API:', pdfData);
                let pdfPath = pdfData.content;
                console.log('CMS: Raw brochure PDF path:', pdfPath);
                
                // Parse PDF path (format: "PDF:path")
                if (pdfPath && pdfPath.indexOf(':') !== -1) {
                    const parts = pdfPath.split(':');
                    pdfPath = parts.slice(1).join(':');
                    console.log('CMS: Parsed brochure PDF path (after removing PDF:):', pdfPath);
                }
                
                // Store PDF path globally for openBrochureModal to use
                if (pdfPath) {
                    // Fix path - normalize slashes, remove leading slash for relative paths
                    pdfPath = pdfPath.replace(/\\/g, '/');
                    // Remove leading slash if present (will be handled by flipbook for relative paths)
                    if (pdfPath.startsWith('/') && !pdfPath.startsWith('http')) {
                        pdfPath = pdfPath.substring(1);
                    }
                    window.brochurePDFPath = pdfPath;
                    console.log('CMS: Brochure PDF path stored in window.brochurePDFPath:', pdfPath);
                    console.log('CMS: Verified window.brochurePDFPath:', window.brochurePDFPath);
                } else {
                    console.warn('CMS: Brochure PDF path is empty after parsing!');
                }
            } else {
                console.log('CMS: No brochure-pdf found in content');
            }
            
            // Handle hero background media (video or image)
            const heroVideo = document.getElementById('hero-background-video');
            const heroImage = document.getElementById('hero-background-image');
            
            console.log('CMS: Hero elements found - Video:', !!heroVideo, 'Image:', !!heroImage);
            console.log('CMS: Content keys:', Object.keys(content));
            console.log('CMS: Full content object:', content);
            
            if (content['hero-background-media']) {
                console.log('CMS: Hero background media entry found:', content['hero-background-media']);
                const heroMedia = content['hero-background-media'];
                
                // Parse media type and path from content (format: "IMAGE:path" or "VIDEO:path")
                let mediaType = 'image';
                let mediaPath = heroMedia.content;
                
                if (heroMedia.content && heroMedia.content.indexOf(':') !== -1) {
                    const parts = heroMedia.content.split(':');
                    mediaType = parts[0].toLowerCase();
                    mediaPath = parts.slice(1).join(':'); // Handle paths that might contain colons
                }
                
                console.log('CMS: Hero media found - Type:', mediaType, 'Path:', mediaPath);
                
                if (mediaType === 'video' && heroVideo) {
                    console.log('CMS: Setting video background. Raw path:', mediaPath);
                    
                    // Get container to remove image background styles
                    const container = document.querySelector('.hero-video-container');
                    if (container) {
                        // Remove image background class and styles
                        container.classList.remove('has-image-bg');
                        container.style.removeProperty('background-image');
                        container.style.removeProperty('background-size');
                        container.style.removeProperty('background-position');
                        container.style.removeProperty('background-repeat');
                        container.style.removeProperty('background-attachment');
                    }
                    
                    // Hide image element
                    if (heroImage) {
                        heroImage.style.display = 'none';
                        heroImage.src = '';
                    }
                    
                    // Fix path - handle Windows backslashes
                    let videoUrl = mediaPath.replace(/\\/g, '/');
                    
                    // Normalize path - ensure it's relative from the site root
                    // Paths stored in database are relative like "uploads/hero/file.mp4"
                    // We need to ensure they resolve correctly from index.html at root
                    if (!videoUrl.startsWith('http') && !videoUrl.startsWith('https')) {
                        // Remove leading slash if present (we want relative path)
                        if (videoUrl.startsWith('/')) {
                            videoUrl = videoUrl.substring(1);
                        }
                        // Now we have a clean relative path like "uploads/hero/file.mp4"
                        // This will resolve correctly from index.html at root
                    }
                    
                    // Ensure the path is correct - add leading slash for absolute path from root
                    // Since index.html is at root, we need absolute path from site root
                    if (!videoUrl.startsWith('http') && !videoUrl.startsWith('https')) {
                        // If it doesn't start with /, add it to make it absolute from root
                        if (!videoUrl.startsWith('/')) {
                            // Get the base path (e.g., /MiHi-Entertainment/)
                            const basePath = window.location.pathname.replace(/\/[^/]*$/, '') || '';
                            videoUrl = basePath + '/' + videoUrl;
                        }
                    }
                    
                    console.log('CMS: Final video URL:', videoUrl);
                    console.log('CMS: Current page location:', window.location.pathname);
                    console.log('CMS: Base path:', window.location.pathname.replace(/\/[^/]*$/, '') || '');
                    console.log('CMS: Full URL will be:', window.location.origin + videoUrl);
                    
                    // Pause and reset current video
                    heroVideo.pause();
                    heroVideo.currentTime = 0;
                    
                    // Update video source
                    heroVideo.src = videoUrl;
                    const source = heroVideo.querySelector('source');
                    if (source) {
                        source.src = videoUrl;
                        // Determine MIME type based on extension
                        if (videoUrl.endsWith('.webm')) {
                            source.type = 'video/webm';
                        } else if (videoUrl.endsWith('.ogg') || videoUrl.endsWith('.ogv')) {
                            source.type = 'video/ogg';
                        } else {
                            source.type = 'video/mp4';
                        }
                    }
                    
                    // Show video and make it visible
                    heroVideo.style.display = 'block';
                    heroVideo.style.visibility = 'visible';
                    heroVideo.style.opacity = '1';
                    heroVideo.style.zIndex = '1';
                    
                    // Reload video to apply new source
                    heroVideo.load();
                    
                    console.log('CMS: Video source set, loading...');
                    
                    // Try to play the video after it loads
                    heroVideo.addEventListener('loadeddata', function() {
                        console.log('✓ CMS: Video loaded successfully from:', videoUrl);
                        heroVideo.play().catch(e => {
                            console.warn('CMS: Video autoplay prevented:', e);
                        });
                    }, { once: true });
                    
                    // Handle video load errors
                    heroVideo.addEventListener('error', function() {
                        console.error('✗ CMS: Error loading video from:', videoUrl);
                        console.error('CMS: Video load error details:', heroVideo.error);
                        console.error('CMS: This usually means the file does not exist at the specified path.');
                        console.error('CMS: Please check:');
                        console.error('  1. The file exists in the uploads/hero/ folder');
                        console.error('  2. The path in the database matches the actual filename');
                        console.error('  3. The file permissions are correct');
                        
                        // Try alternative paths
                        let altUrls = [];
                        
                        // Try with leading slash (absolute from root)
                        if (!videoUrl.startsWith('/') && !videoUrl.startsWith('http')) {
                            altUrls.push('/MiHi-Entertainment/' + videoUrl);
                            altUrls.push('/' + videoUrl);
                        }
                        
                        // Try next alternative if available
                        if (altUrls.length > 0) {
                            const altUrl = altUrls[0];
                            console.log('CMS: Trying alternative path:', altUrl);
                            heroVideo.src = altUrl;
                            if (source) {
                                source.src = altUrl;
                            }
                            heroVideo.load();
                            
                            heroVideo.addEventListener('loadeddata', function() {
                                console.log('✓ CMS: Video loaded with alternative path:', altUrl);
                                heroVideo.play().catch(e => {
                                    console.warn('CMS: Video autoplay prevented:', e);
                                });
                            }, { once: true });
                            
                            // If first alternative fails, try second
                            heroVideo.addEventListener('error', function() {
                                if (altUrls.length > 1) {
                                    const altUrl2 = altUrls[1];
                                    console.log('CMS: Trying second alternative path:', altUrl2);
                                    heroVideo.src = altUrl2;
                                    if (source) {
                                        source.src = altUrl2;
                                    }
                                    heroVideo.load();
                                } else {
                                    console.error('✗ CMS: All path attempts failed. File may be missing or corrupted.');
                                    console.error('CMS: To fix: Go to Admin Panel > Hero Section and re-upload the video.');
                                }
                            }, { once: true });
                        } else {
                            console.error('✗ CMS: All path attempts failed. Please check that the file exists:', videoUrl);
                            console.error('CMS: To fix: Go to Admin Panel > Hero Section and re-upload the video.');
                        }
                    }, { once: true });
                } else if (mediaType === 'image') {
                    console.log('CMS: Setting image background. Raw path:', mediaPath);
                    
                    // Hide video first - completely stop and hide it
                    if (heroVideo) {
                        heroVideo.pause();
                        heroVideo.currentTime = 0;
                        heroVideo.style.display = 'none';
                        heroVideo.style.visibility = 'hidden';
                        heroVideo.style.opacity = '0';
                        heroVideo.style.zIndex = '0';
                        heroVideo.removeAttribute('src');
                        const source = heroVideo.querySelector('source');
                        if (source) {
                            source.removeAttribute('src');
                        }
                    }
                    
                    // Hide image element (we'll use CSS background instead)
                    if (heroImage) {
                        heroImage.style.display = 'none';
                    }
                    
                    // Set background image on container using CSS
                    const container = document.querySelector('.hero-video-container');
                    if (container) {
                        // Fix path - handle Windows backslashes
                        let imageUrl = mediaPath.replace(/\\/g, '/');
                        
                        // Determine the correct path based on current location
                        // If we're at root (index.html), path should be relative
                        // If path doesn't start with /, make it relative to current directory
                        if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
                            // Relative path - use as is (browser will resolve from current page location)
                            imageUrl = imageUrl;
                        } else if (imageUrl.startsWith('/')) {
                            // Absolute from root - keep it
                            imageUrl = imageUrl;
                        }
                        
                        console.log('CMS: Final image URL:', imageUrl);
                        
                        // Set the background image with !important to override any existing styles
                        container.style.setProperty('background-image', 'url("' + imageUrl + '")', 'important');
                        container.style.setProperty('background-size', 'cover', 'important');
                        container.style.setProperty('background-position', 'center center', 'important');
                        container.style.setProperty('background-repeat', 'no-repeat', 'important');
                        container.style.setProperty('background-attachment', 'fixed', 'important');
                        container.classList.add('has-image-bg');
                        
                        // Force browser to apply styles
                        void container.offsetHeight;
                        
                        console.log('CMS: Background image styles applied');
                        console.log('CMS: Container background-image:', window.getComputedStyle(container).backgroundImage);
                        console.log('CMS: Container classes:', container.className);
                        
                        // Test if image loads
                        const testImg = new Image();
                        testImg.onload = function() {
                            console.log('✓ CMS: Image loaded successfully from:', imageUrl);
                            // Ensure it's visible
                            container.style.setProperty('background-image', 'url("' + imageUrl + '")', 'important');
                        };
                        testImg.onerror = function() {
                            console.error('✗ CMS: Image failed to load from:', imageUrl);
                            // Try with leading slash
                            const altUrl = '/' + imageUrl;
                            console.log('CMS: Trying alternative path:', altUrl);
                            container.style.setProperty('background-image', 'url("' + altUrl + '")', 'important');
                            const testImg2 = new Image();
                            testImg2.onload = function() {
                                console.log('✓ CMS: Image loaded with alternative path:', altUrl);
                            };
                            testImg2.onerror = function() {
                                console.error('✗ CMS: Alternative path also failed');
                            };
                            testImg2.src = altUrl;
                        };
                        testImg.src = imageUrl;
                    } else {
                        console.error('✗ CMS: Hero container not found!');
                    }
                }
            } else {
                // No CMS media set, show default video
                console.log('CMS: No hero media found, using default video');
                
                // Clear any background image
                const container = document.querySelector('.hero-video-container');
                if (container) {
                    container.style.backgroundImage = '';
                    container.classList.remove('has-image-bg');
                }
                
                if (heroVideo) {
                    // Set default video source
                    heroVideo.src = 'assets/video/index/main-hero.mp4';
                    const source = heroVideo.querySelector('source');
                    if (source) {
                        source.src = 'assets/video/index/main-hero.mp4';
                    }
                    heroVideo.style.display = 'block';
                    heroVideo.style.visibility = 'visible';
                    heroVideo.style.opacity = '1';
                    heroVideo.style.zIndex = '1';
                    heroVideo.load();
                }
                if (heroImage) {
                    heroImage.style.display = 'none';
                    heroImage.src = '';
                }
            }
            
            // Update all elements with data-cms-id attributes
            document.querySelectorAll('[data-cms-id]').forEach(element => {
                const cmsId = element.getAttribute('data-cms-id');
                if (content[cmsId]) {
                    const item = content[cmsId];
                    
                    // Handle IMG elements - update src attribute
                    if (element.tagName === 'IMG') {
                        let imagePath = item.content;
                        
                        // Parse image path (format: "IMAGE:path")
                        if (imagePath && imagePath.indexOf(':') !== -1) {
                            const parts = imagePath.split(':');
                            imagePath = parts.slice(1).join(':');
                        }
                        
                        if (imagePath && imagePath.trim() !== '') {
                            element.src = imagePath;
                            console.log('CMS: Updated image src for', cmsId, 'to', imagePath);
                        }
                    }
                    // Update the element based on its type
                    else if (item.type === 'title' && element.tagName === 'TITLE') {
                        element.textContent = item.content;
                    } else if (item.type === 'title' && element.tagName !== 'TITLE') {
                        // For title elements that aren't in <title> tag
                        element.textContent = item.content;
                    } else if (item.type.startsWith('h') && element.tagName.toLowerCase() === item.type) {
                        element.textContent = item.content;
                    } else if (item.type === 'p' && element.tagName === 'P') {
                        element.textContent = item.content;
                    } else {
                        // For span and div elements, use innerHTML to preserve HTML tags (like <br>)
                        if (element.tagName === 'SPAN' || element.tagName === 'DIV') {
                            element.innerHTML = item.content;
                        } else {
                            // For other elements, use textContent
                            element.textContent = item.content;
                        }
                    }
                }
            });
        } catch (error) {
            console.warn('CMS: Error loading content:', error);
            // Continue with default content if API fails
        }
    }

    // Load content when DOM is ready
    function initCMS() {
        // Function to initialize hero media immediately
        function initHeroMedia() {
            const heroVideo = document.getElementById('hero-background-video');
            const heroImage = document.getElementById('hero-background-image');
            
            if (heroVideo && !heroVideo.src) {
                // Set default video source if not set
                heroVideo.src = 'assets/video/index/main-hero.mp4';
                const source = heroVideo.querySelector('source');
                if (source) {
                    source.src = 'assets/video/index/main-hero.mp4';
                }
            }
        }
        
        // Function to try loading content with retries
        function tryLoadContent(retries = 3) {
            const heroVideo = document.getElementById('hero-background-video');
            const heroImage = document.getElementById('hero-background-image');
            
            if (!heroVideo || !heroImage) {
                if (retries > 0) {
                    console.log('CMS: Elements not found, retrying...', retries);
                    setTimeout(() => tryLoadContent(retries - 1), 100);
                } else {
                    console.warn('CMS: Hero elements not found after retries');
                }
                return;
            }
            
            // Elements found, load content
            loadContent();
        }
        
        // Initialize hero media immediately if elements exist
        if (document.readyState === 'loading') {
            // If still loading, wait for DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function() {
                initHeroMedia();
                tryLoadContent();
            });
        } else {
            // DOM already loaded
            initHeroMedia();
            tryLoadContent();
        }
    }
    
    initCMS();
    
    // Manual test function - can be called from console: testHeroMedia()
    window.testHeroMedia = function() {
        console.log('=== Testing Hero Media ===');
        fetch('api/get_content.php?page=index')
            .then(response => response.json())
            .then(content => {
                console.log('API Response:', content);
                if (content['hero-background-media']) {
                    console.log('Hero media found:', content['hero-background-media']);
                    const heroMedia = content['hero-background-media'];
                    let mediaType = 'image';
                    let mediaPath = heroMedia.content;
                    
                    if (heroMedia.content && heroMedia.content.indexOf(':') !== -1) {
                        const parts = heroMedia.content.split(':');
                        mediaType = parts[0].toLowerCase();
                        mediaPath = parts.slice(1).join(':');
                    }
                    
                    console.log('Parsed - Type:', mediaType, 'Path:', mediaPath);
                    
                    const container = document.querySelector('.hero-video-container');
                    if (container) {
                        let imageUrl = mediaPath;
                        if (!mediaPath.startsWith('http') && !mediaPath.startsWith('/')) {
                            imageUrl = '/' + mediaPath.replace(/\\/g, '/');
                        }
                        container.style.backgroundImage = 'url("' + imageUrl + '")';
                        container.style.backgroundSize = 'cover';
                        container.style.backgroundPosition = 'center';
                        container.classList.add('has-image-bg');
                        console.log('Background set to:', imageUrl);
                    } else {
                        console.error('Container not found!');
                    }
                } else {
                    console.warn('No hero-background-media in API response');
                }
            })
            .catch(error => {
                console.error('API Error:', error);
            });
    };
})();

