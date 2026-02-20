import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

window.setupHtmlPolling = function setupHtmlPolling({
    containerId,
    url,
    fingerprintUrl = null,
    intervalMs = 30000,
    afterUpdate = null,
    onStateChange = null,
}) {
    const container = document.getElementById(containerId);
    if (!container || !url) return;

    let timerId = null;
    let inFlight = false;
    let etag = null;
    let fingerprint = null;
    let disposed = false;

    const poll = async () => {
        if (document.hidden || inFlight) return;

        inFlight = true;
        if (typeof onStateChange === "function") {
            onStateChange({ inFlight: true });
        }

        try {
            if (fingerprintUrl) {
                const fpResponse = await fetch(fingerprintUrl, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });

                if (!fpResponse.ok) {
                    return;
                }

                const payload = await fpResponse.json();
                const nextFingerprint =
                    typeof payload?.fingerprint === "string"
                        ? payload.fingerprint
                        : null;

                if (
                    fingerprint !== null &&
                    nextFingerprint !== null &&
                    fingerprint === nextFingerprint
                ) {
                    return;
                }

                fingerprint = nextFingerprint;
            }

            const headers = { "X-Requested-With": "XMLHttpRequest" };
            if (etag) {
                headers["If-None-Match"] = etag;
            }

            const response = await fetch(url, { headers });

            if (response.status === 304) {
                return;
            }

            if (response.ok) {
                const nextEtag = response.headers.get("etag");
                if (nextEtag) {
                    etag = nextEtag;
                }

                const html = await response.text();
                if (container.innerHTML !== html) {
                    container.innerHTML = html;
                    if (typeof afterUpdate === "function") {
                        afterUpdate(container);
                    }
                }
            }
        } catch (_) {
            // Polling xatosi bo'lsa keyingi intervalda qayta uriniladi.
        } finally {
            inFlight = false;
            if (typeof onStateChange === "function") {
                onStateChange({ inFlight: false });
            }
        }
    };

    const schedule = (delayMs = intervalMs) => {
        if (disposed) return;
        if (timerId !== null) {
            window.clearTimeout(timerId);
        }

        timerId = window.setTimeout(async () => {
            await poll();
            schedule();
        }, delayMs);
    };

    const start = () => {
        if (timerId !== null) return;
        schedule();
    };

    const stop = () => {
        if (timerId === null) return;
        window.clearTimeout(timerId);
        timerId = null;
    };

    const handleVisibilityChange = () => {
        if (document.hidden) {
            stop();
        } else {
            poll();
            schedule(intervalMs);
        }
    };

    document.addEventListener("visibilitychange", handleVisibilityChange);
    window.addEventListener("beforeunload", () => {
        disposed = true;
        document.removeEventListener(
            "visibilitychange",
            handleVisibilityChange,
        );
        stop();
    });

    if (typeof afterUpdate === "function") {
        afterUpdate(container);
    }

    poll().finally(() => start());
};

Alpine.start();
