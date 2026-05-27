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
		var FRAME_MS = 200;

		var infoEl = document.getElementById('enlil-anim-info');
		var playBtn = document.getElementById('enlil-anim-play');

		function showFrame(idx) {
			if (idx >= frameCount) idx = 0;
			if (idx < 0) idx = frameCount - 1;
			currentFrame = idx;
			animImg.src = frameBase + idx;
			if (infoEl) {
				infoEl.textContent = 'Frame ' + (idx + 1) + ' / ' + frameCount;
			}
		}

		function startAnim() {
			if (playing) return;
			playing = true;
			if (playBtn) playBtn.innerHTML = '&#10074;&#10074;';
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
			if (playBtn) playBtn.innerHTML = '&#9654;';
		}

		if (playBtn) {
			playBtn.addEventListener('click', function () {
				if (playing) {
					stopAnim();
				} else {
					startAnim();
				}
			});
		}
	}
});