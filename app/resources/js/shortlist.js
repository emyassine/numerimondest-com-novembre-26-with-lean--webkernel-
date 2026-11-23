(() => {
  const supportsAjax = typeof window.fetch === 'function' && typeof window.FormData === 'function';

  const updateHeader = (count) => {
    document.querySelectorAll('[data-shortlist-link]').forEach((link) => {
      let badge = link.querySelector('[data-shortlist-badge]');
      if (count < 1) {
        badge?.remove();
        return;
      }

      if (!badge) {
        const template = link.querySelector('[data-shortlist-badge-template]');
        if (!template) return;
        link.append(template.content.cloneNode(true));
        badge = link.querySelector('[data-shortlist-badge]');
      }
      const label = badge?.querySelector('[data-shortlist-count]');
      if (label) label.textContent = String(count);
    });
  };

  const updateInquiryLaunchers = (services) => {
    if (!Array.isArray(services)) return;
    document.querySelectorAll('form.nm-service-inquiry-launch').forEach((form) => {
      form.querySelectorAll('input[name="services[]"]').forEach((input) => input.remove());
      services.forEach((slug) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'services[]';
        input.value = slug;
        form.append(input);
      });
    });
  };

  const animateRemoval = (card) => {
    if (!card) return;
    card.setAttribute('aria-hidden', 'true');
    card.querySelectorAll('button').forEach((button) => { button.disabled = true; });
    card.classList.add('is-removing');
    window.setTimeout(() => card.remove(), 190);
  };

  const updateInquiry = (count, saved, card) => {
    const selectedSection = document.querySelector('[data-inquiry-selection]');
    const selectedGrid = document.querySelector('[data-inquiry-selected-grid]');
    const recommendedGrid = document.querySelector('[data-inquiry-recommended-grid]');

    if (saved && card && recommendedGrid?.contains(card) && selectedGrid && selectedSection) {
      selectedSection.hidden = false;
      selectedGrid.append(card);
      if (recommendedGrid.children.length === 0) {
        const recommendations = recommendedGrid.closest('.nm-service-inquiry-recommendations');
        if (recommendations) recommendations.hidden = true;
      }
    } else if (!saved && card && selectedGrid?.contains(card)) {
      animateRemoval(card);
      if (count === 0 && selectedSection) {
        window.setTimeout(() => {
          if (selectedGrid.children.length === 0) selectedSection.hidden = true;
        }, 200);
      }
    }

    const selectedCount = document.querySelector('[data-inquiry-selection-count]');
    if (selectedCount) selectedCount.textContent = String(count);

    const summary = document.querySelector('.nm-service-inquiry-offer');
    if (summary) {
      if (count > 0) {
        summary.textContent = `Private shortlist · ${count} ${count === 1 ? 'offer' : 'offers'}`;
      } else if (document.querySelector('.nm-service-inquiry-projects')) {
        const projects = document.querySelectorAll('.nm-service-inquiry-projects li').length;
        summary.textContent = `Project enquiry · ${projects} ${projects === 1 ? 'project' : 'projects'}`;
      } else {
        summary.textContent = 'General enquiry · choose an offer or project, or send a general request';
      }
    }
  };

  const updateShortlistPage = (saved, card, count) => {
    if (window.location.pathname.replace(/\/$/, '') !== '/shortlist') return;
    if (saved || !card) return;

    animateRemoval(card);
    const grid = document.querySelector('[data-shortlist-grid]');
    const emptyState = document.querySelector('[data-shortlist-empty]');
    const inquiryButton = document.querySelector('[data-shortlist-cta]');
    if (count === 0) {
      if (emptyState) emptyState.hidden = false;
      if (inquiryButton) inquiryButton.hidden = true;
    }
    if (grid && count > 0) grid.setAttribute('aria-label', `Your saved service offers (${count})`);
  };

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('form.nm-shortlist-button') || !supportsAjax) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;
    const card = form.closest('.nm-inquiry-offer-card, .nm-marketplace-card');
    const previousLabel = button.textContent;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');

    try {
      const response = await window.fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (!response.ok) throw new Error('Shortlist update failed.');
      const state = await response.json();
      if (state.success !== true || typeof state.count !== 'number') throw new Error('Invalid shortlist response.');

      const saved = state.saved === true;
      const compact = form.classList.contains('nm-shortlist-button--compact');
      const action = form.querySelector('input[name="action"]');
      if (action) action.value = saved ? 'remove' : 'add';
      button.textContent = compact
        ? (saved ? 'Remove' : 'Add')
        : (saved ? 'Remove from shortlist' : 'Add to shortlist');
      button.setAttribute('aria-pressed', saved ? 'true' : 'false');
      button.setAttribute('aria-label', `${saved ? 'Remove from shortlist' : 'Add to shortlist'}: ${state.name || 'service'}`);

      updateHeader(state.count);
      updateInquiryLaunchers(state.services);
      updateInquiry(state.count, saved, card);
      updateShortlistPage(saved, card, state.count);
    } catch {
      // Keep the ordinary form navigation as a reliable no-JavaScript/network fallback.
      HTMLFormElement.prototype.submit.call(form);
      return;
    } finally {
      if (!card?.classList.contains('is-removing')) button.disabled = false;
      form.removeAttribute('aria-busy');
      if (!button.textContent.trim()) button.textContent = previousLabel;
    }
  });
})();
