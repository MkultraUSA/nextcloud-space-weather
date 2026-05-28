/**
 * Space Weather Dashboard — Client-side JS
 * CSP-compliant: no inline event handlers, no innerHTML.
 *
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	// --- Refresh button ---
	var btn = document.getElementById('refresh-btn');
	if (btn) {
		btn.addEventListener('click', function () {
			location.reload();
		});
	}

	// --- Image error/load handling ---
	var images = document.querySelectorAll('img[loading="lazy"]');
	Array.prototype.forEach.call(images, function (img) {
		img.addEventListener('error', function () {
			var container = this.parentNode;
			if (!container) return;
			var errorEl = container.querySelector('.image-error');
			var loadingEl = container.querySelector('.image-loading');
			if (errorEl) errorEl.style.display = 'flex';
			if (loadingEl) loadingEl.style.display = 'none';
			this.classList.add('image-loaded');
		});
		img.addEventListener('load', function () {
			var container = this.parentNode;
			if (!container) return;
			var loadingEl = container.querySelector('.image-loading');
			var errorEl = container.querySelector('.image-error');
			if (loadingEl) loadingEl.style.display = 'none';
			if (errorEl) errorEl.style.display = 'none';
			this.classList.add('image-loaded');
		});
	});

	// --- Stale spinner timeout ---
	setTimeout(function () {
		var spinners = document.querySelectorAll('.loading-spinner');
		Array.prototype.forEach.call(spinners, function (spinner) {
			spinner.style.display = 'none';
		});
	}, 10000);

	// --- Lightbox ---
	var lightbox = null;
	var lightboxImg = null;
	var lightboxCaption = null;
	var downloadBtn = null;

	function createLightbox() {
		if (lightbox) return;

		lightbox = document.createElement('div');
		lightbox.className = 'sw-lightbox';
		lightbox.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:10000;cursor:pointer;';

		var closeBtn = document.createElement('button');
		closeBtn.className = 'sw-lightbox-close';
		closeBtn.textContent = '\u2715';
		closeBtn.setAttribute('aria-label', 'Close');
		closeBtn.style.cssText = 'position:fixed;top:20px;right:30px;font-size:32px;color:#fff;background:none;border:none;cursor:pointer;z-index:10001;';
		closeBtn.addEventListener('click', function (e) {
			e.stopPropagation();
			closeLightbox();
		});
		lightbox.appendChild(closeBtn);

		var wrapper = document.createElement('div');
		wrapper.style.cssText = 'display:flex;align-items:center;justify-content:center;width:100%;height:calc(100% - 80px);padding:20px;';

		lightboxImg = document.createElement('img');
		lightboxImg.className = 'sw-lightbox-image';
		lightboxImg.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;border-radius:4px;box-shadow:0 0 30px rgba(0,0,0,0.5);cursor:default;pointer-events:none;';
		wrapper.appendChild(lightboxImg);

		var bottomBar = document.createElement('div');
		bottomBar.style.cssText = 'position:fixed;bottom:0;left:0;width:100%;height:60px;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;gap:20px;padding:0 20px;';

		lightboxCaption = document.createElement('span');
		lightboxCaption.style.cssText = 'color:#fff;font-size:16px;';

		downloadBtn = document.createElement('a');
		downloadBtn.className = 'sw-lightbox-download';
		downloadBtn.textContent = '\u2B07 Download';
		downloadBtn.style.cssText = 'color:#fff;background:#667eea;padding:8px 20px;border-radius:6px;text-decoration:none;font-size:14px;cursor:pointer;';
		downloadBtn.setAttribute('download', '');

		bottomBar.appendChild(lightboxCaption);
		bottomBar.appendChild(downloadBtn);

		lightbox.appendChild(wrapper);
		lightbox.appendChild(bottomBar);

		lightbox.addEventListener('click', closeLightbox);

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && lightbox.style.display !== 'none') {
				closeLightbox();
			}
		});

		document.body.appendChild(lightbox);
	}

	function openLightbox(imgSrc, altText) {
		createLightbox();
		lightboxImg.src = imgSrc;
		lightboxImg.alt = altText || '';
		lightboxCaption.textContent = altText || '';
		downloadBtn.href = imgSrc;
		fetch(imgSrc)
			.then(function (resp) { return resp.blob(); })
			.then(function (blob) {
				downloadBtn.href = URL.createObjectURL(blob);
			})
			.catch(function () {
				downloadBtn.href = imgSrc;
			});
		lightbox.style.display = 'block';
		document.body.style.overflow = 'hidden';
	}

	function closeLightbox() {
		if (lightbox) {
			lightbox.style.display = 'none';
			lightboxImg.src = '';
			document.body.style.overflow = '';
		}
	}

	// --- Click handler for image links (anchor tags wrapping dashboard images) ---
	document.addEventListener('click', function (e) {
		// Walk up to find if click landed on or inside a .sw-image-link
		var target = e.target;
		while (target && target !== document.body) {
			if (target.classList && target.classList.contains('sw-image-link')) {
				var img = target.querySelector('img');
				if (img && img.src) {
					e.preventDefault();
					openLightbox(img.src, img.alt);
					return;
				}
			}
			target = target.parentNode;
		}
	});

	// --- WSA-ENLIL Animation ---
	var animImg = document.getElementById('enlil-anim-img');
	if (animImg) {
		var frameCount = parseInt(animImg.getAttribute('data-frame-count')) || 0;
		var frameBase = animImg.getAttribute('data-frame-base') || '';
		var currentFrame = 0;
		var playing = false;
		var timer = null;
		var speedIdx = 0;
		var speeds = [200, 100, 400];
		var speedLabels = ['1x', '2x', '0.5x'];
		var FRAME_MS = speeds[speedIdx];

		var playerEl = document.getElementById('enlil-player');
		var controlsEl = document.getElementById('enlil-anim-controls');

		// --- Build DOM elements ---

		// Loading overlay (positioned over the image)
		var loadingOverlay = document.createElement('div');
		loadingOverlay.className = 'enlil-anim-loading-overlay';
		loadingOverlay.style.display = 'none';
		var loadingSpinner = document.createElement('div');
		loadingSpinner.className = 'enlil-anim-spinner';
		loadingOverlay.appendChild(loadingSpinner);
		if (playerEl) {
			playerEl.insertBefore(loadingOverlay, animImg.nextSibling);
		}

		// Create a second (hidden) img for double-buffered animation.
		// This prevents white flashes during frame transitions.
		var animImgB = document.createElement('img');
		animImgB.className = 'enlil-anim-image';
		animImgB.alt = '';
		animImgB.style.display = 'none';
		animImgB.style.position = 'absolute';
		animImgB.style.top = '0';
		animImgB.style.left = '0';
		animImgB.style.width = '100%';
		if (animImg.parentNode) {
			animImg.parentNode.style.position = 'relative';
			animImg.parentNode.insertBefore(animImgB, animImg);
		}
		var activeImg = animImg;  // currently visible (A or B)

		// Hide loading overlay when frame image finishes loading
		animImg.addEventListener('load', function () {
			if (loadingOverlay) {
				loadingOverlay.style.display = 'none';
			}
		});

		// Scrub bar (range input between image and controls)
		var scrubBar = document.createElement('input');
		scrubBar.type = 'range';
		scrubBar.className = 'enlil-anim-scrub';
		scrubBar.min = '0';
		scrubBar.max = String(frameCount - 1);
		scrubBar.value = '0';
		scrubBar.setAttribute('aria-label', 'Animation frame scrubber');
		if (playerEl) {
			playerEl.insertBefore(scrubBar, controlsEl);
		}

		// Declare shared references for functions below
		var infoEl = null;
		var playBtn = null;
		var prevBtn = null;
		var nextBtn = null;
		var speedBtn = null;

		// --- Controls bar ---
		if (controlsEl) {
			// Remove any existing children (server-rendered placeholders)
			while (controlsEl.firstChild) {
				controlsEl.removeChild(controlsEl.firstChild);
			}

			// Button group (prev, play, next) — left side
			var btnGroup = document.createElement('div');
			btnGroup.className = 'enlil-anim-btn-group';

			prevBtn = document.createElement('button');
			prevBtn.className = 'enlil-anim-btn';
			prevBtn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M11 18V6l-8.5 6 8.5 6zm.5-6l8.5 6V6l-8.5 6z"/></svg>';
			prevBtn.title = 'Previous Frame';
			prevBtn.setAttribute('aria-label', 'Previous Frame');

			playBtn = document.createElement('button');
			playBtn.className = 'enlil-anim-btn enlil-anim-play-btn';
			playBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
			playBtn.title = 'Play';
			playBtn.setAttribute('aria-label', 'Play/Pause');

			nextBtn = document.createElement('button');
			nextBtn.className = 'enlil-anim-btn';
			nextBtn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M4 18l8.5-6L4 6v12zm9.5-12v12l8.5-6-8.5-6z"/></svg>';
			nextBtn.title = 'Next Frame';
			nextBtn.setAttribute('aria-label', 'Next Frame');

			btnGroup.appendChild(prevBtn);
			btnGroup.appendChild(playBtn);
			btnGroup.appendChild(nextBtn);

			// Info text — center
			infoEl = document.createElement('span');
			infoEl.className = 'enlil-anim-info';
			infoEl.textContent = 'Frame 1 / ' + frameCount;

			// Speed button — right side
			speedBtn = document.createElement('button');
			speedBtn.className = 'enlil-anim-speed-btn';
			speedBtn.textContent = '1x';
			speedBtn.title = 'Playback Speed (1x)';
			speedBtn.setAttribute('aria-label', 'Playback Speed');

			controlsEl.appendChild(btnGroup);
			controlsEl.appendChild(infoEl);
			controlsEl.appendChild(speedBtn);
		}

		// --- Core animation functions ---

		// --- Double-buffered frame loading ---
		// Two stacked <img> elements: one visible (activeImg), one hidden.
		// Load next frame into the hidden one, then swap visibility — zero flash.
		var frameCache = {};       // idx -> Image (fully loaded)
		var MAX_CACHE = 50;
		var PRELOAD_AHEAD = 10;    // preload this many frames ahead

		function clearOldCache() {
			var keys = Object.keys(frameCache);
			if (keys.length > MAX_CACHE) {
				keys.sort(function(a, b) {
					return Math.abs(b - currentFrame) - Math.abs(a - currentFrame);
				});
				for (var i = MAX_CACHE; i < keys.length; i++) {
					delete frameCache[keys[i]];
				}
			}
		}

		function preloadFrame(idx) {
			if (idx < 0 || idx >= frameCount) return;
			if (frameCache[idx]) return;
			var img = new Image();
			img.onload = function() {
				frameCache[idx] = img;
				clearOldCache();
			};
			img.src = frameBase + idx;
		}

		function preloadAhead(startIdx) {
			for (var i = 1; i <= PRELOAD_AHEAD; i++) {
				preloadFrame(startIdx + i);
			}
		}

		function getHiddenImg() {
			// Return the currently hidden image element (A or B)
			return (activeImg === animImg) ? animImgB : animImg;
		}

		function showFrame(idx) {
			if (idx >= frameCount) idx = 0;
			if (idx < 0) idx = frameCount - 1;
			currentFrame = idx;

			if (infoEl) {
				infoEl.textContent = 'Frame ' + (idx + 1) + ' / ' + frameCount;
			}
			if (scrubBar) {
				scrubBar.value = String(idx);
			}

			var src = frameBase + idx;
			var hidden = getHiddenImg();

			// If already showing this frame, skip
			if (activeImg.src && activeImg.src.indexOf(src) >= 0) {
				if (loadingOverlay) loadingOverlay.style.display = 'none';
				preloadAhead(idx);
				return;
			}

			// Check cache for instant load
			var cached = frameCache[idx];
			if (cached && cached.complete && cached.naturalWidth > 0) {
				// Swap: show hidden with cached frame, hide old active
				hidden.src = cached.src;
				hidden.style.display = 'block';
				activeImg.style.display = 'none';
				activeImg = hidden;
				if (loadingOverlay) loadingOverlay.style.display = 'none';
				preloadAhead(idx);
			} else {
				// Not cached — load into hidden img, then swap when ready
				if (loadingOverlay) loadingOverlay.style.display = 'flex';
				hidden.onload = function() {
					hidden.style.display = 'block';
					activeImg.style.display = 'none';
					activeImg = hidden;
					frameCache[idx] = hidden;  // cache the element itself
					if (loadingOverlay) loadingOverlay.style.display = 'none';
					clearOldCache();
					preloadAhead(idx);
					// Clear onload so it doesn't fire again
					hidden.onload = null;
				};
				hidden.onerror = function() {
					if (loadingOverlay) loadingOverlay.style.display = 'none';
					hidden.onerror = null;
				};
				hidden.src = src;
			}
		}

		function startAnim() {
			if (playing) return;
			playing = true;
			if (playBtn) {
				playBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
				playBtn.title = 'Pause';
			}
			timer = setInterval(function () {
				showFrame(currentFrame + 1);
			}, FRAME_MS);
		}

		function stopAnim() {
			playing = false;
			if (timer) {
				clearInterval(timer);
				timer = null;
			}
			if (playBtn) {
				playBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
				playBtn.title = 'Play';
			}
		}

		// --- Button events ---
		if (playBtn) {
			playBtn.addEventListener('click', function () {
				if (playing) {
					stopAnim();
				} else {
					startAnim();
				}
			});
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				showFrame(currentFrame - 1);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				showFrame(currentFrame + 1);
			});
		}

		if (scrubBar) {
			scrubBar.addEventListener('input', function () {
				var targetFrame = parseInt(scrubBar.value, 10);
				showFrame(targetFrame);
			});
		}

		if (speedBtn) {
			speedBtn.addEventListener('click', function () {
				speedIdx = (speedIdx + 1) % speeds.length;
				FRAME_MS = speeds[speedIdx];
				speedBtn.textContent = speedLabels[speedIdx];
				speedBtn.title = 'Playback Speed (' + speedLabels[speedIdx] + ')';
				if (playing) {
					clearInterval(timer);
					timer = setInterval(function () {
						showFrame(currentFrame + 1);
					}, FRAME_MS);
				}
			});
		}

		// --- Keyboard shortcuts ---
		document.addEventListener('keydown', function (e) {
			// Only respond when the animation container is visible
			if (!playerEl || playerEl.offsetParent === null) return;
			// Don't intercept when user is typing in a form field
			var tag = e.target.tagName;
			if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) return;

			if (e.key === ' ' || e.code === 'Space') {
				e.preventDefault();
				if (playing) {
					stopAnim();
				} else {
					startAnim();
				}
			} else if (e.key === 'ArrowLeft') {
				e.preventDefault();
				showFrame(currentFrame - 1);
			} else if (e.key === 'ArrowRight') {
				e.preventDefault();
				showFrame(currentFrame + 1);
			}
		});
	}
});