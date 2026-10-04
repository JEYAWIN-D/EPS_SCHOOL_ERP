{{-- ── Navigation Progress Loader & Touch Feedback (Zero Background Requests) ── --}}
<style>
  /* Top Glow Progress Bar */
  #dasa-nav-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, #a13431 0%, #c8973a 50%, #8c2826 100%);
    box-shadow: 0 0 10px rgba(161, 52, 49, 0.8), 0 0 5px rgba(200, 151, 58, 0.9);
    z-index: 9999999;
    pointer-events: none;
    opacity: 0;
    border-radius: 0 2px 2px 0;
    transition: width 0.25s cubic-bezier(0.1, 0.9, 0.2, 1), opacity 0.2s ease;
  }
  #dasa-nav-loader.active {
    opacity: 1;
  }
  /* Snappy mobile touch feedback */
  @media (max-width: 1023px) {
    .nav-item:active,
    .ps-nav-item:active,
    a.btn:active,
    button.btn:active {
      transform: scale(0.97) !important;
      opacity: 0.85;
      transition: transform 0.08s ease, opacity 0.08s ease;
    }
  }
</style>

<div id="dasa-nav-loader" aria-hidden="true"></div>

<script>
(function() {
  var loader = document.getElementById('dasa-nav-loader');
  var progressTimer = null;
  var currentWidth = 0;

  function setProgress(pct, duration) {
    if (!loader) return;
    currentWidth = pct;
    loader.style.transition = duration ? ('width ' + duration + 's ease-out') : 'width 0.2s ease-out';
    loader.style.width = pct + '%';
  }

  function startNavLoader() {
    if (!loader) return;
    clearInterval(progressTimer);
    loader.classList.add('active');
    setProgress(35, 0.15);

    progressTimer = setInterval(function() {
      if (currentWidth < 85) {
        currentWidth += Math.random() * 8;
        setProgress(Math.min(currentWidth, 85), 0.4);
      }
    }, 400);
  }

  function completeNavLoader() {
    clearInterval(progressTimer);
    if (!loader) return;
    setProgress(100, 0.1);
    setTimeout(function() {
      loader.classList.remove('active');
      setTimeout(function() {
        if (!loader.classList.contains('active')) {
          loader.style.width = '0%';
        }
      }, 250);
    }, 150);
  }

  function isEligibleLink(a) {
    if (!a || !a.href) return false;
    if (a.target === '_blank') return false;
    if (a.hasAttribute('download')) return false;
    if (a.getAttribute('data-no-instant') !== null) return false;
    
    var href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    
    try {
      var url = new URL(a.href, window.location.href);
      if (url.origin !== window.location.origin) return false;
      var path = url.pathname.toLowerCase();
      if (path.includes('/logout') || path.includes('/export') || path.includes('/download') || path.endsWith('.pdf') || path.endsWith('.xlsx')) {
        return false;
      }
      return url.href;
    } catch(e) {
      return false;
    }
  }

  // Visual loader only when the user deliberately clicks a valid link
  document.addEventListener('click', function(e) {
    var a = e.target.closest('a');
    var validUrl = isEligibleLink(a);
    if (validUrl && validUrl !== window.location.href) {
      startNavLoader();
    }
  });

  window.addEventListener('beforeunload', function() {
    startNavLoader();
    setProgress(95, 0.1);
  });

  window.addEventListener('pageshow', function() {
    completeNavLoader();
  });
})();
</script>
