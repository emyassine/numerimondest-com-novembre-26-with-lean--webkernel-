(() => {
    const header = document.querySelector('.nm-header');
    if (!header) return;

    const topThreshold = 1;
    const directionThreshold = 3;
    let previousY = window.scrollY;
    let framePending = false;

    const toneAtHeader = () => {
        const previousPointerEvents = header.style.pointerEvents;
        header.style.pointerEvents = 'none';
        const x = Math.round(window.innerWidth / 2);
        const y = Math.max(0, Math.min(window.innerHeight - 1, header.getBoundingClientRect().bottom - 2));
        const element = document.elementFromPoint(x, y);
        header.style.pointerEvents = previousPointerEvents;

        const markedSection = element?.closest('[data-srs-header-tone]');
        // This attribute is the foreground/logo tone chosen for image-backed hero sections.
        if (markedSection?.dataset.srsHeaderTone === 'light') return 'light';
        if (markedSection?.dataset.srsHeaderTone === 'dark') return 'dark';

        // The public studio theme is light. Keep its dark mark unless a dark hero opts in above.
        return 'dark';
    };

    const update = () => {
        const currentY = Math.max(0, window.scrollY);
        if (currentY <= topThreshold) {
            header.dataset.scrollState = 'top';
            header.dataset.logoTone = toneAtHeader();
        } else {
            if (currentY > previousY + directionThreshold) header.dataset.scrollState = 'down';
            else if (currentY < previousY - directionThreshold) header.dataset.scrollState = 'up';
            header.dataset.logoTone = 'dark';
        }
        previousY = currentY;
        framePending = false;
    };

    const scheduleUpdate = () => {
        if (framePending) return;
        framePending = true;
        window.requestAnimationFrame(update);
    };

    if (window.scrollY > topThreshold) header.dataset.scrollState = 'up';
    header.dataset.logoTone = window.scrollY > topThreshold ? 'dark' : toneAtHeader();
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate, { passive: true });
})();
