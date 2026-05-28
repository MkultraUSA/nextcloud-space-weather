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
		loadingSpinner.className = 'loading-spinner';
		var spinnerIcon = document.createElement('span');
		spinnerIcon.className = 'spinner';
		spinnerIcon.textContent = '\u21BB';
		loadingSpinner.appendChild(spinnerIcon);
		loadingOverlay.appendChild(loadingSpinner);
		if (playerEl) {
			playerEl.insertBefore(loadingOverlay, animImg.nextSibling);
		}

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
			prevBtn.textContent = '\u23EE';
			prevBtn.title = 'Previous Frame';
			prevBtn.setAttribute('aria-label', 'Previous Frame');

			playBtn = document.createElement('button');
			playBtn.className = 'enlil-anim-btn enlil-anim-play-btn';
			playBtn.textContent = '\u25B6';
			playBtn.title = 'Play';
			playBtn.setAttribute('aria-label', 'Play/Pause');

			nextBtn = document.createElement('button');
			nextBtn.className = 'enlil-anim-btn';
			nextBtn.textContent = '\u23ED';
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

		function showFrame(idx) {
			if (idx >= frameCount) idx = 0;
			if (idx < 0) idx = frameCount - 1;
			currentFrame = idx;
			if (loadingOverlay) {
				loadingOverlay.style.display = 'flex';
			}
			animImg.src = frameBase + idx;
			if (infoEl) {
				infoEl.textContent = 'Frame ' + (idx + 1) + ' / ' + frameCount;
			}
			if (scrubBar) {
				scrubBar.value = String(idx);
			}
		}

		function startAnim() {
			if (playing) return;
			playing = true;
			if (playBtn) {
				playBtn.textContent = '\u23F8';
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
				playBtn.textContent = '\u25B6';
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